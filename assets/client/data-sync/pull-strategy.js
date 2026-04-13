import isPublished from "../util/isPublished";
import logger from "../logger/logger";
import idFromPath from "../util/id-from-path";
import { cloneDeep } from "lodash";
import ClientConfigLoader from "../util/client-config-loader.js";
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

// Static ID used as synthetic region ID when campaigns override the screen layout.
const CAMPAIGN_REGION_ID = "01G112XBWFPY029RYFB8X2H4KD";

// Regex to extract regionId from region playlist paths.
const REGION_PATH_REGEX =
  /\/v2\/screens\/([^/]+)\/regions\/([^/]+)\/playlists/;

/**
 * Dispatch an RTK Query endpoint and return the unwrapped result.
 *
 * @param {string} endpoint The endpoint name.
 * @param {object} args The endpoint args.
 * @returns {Promise<any>} The result data.
 */
function query(endpoint, args) {
  return clientStore
    .dispatch(clientApi.endpoints[endpoint].initiate(args))
    .unwrap();
}

/**
 * Fetch all pages from a paginated endpoint.
 *
 * @param {string} endpoint The endpoint name.
 * @param {object} args The endpoint args (page will be added).
 * @returns {Promise<Array>} All hydra:member results concatenated.
 */
async function queryAllPages(endpoint, args) {
  let results = [];
  let page = 1;
  let continueLoop = false;

  do {
    try {
      const responseData = await query(endpoint, { ...args, page });

      if (responseData === null || responseData === undefined) {
        logger.error(`Failed to fetch page ${page} for ${endpoint}`);
        return results;
      }

      results = results.concat(responseData["hydra:member"]);
      if (results.length < responseData["hydra:totalItems"]) {
        page += 1;
        continueLoop = true;
      } else {
        continueLoop = false;
      }
    } catch (err) {
      logger.error(
        `Failed to fetch all pages for ${endpoint}: ${err.message}`,
      );
      return results;
    }
  } while (continueLoop);

  return results;
}

/**
 * PullStrategy.
 *
 * Handles pull strategy.
 */
class PullStrategy {
  latestScreenData;

  // Fetch-interval in ms.
  interval;

  // Path to screen that should be loaded data for.
  entryPoint = "";

  /**
   * Constructor.
   *
   * @param {object} config
   *   The config object.
   */
  constructor(config) {
    this.start = this.start.bind(this);
    this.stop = this.stop.bind(this);
    this.getScreen = this.getScreen.bind(this);

    this.interval = config?.interval ?? 60000 * 5;
    this.entryPoint = config.entryPoint;
  }

  /**
   * Gets all campaigns, both from screen and groups.
   *
   * @param {object} screen The screen object to extract campaigns from.
   * @returns {Promise<object>} Array of campaigns (playlists).
   */
  async getCampaignsData(screen) {
    const screenGroupCampaigns = [];
    const screenId = idFromPath(screen["@id"]);

    try {
      const response = await query("getV2ScreensByIdScreenGroups", {
        id: screenId,
      });

      if (
        response !== null &&
        Object.prototype.hasOwnProperty.call(response, "hydra:member")
      ) {
        const promises = [];

        response["hydra:member"].forEach((group) => {
          const groupId = idFromPath(group["@id"]);
          promises.push(
            queryAllPages("getV2ScreenGroupsByIdCampaigns", { id: groupId }),
          );
        });

        await Promise.allSettled(promises).then((results) => {
          results.forEach((result) => {
            if (result.status === "fulfilled") {
              result.value.forEach(({ campaign }) => {
                screenGroupCampaigns.push(campaign);
              });
            }
          });
        });
      }
    } catch (err) {
      logger.error(err);
    }

    let screenCampaigns = [];

    try {
      const screenCampaignsResponse = await query(
        "getV2ScreensByIdCampaigns",
        { id: screenId },
      );

      if (screenCampaignsResponse !== null) {
        screenCampaigns = screenCampaignsResponse["hydra:member"].map(
          ({ campaign }) => campaign,
        );
      }
    } catch (err) {
      logger.error(err);
    }

    return [...screenCampaigns, ...screenGroupCampaigns];
  }

  /**
   * Get slides for regions.
   *
   * @param {Array} regions Paths to regions.
   * @returns {Promise<object>} Regions data.
   */
  async getRegions(regions) {
    return new Promise((resolve, reject) => {
      const promises = [];
      const regionData = {};

      regions.forEach((regionPath) => {
        const matches = regionPath.match(REGION_PATH_REGEX);
        if (matches) {
          promises.push(
            queryAllPages("getV2ScreensByIdRegionsAndRegionIdPlaylists", {
              id: matches[1],
              regionId: matches[2],
            }).then((results) => ({
              regionId: matches[2],
              results,
            })),
          );
        }
      });

      Promise.allSettled(promises)
        .then((results) => {
          results.forEach((result) => {
            if (result.status === "fulfilled") {
              regionData[result.value.regionId] = result.value.results.map(
                ({ playlist }) => playlist,
              );
            }
          });

          resolve(regionData);
        })
        .catch((err) => reject(err));
    });
  }

  /**
   * Get slides for the given regions.
   *
   * @param {object} regions Regions to fetch slides for.
   * @returns {Promise<object>} Promise with slides for the given regions.
   */
  async getSlidesForRegions(regions) {
    return new Promise((resolve, reject) => {
      const promises = [];
      const regionData = cloneDeep(regions);

      // eslint-disable-next-line guard-for-in,no-restricted-syntax
      for (const regionKey in regionData) {
        const playlists = regionData[regionKey];
        // eslint-disable-next-line guard-for-in,no-restricted-syntax
        for (const playlistKey in playlists) {
          const playlistId = idFromPath(
            regionData[regionKey][playlistKey]["@id"],
          );
          promises.push(
            queryAllPages("getV2PlaylistsByIdSlides", {
              id: playlistId,
            }).then((results) => ({
              regionKey,
              playlistKey,
              results,
            })),
          );
        }
      }

      Promise.allSettled(promises)
        .then((results) => {
          results.forEach((result) => {
            if (result.status === "fulfilled") {
              regionData[result.value.regionKey][
                result.value.playlistKey
              ].slidesData = result.value.results.map(
                (playlistSlide) => playlistSlide.slide,
              );
            }
          });
          resolve(regionData);
        })
        .catch((err) => reject(err));
    });
  }

  /**
   * Fetch screen.
   *
   * @param {string} screenPath Path to the screen.
   */
  async getScreen(screenPath) {
    let screen;

    // Fetch screen
    try {
      screen = await query("getV2ScreensById", {
        id: idFromPath(screenPath),
      });
    } catch (err) {
      logger.warn(
        `Screen (${screenPath}) not loaded. Aborting content update.`,
      );

      return;
    }

    const config = await ClientConfigLoader.loadConfig();
    const relationChecksumEnabled = config.relationsChecksumEnabled;

    if (screen === null) {
      logger.warn(`Screen (${screenPath}) not loaded`);
      return;
    }

    const newScreen = cloneDeep(screen);

    newScreen.hasActiveCampaign = false;

    const newScreenChecksums = newScreen?.relationsChecksum ?? [];
    const oldScreenChecksums =
      this.latestScreenData?.relationsChecksum ?? null;

    if (
      relationChecksumEnabled === false ||
      oldScreenChecksums === null ||
      oldScreenChecksums?.campaigns !== newScreenChecksums?.campaigns ||
      oldScreenChecksums?.inScreenGroups !== newScreenChecksums?.inScreenGroups
    ) {
      logger.info(`Fetching campaigns.`);
      newScreen.campaignsData = await this.getCampaignsData(newScreen);
    } else {
      logger.info(`Campaigns data loaded from cache.`);
      newScreen.campaignsData = this.latestScreenData.campaignsData;
    }

    if (newScreen.campaignsData.length > 0) {
      newScreen.campaignsData.forEach(({ published }) => {
        if (isPublished(published)) {
          newScreen.hasActiveCampaign = true;
        }
      });
    }

    // With active campaigns, we override region/layout values.
    if (newScreen.hasActiveCampaign) {
      logger.info(`Has active campaign.`);

      // Use a static ID to connect the campaign with the regions/playlists.
      const campaignRegionId = CAMPAIGN_REGION_ID;

      // Campaigns are always in full screen layout, for simplicity.
      newScreen.layoutData = {
        grid: {
          rows: 1,
          columns: 1,
        },
        regions: [
          {
            "@id": `/v2/layouts/regions/${campaignRegionId}`,
            gridArea: ["a"],
          },
        ],
      };

      newScreen.regionData = {};
      newScreen.regionData[campaignRegionId] = newScreen.campaignsData;
      newScreen.regions = [
        `/v2/screens/01FV9K4K0Y0X0K1J88SQ6B64VT/regions/${campaignRegionId}/playlists`,
      ];
      newScreen.regionData = await this.getSlidesForRegions(
        newScreen.regionData,
      );
    } else {
      logger.info(`Has no active campaign.`);

      // Get layout: Defines layout and regions.
      if (
        relationChecksumEnabled === false ||
        this.latestScreenData?.hasActiveCampaign ||
        oldScreenChecksums === null ||
        oldScreenChecksums?.layout !== newScreenChecksums?.layout
      ) {
        logger.info(`Fetching layout.`);
        try {
          newScreen.layoutData = await query("getV2LayoutsById", {
            id: idFromPath(newScreen.layout),
          });
        } catch (err) {
          logger.warn(
            `Layout (${newScreen.layout}) not loaded. Aborting content update.`,
          );
          return;
        }

        if (newScreen.layoutData === null) {
          logger.warn(
            `Layout (${newScreen.layout}) not loaded. Aborting content update.`,
          );
          return;
        }
      } else {
        // Get layout: Defines layout and regions.
        logger.info(`Layout loaded from cache.`);
        newScreen.layoutData = this.latestScreenData.layoutData;
      }

      // Fetch regions playlists: Yields playlists of slides for the regions
      if (
        relationChecksumEnabled === false ||
        this.latestScreenData?.hasActiveCampaign ||
        oldScreenChecksums === null ||
        oldScreenChecksums?.regions !== newScreenChecksums?.regions
      ) {
        logger.info(`Fetching regions and slides for regions.`);
        const regions = await this.getRegions(newScreen.regions);
        newScreen.regionData = await this.getSlidesForRegions(regions);
      } else {
        logger.info(`Regions and slides for regions loaded from cache.`);
        newScreen.regionData = this.latestScreenData.regionData;
      }
    }

    // Cached data.
    const fetchedTemplates = {};
    const fetchedMedia = {};

    // Iterate all slides and load required relations.
    const { regionData } = newScreen;
    /* eslint-disable no-restricted-syntax,no-await-in-loop */
    for (const regionKey of Object.keys(regionData)) {
      const regionDataEntry = regionData[regionKey];

      for (const playlistKey of Object.keys(regionDataEntry)) {
        const dataEntryPlaylist = regionDataEntry[playlistKey];
        const dataEntrySlidesData = dataEntryPlaylist.slidesData;

        for (const slideKey of Object.keys(dataEntrySlidesData)) {
          const slide = cloneDeep(dataEntrySlidesData[slideKey]);

          let previousSlide = null;

          // Find the slide in previous data for comparing relationsChecksum values.
          if (
            this.latestScreenData?.regionData[regionKey] &&
            this.latestScreenData.regionData[regionKey][playlistKey] &&
            this.latestScreenData.regionData[regionKey][playlistKey]
              .slidesData[slideKey]
          ) {
            previousSlide = cloneDeep(
              this.latestScreenData.regionData[regionKey][playlistKey]
                .slidesData[slideKey],
            );
          } else {
            previousSlide = {};
          }

          const newSlideChecksums = slide.relationsChecksum ?? [];
          const oldSlideChecksums = previousSlide?.relationsChecksum ?? null;

          // Fetch template if it has changed.
          if (
            relationChecksumEnabled === false ||
            oldSlideChecksums === null ||
            newSlideChecksums.templateInfo !== oldSlideChecksums.templateInfo
          ) {
            const templateId = idFromPath(slide.templateInfo["@id"]);

            // Load template into slide.templateData.
            if (
              Object.prototype.hasOwnProperty.call(
                fetchedTemplates,
                templateId,
              )
            ) {
              slide.templateData = fetchedTemplates[templateId];
            } else {
              logger.info(`Fetching template data.`);
              try {
                const templateData = await query("getV2TemplatesById", {
                  id: templateId,
                });
                slide.templateData = templateData;

                if (templateData !== null) {
                  fetchedTemplates[templateId] = templateData;
                }
              } catch (err) {
                slide.templateData = null;
              }
            }
          } else {
            logger.info(`Template data loaded from cache.`);
            slide.templateData = previousSlide.templateData;
          }

          // A slide cannot work without templateData. Mark as invalid.
          if (slide.templateData === null) {
            logger.warn(
              `Template (${slide.templateInfo["@id"]}) not loaded, slideId: ${slide["@id"]}`,
            );
            slide.invalid = true;
          }

          // Fetch media if it has changed.
          if (
            relationChecksumEnabled === false ||
            oldSlideChecksums === null ||
            newSlideChecksums.media !== oldSlideChecksums.media
          ) {
            const nextMediaData = {};

            for (const mediaPath of slide.media) {
              const mediaId = idFromPath(mediaPath);
              if (
                Object.prototype.hasOwnProperty.call(fetchedMedia, mediaId)
              ) {
                nextMediaData[mediaPath] = fetchedMedia[mediaId];
              } else {
                logger.info(`Fetching media data.`);
                try {
                  const mediaData = await query("getv2MediaById", {
                    id: mediaId,
                  });
                  nextMediaData[mediaPath] = mediaData;

                  if (mediaData !== null) {
                    fetchedMedia[mediaId] = mediaData;
                  }
                } catch (err) {
                  nextMediaData[mediaPath] = null;
                }
              }
            }

            slide.mediaData = nextMediaData;
          } else {
            logger.info(`Media data loaded from cache.`);
            slide.mediaData = previousSlide.mediaData;
          }

          // Fetch feed.
          if (slide?.feed?.feedUrl !== undefined) {
            logger.info(`Fetching feed data.`);
            try {
              slide.feedData = await query("getV2FeedsByIdData", {
                id: idFromPath(slide.feed.feedUrl),
              });
            } catch (err) {
              slide.feedData = null;
            }
          }

          dataEntrySlidesData[slideKey] = slide;
        }
      }
    }
    /* eslint-enable no-restricted-syntax,no-await-in-loop */

    this.latestScreenData = newScreen;

    // Deliver result to rendering
    const event = new CustomEvent("content", {
      detail: {
        screen: newScreen,
      },
    });
    document.dispatchEvent(event);
  }

  /**
   * Start the data synchronization.
   */
  start() {
    // Make sure nothing is running.
    this.stop();

    // Pull now.
    this.getScreen(this.entryPoint)
      .then(() => {
        this.stop();

        // Start interval for pull periodically.
        this.activeInterval = setInterval(
          () => this.getScreen(this.entryPoint),
          this.interval,
        );
      })
      .catch((err) => {
        logger.error(`Failed to start data sync: ${err.message}`);
      });
  }

  /**
   * Stop the data synchronization.
   */
  stop() {
    if (this.activeInterval !== undefined) {
      clearInterval(this.activeInterval);
      this.activeInterval = undefined;
    }
  }
}

export default PullStrategy;

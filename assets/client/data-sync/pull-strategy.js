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
 * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
 * @returns {Promise<any>} The result data.
 */
function query(endpoint, args, forceRefetch = false) {
  return clientStore
    .dispatch(clientApi.endpoints[endpoint].initiate(args, { forceRefetch }))
    .unwrap();
}

/**
 * Fetch all pages from a paginated endpoint.
 *
 * @param {string} endpoint The endpoint name.
 * @param {object} args The endpoint args (page will be added).
 * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
 * @returns {Promise<Array>} All hydra:member results concatenated.
 */
async function queryAllPages(endpoint, args, forceRefetch = false) {
  let results = [];
  let page = 1;
  let continueLoop = false;

  do {
    try {
      const responseData = await query(endpoint, { ...args, page }, forceRefetch);

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
  previousScreenChecksums = null;

  previousSlideChecksums = {};

  previousHadActiveCampaign = false;

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
   * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
   * @returns {Promise<object>} Array of campaigns (playlists).
   */
  async getCampaignsData(screen, forceRefetch) {
    const screenGroupCampaigns = [];
    const screenId = idFromPath(screen["@id"]);

    try {
      const response = await query("getV2ScreensByIdScreenGroups", {
        id: screenId,
      }, forceRefetch);

      if (
        response !== null &&
        Object.prototype.hasOwnProperty.call(response, "hydra:member")
      ) {
        const promises = [];

        response["hydra:member"].forEach((group) => {
          const groupId = idFromPath(group["@id"]);
          promises.push(
            queryAllPages("getV2ScreenGroupsByIdCampaigns", { id: groupId }, forceRefetch),
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
        forceRefetch,
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
   * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
   * @returns {Promise<object>} Regions data.
   */
  async getRegions(regions, forceRefetch) {
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
            }, forceRefetch).then((results) => ({
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
   * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
   * @returns {Promise<object>} Promise with slides for the given regions.
   */
  async getSlidesForRegions(regions, forceRefetch) {
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
            }, forceRefetch).then((results) => ({
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

    // Always forceRefetch the screen to get fresh checksums.
    try {
      screen = await query("getV2ScreensById", {
        id: idFromPath(screenPath),
      }, true);
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

    // Determine which resources need fresh data based on checksum changes.
    const campaignsChanged =
      !relationChecksumEnabled ||
      !this.previousScreenChecksums ||
      this.previousScreenChecksums?.campaigns !== newScreenChecksums?.campaigns ||
      this.previousScreenChecksums?.inScreenGroups !== newScreenChecksums?.inScreenGroups;

    if (campaignsChanged) {
      logger.info(`Fetching campaigns.`);
    }
    newScreen.campaignsData = await this.getCampaignsData(newScreen, campaignsChanged);

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
        true,
      );
    } else {
      logger.info(`Has no active campaign.`);

      const layoutChanged =
        !relationChecksumEnabled ||
        this.previousHadActiveCampaign ||
        !this.previousScreenChecksums ||
        this.previousScreenChecksums?.layout !== newScreenChecksums?.layout;

      if (layoutChanged) {
        logger.info(`Fetching layout.`);
      }

      try {
        newScreen.layoutData = await query("getV2LayoutsById", {
          id: idFromPath(newScreen.layout),
        }, layoutChanged);
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

      const regionsChanged =
        !relationChecksumEnabled ||
        this.previousHadActiveCampaign ||
        !this.previousScreenChecksums ||
        this.previousScreenChecksums?.regions !== newScreenChecksums?.regions;

      if (regionsChanged) {
        logger.info(`Fetching regions and slides for regions.`);
      }

      const regions = await this.getRegions(newScreen.regions, regionsChanged);
      newScreen.regionData = await this.getSlidesForRegions(regions, regionsChanged);
    }

    // Iterate all slides and load required relations.
    const { regionData } = newScreen;
    const nextSlideChecksums = {};

    /* eslint-disable no-restricted-syntax,no-await-in-loop */
    for (const regionKey of Object.keys(regionData)) {
      const regionDataEntry = regionData[regionKey];

      for (const playlistKey of Object.keys(regionDataEntry)) {
        const dataEntryPlaylist = regionDataEntry[playlistKey];
        const dataEntrySlidesData = dataEntryPlaylist.slidesData;

        for (const slideKey of Object.keys(dataEntrySlidesData)) {
          const slide = cloneDeep(dataEntrySlidesData[slideKey]);
          const slideId = slide["@id"];

          const newSlideChecksums = slide.relationsChecksum ?? [];
          const oldSlideChecksums = this.previousSlideChecksums[slideId] ?? null;

          // Fetch template if it has changed.
          const templateChanged =
            !relationChecksumEnabled ||
            !oldSlideChecksums ||
            newSlideChecksums.templateInfo !== oldSlideChecksums.templateInfo;

          const templateId = idFromPath(slide.templateInfo["@id"]);

          if (templateChanged) {
            logger.info(`Fetching template data.`);
          }

          try {
            slide.templateData = await query("getV2TemplatesById", {
              id: templateId,
            }, templateChanged);
          } catch (err) {
            slide.templateData = null;
          }

          // A slide cannot work without templateData. Mark as invalid.
          if (slide.templateData === null) {
            logger.warn(
              `Template (${slide.templateInfo["@id"]}) not loaded, slideId: ${slide["@id"]}`,
            );
            slide.invalid = true;
          }

          // Fetch media if it has changed.
          const mediaChanged =
            !relationChecksumEnabled ||
            !oldSlideChecksums ||
            newSlideChecksums.media !== oldSlideChecksums.media;

          if (mediaChanged) {
            logger.info(`Fetching media data.`);
          }

          const nextMediaData = {};
          for (const mediaPath of slide.media) {
            const mediaId = idFromPath(mediaPath);
            try {
              nextMediaData[mediaPath] = await query("getv2MediaById", {
                id: mediaId,
              }, mediaChanged);
            } catch (err) {
              nextMediaData[mediaPath] = null;
            }
          }
          slide.mediaData = nextMediaData;

          // Fetch feed — always forceRefetch (no checksum, needs fresh data).
          if (slide?.feed?.feedUrl !== undefined) {
            logger.info(`Fetching feed data.`);
            try {
              slide.feedData = await query("getV2FeedsByIdData", {
                id: idFromPath(slide.feed.feedUrl),
              }, true);
            } catch (err) {
              slide.feedData = null;
            }
          }

          nextSlideChecksums[slideId] = newSlideChecksums;
          dataEntrySlidesData[slideKey] = slide;
        }
      }
    }
    /* eslint-enable no-restricted-syntax,no-await-in-loop */

    this.previousScreenChecksums = newScreen.relationsChecksum ?? null;
    this.previousSlideChecksums = nextSlideChecksums;
    this.previousHadActiveCampaign = newScreen.hasActiveCampaign;

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

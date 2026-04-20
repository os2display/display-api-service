import isPublished from "../util/is-published";
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
 * Check if any of the given checksum fields have changed.
 *
 * @param {boolean} enabled Whether checksum comparison is enabled.
 * @param {object|null} oldChecksums Previous checksums (null on first run).
 * @param {object} newChecksums Current checksums.
 * @param {Array<string>} fields Checksum field names to compare.
 * @returns {boolean} True if data should be refetched.
 */
function checksumChanged(enabled, oldChecksums, newChecksums, fields) {
  if (!enabled || !oldChecksums) return true;
  return fields.some((field) => oldChecksums[field] !== newChecksums[field]);
}

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

      results = results.concat(responseData["hydra:member"] ?? []);
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
    this.buildCampaignLayout = this.buildCampaignLayout.bind(this);
    this.fetchLayoutAndRegions = this.fetchLayoutAndRegions.bind(this);
    this.enrichSlides = this.enrichSlides.bind(this);
    this.enrichSlide = this.enrichSlide.bind(this);

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

    if (!screenId) {
      logger.warn(`Could not extract screen ID from ${screen["@id"]}`);
      return [];
    }

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
          if (!groupId) return;
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
        screenCampaigns = (screenCampaignsResponse["hydra:member"] ?? []).map(
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

    const results = await Promise.allSettled(promises);
    results.forEach((result) => {
      if (result.status === "fulfilled") {
        regionData[result.value.regionId] = result.value.results.map(
          ({ playlist }) => playlist,
        );
      }
    });

    return regionData;
  }

  /**
   * Get slides for the given regions.
   *
   * @param {object} regions Regions to fetch slides for.
   * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
   * @returns {Promise<object>} Promise with slides for the given regions.
   */
  async getSlidesForRegions(regions, forceRefetch) {
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
        if (!playlistId) continue;
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

    const results = await Promise.allSettled(promises);
    results.forEach((result) => {
      if (result.status === "fulfilled") {
        regionData[result.value.regionKey][
          result.value.playlistKey
        ].slidesData = result.value.results.map(
          (playlistSlide) => playlistSlide.slide,
        );
      }
    });

    return regionData;
  }

  /**
   * Fetch screen.
   *
   * @param {string} screenPath Path to the screen.
   */
  async getScreen(screenPath) {
    let screen;

    const screenId = idFromPath(screenPath);
    if (!screenId) {
      logger.warn(`Could not extract screen ID from ${screenPath}. Aborting content update.`);
      return;
    }

    // Always forceRefetch the screen to get fresh checksums.
    try {
      screen = await query("getV2ScreensById", {
        id: screenId,
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

    const newScreenChecksums = newScreen?.relationsChecksum ?? {};

    // Determine which resources need fresh data based on checksum changes.
    const campaignsChanged = checksumChanged(
      relationChecksumEnabled, this.previousScreenChecksums, newScreenChecksums,
      ["campaigns", "inScreenGroups"],
    );

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
      const forceRefetch = !this.previousHadActiveCampaign || campaignsChanged;
      await this.buildCampaignLayout(newScreen, forceRefetch);
    } else {
      const success = await this.fetchLayoutAndRegions(
        newScreen, newScreenChecksums, relationChecksumEnabled,
      );
      if (!success) return;
    }

    const nextSlideChecksums = await this.enrichSlides(
      newScreen.regionData, relationChecksumEnabled,
    );

    this.previousScreenChecksums = newScreen.relationsChecksum ?? {};
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
   * Build a synthetic full-screen layout for active campaigns.
   *
   * @param {object} screen The screen object to mutate.
   * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
   */
  async buildCampaignLayout(screen, forceRefetch) {
    logger.info(`Has active campaign.`);

    const campaignRegionId = CAMPAIGN_REGION_ID;

    screen.layoutData = {
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

    screen.regionData = {};
    screen.regionData[campaignRegionId] = screen.campaignsData;
    const campaignScreenId = idFromPath(screen["@id"]);
    screen.regions = [
      `/v2/screens/${campaignScreenId}/regions/${campaignRegionId}/playlists`,
    ];
    screen.regionData = await this.getSlidesForRegions(
      screen.regionData,
      forceRefetch,
    );
  }

  /**
   * Fetch layout and regions for the normal (non-campaign) path.
   *
   * @param {object} screen The screen object to mutate.
   * @param {object} newScreenChecksums Current screen checksums.
   * @param {boolean} relationChecksumEnabled Whether checksum comparison is enabled.
   * @returns {Promise<boolean>} False if content update should be aborted.
   */
  async fetchLayoutAndRegions(screen, newScreenChecksums, relationChecksumEnabled) {
    logger.info(`Has no active campaign.`);

    const layoutChanged =
      this.previousHadActiveCampaign ||
      checksumChanged(
        relationChecksumEnabled, this.previousScreenChecksums, newScreenChecksums,
        ["layout"],
      );

    if (layoutChanged) {
      logger.info(`Fetching layout.`);
    }

    const layoutId = idFromPath(screen.layout);
    if (!layoutId) {
      logger.warn(`Could not extract layout ID from ${screen.layout}. Aborting content update.`);
      return false;
    }

    try {
      screen.layoutData = await query("getV2LayoutsById", {
        id: layoutId,
      }, layoutChanged);
    } catch (err) {
      logger.warn(
        `Layout (${screen.layout}) not loaded. Aborting content update.`,
      );
      return false;
    }

    if (screen.layoutData === null) {
      logger.warn(
        `Layout (${screen.layout}) not loaded. Aborting content update.`,
      );
      return false;
    }

    const regionsChanged =
      this.previousHadActiveCampaign ||
      checksumChanged(
        relationChecksumEnabled, this.previousScreenChecksums, newScreenChecksums,
        ["regions"],
      );

    if (regionsChanged) {
      logger.info(`Fetching regions and slides for regions.`);
    }

    const regions = await this.getRegions(screen.regions ?? [], regionsChanged);
    screen.regionData = await this.getSlidesForRegions(regions, regionsChanged);

    return true;
  }

  /**
   * Enrich all slides in regionData with template, media, and feed data.
   *
   * @param {object} regionData Regions containing playlists with slides.
   * @param {boolean} relationChecksumEnabled Whether checksum comparison is enabled.
   * @returns {Promise<object>} Updated slide checksums.
   */
  async enrichSlides(regionData, relationChecksumEnabled) {
    const nextSlideChecksums = {};
    const promises = [];

    for (const regionKey of Object.keys(regionData)) {
      const regionDataEntry = regionData[regionKey];

      for (const playlistKey of Object.keys(regionDataEntry)) {
        const dataEntryPlaylist = regionDataEntry[playlistKey];
        const dataEntrySlidesData = dataEntryPlaylist.slidesData ?? [];

        for (const slideKey of Object.keys(dataEntrySlidesData)) {
          const slide = cloneDeep(dataEntrySlidesData[slideKey]);

          promises.push(
            this.enrichSlide(slide, relationChecksumEnabled).then(() => {
              nextSlideChecksums[slide["@id"]] = slide.relationsChecksum ?? {};
              dataEntrySlidesData[slideKey] = slide;
            }),
          );
        }
      }
    }

    await Promise.allSettled(promises);

    return nextSlideChecksums;
  }

  /**
   * Enrich a single slide with template, media, and feed data.
   *
   * @param {object} slide The slide object to mutate.
   * @param {boolean} relationChecksumEnabled Whether checksum comparison is enabled.
   */
  async enrichSlide(slide, relationChecksumEnabled) {
    const slideId = slide["@id"];
    const newSlideChecksums = slide.relationsChecksum ?? {};
    const oldSlideChecksums = this.previousSlideChecksums[slideId] ?? null;

    // A slide cannot work without templateInfo. Mark as invalid and skip.
    if (!slide.templateInfo?.["@id"]) {
      logger.warn(
        `Slide (${slide["@id"]}) has no templateInfo. Marking as invalid.`,
      );
      slide.templateData = null;
      slide.invalid = true;
      slide.mediaData = {};
      return;
    }

    // Fetch template if it has changed.
    const templateChanged = checksumChanged(
      relationChecksumEnabled, oldSlideChecksums, newSlideChecksums,
      ["templateInfo"],
    );

    const templateId = idFromPath(slide.templateInfo["@id"]);

    if (!templateId) {
      logger.warn(`Could not extract template ID from ${slide.templateInfo["@id"]}. Marking slide as invalid.`);
      slide.templateData = null;
      slide.invalid = true;
      slide.mediaData = {};
      return;
    }

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
      slide.mediaData = {};
      return;
    }

    // Fetch media if it has changed.
    const mediaChanged = checksumChanged(
      relationChecksumEnabled, oldSlideChecksums, newSlideChecksums,
      ["media"],
    );

    if (mediaChanged) {
      logger.info(`Fetching media data.`);
    }

    const mediaEntries = (slide.media ?? [])
      .map((mediaPath) => ({ mediaPath, mediaId: idFromPath(mediaPath) }))
      .filter(({ mediaId }) => mediaId);

    const mediaResults = await Promise.allSettled(
      mediaEntries.map(({ mediaPath, mediaId }) =>
        query("getv2MediaById", { id: mediaId }, mediaChanged)
          .then((data) => ({ mediaPath, data }))
          .catch(() => ({ mediaPath, data: null })),
      ),
    );

    const nextMediaData = {};
    mediaResults.forEach((result) => {
      if (result.status === "fulfilled") {
        nextMediaData[result.value.mediaPath] = result.value.data;
      }
    });
    slide.mediaData = nextMediaData;

    // Fetch feed — always forceRefetch (no checksum, needs fresh data).
    if (slide?.feed?.feedUrl !== undefined) {
      const feedId = idFromPath(slide.feed.feedUrl);
      if (!feedId) return;
      logger.info(`Fetching feed data.`);
      try {
        slide.feedData = await query("getV2FeedsByIdData", {
          id: feedId,
        }, true);
      } catch (err) {
        slide.feedData = null;
      }
    }
  }

  /**
   * Start the data synchronization.
   */
  start() {
    // Make sure nothing is running.
    this.stop();
    this.stopped = false;

    // Pull now, then schedule the next pull after completion.
    this.pull();
  }

  /**
   * Run a single pull cycle, then schedule the next one.
   */
  pull() {
    this.getScreen(this.entryPoint)
      .catch((err) => {
        logger.error(`Content update failed: ${err.message}`);
      })
      .finally(() => {
        if (this.stopped) {
          return;
        }
        this.activeTimeout = setTimeout(() => {
          this.activeTimeout = undefined;
          this.pull();
        }, this.interval);
      });
  }

  /**
   * Stop the data synchronization.
   */
  stop() {
    this.stopped = true;
    if (this.activeTimeout !== undefined) {
      clearTimeout(this.activeTimeout);
      this.activeTimeout = undefined;
    }
  }
}

export default PullStrategy;

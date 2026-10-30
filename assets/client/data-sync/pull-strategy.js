import isPublished from "../util/isPublished";
import logger from "../logger/logger";
import ApiHelper from "./api-helper";
import { cloneDeep } from "lodash";
import ClientConfigLoader from "../util/client-config-loader.js";
import { settleWithConcurrency } from "../util/concurrency.js";
import templateDataFromSlide from "../util/template-data-from-slide.js";

// Maximum requests in flight during a pull. A multi-region layout fans out one
// request per region, playlist, slide, media item and feed; sending all of them
// at once is what empties the reverse proxy's rate-limit bucket and leaves
// regions blank (#507). Keeping the burst well under the configured burst size
// means the retry layer rarely has to do anything.
const MAX_CONCURRENT_REQUESTS = 6;

// Stored in place of a checksum the pull is not entitled to keep, so the group
// is refetched next time. A fresh symbol is unequal to every string the server
// can send *and* to every other symbol, which null is not: the API answers null
// for a group whose relation map is empty, and null === null would take the
// cached branch - the freeze this sentinel exists to prevent. Only in memory,
// so it never has to survive serialisation.
const CHECKSUM_UNAVAILABLE = () => Symbol("checksum-unavailable");

/**
 * PullStrategy.
 *
 * Handles pull strategy.
 */
class PullStrategy {
  lastestScreenData;

  // Screen checksums to compare the next pull against. Kept apart from
  // lastestScreenData because a pull that fell back to cached data must not be
  // credited with the server's fresh checksum for the part it failed to fetch,
  // and the screen object must not carry that correction: relationsChecksum is
  // handed to the client as the server sent it, not as what this pull happens to
  // be entitled to compare against.
  lastestScreenChecksums;

  // Helper for all api calls.
  apiHelper;

  // Fetch-interval in ms.
  interval;

  // Set by stop(), so a pull already in flight does not schedule another one.
  stopped = false;

  // Handle of the pending pull, and the generation it belongs to. Restarting
  // bumps the generation, so a pull left over from an earlier start() cannot
  // schedule alongside the current chain.
  activeTimeout;

  chainId = 0;

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

    this.apiHelper = new ApiHelper(config.endpoint ?? "");
  }

  /**
   * Gets all campaigns, both from screen and groups.
   *
   * @param {object} screen The screen object to extract campaigns from.
   * @param {object} report Collects which relation groups came back degraded.
   * @returns {Promise<object>} Array of campaigns (playlists).
   */
  async getCampaignsData(screen, report = {}) {
    const screenGroupCampaigns = [];

    try {
      // Paginated collection: fetch every page, not just the first.
      const response = await this.apiHelper.getAllResultsFromPath(
        screen.inScreenGroups,
      );

      if (Object.prototype.hasOwnProperty.call(response, "results")) {
        const tasks = response.results.map(
          (group) => () =>
            this.apiHelper.getAllResultsFromPath(group.campaigns),
        );

        const results = await settleWithConcurrency(
          tasks,
          MAX_CONCURRENT_REQUESTS,
        );

        results.forEach((result) => {
          if (result.status === "fulfilled") {
            (result.value.results ?? []).forEach(({ campaign }) => {
              screenGroupCampaigns.push(campaign);
            });

            return;
          }

          report.campaigns = true;
        });
      } else {
        // getAllResultsFromPath answers a bare {} when a page failed, so a
        // missing results key is a failure rather than an empty collection.
        report.campaigns = true;
      }
    } catch (err) {
      report.campaigns = true;
      logger.error(err);
    }

    let screenCampaigns = [];

    try {
      // Paginated collection: fetch every page, not just the first.
      const screenCampaignsResponse =
        await this.apiHelper.getAllResultsFromPath(screen.campaigns);

      if (
        Object.prototype.hasOwnProperty.call(screenCampaignsResponse, "results")
      ) {
        screenCampaigns = screenCampaignsResponse.results.map(
          ({ campaign }) => campaign,
        );
      } else {
        report.campaigns = true;
      }
    } catch (err) {
      report.campaigns = true;
      logger.error(err);
    }

    return [...screenCampaigns, ...screenGroupCampaigns];
  }

  /**
   * Get slides for regions.
   *
   * @param {Array} regions Paths to regions.
   * @param {object} report Collects which relation groups came back degraded.
   * @returns {Promise<object>} Regions data.
   */
  async getRegions(regions, report = {}) {
    const reg = /\/v2\/screens\/.*\/regions\/(?<regionId>.*)\/playlists/;

    // Pair each region id with its request up front. Reading the id back out of
    // a positional index would depend on this list staying 1:1 with `regions`,
    // and mismatched playlists on a region are worse than a missing region.
    const entries = regions
      .map((regionPath) => ({
        regionPath,
        regionId: regionPath?.match(reg)?.groups?.regionId,
      }))
      .filter(({ regionId }) => regionId !== undefined);

    const results = await settleWithConcurrency(
      entries.map(
        ({ regionPath }) =>
          () =>
            this.apiHelper.getAllResultsFromPath(regionPath),
      ),
      MAX_CONCURRENT_REQUESTS,
    );

    const regionData = {};

    results.forEach((result, index) => {
      const { regionId } = entries[index];

      if (result.status === "fulfilled" && result?.value?.path) {
        regionData[regionId] = (result?.value?.results ?? []).map(
          ({ playlist }) => playlist,
        );

        return;
      }

      report.regions = true;

      // Keep the last known good playlists for this region rather than an empty
      // list. On signage, content that is one pull out of date beats a black
      // region, and a rejected request says nothing about what should be shown.
      const previous = this.lastestScreenData?.regionData?.[regionId];

      if (previous !== undefined) {
        logger.warn(
          `Could not load playlists for region ${regionId}. Keeping the previously loaded content.`,
        );

        regionData[regionId] = previous;

        return;
      }

      // Nothing to fall back to: this is the first pull for the region.
      logger.warn(
        `Could not load playlists for region ${regionId} and have no earlier content for it.`,
      );

      regionData[regionId] = [];
    });

    return regionData;
  }

  /**
   * The playlist this region held on the previous pull.
   *
   * Matched on @id rather than on the position in the region: playlists get
   * added, removed and reordered between pulls, and handing a playlist another
   * playlist's slides is worse than handing it none - the same reasoning
   * getRegions applies when it pairs region ids with their requests up front.
   *
   * @param {string} regionId The region the playlist belongs to.
   * @param {string} playlistId The playlist's @id.
   * @returns {object|undefined} The playlist, if the previous pull had it.
   */
  previousPlaylist(regionId, playlistId) {
    if (playlistId === undefined) {
      return undefined;
    }

    const previousRegion = this.lastestScreenData?.regionData?.[regionId];

    if (!Array.isArray(previousRegion)) {
      return undefined;
    }

    return previousRegion.find((playlist) => playlist["@id"] === playlistId);
  }

  /**
   * Get slides for the given regions.
   *
   * @param {object} regions Regions to fetch slides for.
   * @param {object} report Collects which relation groups came back degraded.
   * @returns {Promise<object>} Promise with slides for the given regions.
   */
  async getSlidesForRegions(regions, report = {}) {
    const regionData = cloneDeep(regions);
    const entries = [];

    Object.keys(regionData).forEach((regionKey) => {
      Object.keys(regionData[regionKey]).forEach((playlistKey) => {
        entries.push({ regionKey, playlistKey });
      });
    });

    // The widest fan-out in a pull: one request per playlist per region. Bounded
    // so a screen with many regions does not open them all at once (#507).
    const results = await settleWithConcurrency(
      entries.map(
        ({ regionKey, playlistKey }) =>
          () =>
            this.apiHelper.getAllResultsFromPath(
              regionData[regionKey][playlistKey].slides,
            ),
      ),
      MAX_CONCURRENT_REQUESTS,
    );

    results.forEach((result, index) => {
      const { regionKey, playlistKey } = entries[index];
      const playlist = regionData[regionKey][playlistKey];

      if (
        result.status !== "fulfilled" ||
        result.value?.results === undefined
      ) {
        report.regions = true;

        // Keep the last known good slides for this playlist rather than none,
        // the same trade getRegions and the layout branch make. cloneDeep only
        // carried slidesData over when the playlists themselves came from the
        // previous pull - the getRegions fallback or the cache branch. In the
        // normal path they are fresh API objects that never had it, so without
        // this the playlist empties for a whole pull.
        const previous = this.previousPlaylist(regionKey, playlist["@id"]);

        if (previous?.slidesData !== undefined) {
          logger.warn(
            `Could not load slides for playlist ${playlistKey} in region ${regionKey}. Keeping the previously loaded slides.`,
          );

          // Cloned so newScreen does not share the array with lastestScreenData:
          // the relations loop below writes back into it in place.
          playlist.slidesData = cloneDeep(previous.slidesData);

          return;
        }

        logger.warn(
          `Could not load slides for playlist ${playlistKey} in region ${regionKey} and have no earlier slides for it.`,
        );

        return;
      }

      // Dropped rather than carried as a hole in the array. A PlaylistSlide
      // whose slide relation is absent maps to undefined, and the relations
      // loop in getScreen writes templateData onto every entry - which throws
      // on undefined and takes the entire pull with it, so one broken row in
      // one playlist froze the whole screen on its last content, every pull.
      playlist.slidesData = result.value.results
        .map((playlistSlide) => playlistSlide.slide)
        .filter((slide) => slide != null);
    });

    return regionData;
  }

  /**
   * Screen checksums the next pull should compare itself against.
   *
   * A pull that fell back to cached data for a relation group is not entitled to
   * the server's checksum for it. Storing it anyway is what froze screens: the
   * next pull compares equal, takes the cache branch, and serves the degraded
   * data until somebody edits the content in Admin (#507). Clearing the
   * checksum makes the group differ, so the next pull fetches it again.
   *
   * @param {object|null} checksums Checksums as the server sent them.
   * @param {object} report Relation groups this pull failed to load.
   * @returns {object|null} Checksums to compare the next pull against.
   */
  static checksumsToStore(checksums, report) {
    if (checksums === null) {
      return null;
    }

    const stored = { ...checksums };

    if (report.campaigns) {
      // One getCampaignsData call covers both, so neither can be trusted.
      stored.campaigns = CHECKSUM_UNAVAILABLE();
      stored.inScreenGroups = CHECKSUM_UNAVAILABLE();
    }

    if (report.layout) {
      stored.layout = CHECKSUM_UNAVAILABLE();
    }

    if (report.regions) {
      stored.regions = CHECKSUM_UNAVAILABLE();
    }

    return stored;
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
      screen = await this.apiHelper.getPath(screenPath);
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

    // Which relation groups this pull failed to load. A group listed here keeps
    // whatever the previous pull had, so its checksum must not be stored - see
    // checksumsToStore.
    const report = {};

    // Null rather than [] when the server sends no checksums at all: an empty
    // object compares equal to the next empty object on every key, which would
    // freeze the screen on cached data after the first pull. The API really can
    // send nothing here - the DTO getter answers null for an empty map.
    const newScreenChecksums = newScreen?.relationsChecksum ?? null;
    const oldScreenChecksums = this.lastestScreenChecksums ?? null;

    if (
      relationChecksumEnabled === false ||
      newScreenChecksums === null ||
      oldScreenChecksums === null ||
      oldScreenChecksums?.campaigns !== newScreenChecksums?.campaigns ||
      oldScreenChecksums?.inScreenGroups !== newScreenChecksums?.inScreenGroups
    ) {
      logger.info(`Fetching campaigns.`);
      newScreen.campaignsData = await this.getCampaignsData(newScreen, report);
    } else {
      logger.info(`Campaigns data loaded from cache.`);
      newScreen.campaignsData = this.lastestScreenData.campaignsData;
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

      // Create ulid to connect the campaign with the regions/playlists.
      const campaignRegionId = "01G112XBWFPY029RYFB8X2H4KD";

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
        report,
      );
    } else {
      logger.info(`Has no active campaign.`);

      // Get layout: Defines layout and regions.
      if (
        relationChecksumEnabled === false ||
        newScreenChecksums === null ||
        this.lastestScreenData?.hasActiveCampaign ||
        oldScreenChecksums === null ||
        oldScreenChecksums?.layout !== newScreenChecksums?.layout
      ) {
        logger.info(`Fetching layout.`);
        newScreen.layoutData = await this.apiHelper.getPath(newScreen.layout);

        if (newScreen.layoutData === null) {
          report.layout = true;

          // Keep the last known good layout rather than none, the same trade
          // getRegions makes. Screen builds its regions from layoutData.regions,
          // so a null unmounts every one of them: regionRemoved drops the
          // scheduling state, and the next good pull restarts playback from the
          // first slide.
          //
          // Only the layout this screen actually wants, though. A previous pull
          // in campaign mode holds the synthetic full-screen layout, and a pull
          // from before the screen was moved to another layout holds regions
          // that no longer match the regionData fetched below.
          const previous = this.lastestScreenData;
          const reusable =
            previous?.layoutData != null &&
            previous.hasActiveCampaign !== true &&
            previous.layout === newScreen.layout;

          if (reusable) {
            logger.warn(
              `Could not load layout (${newScreen.layout}). Keeping the previously loaded layout.`,
            );

            newScreen.layoutData = previous.layoutData;
          } else {
            logger.warn(
              `Could not load layout (${newScreen.layout}) and have no earlier layout for it.`,
            );
          }
        }
      } else {
        // Get layout: Defines layout and regions.
        logger.info(`Layout loaded from cache.`);
        newScreen.layoutData = this.lastestScreenData.layoutData;
      }

      // Fetch regions playlists: Yields playlists of slides for the regions
      if (
        relationChecksumEnabled === false ||
        newScreenChecksums === null ||
        this.lastestScreenData?.hasActiveCampaign ||
        oldScreenChecksums === null ||
        oldScreenChecksums?.regions !== newScreenChecksums?.regions
      ) {
        logger.info(`Fetching regions and slides for regions.`);
        const regions = await this.getRegions(newScreen.regions, report);
        newScreen.regionData = await this.getSlidesForRegions(regions, report);
      } else {
        logger.info(`Regions and slides for regions loaded from cache.`);
        newScreen.regionData = this.lastestScreenData.regionData;
      }
    }

    // Cached data.
    const fetchedMedia = {};

    // Iterate all slides and load required relations.
    const { regionData } = newScreen;
    /* eslint-disable no-restricted-syntax,no-await-in-loop */
    for (const regionKey of Object.keys(regionData)) {
      const regionDataEntry = regionData[regionKey];

      for (const playlistKey of Object.keys(regionDataEntry)) {
        const dataEntryPlaylist = regionDataEntry[playlistKey];
        // A playlist whose slides request failed has no slidesData. Without
        // this guard the whole pull rejects and every region goes blank.
        const dataEntrySlidesData = dataEntryPlaylist.slidesData ?? {};

        // Looked up once per playlist rather than once per slide.
        const previousPlaylist = this.previousPlaylist(
          regionKey,
          dataEntryPlaylist["@id"],
        );

        for (const slideKey of Object.keys(dataEntrySlidesData)) {
          const slide = cloneDeep(dataEntrySlidesData[slideKey]);

          // Find the slide in previous data for comparing relationsChecksum
          // values, and for the media cache below. Matched on @id, not on
          // position: an editor reordering a playlist would otherwise pair a
          // slide with a different slide's relations, and the cached branch
          // would hand it that slide's media.
          const previousSlideSource = previousPlaylist?.slidesData?.find(
            (candidate) => candidate["@id"] === slide["@id"],
          );

          const previousSlide = previousSlideSource
            ? cloneDeep(previousSlideSource)
            : {};

          // Null rather than [] for the same reason as the screen checksums
          // above: two empty maps compare equal on every key.
          const newSlideChecksums = slide.relationsChecksum ?? null;
          const oldSlideChecksums = previousSlide?.relationsChecksum ?? null;

          // Read the template off the slide rather than requesting it. Nothing
          // in the API's template resource is used for rendering - see
          // templateDataFromSlide.
          slide.templateData = templateDataFromSlide(slide);

          // A slide cannot work without templateData. Mark as invalid.
          if (slide.templateData === null) {
            logger.warn(
              `Template (${slide.templateInfo?.["@id"]}) has no id, slideId: ${slide["@id"]}`,
            );
            slide.invalid = true;
          } else if (slide.invalid === true) {
            // Carried over from an earlier pull, either through the cached
            // regions branch or from lastestScreenData. Region filters invalid
            // slides out, so reading a usable template achieves nothing unless
            // the flag is cleared with it.
            delete slide.invalid;
          }

          // Fetch media if it has changed, or if any item in the cached set
          // failed to load last time. Without the second condition a failed
          // fetch is cached as null behind an unchanged checksum, and every
          // later pull reuses the null - the item stays missing until an editor
          // touches the slide (#507). The checksum cannot carry that signal:
          // when the regions branch is served from cache, slide and
          // previousSlide are clones of the same cached object, so their
          // checksums always agree.
          if (
            relationChecksumEnabled === false ||
            newSlideChecksums === null ||
            oldSlideChecksums === null ||
            Object.values(previousSlide.mediaData ?? {}).includes(null) ||
            newSlideChecksums.media !== oldSlideChecksums.media
          ) {
            const nextMediaData = {};

            for (const mediaId of slide.media) {
              if (Object.prototype.hasOwnProperty.call(fetchedMedia, mediaId)) {
                nextMediaData[mediaId] = fetchedMedia[mediaId];
              } else {
                logger.info(`Fetching media data.`);
                const mediaData = await this.apiHelper.getPath(mediaId);
                nextMediaData[mediaId] = mediaData;

                if (mediaData !== null) {
                  fetchedMedia[mediaId] = mediaData;
                }
              }
            }

            slide.mediaData = nextMediaData;

            if (Object.values(nextMediaData).includes(null)) {
              logger.warn(
                `Media not loaded for slideId: ${slide["@id"]}. Retrying on the next pull.`,
              );
            }
          } else {
            logger.info(`Media data loaded from cache.`);
            slide.mediaData = previousSlide.mediaData;
          }

          // Fetch feed.
          if (slide?.feed?.feedUrl !== undefined) {
            logger.info(`Fetching feed data.`);
            slide.feedData = await this.apiHelper.getPath(slide.feed.feedUrl);
          }

          dataEntrySlidesData[slideKey] = slide;
        }
      }
    }
    /* eslint-enable no-restricted-syntax,no-await-in-loop */

    this.lastestScreenData = newScreen;
    this.lastestScreenChecksums = PullStrategy.checksumsToStore(
      newScreenChecksums,
      report,
    );

    // Deliver result to rendering
    const event = new CustomEvent("content", {
      detail: {
        screen: newScreen,
      },
    });
    document.dispatchEvent(event);
  }

  getPath(id) {
    return this.apiHelper.getPath(id);
  }

  getAllResultsFromPath(path, keys = {}) {
    return this.apiHelper.getAllResultsFromPath(path, keys);
  }

  async getFeedData(slide) {
    return new Promise((resolve) => {
      if (!slide?.feed?.feedUrl) {
        resolve([]);
      } else {
        this.apiHelper.getPath(slide.feed.feedUrl).then((data) => {
          resolve(data);
        });
      }
    });
  }

  async getMediaData(media) {
    return new Promise((resolve) => {
      this.apiHelper.getPath(media).then((data) => {
        resolve(data);
      });
    });
  }

  /**
   * Start the data synchronization.
   */
  start() {
    // Make sure nothing is running.
    this.stop();

    this.stopped = false;
    this.chainId += 1;

    // Pull now, then keep rescheduling.
    this.pull(this.chainId);
  }

  /**
   * Run one pull, then schedule the next one.
   *
   * @param {number} chainId The generation this chain belongs to.
   */
  async pull(chainId) {
    try {
      await this.getScreen(this.entryPoint);
    } catch (err) {
      // A failed pull must not stop the poll from being scheduled, or the
      // screen stays dead until it is reloaded.
      logger.error(err);
    } finally {
      // In finally rather than after the catch: a throw from logger.error
      // would otherwise end the chain silently. The generation check keeps a
      // restart from leaving two chains scheduling against each other.
      if (this.stopped === false && chainId === this.chainId) {
        // Scheduled only once the previous pull settled. setInterval would
        // start a second pull on top of a slow one, doubling the fan-out
        // exactly when the backend is already struggling (#507).
        this.activeTimeout = setTimeout(
          () => this.pull(chainId),
          this.interval,
        );
      }
    }
  }

  /**
   * Stop the data synchronization.
   */
  stop() {
    // A pull already in flight cannot be cancelled, so the flag is what keeps
    // it from scheduling a successor once it finishes.
    this.stopped = true;

    if (this.activeTimeout !== undefined) {
      clearTimeout(this.activeTimeout);
      delete this.activeTimeout;
    }
  }
}

export default PullStrategy;

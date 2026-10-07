import PullStrategy from "../data-sync/pull-strategy";
import {
  screenForPlaylistPreview,
  screenForSlidePreview,
} from "../util/preview";
import logger from "../logger/logger";
import DataSync from "../data-sync/data-sync";
import ScheduleService from "./schedule-service";
import ClientConfigLoader from "../util/client-config-loader.js";
import templateDataFromSlide from "../util/template-data-from-slide.js";

/**
 * ContentService.
 *
 * The central component responsible for receiving data from DataSync and sending data to the react components.
 */
class ContentService {
  dataSync;

  currentScreen;

  scheduleService;

  /**
   * Constructor.
   */
  constructor() {
    // Setup schedule service.
    this.scheduleService = new ScheduleService();

    this.startSyncing = this.startSyncing.bind(this);
    this.stopSyncHandler = this.stopSyncHandler.bind(this);
    this.startDataSyncHandler = this.startDataSyncHandler.bind(this);
    this.regionReadyHandler = this.regionReadyHandler.bind(this);
    this.regionRemovedHandler = this.regionRemovedHandler.bind(this);
    this.contentHandler = this.contentHandler.bind(this);
    this.start = this.start.bind(this);
  }

  /**
   * Start data synchronization.
   *
   * @param {string} screenPath Path to the screen.
   */
  startSyncing(screenPath) {
    logger.info("Starting data synchronization");

    ClientConfigLoader.loadConfig().then((config) => {
      const dataStrategyConfig = {
        interval: config.pullStrategyInterval,
        endpoint: "",
      };

      if (screenPath) {
        dataStrategyConfig.entryPoint = screenPath;
      }

      this.dataSync = new DataSync(dataStrategyConfig);
      this.dataSync.start();
    });
  }

  /**
   * Stop sync event handler.
   */
  stopSyncHandler() {
    logger.info("Event received: Stop data synchronization");

    if (this.dataSync) {
      logger.info("Stopping data synchronization");
      this.dataSync.stop();
      this.dataSync = null;
    }
  }

  /**
   * Start data event handler.
   *
   * @param {CustomEvent} event
   *   The event.
   */
  startDataSyncHandler(event) {
    const data = event.detail;

    this.stopSyncHandler();

    logger.log(
      "info",
      `Event received: Start data synchronization from ${data?.screenPath}`,
    );
    if (data?.screenPath) {
      logger.info(
        `Event received: Start data synchronization from ${data.screenPath}`,
      );
      this.startSyncing(data.screenPath);
    } else {
      logger.log("error", "Error: screenPath not set.");
    }
  }

  /**
   * New content event handler.
   *
   * @param {CustomEvent} event
   *   The event.
   */
  contentHandler(event) {
    logger.info("Event received: content");

    this.currentScreen = event.detail.screen;

    const { regionData } = this.currentScreen;

    // Push before emitting. A pull that served the layout from cache hands back
    // the very same region objects, so a region cannot be relied on to notice
    // the new content itself - that is what left playlists stale (#507).
    // Pushing first also means the cache is populated before a region mounted by
    // the emit below asks for it in regionReady.
    // eslint-disable-next-line no-restricted-syntax
    for (const regionKey of Object.keys(regionData ?? {})) {
      this.scheduleService.updateRegion(regionKey, regionData[regionKey]);
    }

    // Regions are fed through ScheduleService, so the screen goes out without
    // them. Emitted unconditionally: re-rendering is cheap because Screen keys
    // its regions by id, so React reconciles them in place and playback state
    // survives.
    const screenData = { ...this.currentScreen };
    delete screenData.regionData;

    ContentService.emitScreen(screenData);
  }

  /**
   * Region ready handler.
   *
   * @param {CustomEvent} event
   *   The event.
   */
  regionReadyHandler(event) {
    const data = event.detail;
    const regionId = data.id;

    logger.info(`Event received: regionReady for ${regionId}`);

    // A region that changes type - default <-> touch-buttons under the same
    // region id - is a different component, so Screen unmounts one and mounts
    // the other in a single commit. React runs the old component's cleanup
    // before the new one's effects, so regionRemoved has already dropped the
    // cached slides and cleared the scheduling interval by the time regionReady
    // asks for them. The region would then show nothing until the next pull
    // happened to change its content - up to the pull interval of blank screen.
    //
    // Rebuild from the screen the last pull delivered instead. updateRegion
    // caches the slides, re-registers the interval and sends the slides on,
    // which is everything regionReady would have done.
    if (!this.scheduleService.hasRegion(regionId)) {
      const regionData = this.currentScreen?.regionData?.[regionId];

      if (regionData !== undefined) {
        logger.info(`Restoring content for remounted region ${regionId}.`);

        this.scheduleService.updateRegion(regionId, regionData);

        return;
      }
    }

    this.scheduleService.regionReady(regionId);
  }

  /**
   * Region removed handler.
   *
   * @param {CustomEvent} event
   *   The event.
   */
  regionRemovedHandler(event) {
    const data = event.detail;
    const regionId = data.id;

    logger.info(`Event received: regionRemoved for ${regionId}`);

    this.scheduleService.regionRemoved(regionId);
  }

  /**
   * Start the engine.
   */
  start() {
    logger.info("Content service started.");

    document.addEventListener("stopDataSync", this.stopSyncHandler);
    document.addEventListener("startDataSync", this.startDataSyncHandler);
    document.addEventListener("content", this.contentHandler);
    document.addEventListener("regionReady", this.regionReadyHandler);
    document.addEventListener("regionRemoved", this.regionRemovedHandler);
    document.addEventListener("startPreview", this.startPreview);
  }

  /**
   * Stop the engine.
   */
  stop() {
    logger.info("Content service stopped.");

    document.removeEventListener("stopDataSync", this.stopSyncHandler);
    document.removeEventListener("startDataSync", this.startDataSyncHandler);
    document.removeEventListener("content", this.contentHandler);
    document.removeEventListener("regionReady", this.regionReadyHandler);
    document.removeEventListener("regionRemoved", this.regionRemovedHandler);
    document.removeEventListener("startPreview", this.startPreview);
  }

  /**
   * Start preview.
   *
   * @param {CustomEvent} event The event.
   */
  async startPreview(event) {
    const data = event.detail;
    const { mode, id } = data;
    logger.log("info", `Starting preview. Mode: ${mode}, ID: ${id}`);

    if (mode === "screen") {
      this.startSyncing(`/v2/screen/${id}`);
    } else if (mode === "playlist") {
      const pullStrategy = new PullStrategy({
        endpoint: "",
      });

      const playlist = await pullStrategy.getPath(`/v2/playlists/${id}`);

      // Paginated collection: fetch every page, not just the first.
      const playlistSlidesResponse = await pullStrategy.getAllResultsFromPath(
        playlist.slides,
      );

      playlist.slidesData = (playlistSlidesResponse.results ?? []).map(
        (playlistSlide) => playlistSlide.slide,
      );

      // eslint-disable-next-line no-restricted-syntax
      for (const slide of playlist.slidesData) {
        // eslint-disable-next-line no-await-in-loop
        await ContentService.attachReferencesToSlide(pullStrategy, slide);
      }

      const screen = screenForPlaylistPreview(playlist);

      document.dispatchEvent(
        new CustomEvent("content", {
          detail: {
            screen,
          },
        }),
      );
    } else if (mode === "slide") {
      const pullStrategy = new PullStrategy({
        endpoint: "",
      });

      const slide = await pullStrategy.getPath(`/v2/slides/${id}`);

      // eslint-disable-next-line no-await-in-loop
      await ContentService.attachReferencesToSlide(pullStrategy, slide);

      const screen = screenForSlidePreview(slide);

      document.dispatchEvent(
        new CustomEvent("content", {
          detail: {
            screen,
          },
        }),
      );
    } else {
      logger.error(`Unsupported preview mode: ${mode}.`);
    }
  }

  static async attachReferencesToSlide(strategy, slide) {
    /* eslint-disable no-param-reassign */
    slide.templateData = templateDataFromSlide(slide);
    slide.feedData = await strategy.getFeedData(slide);

    slide.mediaData = {};
    // eslint-disable-next-line no-restricted-syntax
    for (const media of slide.media) {
      // eslint-disable-next-line no-await-in-loop
      slide.mediaData[media] = await strategy.getMediaData(media);
    }

    if (typeof slide.theme === "string" || slide.theme instanceof String) {
      slide.theme = await strategy.getPath(slide.theme);
    }
    /* eslint-enable no-param-reassign */
  }

  /**
   * Emit screen.
   *
   * @param {object} screen
   *   Screen data.
   */
  static emitScreen(screen) {
    logger.info("Emitting screen");

    const event = new CustomEvent("screen", {
      detail: {
        screen,
      },
    });
    document.dispatchEvent(event);
  }
}

export default ContentService;

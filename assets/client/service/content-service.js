import sha256 from "crypto-js/sha256";
import Base64 from "crypto-js/enc-base64";
import {
  screenForPlaylistPreview,
  screenForSlidePreview,
} from "../util/preview";
import logger from "../logger/logger";
import idFromPath from "../util/id-from-path";
import DataSync from "../data-sync/data-sync";
import ScheduleService from "./schedule-service";
import ClientConfigLoader from "../util/client-config-loader.js";
import { query } from "../util/api-query.js";

/**
 * ContentService.
 *
 * The central component responsible for receiving data from DataSync and sending data to the react components.
 */
class ContentService {
  dataSync;

  currentScreen;

  scheduleService;

  screenHash;

  /**
   * Constructor.
   *
   * @param {object} callbacks - Ref object whose .current holds callback functions
   *   (setScreen, setIsContentEmpty, updateRegionSlides, onRegionReady, onRegionRemoved).
   */
  constructor(callbacks) {
    this.callbacks = callbacks;

    // Setup schedule service.
    this.scheduleService = new ScheduleService(callbacks);

    this.startSyncing = this.startSyncing.bind(this);
    this.stopSync = this.stopSync.bind(this);
    this.contentHandler = this.contentHandler.bind(this);
    this.startPreview = this.startPreview.bind(this);
    this.start = this.start.bind(this);
    this.regionReady = this.regionReady.bind(this);
    this.regionRemoved = this.regionRemoved.bind(this);
  }

  /**
   * Start data synchronization.
   *
   * @param {string} screenPath Path to the screen.
   */
  startSyncing(screenPath) {
    logger.info("Starting data synchronization");
    this.syncingStopped = false;

    ClientConfigLoader.loadConfig().then((config) => {
      if (this.syncingStopped) return;

      const dataStrategyConfig = {
        interval: config.pullStrategyInterval,
        endpoint: "",
        onContent: this.contentHandler,
      };

      if (screenPath) {
        dataStrategyConfig.entryPoint = screenPath;
      }

      this.dataSync = new DataSync(dataStrategyConfig);
      this.dataSync.start();
    });
  }

  /**
   * Stop data synchronization.
   */
  stopSync() {
    logger.info("Stopping data synchronization");
    this.syncingStopped = true;

    if (this.dataSync) {
      this.dataSync.stop();
      this.dataSync = null;
    }
  }

  /**
   * New content handler.
   *
   * @param {object} screen - The screen data.
   */
  contentHandler(screen) {
    logger.info("Content received");

    this.currentScreen = screen;

    const screenData = { ...this.currentScreen };

    // Remove regionData to only emit screen when it has changed.
    delete screenData.regionData;

    const newHash = Base64.stringify(sha256(JSON.stringify(screenData)));

    if (newHash !== this.screenHash) {
      logger.info("Screen has changed. Updating screen.");
      this.screenHash = newHash;
      this.callbacks.current.setScreen(screenData);
    } else {
      logger.info("Screen has not changed. Not updating screen.");
    }

    // Always push region data so both new and existing regions get content.
    // eslint-disable-next-line guard-for-in,no-restricted-syntax
    for (const regionKey in screen.regionData) {
      this.scheduleService.updateRegion(regionKey, screen.regionData[regionKey]);
    }
  }

  /**
   * Region ready handler.
   *
   * @param {string} regionId - The region id.
   */
  regionReady(regionId) {
    logger.info(`Region ready: ${regionId}`);

    if (this.currentScreen) {
      this.scheduleService.updateRegion(
        regionId,
        this.currentScreen.regionData[regionId],
      );
    }
  }

  /**
   * Region removed handler.
   *
   * @param {string} regionId - The region id.
   */
  regionRemoved(regionId) {
    logger.info(`Region removed: ${regionId}`);

    this.scheduleService.regionRemoved(regionId);
  }

  /**
   * Start the engine.
   */
  start() {
    if (this.started) {
      logger.warn("Content service already started.");
      return;
    }
    this.started = true;

    logger.info("Content service started.");

    // Wire up region lifecycle callbacks so components can notify us directly.
    this.callbacks.current.onRegionReady = this.regionReady;
    this.callbacks.current.onRegionRemoved = this.regionRemoved;
  }

  /**
   * Stop the engine.
   */
  stop() {
    if (!this.started) return;
    this.started = false;

    logger.info("Content service stopped.");

    this.callbacks.current.onRegionReady = () => {};
    this.callbacks.current.onRegionRemoved = () => {};
  }

  /**
   * Start preview.
   *
   * @param {string} mode - Preview mode (screen, playlist, slide).
   * @param {string} id - Entity ID to preview.
   */
  async startPreview(mode, id) {
    logger.info(`Starting preview. Mode: ${mode}, ID: ${id}`);

    try {
      if (mode === "screen") {
        this.startSyncing(`/v2/screens/${id}`);
      } else if (mode === "playlist") {
        const playlist = await query("getV2PlaylistsById", { id }, true);

        const playlistSlidesResponse = await query(
          "getV2PlaylistsByIdSlides",
          { id: idFromPath(playlist.slides) },
          true,
        );

        playlist.slidesData = playlistSlidesResponse["hydra:member"].map(
          (playlistSlide) => playlistSlide.slide,
        );

        // eslint-disable-next-line no-restricted-syntax
        for (const slide of playlist.slidesData) {
          // eslint-disable-next-line no-await-in-loop
          await ContentService.attachReferencesToSlide(slide);
        }

        const screen = screenForPlaylistPreview(playlist);
        this.contentHandler(screen);
      } else if (mode === "slide") {
        const slide = await query("getV2SlidesById", { id }, true);

        // eslint-disable-next-line no-await-in-loop
        await ContentService.attachReferencesToSlide(slide);

        const screen = screenForSlidePreview(slide);
        this.contentHandler(screen);
      } else {
        logger.error(`Unsupported preview mode: ${mode}.`);
      }
    } catch (err) {
      logger.error(
        `Preview failed (mode: ${mode}, id: ${id}): ${err.message}`,
      );
    }
  }

  static async attachReferencesToSlide(slide) {
    /* eslint-disable no-param-reassign */
    try {
      slide.templateData = await query("getV2TemplatesById", {
        id: idFromPath(slide.templateInfo["@id"]),
      }, true);
    } catch (err) {
      slide.templateData = null;
      slide.invalid = true;
      slide.mediaData = {};
      slide.feedData = null;
      return;
    }

    if (slide?.feed?.feedUrl !== undefined) {
      try {
        slide.feedData = await query("getV2FeedsByIdData", {
          id: idFromPath(slide.feed.feedUrl),
        }, true);
      } catch (err) {
        slide.feedData = null;
      }
    } else {
      slide.feedData = [];
    }

    slide.mediaData = {};
    // eslint-disable-next-line no-restricted-syntax
    for (const media of slide.media) {
      try {
        // eslint-disable-next-line no-await-in-loop
        slide.mediaData[media] = await query("getV2MediaById", {
          id: idFromPath(media),
        }, true);
      } catch (err) {
        slide.mediaData[media] = null;
      }
    }

    if (typeof slide.theme === "string" || slide.theme instanceof String) {
      try {
        slide.theme = await query("getV2ThemesById", {
          id: idFromPath(slide.theme),
        }, true);
      } catch (err) {
        // Keep theme as the original string path.
      }
    }
    /* eslint-enable no-param-reassign */
  }
}

export default ContentService;

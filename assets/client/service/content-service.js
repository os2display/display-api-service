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
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

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
    this.startPreview = this.startPreview.bind(this);
    this.start = this.start.bind(this);
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
    this.syncingStopped = true;

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

    if (data?.screenPath) {
      logger.info(
        `Event received: Start data synchronization from ${data.screenPath}`,
      );
      this.startSyncing(data.screenPath);
    } else {
      logger.error("Error: screenPath not set.");
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

    const data = event.detail;
    this.currentScreen = data.screen;

    const screenData = { ...this.currentScreen };

    // Remove regionData to only emit screen when it has changed.
    delete screenData.regionData;

    const newHash = Base64.stringify(sha256(JSON.stringify(screenData)));

    if (newHash !== this.screenHash) {
      logger.info("Screen has changed. Emitting screen.");
      this.screenHash = newHash;
      ContentService.emitScreen(screenData);
    } else {
      logger.info("Screen has not changed. Not emitting screen.");

      // eslint-disable-next-line guard-for-in,no-restricted-syntax
      for (const regionKey in data.screen.regionData) {
        const region = this.currentScreen.regionData[regionKey];
        this.scheduleService.updateRegion(regionKey, region);
      }
    }
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
    if (this.started) {
      logger.warn("Content service already started.");
      return;
    }
    this.started = true;

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
    if (!this.started) return;
    this.started = false;

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
    logger.info(`Starting preview. Mode: ${mode}, ID: ${id}`);

    try {
      if (mode === "screen") {
        this.startSyncing(`/v2/screen/${id}`);
      } else if (mode === "playlist") {
        const playlist = await ContentService.query("getV2PlaylistsById", {
          id,
        });

        const playlistSlidesResponse = await ContentService.query(
          "getV2PlaylistsByIdSlides",
          { id: idFromPath(playlist.slides) },
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

        document.dispatchEvent(
          new CustomEvent("content", {
            detail: {
              screen,
            },
          }),
        );
      } else if (mode === "slide") {
        const slide = await ContentService.query("getV2SlidesById", { id });

        // eslint-disable-next-line no-await-in-loop
        await ContentService.attachReferencesToSlide(slide);

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
    } catch (err) {
      logger.error(
        `Preview failed (mode: ${mode}, id: ${id}): ${err.message}`,
      );
    }
  }

  static query(endpoint, args) {
    const request = clientStore.dispatch(
      clientApi.endpoints[endpoint].initiate(args),
    );
    return request.unwrap().finally(() => {
      request.unsubscribe();
    });
  }

  static async attachReferencesToSlide(slide) {
    /* eslint-disable no-param-reassign */
    slide.templateData = await ContentService.query("getV2TemplatesById", {
      id: idFromPath(slide.templateInfo["@id"]),
    });

    if (slide?.feed?.feedUrl) {
      slide.feedData = await ContentService.query("getV2FeedsByIdData", {
        id: idFromPath(slide.feed.feedUrl),
      });
    } else {
      slide.feedData = [];
    }

    slide.mediaData = {};
    // eslint-disable-next-line no-restricted-syntax
    for (const media of slide.media) {
      // eslint-disable-next-line no-await-in-loop
      slide.mediaData[media] = await ContentService.query("getV2MediaById", {
        id: idFromPath(media),
      });
    }

    if (typeof slide.theme === "string" || slide.theme instanceof String) {
      slide.theme = await ContentService.query("getV2ThemesById", {
        id: idFromPath(slide.theme),
      });
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

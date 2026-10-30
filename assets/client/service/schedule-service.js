import sha256 from "crypto-js/sha256";
import Md5 from "crypto-js/md5";
import Base64 from "crypto-js/enc-base64";
import isPublished from "../util/isPublished";
import logger from "../logger/logger";
import ClientConfigLoader from "../util/client-config-loader.js";
import ScheduleUtils from "../util/schedule";
import isRenderableSlide from "../util/is-renderable-slide";
import { cloneDeep } from "lodash";

/**
 * ScheduleService.
 *
 * Supplies slides to the different regions.
 * Handles content scheduling.
 */
class ScheduleService {
  regions = {};

  intervals = {};

  contentEmpty = true;

  constructor() {
    this.updateRegion = this.updateRegion.bind(this);
    this.regionReady = this.regionReady.bind(this);
    this.checkForEmptyContent = this.checkForEmptyContent.bind(this);
    this.sendSlides = this.sendSlides.bind(this);
  }

  checkForEmptyContent() {
    logger.info("Checking for empty content.");

    // Check for empty content. Counted with the test Region applies when it
    // renders, not just "has slides": a region whose slides all failed their
    // template fetch holds slides that will never be shown, and counting those
    // as content suppressed the fallback image and left the screen black.
    const values = Object.values(this.regions);

    const contentEmpty =
      values.filter((value) => value?.slides?.some(isRenderableSlide))
        .length === 0;

    if (contentEmpty !== this.contentEmpty) {
      this.contentEmpty = contentEmpty;

      // Deliver result to rendering
      const event = new Event(
        contentEmpty ? "contentEmpty" : "contentNotEmpty",
      );
      document.dispatchEvent(event);
    }
  }

  /**
   * A region has mounted and is listening. Send it what is cached.
   *
   * updateRegion's hash gate exists to avoid re-sending content a region is
   * already showing. A region that has only just mounted is showing nothing and
   * missed whatever was dispatched before it registered its listener, so it gets
   * the current slides regardless of the hash.
   *
   * @param {string} regionId - The region id.
   */
  regionReady(regionId) {
    const cached = this.regions[regionId];

    if (!cached) {
      logger.info(`ScheduleService: no content cached for region ${regionId}.`);
      return;
    }

    this.sendSlides(regionId, cached.slides);
  }

  /**
   * Whether slides are cached for a region.
   *
   * @param {string} regionId - The region id.
   * @returns {boolean} True if the region has cached content.
   */
  hasRegion(regionId) {
    return Object.prototype.hasOwnProperty.call(this.regions, regionId);
  }

  /**
   * Remove scheduling interval for region if region is removed.
   *
   * @param {string} regionId - The region id.
   */
  regionRemoved(regionId) {
    logger.info(`removing scheduling interval for region: ${regionId}`);

    if (Object.prototype.hasOwnProperty.call(this.intervals, regionId)) {
      clearInterval(this.intervals[regionId]);
      delete this.intervals[regionId];
    }

    // Remove cached version of region data.
    delete this.regions[regionId];

    // Losing a region changes whether anything is left to show. Without this,
    // unmounting the last region that had content leaves contentEmpty stuck at
    // false, so the fallback image never comes back and the screen is black -
    // which is exactly what a failed layout request does (it renders no
    // regions at all).
    this.checkForEmptyContent();
  }

  /**
   * Handle region updates.
   *
   * @param {string} regionId - The region id.
   * @param {object} region - The region content, with playlists and slides, to start scheduling.
   */
  updateRegion(regionId, region) {
    logger.info(`ScheduleService: updateRegion(${regionId})`);

    if (!region || !regionId) {
      logger.info(`ScheduleService: regionId and/or region not set.`);
      return;
    }

    // Extract slides from playlists.
    const slides = ScheduleService.findScheduledSlides(region, regionId);

    // Calculate a hash of the scheduled slides to test if they have changed.
    //
    // This gate stays, unlike the one ContentService used to have: that one only
    // decided whether React re-rendered, this one decides whether new arrays are
    // pushed into region state. It is also the only thing that notices a feed
    // changed - feedData is refetched on every pull and carries no checksum, so
    // nothing else can see it. That is why the whole slide payload is hashed and
    // not just ids.
    //
    // The region itself is deliberately not part of the input: findScheduledSlides
    // derives the slides from it, so anything that changes what is rendered shows
    // up in slides anyway, and hashing both doubles the work for every pull.
    const hash = Base64.stringify(sha256(JSON.stringify(slides)));
    const newContent = hash !== this?.regions[regionId]?.hash;

    // Update region.
    this.regions[regionId] = {
      hash,
      slides,
      region,
    };

    const { intervals } = this;

    if (!Object.prototype.hasOwnProperty.call(intervals, regionId)) {
      ClientConfigLoader.loadConfig().then((config) => {
        const schedulingInterval = config?.schedulingInterval ?? 60000;

        // Extra check because of async.
        if (!Object.prototype.hasOwnProperty.call(intervals, regionId)) {
          logger.info(
            `registering scheduling interval for region: ${regionId}, with an update rate of ${schedulingInterval}`,
          );

          this.intervals[regionId] = setInterval(
            () => this.checkScheduling(regionId),
            schedulingInterval,
          );
        }
      });
    }

    if (newContent) {
      // Send slides to region.
      this.sendSlides(regionId, slides);
    }
  }

  /**
   * Check scheduling for playlists and slides, to see if there are changes compared with current shown content.
   *
   * @param {string} regionId - The region to check.
   */
  checkScheduling(regionId) {
    logger.info(`checkScheduling for region: ${regionId}`);

    const region = this.regions[regionId];

    // Extract slides from playlists.
    const slides = ScheduleService.findScheduledSlides(region.region, regionId);

    // Calculate a hash of the scheduled slides to test if they have changed.
    const hash = Base64.stringify(sha256(JSON.stringify(slides)));
    const newContent = hash !== this?.regions[regionId]?.hash;

    // Update region. The slides have to be stored under the same key updateRegion
    // uses - regionReady replays them to a region that has just mounted, and a
    // stale entry here would hand it the content from before the last schedule
    // change.
    this.regions[regionId].hash = hash;
    this.regions[regionId].slides = slides;

    if (newContent) {
      // Send slides to region.
      this.sendSlides(regionId, slides);
    }
  }

  /**
   * Send next slides.
   *
   * @param {string} regionId
   *   The region id to send slides to.
   * @param {Array} slides
   *   Array of slides.
   */
  sendSlides(regionId, slides) {
    logger.info(`sendSlides regionContent-${regionId}`);
    const event = new CustomEvent(`regionContent-${regionId}`, {
      detail: {
        slides,
      },
    });
    document.dispatchEvent(event);

    this.checkForEmptyContent();
  }

  /**
   * Find slides that are scheduled and published now in the given region.
   *
   * @param {Array} playlists - The playlists to look through, with the slidesData attached.
   * @param {string} regionId - The region id. Used to creating a unique executionId for each slide.
   * @returns {Array} - Array of slides.
   */
  static findScheduledSlides(playlists, regionId) {
    const slides = [];

    playlists.forEach((playlist) => {
      const { schedules } = playlist;

      if (!isPublished(playlist?.published)) {
        return;
      }

      let active = true;

      // If schedules are set for the playlist, do not show playlist unless a schedule is active.
      if (schedules.length > 0) {
        active = false;

        // Run through all schedule item and see if it occurs now. If one or more occur now, the playlist is active.
        schedules.every((schedule) => {
          const scheduleOccurs = ScheduleUtils.occursNow(
            schedule.rrule,
            schedule.duration,
          );

          if (scheduleOccurs) {
            active = true;

            // Break iteration.
            return false;
          }

          // Continue iteration.
          return true;
        });
      }

      if (active) {
        playlist?.slidesData?.forEach((slide) => {
          if (!isPublished(slide.published)) {
            return;
          }

          const newSlide = cloneDeep(slide);

          // Execution id is the product of region, playlist and slide id, to ensure uniqueness in the client.
          const executionId = Md5(regionId + playlist["@id"] + slide["@id"]);
          newSlide.executionId = `EXE-ID-${executionId}`;
          slides.push(newSlide);
        });
      } else {
        logger.log("info", `Playlist ${playlist["@id"]} not scheduled for now`);
      }
    });

    return slides;
  }
}

export default ScheduleService;

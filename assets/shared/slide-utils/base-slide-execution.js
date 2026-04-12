/**
 * BaseSlideExecution.
 *
 * Slide runs for duration then calls slideDone().
 */
class BaseSlideExecution {
  // Function to call when the slide is done executing.
  slideDone;

  // Slide that should be run.
  slide;

  // Slide timeout.
  slideTimeout = null;

  /**
   * Constructor.
   *
   * @param {object} slide The slide to execute.
   * @param {Function} slideDone The function to invoke when execution is done.
   */
  constructor(slide, slideDone) {
    this.slide = slide;
    this.slideDone = slideDone;
  }

  /**
   * Start execution of slide.
   *
   * @param {number} duration Slide duration in milliseconds.
   */
  start(duration) {
    if (this.slideTimeout !== null) {
      clearTimeout(this.slideTimeout);
    }

    const safeDuration =
      Number.isFinite(duration) && duration > 0 ? duration : 15000;

    // Wait duration then call slideDone.
    this.slideTimeout = setTimeout(() => {
      if (typeof this.slideDone === "function") {
        this.slideDone(this.slide);
      }
      this.slideTimeout = null;
    }, safeDuration);
  }

  /**
   * Stops execution timeout.
   *
   * Does not call slideDone — this is intentional for cleanup-on-unmount
   * scenarios where the slide was cancelled, not completed.
   */
  stop() {
    if (this.slideTimeout !== null) {
      clearTimeout(this.slideTimeout);
      this.slideTimeout = null;
    }
  }
}

export default BaseSlideExecution;

import PullStrategy from "./pull-strategy";

/**
 * DataSync.
 *
 * Handles data synchronization.
 */
class DataSync {
  /**
   * Constructor.
   *
   * @param {object} config
   *   The config object.
   */
  constructor(config) {
    this.start = this.start.bind(this);
    this.stop = this.stop.bind(this);

    this.config = config;
    this.strategy = new PullStrategy(this.config, this.config.onContent);
  }

  /**
   * Start the data synchronization.
   */
  start() {
    this.strategy.start();
  }

  /**
   * Stop the data synchronization.
   */
  stop() {
    this.strategy.stop();
  }
}

export default DataSync;

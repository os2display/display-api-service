const DEFAULT_FETCH_INTERVAL = 5 * 60 * 1000;

const DEFAULT_RELEASE = {
  releaseTime: null,
  releaseTimestamp: null,
  releaseVersion: null,
};

class ReleaseLoader {
  #releaseData = null;
  #latestFetchTimestamp = null;
  #activePromise = null;
  #fetchFn;
  #nowFn;
  #fetchInterval;

  /**
   * @param {object} options
   * @param {Function} options.fetchFn - Fetch implementation. Defaults to global fetch.
   * @param {Function} options.nowFn - Returns current time in ms. Defaults to Date.now.
   * @param {number} options.fetchInterval - Cache lifetime in ms. Defaults to 5 minutes.
   */
  constructor({
    fetchFn = (...args) => fetch(...args),
    nowFn = () => Date.now(),
    fetchInterval = DEFAULT_FETCH_INTERVAL,
  } = {}) {
    this.#fetchFn = fetchFn;
    this.#nowFn = nowFn;
    this.#fetchInterval = fetchInterval;
  }

  async loadRelease() {
    if (this.#activePromise !== null) {
      return this.#activePromise;
    }

    const nowTimestamp = this.#nowFn();

    // Return early without going through activePromise so the caller always
    // receives a real promise, not null.
    if (
      this.#latestFetchTimestamp !== null &&
      this.#latestFetchTimestamp + this.#fetchInterval >= nowTimestamp
    ) {
      return Promise.resolve(this.#releaseData);
    }

    this.#activePromise = this.#fetchFn(`/release.json?t=${nowTimestamp}`)
      .then((response) => response.json())
      .then((data) => {
        this.#latestFetchTimestamp = nowTimestamp;
        this.#releaseData = data;
        return this.#releaseData;
      })
      .catch(() => {
        if (this.#releaseData !== null) {
          // Advance the timestamp so the next call uses the cache instead of
          // immediately retrying after a failed fetch.
          this.#latestFetchTimestamp = nowTimestamp;
          return this.#releaseData;
        }

        /* eslint-disable-next-line no-console */
        console.warn("Could not find release.json. Returning defaults.");

        return DEFAULT_RELEASE;
      })
      .finally(() => {
        // Always clear activePromise via finally so concurrent callers share a
        // single in-flight fetch. It is cleared on both success and failure.
        this.#activePromise = null;
      });

    return this.#activePromise;
  }
}

// Default singleton for production use.
const releaseLoader = new ReleaseLoader();
export default releaseLoader;

export { ReleaseLoader };

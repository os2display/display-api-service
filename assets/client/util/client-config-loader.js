// Only fetch new config if more than 15 minutes have passed.
import appStorage from "./app-storage.js";
import fetchWithTimeout from "./fetch-with-timeout.js";

const configFetchIntervalDefault = 15 * 60 * 1000;

// Defaults.
let configData = null;

// Last time the config was fetched.
let latestFetchTimestamp = 0;

let activePromise = null;

/**
 * Config used when it cannot be loaded at all.
 *
 * Built fresh per call so a caller that edits what it got back cannot corrupt
 * the fallback for everyone after it, and deliberately not assigned to
 * configData, so a later call tries the real config again.
 *
 * @returns {object} The default config.
 */
function buildDefaultConfig() {
  return {
    apiEndpoint: "/api",
    dataStrategy: {
      type: "pull",
      config: {
        interval: 30000,
      },
    },
    loginCheckTimeout: 20000,
    configFetchInterval: 900000,
    refreshTokenTimeout: 15000,
    releaseTimestampIntervalTimeout: 600000,
    colorScheme: {
      type: "library",
      lat: 56.0,
      lng: 10.0,
    },
    schedulingInterval: 60000,
    debug: false,
  };
}

const ClientConfigLoader = {
  async loadConfig() {
    if (activePromise) {
      return activePromise;
    }

    const nowTimestamp = new Date().getTime();

    // `configData !== null` guards the cold start: with no config loaded yet,
    // the interval comparison alone can be satisfied by a small clock value and
    // hand back null as though it were config.
    if (
      configData !== null &&
      latestFetchTimestamp +
        (configData?.configFetchInterval ?? configFetchIntervalDefault) >=
        nowTimestamp
    ) {
      return configData;
    }

    // Cleared on every path. The cached branch used to leave it set, which
    // pinned every later call to this one promise - so a single request that
    // never answered could never be retried, and every screen pull awaiting the
    // config would stall behind it (#507).
    activePromise = ClientConfigLoader.fetchConfig(nowTimestamp).finally(() => {
      activePromise = null;
    });

    return activePromise;
  },

  /**
   * Fetch the config, falling back to the last known good one.
   *
   * @param {number} nowTimestamp Time the request was started.
   * @returns {Promise<object>} The config.
   */
  async fetchConfig(nowTimestamp) {
    try {
      const response = await fetchWithTimeout(`/config/client`);

      // An error body is not config. fetch only rejects on transport failure,
      // so without this a 4xx/5xx payload that happens to parse as JSON is
      // stored as the config and served for the whole config interval - with
      // apiEndpoint undefined, which takes every later request with it.
      if (!response.ok) {
        throw new Error(`Config request answered ${response.status}.`);
      }

      const data = await response.json();

      latestFetchTimestamp = nowTimestamp;
      configData = data;

      // Make api endpoint available through localstorage.
      appStorage.setApiUrl(configData.apiEndpoint);

      return configData;
    } catch {
      if (configData !== null) {
        return configData;
      }

      // eslint-disable-next-line no-console
      console.error("Could not load config. Will use default config.");

      return buildDefaultConfig();
    }
  },
};

Object.freeze(ClientConfigLoader);

export default ClientConfigLoader;

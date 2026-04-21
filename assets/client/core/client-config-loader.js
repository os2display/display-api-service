import appStorage from "./app-storage.js";
import logger from "./logger.js";
import defaults from "../util/defaults.js";

// Defaults.
let configData = null;

// Last time the config was fetched.
let latestFetchTimestamp = 0;

let activePromise = null;

const ClientConfigLoader = {
  async loadConfig() {
    if (activePromise) {
      return activePromise;
    }

    const nowTimestamp = new Date().getTime();

    // Return cached data directly — no promise, so activePromise stays null
    // and a future call after the cache interval will trigger a real fetch.
    if (
      configData !== null &&
      latestFetchTimestamp +
        (configData?.configFetchInterval ?? defaults.configFetchIntervalDefault) >=
        nowTimestamp
    ) {
      return configData;
    }

    activePromise = new Promise((resolve) => {
      fetch(`/config/client`)
        .then((response) => response.json())
        .then((data) => {
          latestFetchTimestamp = new Date().getTime();
          configData = data;

          // Make api endpoint available through localstorage.
          appStorage.setApiUrl(configData.apiEndpoint);

          resolve(configData);
        })
        .catch(() => {
          if (configData !== null) {
            resolve(configData);
          } else {
            logger.error("Could not load config. Will use default config.");

            // Default config.
            resolve({
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
            });
          }
        })
        .finally(() => {
          activePromise = null;
        });
    });

    return activePromise;
  },
};

Object.freeze(ClientConfigLoader);

export default ClientConfigLoader;

// Only fetch new release.json if more than 5 minutes have passed.
const configFetchInterval = 5 * 60 * 1000;

// Defaults.
let releaseData = null;

// Last time the config was fetched.
let latestFetchTimestamp = 0;

let activePromise = null;

const ReleaseLoader = {
  async loadRelease() {
    if (activePromise) {
      return activePromise;
    }

    activePromise = new Promise((resolve, reject) => {
      const nowTimestamp = new Date().getTime();

      if (
        latestFetchTimestamp + configFetchInterval >= nowTimestamp
      ) {
        resolve(releaseData);
      } else {
        fetch(`/release.json?t=${nowTimestamp}`)
          .then((response) => response.json())
          .then((data) => {
            latestFetchTimestamp = nowTimestamp;
            releaseData = data;
            resolve(releaseData);
          })
          .catch((err) => {
            if (releaseData !== null) {
              resolve(releaseData);
            } else {
              /* eslint-disable-next-line no-console */
              console.warn("Could not find release.json. Returning defaults.");

              return {
                releaseTimestamp: null,
                releaseVersion: null,
              };
            }
          })
          .finally(() => {
            activePromise = null;
          });
      }
    });

    return activePromise;
  },
};

Object.freeze(ReleaseLoader);

export default ReleaseLoader;

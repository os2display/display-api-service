// Only fetch new release.json if more than 5 minutes have passed.
const configFetchInterval = 5 * 60 * 1000;

// Fetched release data.
let releaseData = null;

// Last time the release was fetched.
let latestFetchTimestamp = 0;

let activePromise = null;

const ReleaseLoader = {
  async loadRelease() {
    if (activePromise !== null) {
      return activePromise;
    }

    const nowTimestamp = new Date().getTime();

    // Return early without going through activePromise so the caller always
    // receives a real promise, not null.
    if (latestFetchTimestamp + configFetchInterval >= nowTimestamp) {
      return Promise.resolve(releaseData);
    }

    activePromise = fetch(`/release.json?t=${nowTimestamp}`)
      .then((response) => response.json())
      .then((data) => {
        latestFetchTimestamp = nowTimestamp;
        releaseData = data;
        return releaseData;
      })
      .catch(() => {
        if (releaseData !== null) {
          // Bug 3 fix: advance the timestamp so the next call uses the
          // cache instead of immediately retrying after a failed fetch.
          latestFetchTimestamp = nowTimestamp;
          return releaseData;
        }

        /* eslint-disable-next-line no-console */
        console.warn("Could not find release.json. Returning defaults.");

        // Return defaults.
        return {
          releaseTime: null,
          releaseTimestamp: null,
          releaseVersion: null,
        };
      })
      .finally(() => {
        // Always clear activePromise via finally so concurrent callers share a
        // single in-flight fetch and it is cleared on both success and failure.
        activePromise = null;
      });

    return activePromise;
  },
};

Object.freeze(ReleaseLoader);

export default ReleaseLoader;

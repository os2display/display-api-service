import logger from "../logger/logger";

/**
 * Release loader.
 */
export default class ReleaseLoader {
  static async loadRelease() {
    const nowTimestamp = new Date().getTime();
    return fetch(`/release.json?ts=${nowTimestamp}`)
      .then((response) => response.json())
      .catch((err) => {
        logger.warn("Could not find release.json. Returning defaults.", err);

        return {
          releaseTimestamp: null,
          releaseVersion: null,
        };
      });
  }
}

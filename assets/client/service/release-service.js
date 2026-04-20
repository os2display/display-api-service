import ClientConfigLoader from "../util/client-config-loader.js";
import defaults from "../util/defaults";
import idFromPath from "../util/id-from-path";
import appStorage from "../util/app-storage";
import logger from "../logger/logger";
import statusService from "./status-service";
import constants from "../util/constants";
import releaseLoader from "../../shared/release-loader.js";

class ReleaseService {
  releaseCheckInterval = null;

  checkForNewRelease = () => {
    logger.info("Checking for new release.");

    return new Promise((resolve, reject) => {
      const url = new URL(window.location.href);
      const currentTimestamp = url.searchParams.get("releaseTimestamp");

      releaseLoader.loadRelease()
        .then((release) => {
          if (release.releaseTimestamp === null) {
            statusService.setError(constants.ERROR_RELEASE_FILE_NOT_LOADED);
          } else if (
            statusService.error === constants.ERROR_RELEASE_FILE_NOT_LOADED
          ) {
            statusService.setError(null);
          }

          if (
            release.releaseTimestamp !== null &&
            (!currentTimestamp ||
              currentTimestamp !== release.releaseTimestamp.toString())
          ) {
            const redirectUrl = url;

            redirectUrl.searchParams.set(
              "releaseTimestamp",
              release.releaseTimestamp,
            );

            if (release.releaseVersion !== null) {
              redirectUrl.searchParams.set(
                "releaseVersion",
                release.releaseVersion,
              );
            }

            window.location.replace(redirectUrl);
            reject();
          } else {
            resolve();
          }
        })
        .catch((err) => {
          logger.error(`Failed to load release: ${err}`);
          resolve();
        });
    });
  };

  setScreenIdInUrl = (screenId) => {
    // Append screenId to current url for easier debugging. If errors are logged in the API's standard http log this
    // makes it easy to see what screen client has made the http call by putting the screen id in the referer http
    // header.
    const url = new URL(window.location.href);
    url.searchParams.set("screenId", idFromPath(screenId));
    window.history.replaceState(null, "", url);
  };

  setPreviousBootInUrl = () => {
    const url = new URL(window.location.href);
    url.searchParams.set("pb", appStorage.getPreviousBoot());
    window.history.replaceState(null, "", url);
  };

  startReleaseCheck = () => {
    this.stopReleaseCheck();
    this.releaseCheckStopped = false;

    ClientConfigLoader.loadConfig().then((config) => {
      if (this.releaseCheckStopped) return;

      this.releaseCheckInterval = setInterval(
        this.checkForNewRelease,
        config.releaseTimestampIntervalTimeout ??
          defaults.releaseTimestampIntervalTimeoutDefault,
      );
    });
  };

  stopReleaseCheck = () => {
    this.releaseCheckStopped = true;
    if (this.releaseCheckInterval) {
      clearInterval(this.releaseCheckInterval);
      this.releaseCheckInterval = null;
    }
  };
}

// Singleton.
const releaseService = new ReleaseService();

export default releaseService;

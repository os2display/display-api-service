import { useEffect, useRef, useState } from "react";
import Screen from "./components/screen.jsx";
import ContentService from "./service/content-service";
import ClientConfigLoader from "./core/client-config-loader.js";
import logger from "./logger";
import fallback from "./assets/fallback.png";
import appStorage from "./core/app-storage";
import defaults from "./util/defaults";
import tokenService from "./service/token-service";
import releaseService from "./service/release-service";
import tenantService from "./service/tenant-service";
import statusService from "./service/status-service";
import constants from "./util/constants";
import reauthenticateRef from "./redux/reauthenticate-ref";
import { useClientState } from "./client-state-context.jsx";
import "./app.scss";

/**
 * App component.
 *
 * @param {object} props The props.
 * @param {string | null} props.preview Type of preview to enable.
 * @param {string | null} props.previewId The id of the entity to preview.
 * @returns {object}
 *   The component.
 */
function App({ preview, previewId }) {
  const [running, setRunning] = useState(false);
  const [bindKey, setBindKey] = useState(null);
  const [debug, setDebug] = useState(false);

  const { screen, isContentEmpty, callbacks } = useClientState();

  const checkLoginTimeoutRef = useRef(null);
  const loginTimeoutGenRef = useRef(0);
  const contentServiceRef = useRef(null);
  const runningRef = useRef(false);
  const reauthenticatingRef = useRef(false);

  const fallbackImageUrl = appStorage.getFallbackImageUrl();
  const safeFallbackUrl = (
    fallbackImageUrl !== null ? fallbackImageUrl : fallback
  ).replace(/"/g, "");
  const fallbackStyle = {
    backgroundImage: `url("${safeFallbackUrl}")`,
  };

  const appStyle = {};

  if (!debug) {
    appStyle.cursor = "none";
  }

  const startContent = (localScreenId) => {
    logger.info("Starting content.");

    statusService.setStatus(constants.STATUS_RUNNING);

    if (contentServiceRef.current !== null) {
      logger.warn("ContentServiceRef is not null.");
      return;
    }

    setBindKey(null);
    runningRef.current = true;
    setRunning(true);

    contentServiceRef.current = new ContentService(callbacks);

    // Start the content service.
    contentServiceRef.current.start();

    const entrypoint = `/v2/screens/${localScreenId}`;
    contentServiceRef.current.stopSync();
    contentServiceRef.current.startSyncing(entrypoint);

    tokenService.startRefreshing();
  };

  /* eslint-disable no-use-before-define */
  const restartLoginTimeout = () => {
    if (checkLoginTimeoutRef.current !== null) {
      clearTimeout(checkLoginTimeoutRef.current);
      checkLoginTimeoutRef.current = null;
    }

    loginTimeoutGenRef.current += 1;
    const gen = loginTimeoutGenRef.current;

    ClientConfigLoader.loadConfig().then((config) => {
      if (gen !== loginTimeoutGenRef.current) return;
      checkLoginTimeoutRef.current = setTimeout(
        checkLogin,
        config.loginCheckTimeout ?? defaults.loginCheckTimeoutDefault,
      );
    });
  };
  /* eslint-enable no-use-before-define */

  const checkLogin = () => {
    logger.info("Check login.");

    const localStorageToken = appStorage.getToken();
    const localScreenId = appStorage.getScreenId();

    if (!runningRef.current && localStorageToken && localScreenId) {
      startContent(localScreenId);
    } else {
      statusService.setStatus(constants.STATUS_LOGIN);

      tokenService
        .checkLogin()
        .then((data) => {
          if (data.status === constants.LOGIN_STATUS_READY) {
            startContent(data.screenId);
          } else if (data.status === constants.LOGIN_STATUS_AWAITING_BIND_KEY) {
            if (data?.bindKey) {
              setBindKey(data.bindKey);
            }

            restartLoginTimeout();
          }
        })
        .catch(() => {
          restartLoginTimeout();
        });
    }
  };

  const reauthenticateHandler = () => {
    if (reauthenticatingRef.current) return;
    reauthenticatingRef.current = true;

    logger.info("Reauthenticate handler invoked. Trying to use refresh token.");

    tokenService
      .refreshToken()
      .then(() => {
        logger.info("Reauthenticate refresh token success");
      })
      .catch(() => {
        logger.warn("Reauthenticate refresh token failed. Logging out.");

        statusService.setError(constants.ERROR_TOKEN_REFRESH_FAILED);

        if (contentServiceRef.current !== null) {
          contentServiceRef.current.stopSync();
          contentServiceRef.current.stop();
          contentServiceRef.current = null;
        }

        appStorage.clearToken();
        appStorage.clearRefreshToken();
        appStorage.clearScreenId();
        appStorage.clearTenant();
        appStorage.clearFallbackImageUrl();

        callbacks.current.setScreen(null);
        runningRef.current = false;
        setRunning(false);

        tokenService.stopRefreshing();

        checkLogin();
      })
      .finally(() => {
        reauthenticatingRef.current = false;
      });
  };

  // ctrl/cmd i will log screen out and refresh
  const handleKeyboard = ({ repeat, metaKey, ctrlKey, code }) => {
    if (!repeat && (metaKey || ctrlKey) && code === "KeyI") {
      appStorage.clearAppStorage();
      window.location.reload();
    }
  };

  useEffect(() => {
    logger.info("Mounting App.");
    if (preview !== null) {
      if (preview === "screen") {
        startContent(previewId);
      } else {
        setRunning(true);
        contentServiceRef.current = new ContentService(callbacks);
        contentServiceRef.current.start();
        contentServiceRef.current.startPreview(preview, previewId);
      }
    } else {
      document.addEventListener("keydown", handleKeyboard);

      // Wire up reauthenticate callback for base-query (outside React tree).
      reauthenticateRef.current = reauthenticateHandler;

      tokenService.checkToken();

      ClientConfigLoader.loadConfig()
        .then((config) => {
          setDebug(config.debug ?? false);

          const relationChecksumEnabled = config.relationsChecksumEnabled;
          logger.info(`Relation checksum enabled: ${relationChecksumEnabled}`);
        })
        .catch((err) => {
          logger.error(`Failed to load config: ${err}`);
        });

      releaseService.checkForNewRelease().finally(() => {
        releaseService.setPreviousBootInUrl();
        releaseService.startReleaseCheck();

        checkLogin();

        appStorage.setPreviousBoot(new Date().getTime());
      });

      statusService.setStatusInUrl();
    }

    return function cleanup() {
      logger.info("Unmounting App.");

      if (contentServiceRef.current !== null) {
        contentServiceRef.current.stopSync();
        contentServiceRef.current.stop();
        contentServiceRef.current = null;
      }

      if (preview === null) {
        document.removeEventListener("keydown", handleKeyboard);
        reauthenticateRef.current = () => {};

        if (checkLoginTimeoutRef.current) {
          clearTimeout(checkLoginTimeoutRef.current);
        }

        tokenService.stopRefreshing();
        releaseService.stopReleaseCheck();
      }
    };
  }, []);

  useEffect(() => {
    if (screen && screen["@id"]) {
      releaseService.setScreenIdInUrl(screen["@id"]);
      tenantService.loadTenantConfig();
    }
  }, [screen]);

  return (
    <div className="app" style={appStyle}>
      {!screen && bindKey && (
        <>
          {statusService.error && (
            <h2 className="frontpage-error">{statusService.error}</h2>
          )}
          <div className="bind-key-container">
            <h1 className="bind-key">{bindKey}</h1>
          </div>
        </>
      )}
      {screen && (
        <>
          <Screen screen={screen} />
        </>
      )}
      {isContentEmpty && !bindKey && (
        <div className="fallback" style={fallbackStyle} />
      )}
    </div>
  );
}

export default App;

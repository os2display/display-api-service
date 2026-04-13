import logger from "../logger/logger";
import appStorage from "../util/app-storage";
import ClientConfigLoader from "../util/client-config-loader.js";
import defaults from "../util/defaults";
import statusService from "./status-service";
import constants from "../util/constants";
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

class TokenService {
  refreshingToken = false;

  refreshInterval = null;

  refreshPromise = null;

  ensureFreshToken = () => {
    logger.info("Refresh token check");

    // Ignore if already refreshing token.
    if (this.refreshingToken) {
      logger.info("Already refreshing token.");
      return;
    }

    const refreshToken = appStorage.getRefreshToken();
    const expire = appStorage.getTokenExpire();
    const issueAt = appStorage.getTokenIssueAt();

    if (!refreshToken || !expire || !issueAt) {
      logger.warn("Refresh token, exp or iat not set.");
      statusService.setError(constants.ERROR_TOKEN_EXP_IAT_NOT_SET);
      return;
    }

    const timeDiff = expire - issueAt;

    const nowSeconds = Math.floor(new Date().getTime() / 1000);

    // If more than half the time till expire has been passed refresh the token.
    if (nowSeconds > issueAt + timeDiff / 2) {
      logger.info("Refreshing token.");
      this.refreshToken().catch(() => {
        statusService.setError(constants.ERROR_TOKEN_REFRESH_LOOP_FAILED);
      });
    } else {
      logger.info(
        `Half the time until expire has not been reached. Will not refresh. Token will expire at ${new Date(
          expire * 1000,
        ).toISOString()}`,
      );
    }
  };

  getExpireState = () => {
    const expire = appStorage.getTokenExpire();
    const issueAt = appStorage.getTokenIssueAt();
    const token = appStorage.getToken();

    if (expire === null) {
      return constants.NO_EXPIRE;
    }
    if (issueAt === null) {
      return constants.NO_ISSUED_AT;
    }
    if (token === null) {
      return constants.NO_TOKEN;
    }

    const timeDiff = expire - issueAt;
    const nowSeconds = Math.floor(new Date().getTime() / 1000);

    if (nowSeconds > expire) {
      return constants.TOKEN_EXPIRED;
    }
    if (nowSeconds > issueAt + timeDiff / 2) {
      return constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED;
    }
    return constants.TOKEN_VALID;
  };

  refreshToken = () => {
    logger.info("Refresh token invoked.");

    if (this.refreshPromise === null) {
      this.refreshingToken = true;
      const refreshToken = appStorage.getRefreshToken();

      this.refreshPromise = clientStore
        .dispatch(
          clientApi.endpoints.postRefreshTokenItem.initiate({
            refreshTokenRequest: { refresh_token: refreshToken },
          }),
        )
        .unwrap()
        .then((data) => {
          logger.info("Token refreshed.");

          appStorage.setToken(data.token);
          appStorage.setRefreshToken(data.refresh_token);

          // Remove token expired error codes.
          if (
            [
              constants.ERROR_TOKEN_EXPIRED,
              constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
            ].includes(statusService.error)
          ) {
            statusService.setError(null);
          }
        })
        .catch((err) => {
          logger.error("Token refresh error.");
          throw err;
        })
        .finally(() => {
          this.refreshingToken = false;
          this.refreshPromise = null;
        });
    }

    return this.refreshPromise;
  };

  checkToken = () => {
    const expiredState = this.getExpireState();

    if (
      [
        constants.NO_EXPIRE,
        constants.NO_ISSUED_AT,
        constants.NO_TOKEN,
      ].includes(expiredState)
    ) {
      // Ignore. No token saved in storage.
    } else if (expiredState === constants.TOKEN_EXPIRED) {
      statusService.setError(constants.ERROR_TOKEN_EXPIRED);
    } else if (
      expiredState === constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED
    ) {
      statusService.setError(
        constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
      );
    } else {
      const err = statusService.error;

      if (
        err !== null &&
        [
          constants.TOKEN_EXPIRED,
          constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
        ].includes(err)
      ) {
        statusService.setError(null);
      }
    }
  };

  checkLogin = () => {
    return clientStore
      .dispatch(
        clientApi.endpoints.postLoginInfoScreen.initiate({
          screenLoginInput: {},
        }),
      )
      .unwrap()
      .then((data) => {
        if (
          data?.status === constants.LOGIN_STATUS_READY &&
          data?.token &&
          data?.screenId &&
          data?.tenantKey &&
          data?.refresh_token
        ) {
          appStorage.setToken(data.token);
          appStorage.setRefreshToken(data.refresh_token);
          appStorage.setScreenId(data.screenId);
          appStorage.setTenant(data.tenantKey, data.tenantId);

          // Remove token expired error codes.
          if (
            [
              constants.ERROR_TOKEN_REFRESH_FAILED,
              constants.ERROR_TOKEN_REFRESH_LOOP_FAILED,
              constants.ERROR_TOKEN_EXP_IAT_NOT_SET,
              constants.ERROR_TOKEN_EXPIRED,
              constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED,
            ].includes(statusService.error)
          ) {
            statusService.setError(null);
          }

          return {
            status: constants.LOGIN_STATUS_READY,
            screenId: data.screenId,
          };
        } else if (data?.status === constants.LOGIN_STATUS_AWAITING_BIND_KEY) {
          return {
            status: constants.LOGIN_STATUS_AWAITING_BIND_KEY,
            bindKey: data.bindKey,
          };
        }
        return {
          status: constants.LOGIN_STATUS_UNKNOWN,
        };
      });
  };

  startRefreshing = () => {
    this.stopRefreshing();

    ClientConfigLoader.loadConfig().then((config) => {
      // Start refresh token interval.
      this.refreshInterval = setInterval(
        this.ensureFreshToken,
        config.refreshTokenTimeout ?? defaults.refreshTokenTimeoutDefault,
      );
    });
  };

  stopRefreshing = () => {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  };
}

// Singleton.
const tokenService = new TokenService();

export default tokenService;

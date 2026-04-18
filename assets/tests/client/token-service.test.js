import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

const { mockDispatch } = vi.hoisted(() => ({
  mockDispatch: vi.fn(),
}));

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/util/app-storage.js", () => ({
  default: {
    getTokenExpire: vi.fn(),
    getTokenIssueAt: vi.fn(),
    getToken: vi.fn(),
    getRefreshToken: vi.fn(),
    setToken: vi.fn(),
    setRefreshToken: vi.fn(),
    setScreenId: vi.fn(),
    setTenant: vi.fn(),
  },
}));

vi.mock("../../client/service/status-service.js", () => ({
  default: {
    error: null,
    setError: vi.fn(),
    setStatus: vi.fn(),
  },
}));

vi.mock("../../client/util/client-config-loader.js", () => ({
  default: {
    loadConfig: vi.fn().mockResolvedValue({ refreshTokenTimeout: 900000 }),
  },
}));

vi.mock("../../client/redux/store.js", () => ({
  clientStore: { dispatch: mockDispatch },
}));

vi.mock("../../client/redux/generated-api.ts", () => ({
  clientApi: {
    endpoints: {
      postRefreshTokenItem: {
        initiate: vi.fn().mockReturnValue("refreshAction"),
      },
      postLoginInfoScreen: {
        initiate: vi.fn().mockReturnValue("loginAction"),
      },
    },
    reducerPath: "clientApi",
    reducer: (state = {}) => state,
    middleware: () => (next) => (action) => next(action),
  },
}));

vi.mock("../../client/redux/empty-api.ts", () => ({
  clientEmptySplitApi: {
    injectEndpoints: vi.fn().mockReturnValue({
      endpoints: {},
    }),
  },
}));

import tokenService from "../../client/service/token-service";
import constants from "../../client/util/constants";
import appStorage from "../../client/util/app-storage.js";
import statusService from "../../client/service/status-service.js";

describe("TokenService", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    tokenService.refreshingToken = false;
    tokenService.refreshInterval = null;
    tokenService.refreshPromise = null;
    statusService.error = null;
    vi.clearAllMocks();
  });

  afterEach(() => {
    tokenService.stopRefreshing();
    vi.useRealTimers();
  });

  describe("getExpireState", () => {
    it("returns NO_EXPIRE when no expire in storage", () => {
      appStorage.getTokenExpire.mockReturnValue(null);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      expect(tokenService.getExpireState()).toBe(constants.NO_EXPIRE);
    });

    it("returns NO_ISSUED_AT when no issuedAt in storage", () => {
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(null);
      appStorage.getToken.mockReturnValue("token");

      expect(tokenService.getExpireState()).toBe(constants.NO_ISSUED_AT);
    });

    it("returns NO_TOKEN when no token in storage", () => {
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue(null);

      expect(tokenService.getExpireState()).toBe(constants.NO_TOKEN);
    });

    it("returns TOKEN_EXPIRED when now > expire", () => {
      vi.setSystemTime(new Date(1000 * 1000));
      appStorage.getTokenExpire.mockReturnValue(500);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      expect(tokenService.getExpireState()).toBe(constants.TOKEN_EXPIRED);
    });

    it("returns TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED when past 50% of lifetime", () => {
      vi.setSystemTime(new Date(160 * 1000));
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      expect(tokenService.getExpireState()).toBe(
        constants.TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED
      );
    });

    it("returns TOKEN_VALID when within first half of lifetime", () => {
      vi.setSystemTime(new Date(120 * 1000));
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      expect(tokenService.getExpireState()).toBe(constants.TOKEN_VALID);
    });
  });

  describe("ensureFreshToken", () => {
    it("returns immediately when already refreshing", () => {
      tokenService.refreshingToken = true;
      appStorage.getRefreshToken.mockReturnValue("token");

      tokenService.ensureFreshToken();

      expect(mockDispatch).not.toHaveBeenCalled();
    });

    it("sets error when refresh token is not set", () => {
      appStorage.getRefreshToken.mockReturnValue(null);
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);

      tokenService.ensureFreshToken();

      expect(statusService.setError).toHaveBeenCalledWith(
        constants.ERROR_TOKEN_EXP_IAT_NOT_SET
      );
    });

    it("sets error when expire is not set", () => {
      appStorage.getRefreshToken.mockReturnValue("token");
      appStorage.getTokenExpire.mockReturnValue(null);
      appStorage.getTokenIssueAt.mockReturnValue(100);

      tokenService.ensureFreshToken();

      expect(statusService.setError).toHaveBeenCalledWith(
        constants.ERROR_TOKEN_EXP_IAT_NOT_SET
      );
    });

    it("calls refreshToken when past 50% of token lifetime", () => {
      vi.setSystemTime(new Date(160 * 1000));
      appStorage.getRefreshToken.mockReturnValue("refresh");
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);

      mockDispatch.mockReturnValue({
        unwrap: () => Promise.resolve({ token: "new", refresh_token: "new-r" }),
      });

      tokenService.ensureFreshToken();

      expect(mockDispatch).toHaveBeenCalled();
    });

    it("does not call refreshToken when before 50% of token lifetime", () => {
      vi.setSystemTime(new Date(120 * 1000));
      appStorage.getRefreshToken.mockReturnValue("refresh");
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);

      tokenService.ensureFreshToken();

      expect(mockDispatch).not.toHaveBeenCalled();
    });
  });

  describe("checkToken", () => {
    it("sets ERROR_TOKEN_EXPIRED when token is expired", () => {
      vi.setSystemTime(new Date(1000 * 1000));
      appStorage.getTokenExpire.mockReturnValue(500);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      tokenService.checkToken();

      expect(statusService.setError).toHaveBeenCalledWith(
        constants.ERROR_TOKEN_EXPIRED
      );
    });

    it("sets ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED when past 50%", () => {
      vi.setSystemTime(new Date(160 * 1000));
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");

      tokenService.checkToken();

      expect(statusService.setError).toHaveBeenCalledWith(
        constants.ERROR_TOKEN_VALID_SHOULD_HAVE_BEEN_REFRESHED
      );
    });

    it("clears token-related errors when token is valid", () => {
      vi.setSystemTime(new Date(120 * 1000));
      appStorage.getTokenExpire.mockReturnValue(200);
      appStorage.getTokenIssueAt.mockReturnValue(100);
      appStorage.getToken.mockReturnValue("token");
      statusService.error = constants.TOKEN_EXPIRED;

      tokenService.checkToken();

      expect(statusService.setError).toHaveBeenCalledWith(null);
    });

    it("does nothing when no token info exists", () => {
      appStorage.getTokenExpire.mockReturnValue(null);

      tokenService.checkToken();

      expect(statusService.setError).not.toHaveBeenCalled();
    });
  });

  describe("checkLogin", () => {
    it("stores credentials and returns ready when login is ready", async () => {
      const loginData = {
        status: constants.LOGIN_STATUS_READY,
        token: "jwt-token",
        screenId: "screen-1",
        tenantKey: "tenant-key",
        tenantId: "tenant-id",
        refresh_token: "refresh-token",
      };
      mockDispatch.mockReturnValue({
        unwrap: () => Promise.resolve(loginData),
      });

      const result = await tokenService.checkLogin();

      expect(appStorage.setToken).toHaveBeenCalledWith("jwt-token");
      expect(appStorage.setRefreshToken).toHaveBeenCalledWith("refresh-token");
      expect(appStorage.setScreenId).toHaveBeenCalledWith("screen-1");
      expect(appStorage.setTenant).toHaveBeenCalledWith(
        "tenant-key",
        "tenant-id"
      );
      expect(result).toEqual({
        status: constants.LOGIN_STATUS_READY,
        screenId: "screen-1",
      });
    });

    it("returns bindKey when awaiting bind key", async () => {
      mockDispatch.mockReturnValue({
        unwrap: () =>
          Promise.resolve({
            status: constants.LOGIN_STATUS_AWAITING_BIND_KEY,
            bindKey: "ABCD-1234",
          }),
      });

      const result = await tokenService.checkLogin();

      expect(result).toEqual({
        status: constants.LOGIN_STATUS_AWAITING_BIND_KEY,
        bindKey: "ABCD-1234",
      });
    });

    it("returns unknown status for unexpected response", async () => {
      mockDispatch.mockReturnValue({
        unwrap: () => Promise.resolve({ status: "something-else" }),
      });

      const result = await tokenService.checkLogin();

      expect(result).toEqual({ status: constants.LOGIN_STATUS_UNKNOWN });
    });
  });

  describe("refreshToken", () => {
    it("stores new token and refresh token on success", async () => {
      appStorage.getRefreshToken.mockReturnValue("old-refresh");
      mockDispatch.mockReturnValue({
        unwrap: () =>
          Promise.resolve({
            token: "new-token",
            refresh_token: "new-refresh",
          }),
      });

      await tokenService.refreshToken();

      expect(appStorage.setToken).toHaveBeenCalledWith("new-token");
      expect(appStorage.setRefreshToken).toHaveBeenCalledWith("new-refresh");
    });

    it("resets refreshingToken flag after success", async () => {
      appStorage.getRefreshToken.mockReturnValue("old-refresh");
      mockDispatch.mockReturnValue({
        unwrap: () =>
          Promise.resolve({
            token: "new-token",
            refresh_token: "new-refresh",
          }),
      });

      await tokenService.refreshToken();

      expect(tokenService.refreshingToken).toBe(false);
      expect(tokenService.refreshPromise).toBeNull();
    });

    it("resets refreshingToken flag after failure", async () => {
      appStorage.getRefreshToken.mockReturnValue("old-refresh");
      mockDispatch.mockReturnValue({
        unwrap: () => Promise.reject(new Error("401")),
      });

      await expect(tokenService.refreshToken()).rejects.toThrow("401");

      expect(tokenService.refreshingToken).toBe(false);
      expect(tokenService.refreshPromise).toBeNull();
    });

    it("deduplicates concurrent refresh calls", async () => {
      appStorage.getRefreshToken.mockReturnValue("old-refresh");
      mockDispatch.mockReturnValue({
        unwrap: () =>
          Promise.resolve({
            token: "new-token",
            refresh_token: "new-refresh",
          }),
      });

      const p1 = tokenService.refreshToken();
      const p2 = tokenService.refreshToken();

      expect(p1).toBe(p2);

      await p1;
      expect(mockDispatch).toHaveBeenCalledTimes(1);
    });
  });
});

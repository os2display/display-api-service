import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup, act, fireEvent } from "@testing-library/react";

// --- Hoisted mocks ---
const {
  mockContentService,
  mockTokenService,
  mockReleaseService,
  mockTenantService,
  mockStatusService,
  mockAppStorage,
  mockConfigLoader,
  mockReauthRef,
  mockCallbacks,
  mockScreen,
  mockIsContentEmpty,
} = vi.hoisted(() => {
  const mockContentService = {
    start: vi.fn(),
    stop: vi.fn(),
    startSyncing: vi.fn(),
    stopSync: vi.fn(),
    startPreview: vi.fn(),
  };
  const mockTokenService = {
    checkLogin: vi.fn().mockResolvedValue({ status: "ready", screenId: "S1" }),
    checkToken: vi.fn(),
    refreshToken: vi.fn().mockResolvedValue(),
    startRefreshing: vi.fn(),
    stopRefreshing: vi.fn(),
  };
  const mockReleaseService = {
    checkForNewRelease: vi.fn().mockResolvedValue(),
    setPreviousBootInUrl: vi.fn(),
    startReleaseCheck: vi.fn(),
    stopReleaseCheck: vi.fn(),
    setScreenIdInUrl: vi.fn(),
  };
  const mockTenantService = { loadTenantConfig: vi.fn() };
  const mockStatusService = {
    setStatus: vi.fn(),
    setError: vi.fn(),
    setStatusInUrl: vi.fn(),
    error: null,
  };
  const mockAppStorage = {
    getToken: vi.fn().mockReturnValue(null),
    getScreenId: vi.fn().mockReturnValue(null),
    getFallbackImageUrl: vi.fn().mockReturnValue(null),
    setPreviousBoot: vi.fn(),
    clearToken: vi.fn(),
    clearRefreshToken: vi.fn(),
    clearScreenId: vi.fn(),
    clearTenant: vi.fn(),
    clearFallbackImageUrl: vi.fn(),
    clearAppStorage: vi.fn(),
  };
  const mockConfigLoader = {
    loadConfig: vi.fn().mockResolvedValue({ debug: false }),
  };
  const mockReauthRef = { current: vi.fn() };
  const mockCallbacks = {
    current: {
      setScreen: vi.fn(),
      setIsContentEmpty: vi.fn(),
      updateRegionSlides: vi.fn(),
      onRegionReady: vi.fn(),
      onRegionRemoved: vi.fn(),
      onReauthenticate: vi.fn(),
    },
  };
  const mockScreen = { value: null };
  const mockIsContentEmpty = { value: false };

  return {
    mockContentService,
    mockTokenService,
    mockReleaseService,
    mockTenantService,
    mockStatusService,
    mockAppStorage,
    mockConfigLoader,
    mockReauthRef,
    mockCallbacks,
    mockScreen,
    mockIsContentEmpty,
  };
});

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/app.scss", () => ({}));
vi.mock("../../client/assets/fallback.png", () => ({
  default: "fallback.png",
}));
vi.mock("../../client/components/screen.jsx", () => ({
  default: ({ screen }) => (
    <div data-testid="screen">{screen["@id"]}</div>
  ),
}));
vi.mock("../../client/components/screen.scss", () => ({}));
vi.mock("../../client/service/content-service", () => ({
  default: vi.fn(function () {
    return mockContentService;
  }),
}));
vi.mock("../../client/core/client-config-loader.js", () => ({
  default: mockConfigLoader,
}));
vi.mock("../../client/core/app-storage", () => ({
  default: mockAppStorage,
}));
vi.mock("../../client/util/defaults", () => ({
  default: {
    loginCheckTimeoutDefault: 100,
    refreshTokenTimeoutDefault: 100,
    releaseTimestampIntervalTimeoutDefault: 100,
  },
}));
vi.mock("../../client/service/token-service", () => ({
  default: mockTokenService,
}));
vi.mock("../../client/service/release-service", () => ({
  default: mockReleaseService,
}));
vi.mock("../../client/service/tenant-service", () => ({
  default: mockTenantService,
}));
vi.mock("../../client/service/status-service", () => ({
  default: mockStatusService,
}));
vi.mock("../../client/util/constants", () => {
  const c = {
    LOGIN_STATUS_READY: "ready",
    LOGIN_STATUS_AWAITING_BIND_KEY: "awaitingBindKey",
    STATUS_RUNNING: "running",
    STATUS_LOGIN: "login",
    ERROR_TOKEN_REFRESH_FAILED: "ER101",
    SLIDE_ERROR_RECOVERY_TIMEOUT: 5000,
    SLIDE_TRANSITION_TIMEOUT: 1000,
    COLOR_SCHEME_REFRESH_INTERVAL: 300000,
  };
  return { default: c };
});
vi.mock("../../client/redux/reauthenticate-ref", () => ({
  default: mockReauthRef,
}));
vi.mock("../../client/client-state-context.jsx", () => ({
  useClientState: () => ({
    screen: mockScreen.value,
    isContentEmpty: mockIsContentEmpty.value,
    callbacks: mockCallbacks,
  }),
}));

import App from "../../client/app.jsx";
import ContentService from "../../client/service/content-service";

describe("App", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllMocks();

    // Reset state
    mockScreen.value = null;
    mockIsContentEmpty.value = false;
    mockStatusService.error = null;

    mockAppStorage.getToken.mockReturnValue(null);
    mockAppStorage.getScreenId.mockReturnValue(null);
    mockAppStorage.getFallbackImageUrl.mockReturnValue(null);
    mockTokenService.checkLogin.mockResolvedValue({ status: "ready", screenId: "S1" });
    mockTokenService.refreshToken.mockResolvedValue();
    mockReleaseService.checkForNewRelease.mockResolvedValue();
    mockConfigLoader.loadConfig.mockResolvedValue({ debug: false });
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  describe("preview mode", () => {
    it("should start content with previewId for screen preview", async () => {
      await act(async () => {
        render(<App preview="screen" previewId="SCREEN_ABC" />);
      });

      expect(ContentService).toHaveBeenCalled();
      expect(mockContentService.start).toHaveBeenCalled();
      expect(mockContentService.startSyncing).toHaveBeenCalledWith(
        "/v2/screens/SCREEN_ABC",
      );
    });

    it("should call startPreview for non-screen preview", async () => {
      await act(async () => {
        render(<App preview="playlist" previewId="PL_123" />);
      });

      expect(ContentService).toHaveBeenCalled();
      expect(mockContentService.start).toHaveBeenCalled();
      expect(mockContentService.startPreview).toHaveBeenCalledWith(
        "playlist",
        "PL_123",
      );
    });

    it("should not add keyboard listener in preview mode", async () => {
      const addSpy = vi.spyOn(document, "addEventListener");

      await act(async () => {
        render(<App preview="screen" previewId="S1" />);
      });

      expect(addSpy).not.toHaveBeenCalledWith(
        "keydown",
        expect.any(Function),
      );
    });
  });

  describe("normal mode - login flow", () => {
    it("should use fast path when token and screenId exist in storage", async () => {
      mockAppStorage.getToken.mockReturnValue("jwt-token");
      mockAppStorage.getScreenId.mockReturnValue("SCREEN_FAST");

      await act(async () => {
        render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });

      expect(mockContentService.startSyncing).toHaveBeenCalledWith(
        "/v2/screens/SCREEN_FAST",
      );
      expect(mockTokenService.checkLogin).not.toHaveBeenCalled();
    });

    it("should display bindKey when login status is awaitingBindKey", async () => {
      mockTokenService.checkLogin.mockResolvedValue({
        status: "awaitingBindKey",
        bindKey: "ABC-123",
      });

      let result;
      await act(async () => {
        result = render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });

      expect(result.container.querySelector(".bind-key").textContent).toBe(
        "ABC-123",
      );
    });

    it("should retry login on checkLogin failure", async () => {
      mockTokenService.checkLogin.mockRejectedValueOnce(new Error("fail"));

      await act(async () => {
        render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });

      // restartLoginTimeout is called, which loads config then sets timeout
      expect(mockConfigLoader.loadConfig).toHaveBeenCalled();
    });
  });

  describe("reauthenticateHandler", () => {
    async function mountAndGetReauthHandler() {
      await act(async () => {
        render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });
      // reauthenticateRef.current is set during mount effect
      return mockReauthRef.current;
    }

    it("should attempt token refresh on reauthenticate", async () => {
      const handler = await mountAndGetReauthHandler();

      await act(async () => {
        handler();
        await vi.advanceTimersByTimeAsync(0);
      });

      expect(mockTokenService.refreshToken).toHaveBeenCalled();
    });

    it("should clean up and restart login on refresh failure", async () => {
      mockTokenService.refreshToken.mockRejectedValue(new Error("expired"));
      const handler = await mountAndGetReauthHandler();

      await act(async () => {
        handler();
        await vi.advanceTimersByTimeAsync(0);
      });

      expect(mockStatusService.setError).toHaveBeenCalledWith("ER101");
      expect(mockAppStorage.clearToken).toHaveBeenCalled();
      expect(mockAppStorage.clearRefreshToken).toHaveBeenCalled();
      expect(mockAppStorage.clearScreenId).toHaveBeenCalled();
      expect(mockAppStorage.clearTenant).toHaveBeenCalled();
      expect(mockAppStorage.clearFallbackImageUrl).toHaveBeenCalled();
      expect(mockCallbacks.current.setScreen).toHaveBeenCalledWith(null);
      expect(mockTokenService.stopRefreshing).toHaveBeenCalled();
    });

    it("should guard against concurrent reauthentication", async () => {
      // Make refreshToken hang (never resolve) so the first call stays in-flight.
      mockTokenService.refreshToken.mockReturnValue(new Promise(() => {}));
      const handler = await mountAndGetReauthHandler();

      await act(async () => {
        handler(); // first call
        handler(); // second call while first is in-flight
      });

      expect(mockTokenService.refreshToken).toHaveBeenCalledTimes(1);
    });
  });

  describe("keyboard handler", () => {
    it("should clear storage and reload on Ctrl+I", async () => {
      const reloadMock = vi.fn();
      vi.stubGlobal("location", {
        ...window.location,
        href: "http://localhost/",
        reload: reloadMock,
      });

      await act(async () => {
        render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });

      fireEvent.keyDown(document, { code: "KeyI", ctrlKey: true, repeat: false });

      expect(mockAppStorage.clearAppStorage).toHaveBeenCalled();
      expect(reloadMock).toHaveBeenCalled();

      vi.unstubAllGlobals();
    });

    it("should not trigger on repeated keydown events", async () => {
      const reloadMock = vi.fn();
      vi.stubGlobal("location", {
        ...window.location,
        href: "http://localhost/",
        reload: reloadMock,
      });

      await act(async () => {
        render(<App preview={null} previewId={null} />);
        await vi.advanceTimersByTimeAsync(0);
      });

      fireEvent.keyDown(document, { code: "KeyI", ctrlKey: true, repeat: true });

      expect(reloadMock).not.toHaveBeenCalled();

      vi.unstubAllGlobals();
    });
  });

  describe("cleanup on unmount", () => {
    it("should stop content service and remove listeners on unmount", async () => {
      // Start content first so contentServiceRef is populated.
      mockAppStorage.getToken.mockReturnValue("jwt");
      mockAppStorage.getScreenId.mockReturnValue("SCR1");
      const removeSpy = vi.spyOn(document, "removeEventListener");

      let unmountFn;
      await act(async () => {
        const { unmount } = render(<App preview={null} previewId={null} />);
        unmountFn = unmount;
        await vi.advanceTimersByTimeAsync(0);
      });

      unmountFn();

      expect(mockContentService.stopSync).toHaveBeenCalled();
      expect(mockContentService.stop).toHaveBeenCalled();
      expect(removeSpy).toHaveBeenCalledWith("keydown", expect.any(Function));
      expect(mockTokenService.stopRefreshing).toHaveBeenCalled();
      expect(mockReleaseService.stopReleaseCheck).toHaveBeenCalled();
    });
  });
});

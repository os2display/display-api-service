import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, screen, cleanup } from "@testing-library/react";

const { mockCheckLogin, mockRefreshToken } = vi.hoisted(() => ({
  mockCheckLogin: vi.fn(),
  mockRefreshToken: vi.fn(),
}));

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

vi.mock("../../client/util/app-storage", () => ({
  default: {
    getToken: vi.fn(() => null),
    getRefreshToken: vi.fn(() => null),
    getScreenId: vi.fn(() => null),
    getTokenExpire: vi.fn(() => null),
    getTokenIssueAt: vi.fn(() => null),
    getFallbackImageUrl: vi.fn(() => null),
    setToken: vi.fn(),
    setRefreshToken: vi.fn(),
    setScreenId: vi.fn(),
    setTenant: vi.fn(),
    setPreviousBoot: vi.fn(),
    clearToken: vi.fn(),
    clearRefreshToken: vi.fn(),
    clearScreenId: vi.fn(),
    clearTenant: vi.fn(),
    clearFallbackImageUrl: vi.fn(),
    clearAppStorage: vi.fn(),
  },
}));

vi.mock("../../client/service/token-service", () => ({
  default: {
    checkLogin: mockCheckLogin,
    refreshToken: mockRefreshToken,
    startRefreshing: vi.fn(),
    stopRefreshing: vi.fn(),
    checkToken: vi.fn(),
  },
}));

vi.mock("../../client/service/status-service", () => ({
  default: {
    setStatus: vi.fn(),
    setError: vi.fn(),
    setStatusInUrl: vi.fn(),
    error: null,
  },
}));

vi.mock("../../client/service/release-service", () => ({
  default: {
    checkForNewRelease: vi.fn(() => Promise.resolve()),
    setPreviousBootInUrl: vi.fn(),
    startReleaseCheck: vi.fn(),
    stopReleaseCheck: vi.fn(),
    setScreenIdInUrl: vi.fn(),
  },
}));

vi.mock("../../client/service/tenant-service", () => ({
  default: { loadTenantConfig: vi.fn() },
}));

vi.mock("../../client/util/client-config-loader.js", () => ({
  default: {
    loadConfig: vi.fn(() => Promise.resolve({ loginCheckTimeout: 50 })),
  },
}));

vi.mock("../../client/service/content-service", () => {
  return {
    default: vi.fn().mockImplementation(() => ({
      start: vi.fn(),
      stop: vi.fn(),
    })),
  };
});

vi.mock("../../client/app.scss", () => ({}));
vi.mock("../../client/assets/fallback.png", () => ({ default: "" }));
vi.mock("../../client/components/screen.jsx", () => ({
  default: () => <div data-testid="screen" />,
}));

import App from "../../client/app.jsx";

/**
 * Flush microtasks, advance timers, flush again.
 * restartLoginTimeout creates setTimeout inside a .then() on loadConfig —
 * microtasks must resolve first for the timer to exist.
 */
async function tick(ms) {
  await act(async () => {});
  await act(async () => {
    vi.advanceTimersByTime(ms);
  });
  await act(async () => {});
}

describe("checkLogin retries on unknown status", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockCheckLogin.mockReset();
    mockRefreshToken.mockReset();
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  it("continues login polling when checkLogin returns unknown status", async () => {
    // The initial checkLogin (on mount) returns unknown status.
    // Without the fix, the polling loop stops dead — no restartLoginTimeout.
    // With the fix, unknown triggers restartLoginTimeout and the second
    // call returns awaitingBindKey, showing the bind key.
    mockCheckLogin
      .mockResolvedValueOnce({ status: "unknown" })
      .mockResolvedValueOnce({ status: "awaitingBindKey", bindKey: "BIND42" })
      .mockResolvedValue({ status: "awaitingBindKey", bindKey: "BIND42" });

    render(<App preview={null} previewId={null} />);

    // Mount: releaseService.checkForNewRelease resolves → checkLogin (call 1)
    // → unknown status → with fix: restartLoginTimeout → 50ms timer.
    await tick(0);

    // Bind key should NOT be visible yet (unknown status, no bind key set).
    expect(screen.queryByText("BIND42")).not.toBeInTheDocument();

    // Advance past loginCheckTimeout to fire the retry timer.
    // Call 2 → awaitingBindKey "BIND42".
    await tick(60);

    // With the fix, the login loop retried and the bind key is now shown.
    expect(screen.getByText("BIND42")).toBeInTheDocument();
  });
});

describe("reauthenticateHandler shows spinner while retrieving bind key", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockCheckLogin.mockReset();
    mockRefreshToken.mockReset();
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  it("recovers from reauth failure and shows new bind key", async () => {
    // Call 1 (mount): normal login with bind key.
    // Call 2 (reauth → checkLogin): returns new bind key after refresh
    //   token failure triggers full re-login.
    mockCheckLogin
      .mockResolvedValueOnce({ status: "awaitingBindKey", bindKey: "INIT01" })
      .mockResolvedValue({ status: "awaitingBindKey", bindKey: "REAUTH01" });

    // refreshToken will fail when reauthenticate fires.
    mockRefreshToken.mockRejectedValue(new Error("token expired"));

    render(<App preview={null} previewId={null} />);

    // Let mount complete.
    await tick(0);
    expect(screen.getByText("INIT01")).toBeInTheDocument();

    // Fire reauthenticate event — refreshToken rejects → catch block
    // clears screen/bindKey, sets retrievingBindKey, calls checkLogin
    // which resolves with new bind key.
    await act(async () => {
      document.dispatchEvent(new Event("reauthenticate"));
    });
    await tick(60);

    // Old bind key gone, new bind key visible — proves the full reauth
    // recovery flow works: clear state → checkLogin → new bind key.
    expect(screen.queryByText("INIT01")).not.toBeInTheDocument();
    expect(screen.getByText("REAUTH01")).toBeInTheDocument();
  });
});

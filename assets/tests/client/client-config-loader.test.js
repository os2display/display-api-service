import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/core/app-storage.js", () => ({
  default: { setApiUrl: vi.fn() },
}));
vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

const mockConfig = {
  apiEndpoint: "https://api.test.com",
  schedulingInterval: 60000,
  debug: false,
};

describe("ClientConfigLoader", () => {
  let ClientConfigLoader;
  let appStorage;

  beforeEach(async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2025-06-15T12:00:00Z"));
    vi.resetModules();

    // Re-mock after resetModules
    vi.doMock("../../client/core/app-storage.js", () => ({
      default: { setApiUrl: vi.fn() },
    }));
    vi.doMock("../../client/core/logger.js", () => ({
      default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
    }));

    const module = await import("../../client/core/client-config-loader.js");
    ClientConfigLoader = module.default;

    const storageModule = await import("../../client/core/app-storage.js");
    appStorage = storageModule.default;
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it("fetches /config/client and returns the data", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        json: () => Promise.resolve(mockConfig),
      })
    );

    const config = await ClientConfigLoader.loadConfig();
    expect(fetch).toHaveBeenCalledWith("/config/client");
    expect(config).toEqual(mockConfig);
  });

  it("calls appStorage.setApiUrl with the apiEndpoint", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        json: () => Promise.resolve(mockConfig),
      })
    );

    await ClientConfigLoader.loadConfig();
    expect(appStorage.setApiUrl).toHaveBeenCalledWith("https://api.test.com");
  });

  it("returns cached data on second call within cache window", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      json: () => Promise.resolve(mockConfig),
    });
    vi.stubGlobal("fetch", fetchMock);

    await ClientConfigLoader.loadConfig();

    // Advance 5 minutes (within 15 min default cache)
    vi.setSystemTime(new Date("2025-06-15T12:05:00Z"));

    const config = await ClientConfigLoader.loadConfig();
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(config).toEqual(mockConfig);
  });

  it("re-fetches after cache expires", async () => {
    const fetchMock = vi.fn().mockResolvedValue({
      json: () => Promise.resolve(mockConfig),
    });
    vi.stubGlobal("fetch", fetchMock);

    await ClientConfigLoader.loadConfig();

    // Wait for the finally() callback to clear activePromise
    await vi.advanceTimersByTimeAsync(0);

    // Advance 16 minutes (past 15 min default cache)
    vi.setSystemTime(new Date("2025-06-15T12:16:00Z"));

    await ClientConfigLoader.loadConfig();
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it("returns default config when fetch fails and no cached data", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("network")));

    const config = await ClientConfigLoader.loadConfig();
    expect(config.apiEndpoint).toBe("/api");
    expect(config.schedulingInterval).toBe(60000);
    expect(config.debug).toBe(false);
  });

  it("returns cached data when fetch fails and cached data exists", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({
        json: () => Promise.resolve(mockConfig),
      })
      .mockRejectedValueOnce(new Error("network"));
    vi.stubGlobal("fetch", fetchMock);

    // First call succeeds
    await ClientConfigLoader.loadConfig();

    // Advance past cache
    vi.setSystemTime(new Date("2025-06-15T12:16:00Z"));

    // Second call fails — should return cached
    const config = await ClientConfigLoader.loadConfig();
    expect(config).toEqual(mockConfig);
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

describe("AdminConfigLoader", () => {
  let fetchMock;

  beforeEach(() => {
    vi.resetModules();
    fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("fetches config from /config/admin on first call", async () => {
    fetchMock.mockResolvedValue({
      json: () => Promise.resolve({ mediaMaxUploadSizeMb: 50 }),
    });

    const { default: AdminConfigLoader } =
      await import("../../admin/components/util/admin-config-loader.js");

    const config = await AdminConfigLoader.loadConfig();

    expect(fetchMock).toHaveBeenCalledWith("/config/admin");
    expect(config.mediaMaxUploadSizeMb).toBe(50);
  });

  it("caches the response across repeated calls", async () => {
    fetchMock.mockResolvedValue({
      json: () => Promise.resolve({ mediaMaxUploadSizeMb: 75 }),
    });

    const { default: AdminConfigLoader } =
      await import("../../admin/components/util/admin-config-loader.js");

    await AdminConfigLoader.loadConfig();
    await AdminConfigLoader.loadConfig();
    await AdminConfigLoader.loadConfig();

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("falls back to defaults when fetch rejects", async () => {
    fetchMock.mockRejectedValue(new Error("network down"));

    const { default: AdminConfigLoader } =
      await import("../../admin/components/util/admin-config-loader.js");

    const config = await AdminConfigLoader.loadConfig();

    expect(config.mediaMaxUploadSizeMb).toBe(200);
    expect(config.loginMethods).toBeDefined();
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

import { REQUEST_TIMEOUT } from "../../client/util/fetch-with-timeout";

const loaderPath = "../../client/util/client-config-loader.js";

/**
 * A fetch that only ever settles by being aborted.
 *
 * @returns {Function} A fetch stub.
 */
function neverAnswers() {
  return vi.fn(
    (resource, options) =>
      new Promise((resolve, reject) => {
        options.signal.addEventListener("abort", () => {
          const err = new Error("The operation was aborted.");
          err.name = "AbortError";

          reject(err);
        });
      }),
  );
}

describe("ClientConfigLoader", () => {
  let fetchMock;

  beforeEach(() => {
    // The loader keeps its cache in module scope, so each case needs a fresh
    // copy of the module.
    vi.resetModules();
    fetchMock = vi.fn();
    vi.stubGlobal("fetch", fetchMock);
    localStorage.clear();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
  });

  it("fetches the config on the first call", async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ relationsChecksumEnabled: true }),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    const config = await ClientConfigLoader.loadConfig();

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(fetchMock.mock.calls[0][0]).toBe("/config/client");
    expect(config.relationsChecksumEnabled).toBe(true);
  });

  it("caches the response across repeated calls", async () => {
    fetchMock.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ relationsChecksumEnabled: true }),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    await ClientConfigLoader.loadConfig();
    await ClientConfigLoader.loadConfig();
    await ClientConfigLoader.loadConfig();

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("falls back to defaults when the request fails", async () => {
    fetchMock.mockRejectedValue(new Error("network down"));

    const { default: ClientConfigLoader } = await import(loaderPath);

    const config = await ClientConfigLoader.loadConfig();

    expect(config.apiEndpoint).toBe("/api");
    expect(config.schedulingInterval).toBe(60000);
  });

  it("does not hand back null before any config has loaded", async () => {
    // The cache guard used to be satisfied by the interval comparison alone, so
    // a small clock value could return the not-yet-loaded null as config.
    vi.useFakeTimers();
    vi.setSystemTime(0);

    fetchMock.mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ relationsChecksumEnabled: true }),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    await expect(ClientConfigLoader.loadConfig()).resolves.not.toBeNull();
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("recovers after a request that never answers", async () => {
    // The in-flight promise used to be cleared only on the fetch path's
    // finally, so a stalled request pinned every later call to the same pending
    // promise - and every screen pull awaiting the config stalled behind it
    // (#507).
    vi.useFakeTimers();
    vi.stubGlobal("fetch", neverAnswers());

    const { default: ClientConfigLoader } = await import(loaderPath);

    const first = ClientConfigLoader.loadConfig();

    await vi.advanceTimersByTimeAsync(REQUEST_TIMEOUT + 1);

    // It settles rather than hanging, on the default config.
    await expect(first).resolves.toMatchObject({ apiEndpoint: "/api" });

    // And a later call tries again instead of returning the stalled promise.
    fetchMock = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ relationsChecksumEnabled: true }),
    });
    vi.stubGlobal("fetch", fetchMock);

    await expect(ClientConfigLoader.loadConfig()).resolves.toMatchObject({
      relationsChecksumEnabled: true,
    });
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("falls back to the default config on an error response", async () => {
    // fetch only rejects on transport failure, so a 500 whose body happens to
    // parse as JSON was stored as the config and served for the whole config
    // interval - with apiEndpoint undefined, taking every request with it.
    fetchMock.mockResolvedValue({
      ok: false,
      status: 500,
      json: () => Promise.resolve({ error: "Internal Server Error" }),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    await expect(ClientConfigLoader.loadConfig()).resolves.toMatchObject({
      apiEndpoint: "/api",
    });
  });

  it("does not cache an error response", async () => {
    // Nothing was stored, so the next call goes back to the network rather than
    // waiting out the config interval on a fallback.
    fetchMock.mockResolvedValueOnce({
      ok: false,
      status: 503,
      json: () => Promise.resolve({}),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    await ClientConfigLoader.loadConfig();

    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ relationsChecksumEnabled: true }),
    });

    await expect(ClientConfigLoader.loadConfig()).resolves.toMatchObject({
      relationsChecksumEnabled: true,
    });
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it("keeps the last known good config when a later request errors", async () => {
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: () => Promise.resolve({ apiEndpoint: "/real-api" }),
    });

    const { default: ClientConfigLoader } = await import(loaderPath);

    await ClientConfigLoader.loadConfig();

    // Past the config interval, so the next call refetches.
    vi.useFakeTimers();
    vi.setSystemTime(Date.now() + 16 * 60 * 1000);

    fetchMock.mockResolvedValueOnce({
      ok: false,
      status: 502,
      json: () => Promise.resolve({}),
    });

    await expect(ClientConfigLoader.loadConfig()).resolves.toMatchObject({
      apiEndpoint: "/real-api",
    });
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/logger/logger", () => ({
  default: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

import ApiHelper from "../../client/data-sync/api-helper";
import localStorageKeys from "../../client/util/local-storage-keys";

const MEDIA_PATH = "/v2/media/01ARZ3NDEKTSV4RRFFQ69G5FAV";

/**
 * Resolve a getPath() promise, letting any backoff timers run.
 *
 * @param {Promise} promise The getPath() promise.
 * @returns {Promise<any>} The resolved value.
 */
async function resolveWithTimers(promise) {
  await vi.advanceTimersByTimeAsync(30000);

  return promise;
}

describe("ApiHelper.getPath retries throttled requests", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    localStorage.setItem(localStorageKeys.API_TOKEN, "a-token");
    localStorage.setItem(localStorageKeys.TENANT_KEY, "a-tenant-key");
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    localStorage.clear();
  });

  it("retries a 503 and returns the data from the retry", async () => {
    // A screen client on a multi-region layout fires far more requests per
    // pull than the reverse proxy rate limit allows, so a timing dependent
    // tail of them is rejected. Without a retry the rejected request becomes
    // a silent null and the region renders black. See #507.
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({ ok: false, status: 503, headers: new Headers() })
      .mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ "@id": MEDIA_PATH }),
      });

    vi.stubGlobal("fetch", fetchMock);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    expect(result).toEqual({ "@id": MEDIA_PATH });
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it("retries a 429 and returns the data from the retry", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({ ok: false, status: 429, headers: new Headers() })
      .mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ "@id": MEDIA_PATH }),
      });

    vi.stubGlobal("fetch", fetchMock);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    expect(result).toEqual({ "@id": MEDIA_PATH });
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it("gives up and returns null when every attempt is throttled", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: false, status: 503, headers: new Headers() });

    vi.stubGlobal("fetch", fetchMock);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    expect(result).toBeNull();
    expect(fetchMock.mock.calls.length).toBeGreaterThan(1);
  });

  it("does not retry a 401 and triggers reauthenticate once", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: false, status: 401, headers: new Headers() });

    vi.stubGlobal("fetch", fetchMock);

    const reauthenticate = vi.fn();
    document.addEventListener("reauthenticate", reauthenticate);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    document.removeEventListener("reauthenticate", reauthenticate);

    expect(result).toBeNull();
    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(reauthenticate).toHaveBeenCalledTimes(1);
  });

  it("does not retry a 404", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: false, status: 404, headers: new Headers() });

    vi.stubGlobal("fetch", fetchMock);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    expect(result).toBeNull();
    expect(fetchMock).toHaveBeenCalledTimes(1);
  });

  it("honours Retry-After from the response", async () => {
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({
        ok: false,
        status: 429,
        headers: new Headers({ "Retry-After": "5" }),
      })
      .mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ "@id": MEDIA_PATH }),
      });

    vi.stubGlobal("fetch", fetchMock);

    const promise = new ApiHelper("").getPath(MEDIA_PATH);

    // Not yet: the server asked for five seconds.
    await vi.advanceTimersByTimeAsync(4000);
    expect(fetchMock).toHaveBeenCalledTimes(1);

    await vi.advanceTimersByTimeAsync(1500);
    expect(fetchMock).toHaveBeenCalledTimes(2);

    await expect(resolveWithTimers(promise)).resolves.toEqual({
      "@id": MEDIA_PATH,
    });
  });

  it("clamps an unreasonable Retry-After", async () => {
    // A CDN or maintenance page in front of nginx can answer with an hour.
    // Honouring that verbatim would park the screen for the rest of the day.
    const fetchMock = vi
      .fn()
      .mockResolvedValueOnce({
        ok: false,
        status: 503,
        headers: new Headers({ "Retry-After": "3600" }),
      })
      .mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ "@id": MEDIA_PATH }),
      });

    vi.stubGlobal("fetch", fetchMock);

    const promise = new ApiHelper("").getPath(MEDIA_PATH);

    // The clamp is 30s, so the retry must have happened well inside the hour.
    await vi.advanceTimersByTimeAsync(31000);
    expect(fetchMock).toHaveBeenCalledTimes(2);

    await expect(resolveWithTimers(promise)).resolves.toEqual({
      "@id": MEDIA_PATH,
    });
  });

  it("keeps every backoff wait inside the cap", async () => {
    // Full jitter picks anywhere in [0, cap]; with Math.random pinned high the
    // longest possible wait must still be under the ceiling, or a screen could
    // stall past its own pull interval.
    vi.spyOn(Math, "random").mockReturnValue(0.999999);

    const fetchMock = vi
      .fn()
      .mockResolvedValue({ ok: false, status: 503, headers: new Headers() });

    vi.stubGlobal("fetch", fetchMock);

    const promise = new ApiHelper("").getPath(MEDIA_PATH);

    // 3 retries, each capped at 30s, so everything is done inside 90s.
    await vi.advanceTimersByTimeAsync(90000);

    await expect(promise).resolves.toBeNull();
    expect(fetchMock).toHaveBeenCalledTimes(4);

    Math.random.mockRestore();
  });

  it("retries a request that times out", async () => {
    // A socket that never answers is the one case backoff cannot help, so the
    // request has to be aborted rather than held open.
    const abortError = new Error("The operation was aborted.");
    abortError.name = "AbortError";

    const fetchMock = vi
      .fn()
      .mockRejectedValueOnce(abortError)
      .mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({ "@id": MEDIA_PATH }),
      });

    vi.stubGlobal("fetch", fetchMock);

    const result = await resolveWithTimers(
      new ApiHelper("").getPath(MEDIA_PATH),
    );

    expect(result).toEqual({ "@id": MEDIA_PATH });
    expect(fetchMock).toHaveBeenCalledTimes(2);
  });

  it("does not retry when no credentials are stored", async () => {
    // Local state, not a transport problem: it fails identically every time.
    localStorage.clear();

    const fetchMock = vi.fn();

    vi.stubGlobal("fetch", fetchMock);

    await expect(
      resolveWithTimers(new ApiHelper("").getPath(MEDIA_PATH)),
    ).resolves.toBeNull();
    expect(fetchMock).not.toHaveBeenCalled();
  });
});

describe("ApiHelper.retryDelay spreads a Retry-After", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  /**
   * A response carrying a Retry-After header.
   *
   * @param {string} value The header value.
   * @returns {object} A response-alike.
   */
  function withRetryAfter(value) {
    return { headers: new Headers({ "Retry-After": value }) };
  }

  it("waits at least as long as the server asked", () => {
    // RFC 9110 makes Retry-After a minimum, so the jitter is added rather than
    // subtracted - a client must never come back early.
    vi.spyOn(Math, "random").mockReturnValue(0);

    expect(ApiHelper.retryDelay(withRetryAfter("5"), 0)).toBeGreaterThanOrEqual(
      5000,
    );
  });

  it("does not hand every client the same wait", () => {
    // Everyone rejected in the same second gets the same header value, so
    // honouring it verbatim re-synchronises the burst it exists to spread.
    vi.spyOn(Math, "random").mockReturnValue(0.999999);

    const delay = ApiHelper.retryDelay(withRetryAfter("5"), 0);

    expect(delay).toBeGreaterThan(5000);
    expect(delay).toBeLessThan(5500);
  });

  it("keeps the jitter inside the clamp for an absurd Retry-After", () => {
    vi.spyOn(Math, "random").mockReturnValue(0.999999);

    const delay = ApiHelper.retryDelay(withRetryAfter("3600"), 0);

    expect(delay).toBeGreaterThan(30000);
    expect(delay).toBeLessThan(30500);
  });

  it("falls back to backoff for an HTTP-date Retry-After", () => {
    // parseInt turns a date into NaN; the backoff is the acceptable degradation.
    vi.spyOn(Math, "random").mockReturnValue(0.5);

    const delay = ApiHelper.retryDelay(
      withRetryAfter("Wed, 21 Oct 2026 07:28:00 GMT"),
      0,
    );

    expect(delay).toBeLessThan(500);
  });
});

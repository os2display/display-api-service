import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

import fetchWithTimeout, {
  REQUEST_TIMEOUT,
} from "../../client/util/fetch-with-timeout";

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

describe("fetchWithTimeout", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  it("aborts a request that never answers", async () => {
    // A socket that never answers neither fails nor succeeds. Awaiting one is
    // what let a single stalled request stall a whole screen pull (#507).
    vi.stubGlobal("fetch", neverAnswers());

    const promise = fetchWithTimeout("/config/client");
    const assertion = expect(promise).rejects.toThrowError(
      /operation was aborted/,
    );

    await vi.advanceTimersByTimeAsync(REQUEST_TIMEOUT + 1);

    await assertion;
  });

  it("leaves a request that answers in time alone", async () => {
    const response = { ok: true, status: 200 };

    vi.stubGlobal(
      "fetch",
      vi.fn(() => Promise.resolve(response)),
    );

    await expect(fetchWithTimeout("/config/client")).resolves.toBe(response);
  });

  it("clears its timer on the success path", async () => {
    // An uncleared timer would survive every successful request.
    vi.stubGlobal(
      "fetch",
      vi.fn(() => Promise.resolve({ ok: true })),
    );

    await fetchWithTimeout("/config/client");

    expect(vi.getTimerCount()).toBe(0);
  });

  it("passes options through and adds a signal", async () => {
    const fetchMock = vi.fn(() => Promise.resolve({ ok: true }));

    vi.stubGlobal("fetch", fetchMock);

    await fetchWithTimeout("/v2/screens", { method: "POST" });

    const [resource, options] = fetchMock.mock.calls[0];

    expect(resource).toBe("/v2/screens");
    expect(options.method).toBe("POST");
    expect(options.signal).toBeInstanceOf(AbortSignal);
  });
});

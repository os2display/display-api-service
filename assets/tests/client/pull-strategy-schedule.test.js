import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

const { mockLogger } = vi.hoisted(() => ({
  mockLogger: { log: vi.fn(), info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

vi.mock("../../client/logger/logger", () => ({ default: mockLogger }));

vi.mock("../../client/data-sync/api-helper", () => ({
  default: vi.fn().mockImplementation(function () {
    this.getPath = vi.fn();
    this.getAllResultsFromPath = vi.fn();
  }),
}));

import PullStrategy from "../../client/data-sync/pull-strategy";

const INTERVAL = 1000;
const screenPath = "/v2/screens/01ARZ3NDEKTSV4RRFFQ69G5FAV";

/**
 * A PullStrategy whose pulls are held open until released.
 *
 * @returns {object} The strategy and a release function.
 */
function strategyWithHeldPulls() {
  const strategy = new PullStrategy({
    endpoint: "",
    entryPoint: screenPath,
    interval: INTERVAL,
  });

  const pending = [];

  // getScreen is an own property from the constructor bind, so replacing it
  // here is what pull() will call.
  strategy.getScreen = vi.fn(
    () =>
      new Promise((resolve) => {
        pending.push(resolve);
      }),
  );

  return {
    strategy,
    async releaseAll() {
      while (pending.length > 0) {
        pending.shift()();
        // Let the finally block that schedules the next pull run.
        // eslint-disable-next-line no-await-in-loop
        await Promise.resolve();
      }
    },
  };
}

describe("PullStrategy scheduling", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    mockLogger.error.mockReset();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("does not start a second pull while one is still running", async () => {
    // setInterval fired regardless of whether the previous pull had finished,
    // so a slow pull got a second one stacked on top of it - doubling the
    // fan-out exactly when the backend was already struggling (#507).
    const { strategy, releaseAll } = strategyWithHeldPulls();

    strategy.start();

    expect(strategy.getScreen).toHaveBeenCalledTimes(1);

    await vi.advanceTimersByTimeAsync(INTERVAL * 5);

    expect(strategy.getScreen).toHaveBeenCalledTimes(1);

    await releaseAll();
    await vi.advanceTimersByTimeAsync(INTERVAL);

    expect(strategy.getScreen).toHaveBeenCalledTimes(2);

    strategy.stop();
  });

  it("keeps polling after a pull rejects", async () => {
    const strategy = new PullStrategy({
      endpoint: "",
      entryPoint: screenPath,
      interval: INTERVAL,
    });

    strategy.getScreen = vi
      .fn()
      .mockRejectedValueOnce(new Error("boom"))
      .mockResolvedValue(undefined);

    strategy.start();

    await vi.advanceTimersByTimeAsync(INTERVAL);

    expect(strategy.getScreen).toHaveBeenCalledTimes(2);
    expect(mockLogger.error).toHaveBeenCalled();

    strategy.stop();
  });

  it("schedules nothing more when stopped during the first pull", async () => {
    // stop() used to be ignored here: start()'s finally scheduled the interval
    // anyway, so a DataSync that ContentService had already discarded kept
    // polling and dispatching content events.
    const { strategy, releaseAll } = strategyWithHeldPulls();

    strategy.start();
    strategy.stop();

    await releaseAll();
    await vi.advanceTimersByTimeAsync(INTERVAL * 5);

    expect(strategy.getScreen).toHaveBeenCalledTimes(1);
    expect(vi.getTimerCount()).toBe(0);
  });

  it("leaves only one chain running when restarted mid-pull", async () => {
    const { strategy, releaseAll } = strategyWithHeldPulls();

    strategy.start();
    strategy.start();

    // Two pulls are in flight - the abandoned one and the new chain's - but
    // only the current generation may schedule a successor.
    expect(strategy.getScreen).toHaveBeenCalledTimes(2);

    await releaseAll();
    await vi.advanceTimersByTimeAsync(INTERVAL);

    expect(strategy.getScreen).toHaveBeenCalledTimes(3);

    strategy.stop();
  });

  it("stops cleanly between pulls", async () => {
    const strategy = new PullStrategy({
      endpoint: "",
      entryPoint: screenPath,
      interval: INTERVAL,
    });

    strategy.getScreen = vi.fn().mockResolvedValue(undefined);

    strategy.start();

    await vi.advanceTimersByTimeAsync(INTERVAL);
    expect(strategy.getScreen).toHaveBeenCalledTimes(2);

    strategy.stop();

    await vi.advanceTimersByTimeAsync(INTERVAL * 5);

    expect(strategy.getScreen).toHaveBeenCalledTimes(2);
  });
});

import { test, expect } from "@playwright/test";
import { ReleaseLoader } from "../../shared/release-loader.js";

const RELEASE_DATA = {
  releaseTime: "2024-01-01T00:00:00Z",
  releaseTimestamp: 1704067200,
  releaseVersion: "1.0.0",
};

function createMockFetch(response = RELEASE_DATA) {
  return () =>
    Promise.resolve({
      json: () => Promise.resolve(response),
    });
}

function createFailingFetch() {
  return () => Promise.reject(new Error("Network error"));
}

test.describe("ReleaseLoader", () => {
  test("It fetches and returns release data", async () => {
    const loader = new ReleaseLoader({ fetchFn: createMockFetch() });
    const result = await loader.loadRelease();

    expect(result).toEqual(RELEASE_DATA);
  });

  test("It returns cached data within the fetch interval", async () => {
    let fetchCount = 0;
    const fetchFn = () => {
      fetchCount += 1;
      return createMockFetch()();
    };

    const loader = new ReleaseLoader({ fetchFn, nowFn: () => 1000 });

    await loader.loadRelease();
    await loader.loadRelease();

    expect(fetchCount).toBe(1);
  });

  test("It fetches again after the interval has passed", async () => {
    let fetchCount = 0;
    const fetchFn = () => {
      fetchCount += 1;
      return createMockFetch()();
    };

    let now = 0;
    const loader = new ReleaseLoader({
      fetchFn,
      nowFn: () => now,
      fetchInterval: 1000,
    });

    await loader.loadRelease();
    now = 1001;
    await loader.loadRelease();

    expect(fetchCount).toBe(2);
  });

  test("It returns defaults when fetch fails and no cached data exists", async () => {
    const loader = new ReleaseLoader({ fetchFn: createFailingFetch() });
    const result = await loader.loadRelease();

    expect(result).toEqual({
      releaseTime: null,
      releaseTimestamp: null,
      releaseVersion: null,
    });
  });

  test("It returns cached data when fetch fails after a successful fetch", async () => {
    let shouldFail = false;
    const fetchFn = () => {
      if (shouldFail) {
        return createFailingFetch()();
      }
      return createMockFetch()();
    };

    let now = 0;
    const loader = new ReleaseLoader({
      fetchFn,
      nowFn: () => now,
      fetchInterval: 1000,
    });

    await loader.loadRelease();

    shouldFail = true;
    now = 1001;
    const result = await loader.loadRelease();

    expect(result).toEqual(RELEASE_DATA);
  });

  test("It deduplicates concurrent calls", async () => {
    let fetchCount = 0;
    let resolveResponse;

    const fetchFn = () => {
      fetchCount += 1;
      return new Promise((resolve) => {
        resolveResponse = resolve;
      });
    };

    const loader = new ReleaseLoader({ fetchFn });

    const promise1 = loader.loadRelease();
    const promise2 = loader.loadRelease();

    resolveResponse({ json: () => Promise.resolve(RELEASE_DATA) });

    const [result1, result2] = await Promise.all([promise1, promise2]);

    expect(fetchCount).toBe(1);
    expect(result1).toEqual(RELEASE_DATA);
    expect(result2).toEqual(RELEASE_DATA);
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

// --- Hoisted mocks ---
//
// RTK Query's dispatch(endpoint.initiate(args)) returns a "request" object
// with { unwrap(), abort(), unsubscribe() }. We simulate that here so the
// source's Promise.race / cleanup logic can be tested in isolation.
//
// dispatchDefaults.unwrapResult controls what unwrap() resolves/rejects with.
// Set it in each test before calling query(). Tests that need per-call control
// (e.g. pagination) override mockDispatch.mockImplementation() directly.
//
// mockSelect simulates clientApi.endpoints[name].select(args) which returns
// a selector function (state => cacheEntry). The double-arrow mirrors the real
// RTK Query API: select(args) returns (state) => ({ data, ... }).
const { mockDispatch, endpoints, mockSelect, dispatchDefaults } = vi.hoisted(() => {
  const mockSelect = vi.fn(() => () => undefined);
  const mockAbort = vi.fn();
  const mockUnsubscribe = vi.fn();

  const dispatchDefaults = {
    unwrapResult: Promise.resolve("data"),
    makeReturnValue() {
      return {
        unwrap: () => dispatchDefaults.unwrapResult,
        abort: mockAbort,
        unsubscribe: mockUnsubscribe,
      };
    },
  };

  const mockDispatch = vi.fn(() => dispatchDefaults.makeReturnValue());

  mockDispatch._abort = mockAbort;
  mockDispatch._unsubscribe = mockUnsubscribe;

  const endpoints = {};
  const initiate = vi.fn((args, opts) => ({
    _endpoint: "testEndpoint",
    _args: args,
    _opts: opts,
  }));
  endpoints.testEndpoint = { initiate, select: mockSelect };

  return { mockDispatch, endpoints, mockSelect, dispatchDefaults };
});

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/redux/store.js", () => ({
  clientStore: { dispatch: mockDispatch, getState: () => ({}) },
}));

vi.mock("../../client/redux/enhanced-api.ts", () => ({
  clientApi: {
    endpoints,
    reducerPath: "clientApi",
    reducer: (state = {}) => state,
    middleware: () => (next) => (action) => next(action),
  },
}));

vi.mock("../../client/util/defaults.js", () => ({
  default: { queryTimeoutDefault: 500 },
}));

import { query, queryAllPages } from "../../client/core/api-query.js";
import logger from "../../client/core/logger.js";

describe("query", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllMocks();
    // Restore default implementation (mockImplementation from prior tests persists through clearAllMocks).
    mockDispatch.mockImplementation(() => dispatchDefaults.makeReturnValue());
    dispatchDefaults.unwrapResult = Promise.resolve("data");
    mockSelect.mockReturnValue(() => undefined);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("should resolve with unwrapped data when fetch succeeds", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve({ id: 1 });

    const result = await query("testEndpoint", { id: "abc" });

    expect(result).toEqual({ id: 1 });
  });

  it("should pass forceRefetch false by default", () => {
    query("testEndpoint", { id: "abc" });

    expect(endpoints.testEndpoint.initiate).toHaveBeenCalledWith(
      { id: "abc" },
      { forceRefetch: false },
    );
  });

  it("should pass forceRefetch true when requested", () => {
    query("testEndpoint", { id: "abc" }, true);

    expect(endpoints.testEndpoint.initiate).toHaveBeenCalledWith(
      { id: "abc" },
      { forceRefetch: true },
    );
  });

  it("should reject with timeout error when fetch exceeds queryTimeoutDefault", async () => {
    dispatchDefaults.unwrapResult = new Promise(() => {}); // never resolves

    const promise = query("testEndpoint", {});
    vi.advanceTimersByTime(500);

    await expect(promise).rejects.toThrow("Request timeout: testEndpoint");
  });

  it("should call request.abort on timeout", async () => {
    dispatchDefaults.unwrapResult = new Promise(() => {});

    const promise = query("testEndpoint", {});
    vi.advanceTimersByTime(500);

    await promise.catch(() => {});
    expect(mockDispatch._abort).toHaveBeenCalled();
  });

  it("should return cached data when fetch fails but cache exists", async () => {
    dispatchDefaults.unwrapResult = Promise.reject(new Error("network"));
    mockSelect.mockReturnValue(() => ({ data: { cached: true } }));

    const result = await query("testEndpoint", { id: "abc" });

    expect(result).toEqual({ cached: true });
  });

  it("should log warning when falling back to cached data", async () => {
    dispatchDefaults.unwrapResult = Promise.reject(new Error("network"));
    mockSelect.mockReturnValue(() => ({ data: { cached: true } }));

    await query("testEndpoint", { id: "abc" });

    expect(logger.warn).toHaveBeenCalledWith(
      "Using cached data for testEndpoint after fetch failure.",
    );
  });

  it("should re-throw when fetch fails and no cache exists", async () => {
    dispatchDefaults.unwrapResult = Promise.reject(new Error("network"));
    mockSelect.mockReturnValue(() => undefined);

    await expect(query("testEndpoint", {})).rejects.toThrow("network");
  });

  it("should re-throw when cached data is undefined", async () => {
    dispatchDefaults.unwrapResult = Promise.reject(new Error("network"));
    mockSelect.mockReturnValue(() => ({ data: undefined }));

    await expect(query("testEndpoint", {})).rejects.toThrow("network");
  });

  it("should call unsubscribe on success", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve("ok");

    await query("testEndpoint", {});

    expect(mockDispatch._unsubscribe).toHaveBeenCalled();
  });

  it("should call unsubscribe on error", async () => {
    dispatchDefaults.unwrapResult = Promise.reject(new Error("fail"));
    mockSelect.mockReturnValue(() => undefined);

    await query("testEndpoint", {}).catch(() => {});

    expect(mockDispatch._unsubscribe).toHaveBeenCalled();
  });
});

describe("queryAllPages", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.clearAllMocks();
    mockDispatch.mockImplementation(() => dispatchDefaults.makeReturnValue());
    dispatchDefaults.unwrapResult = Promise.resolve("data");
    mockSelect.mockReturnValue(() => undefined);
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("should return hydra:member from a single page with no hydra:next", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve({
      "hydra:member": [{ id: 1 }, { id: 2 }],
      "hydra:view": {},
    });

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toEqual([{ id: 1 }, { id: 2 }]);
  });

  it("should concatenate members across multiple pages", async () => {
    let callCount = 0;
    mockDispatch.mockImplementation(() => {
      callCount += 1;
      const page = callCount;
      return {
        unwrap: () =>
          Promise.resolve({
            "hydra:member": [{ id: page }],
            "hydra:view":
              page < 3 ? { "hydra:next": `/page/${page + 1}` } : {},
          }),
        abort: vi.fn(),
        unsubscribe: vi.fn(),
      };
    });

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toEqual([{ id: 1 }, { id: 2 }, { id: 3 }]);
  });

  it("should stop when hydra:view has no hydra:next", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve({
      "hydra:member": [{ id: 1 }],
      "hydra:view": { "hydra:last": "/page/1" },
    });

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toEqual([{ id: 1 }]);
    expect(mockDispatch).toHaveBeenCalledTimes(1);
  });

  it("should return partial results when mid-pagination fetch throws", async () => {
    let callCount = 0;
    mockDispatch.mockImplementation(() => {
      callCount += 1;
      const page = callCount;
      if (page === 2) {
        return {
          unwrap: () => Promise.reject(new Error("fail page 2")),
          abort: vi.fn(),
          unsubscribe: vi.fn(),
        };
      }
      return {
        unwrap: () =>
          Promise.resolve({
            "hydra:member": [{ id: page }],
            "hydra:view": { "hydra:next": `/page/${page + 1}` },
          }),
        abort: vi.fn(),
        unsubscribe: vi.fn(),
      };
    });

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toEqual([{ id: 1 }]);
  });

  it("should return empty array when page 1 returns null", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve(null);

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toEqual([]);
  });

  it("should log error on null response", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve(null);

    await queryAllPages("testEndpoint", {});

    expect(logger.error).toHaveBeenCalledWith(
      "Failed to fetch page 1 for testEndpoint",
    );
  });

  it("should pass forceRefetch through to query", async () => {
    dispatchDefaults.unwrapResult = Promise.resolve({
      "hydra:member": [],
      "hydra:view": {},
    });

    await queryAllPages("testEndpoint", { filter: "x" }, true);

    expect(endpoints.testEndpoint.initiate).toHaveBeenCalledWith(
      { filter: "x", page: 1 },
      { forceRefetch: true },
    );
  });

  it("should stop at MAX_PAGES and log warning", async () => {
    mockDispatch.mockImplementation(() => ({
      unwrap: () =>
        Promise.resolve({
          "hydra:member": [{ id: "item" }],
          "hydra:view": { "hydra:next": "/next" },
        }),
      abort: vi.fn(),
      unsubscribe: vi.fn(),
    }));

    const result = await queryAllPages("testEndpoint", {});

    expect(result).toHaveLength(50);
    expect(logger.warn).toHaveBeenCalledWith(
      "Reached max page limit (50) for testEndpoint",
    );
  });
});

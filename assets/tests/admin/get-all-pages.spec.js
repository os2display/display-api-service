import { test, expect } from "@playwright/test";
import getAllPages from "../../admin/components/util/helpers/get-all-pages.js";

function createHydraResponse(members, hasNext = false) {
  return {
    data: {
      "hydra:member": members,
      "hydra:view": hasNext ? { "hydra:next": "/next" } : null,
    },
  };
}

function createMockEndpoint() {
  return { initiate: (params) => params };
}

function createMockDispatch(responses) {
  let callIndex = 0;
  const fn = () => {
    const response = responses[callIndex];
    callIndex += 1;
    return Promise.resolve(response);
  };
  fn.getCallCount = () => callIndex;
  return fn;
}

test.describe("getAllPages", () => {
  test("It returns results from a single page", async () => {
    const dispatch = createMockDispatch([
      createHydraResponse([{ id: 1 }, { id: 2 }]),
    ]);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toEqual([{ id: 1 }, { id: 2 }]);
    expect(dispatch.getCallCount()).toBe(1);
  });

  test("It fetches multiple pages when hydra:next is present", async () => {
    const dispatch = createMockDispatch([
      createHydraResponse([{ id: 1 }], true),
      createHydraResponse([{ id: 2 }], true),
      createHydraResponse([{ id: 3 }], false),
    ]);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toEqual([{ id: 1 }, { id: 2 }, { id: 3 }]);
    expect(dispatch.getCallCount()).toBe(3);
  });

  test("It passes page number and params to endpoint", async () => {
    const calls = [];
    const endpoint = {
      initiate: (params) => {
        calls.push(params);
        return params;
      },
    };
    const dispatch = createMockDispatch([
      createHydraResponse([{ id: 1 }], true),
      createHydraResponse([{ id: 2 }], false),
    ]);

    await getAllPages(dispatch, endpoint, { itemsPerPage: 10 });

    expect(calls).toEqual([
      { itemsPerPage: 10, page: 1 },
      { itemsPerPage: 10, page: 2 },
    ]);
  });

  test("It stops when hydra:view is null", async () => {
    const dispatch = createMockDispatch([
      {
        data: {
          "hydra:member": [{ id: 1 }],
          "hydra:view": undefined,
        },
      },
    ]);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toEqual([{ id: 1 }]);
    expect(dispatch.getCallCount()).toBe(1);
  });

  test("It stops when a page returns empty results", async () => {
    const dispatch = createMockDispatch([
      createHydraResponse([{ id: 1 }], true),
      createHydraResponse([], true),
    ]);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toEqual([{ id: 1 }]);
    expect(dispatch.getCallCount()).toBe(2);
  });

  test("It respects the max pages limit", async () => {
    const responses = Array.from({ length: 101 }, (_, i) =>
      createHydraResponse([{ id: i }], true)
    );
    const dispatch = createMockDispatch(responses);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toHaveLength(100);
    expect(dispatch.getCallCount()).toBe(100);
  });

  test("It propagates fetch errors", async () => {
    const dispatch = () => Promise.reject(new Error("Network error"));

    await expect(
      getAllPages(dispatch, createMockEndpoint(), {})
    ).rejects.toThrow("Network error");
  });

  test("It returns empty array when first page has no results", async () => {
    const dispatch = createMockDispatch([createHydraResponse([])]);

    const result = await getAllPages(dispatch, createMockEndpoint(), {});

    expect(result).toEqual([]);
  });
});

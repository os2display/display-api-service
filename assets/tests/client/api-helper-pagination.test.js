import { describe, it, expect, vi } from "vitest";
import ApiHelper from "../../client/data-sync/api-helper.js";

const PATH = "/v2/playlists/X/slides";
const ITEMS_PER_PAGE = 10;

/**
 * Builds a JSON-LD collection page the way API Platform does: hydra:next is
 * present only while there is a page after this one.
 *
 * @param {number} members Members on this page.
 * @param {number} totalItems Value reported in hydra:totalItems.
 * @param {number} page Current page number.
 * @param {number} lastPage Last page that exists.
 * @returns {object} The response body.
 */
function page(members, totalItems, page_, lastPage) {
  const body = {
    "hydra:member": new Array(members).fill({ slide: {} }),
    "hydra:totalItems": totalItems,
  };

  // API Platform omits hydra:view entirely for single-page collections.
  if (lastPage > 1) {
    body["hydra:view"] = { "@id": `${PATH}?page=${page_}` };
    if (page_ < lastPage) {
      body["hydra:view"]["hydra:next"] = `${PATH}?page=${page_ + 1}`;
    }
  }

  return body;
}

/**
 * Serves a collection of `deliverable` rows, reporting `totalItems` as the
 * count, and counts requests.
 *
 * @param {number} deliverable Rows the API can actually hand out.
 * @param {number} totalItems Value the API reports in hydra:totalItems.
 * @returns {object} The helper and a request counter.
 */
function helperFor(deliverable, totalItems) {
  const apiHelper = new ApiHelper("https://example.test");
  const lastPage = Math.max(1, Math.ceil(totalItems / ITEMS_PER_PAGE));
  let calls = 0;

  vi.spyOn(apiHelper, "getPath").mockImplementation((path) => {
    calls += 1;
    if (calls > 500) {
      return Promise.reject(new Error(`runaway: still requesting ${path}`));
    }
    const current = Number(path.match(/page=(\d+)/)?.[1] ?? 1);
    const remaining = Math.max(0, deliverable - (current - 1) * ITEMS_PER_PAGE);

    return Promise.resolve(
      page(Math.min(ITEMS_PER_PAGE, remaining), totalItems, current, lastPage),
    );
  });

  return { apiHelper, getCalls: () => calls };
}

describe("ApiHelper.getAllResultsFromPath", () => {
  it("collects every page of a multi-page collection", async () => {
    const { apiHelper, getCalls } = helperFor(20, 20);

    const result = await apiHelper.getAllResultsFromPath(PATH);

    expect(result.results).toHaveLength(20);
    expect(getCalls()).toBe(2);
  });

  it("stops after one request when the collection fits on a page", async () => {
    // Single-page collections carry no hydra:view at all.
    const { apiHelper, getCalls } = helperFor(4, 4);

    const result = await apiHelper.getAllResultsFromPath(PATH);

    expect(result.results).toHaveLength(4);
    expect(getCalls()).toBe(1);
  });

  it("stops when the reported count exceeds the rows delivered", async () => {
    // Regression test for #517: hydra:totalItems reports 20 while only 11 rows
    // are deliverable. Observed in production 2026-08-11 08:57:23, where the
    // previous count-based loop turned one such response into ~435k requests
    // over 21 hours. Following hydra:next ends it at the last page instead.
    const { apiHelper, getCalls } = helperFor(11, 20);

    const result = await apiHelper.getAllResultsFromPath(PATH);

    expect(result.results).toHaveLength(11);
    expect(getCalls()).toBe(2);
  });

  it("passes keys through and reports the path", async () => {
    const { apiHelper } = helperFor(4, 4);

    const result = await apiHelper.getAllResultsFromPath(PATH, {
      regionKey: "r1",
      playlistKey: "p1",
    });

    expect(result.path).toBe(PATH);
    expect(result.keys).toEqual({ regionKey: "r1", playlistKey: "p1" });
  });

  it("gives up the collection when a page fails, without throwing", async () => {
    const apiHelper = new ApiHelper("https://example.test");
    // getPath() resolves to null on a failed request rather than rejecting.
    vi.spyOn(apiHelper, "getPath").mockResolvedValue(null);

    const result = await apiHelper.getAllResultsFromPath(PATH);

    // Callers detect the failure by the absent keys/results, and no error
    // escapes to interrupt the screen.
    expect(result).toEqual({});
  });

  it("does not throw when a page rejects", async () => {
    const apiHelper = new ApiHelper("https://example.test");
    vi.spyOn(apiHelper, "getPath").mockRejectedValue(new Error("network"));

    await expect(apiHelper.getAllResultsFromPath(PATH)).resolves.toEqual({});
  });

  it("gives up the collection when the page backstop is reached", async () => {
    const apiHelper = new ApiHelper("https://example.test");
    let calls = 0;

    // A collection that never stops offering a next page, and never returns an
    // empty one - so neither of the two normal termination conditions fires and
    // only the backstop is left.
    vi.spyOn(apiHelper, "getPath").mockImplementation((path) => {
      calls += 1;
      const current = Number(path.match(/page=(\d+)/)?.[1] ?? 1);

      return Promise.resolve(page(ITEMS_PER_PAGE, 1e6, current, current + 1));
    });

    const result = await apiHelper.getAllResultsFromPath(PATH);

    // Bounded, and given up rather than returned as a prefix: a truncated
    // collection in the shape of a complete one reads as a healthy pull to every
    // caller, which then caches it behind the server's checksum (#507).
    expect(calls).toBe(100);
    expect(result).toEqual({});
  });
});

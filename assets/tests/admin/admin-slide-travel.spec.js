import { test, expect } from "@playwright/test";
import {
  beforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  loginTest,
} from "./test-helper.js";
import { emptyJson } from "./data-fixtures.js";

const travelTemplateId = "01FZD7K807VAKZ99BGSSCHRJM6";
const slideId = "01K5E2JGX3G60S73YRGR26W81K";
const feedSourceId = "01K5E2JGX3G60S73YRGR26W81M";

const travelSlideJson = (content) => ({
  "@context": "/contexts/Slide",
  "@id": `/v2/slides/${slideId}`,
  "@type": "Slide",
  title: "Rejseplanen slide",
  description: "",
  templateInfo: {
    "@id": `/v2/templates/${travelTemplateId}`,
    options: [],
  },
  onPlaylists: [],
  published: { from: null, to: null },
  media: [],
  content,
  feed: null,
  id: slideId,
  relationsChecksum: {},
});

const travelSlidesListJson = (content) => ({
  "@id": "/v2/slides",
  "hydra:member": [travelSlideJson(content)],
  "hydra:totalItems": 1,
});

const rejseplanenFeedSourcesJson = {
  "@id": "/v2/feed-sources",
  "hydra:member": [
    {
      "@id": `/v2/feed-sources/${feedSourceId}`,
      "@type": "FeedSource",
      title: "Rejseplanen",
      feedType: "App\\Feed\\RejseplanenFeedType",
      supportedFeedOutputType: "travel",
    },
  ],
  "hydra:totalItems": 1,
};

const rejseplanenFeedSourceJson = {
  "@id": `/v2/feed-sources/${feedSourceId}`,
  "@type": "FeedSource",
  title: "Rejseplanen",
  feedType: "App\\Feed\\RejseplanenFeedType",
  supportedFeedOutputType: "travel",
  secrets: [],
  admin: [
    {
      key: "rejseplanen-station-selector",
      input: "station-selector",
      endpoint: `/v2/feed-sources/${feedSourceId}/config/stations`,
      name: "stations",
      label: "Vælg stoppested",
      helpText: "",
    },
  ],
};

const openTravelSlide = async (page, content) => {
  // Catch-all for API calls the slide editor makes that this spec does not
  // care about. An unmocked call reaches the real API, answers 401 for the
  // fake token and logs the admin out. Registered first so every specific
  // route below (and in loginTest) takes precedence.
  await fulfillEmptyRoutes(page, ["**/v2/**"]);

  await loginTest(page, travelSlidesListJson(content));

  await fulfillDataRoute(page, `**/templates/${travelTemplateId}`, {
    "@id": `/v2/templates/${travelTemplateId}`,
    title: "Rejseplanen",
    id: travelTemplateId,
  });
  await fulfillDataRoute(
    page,
    `**/v2/slides/${slideId}`,
    travelSlideJson(content),
  );
  await fulfillDataRoute(page, `**/slides/${slideId}/playlists*`, emptyJson);
  await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);
  await page.route(
    (url) => url.pathname === "/v2/feed-sources",
    (route) => route.fulfill({ json: rejseplanenFeedSourcesJson }),
  );
  await fulfillDataRoute(
    page,
    `**/v2/feed-sources/${feedSourceId}`,
    rejseplanenFeedSourceJson,
  );

  // Wait for the mocked row itself; a forced click can land before the list
  // has rendered it.
  const editLink = page
    .getByRole("row", { name: /Rejseplanen slide/ })
    .getByRole("link", { name: "Rediger" });
  await expect(editLink).toBeVisible();
  await editLink.click();
  await page.waitForURL("**/slide/edit/*");
  await expect(page.getByText("Rediger slide:")).toBeVisible();
};

test.describe("Travel slide", () => {
  let pageErrors;

  test.beforeEach(async ({ page }) => {
    pageErrors = [];
    page.on("pageerror", (error) => pageErrors.push(error.message));
    await beforeEachTest(page);
  });

  test.afterEach(() => {
    // Opening a slide saved before the template used a feed must not crash the editor.
    expect(pageErrors).toEqual([]);
  });

  test("It searches stations through the feed source config endpoint", async ({
    page,
  }) => {
    const rejseplanenRequests = [];
    page.on("request", (request) => {
      if (request.url().includes("rejseplanen.dk")) {
        rejseplanenRequests.push(request.url());
      }
    });

    await openTravelSlide(page, { title: "Ny" });

    const searches = [];
    await page.route(
      (url) =>
        url.pathname === `/v2/feed-sources/${feedSourceId}/config/stations`,
      (route) => {
        const url = new URL(route.request().url());
        searches.push({
          search: url.searchParams.get("search"),
          authorization: route.request().headers()["authorization"],
        });
        return route.fulfill({
          json: [{ id: "860005301", name: "Aarhus H" }],
        });
      },
    );

    // Pick the feed source the way an editor would.
    const feedSourceDropdown = page.locator(
      '.dropdown-container[aria-labelledby="feedSource"]',
    );
    await feedSourceDropdown.press("Enter");
    await feedSourceDropdown
      .locator(".dropdown-content")
      .getByText("Rejseplanen", { exact: true })
      .click();

    const selector = page.locator("#station-selector");
    await selector.locator(".dropdown-container").press("Enter");
    await selector.locator(".search").locator('[type="text"]').fill("aarhus");

    await expect(
      page.locator(".dropdown-content").getByText("Aarhus H"),
    ).toBeVisible();

    expect(searches.length).toBeGreaterThan(0);
    expect(searches[searches.length - 1].search).toBe("aarhus");
    expect(searches[searches.length - 1].authorization).toMatch(/^Bearer /);
    expect(rejseplanenRequests).toEqual([]);

    // Content-stored stations are gone, so there is nothing deprecated.
    await expect(page.locator("#legacy-station-notice")).toHaveCount(0);
  });

  test("It shows a deprecation notice for stations stored in slide content", async ({
    page,
  }) => {
    await openTravelSlide(page, {
      station: [{ id: "860005301", name: "Aarhus H (Letbane)" }],
    });

    const notice = page.locator("#legacy-station-notice");
    await expect(notice).toBeVisible();
    await expect(notice).toContainText("udfaset");
    await expect(notice).toContainText("Aarhus H (Letbane)");
    await expect(
      notice.getByRole("link", { name: "Opret datakilde" }),
    ).toHaveAttribute("href", /\/feed-sources\/create$/);
  });
});

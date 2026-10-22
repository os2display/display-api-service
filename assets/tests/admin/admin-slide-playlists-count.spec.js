import { test, expect } from "@playwright/test";
import {
  beforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  loginTest,
} from "./test-helper.js";

const templateId = "01FP2SNGFN0BZQH03KCBXHKYHG";
const slideId = "01K8A0000000000000000SLIDE";
const playlistJson = (number) => ({
  "@id": `/v2/playlists/01K8A000000000000PLAYLIST${number}`,
  "@type": "Playlist",
  title: `Spilleliste ${number}`,
  description: "",
  isCampaign: false,
  published: { from: null, to: null },
  slides: `/v2/playlists/01K8A000000000000PLAYLIST${number}/slides`,
  campaignScreens: [],
  campaignScreenGroups: [],
  screens: [],
  tenants: [],
  relationsChecksum: {},
});
const playlists = [playlistJson(1), playlistJson(2)];

const slideJson = (onPlaylists) => ({
  "@id": `/v2/slides/${slideId}`,
  "@type": "Slide",
  title: "Slide på spilleliste",
  description: "",
  templateInfo: { "@id": `/v2/templates/${templateId}`, options: [] },
  theme: "",
  onPlaylists,
  published: { from: null, to: null },
  media: [],
  content: {},
  feed: null,
  id: slideId,
  relationsChecksum: {},
});

const slidesListJson = (onPlaylists) => ({
  "@id": "/v2/slides",
  "hydra:member": [slideJson(onPlaylists)],
  "hydra:totalItems": 1,
});

/**
 * Mock the API for one slide, which starts on the given playlists.
 *
 * @param {object} page The page.
 * @param {Array} onPlaylists The playlists the slide starts on.
 * @param {Function} putPlaylists Answers the PUT of the slide's playlists:
 *   (route, ids, before) => the playlists the slide is on after the save.
 */
const mockSlideApi = async (page, onPlaylists, putPlaylists) => {
  // Catch-all, so no call reaches the real API and logs the admin out.
  await fulfillEmptyRoutes(page, ["**/v2/**"]);
  await fulfillDataRoute(page, `**/templates/${templateId}`, {
    "@id": `/v2/templates/${templateId}`,
    title: "Billede og tekst",
    id: templateId,
  });

  await loginTest(page, slidesListJson(onPlaylists));

  await page.route(
    (url) => url.pathname === "/v2/slides",
    (route) => route.fulfill({ json: slidesListJson(onPlaylists) }),
  );
  await page.route(
    (url) => url.pathname === `/v2/slides/${slideId}`,
    (route) => route.fulfill({ json: slideJson(onPlaylists) }),
  );
  await page.route(
    (url) => url.pathname === `/v2/slides/${slideId}/playlists`,
    async (route) => {
      if (route.request().method() === "PUT") {
        // Answer after the editor has moved on, as a slow server would.
        await new Promise((resolve) => {
          setTimeout(resolve, 500);
        });
        onPlaylists = await putPlaylists(
          route,
          route
            .request()
            .postDataJSON()
            .map(({ playlist: id }) => `/v2/playlists/${id}`),
          onPlaylists,
        );
        return;
      }
      await route.fulfill({
        json: {
          "@id": `/v2/slides/${slideId}/playlists`,
          "hydra:member": playlists
            .filter((playlist) => onPlaylists.includes(playlist["@id"]))
            .map((playlist) => ({ playlist })),
          "hydra:totalItems": onPlaylists.length,
        },
      });
    },
  );
};

/**
 * Open the slide from the list.
 *
 * @param {object} page The page.
 */
const openSlide = async (page) => {
  await page
    .getByRole("row", { name: /Slide på spilleliste/ })
    .getByRole("link", { name: "Rediger" })
    .click();
  await page.waitForURL("**/slide/edit/*");
};

/**
 * Remove a playlist from the slide in the editor.
 *
 * @param {object} page The page.
 * @param {string} title The playlist title.
 */
const removePlaylist = async (page, title) => {
  await page
    .locator("#add-slide-to-playlist-section")
    .getByRole("row", { name: new RegExp(title) })
    .getByRole("button", { name: "Fjern" })
    .click();
};

const allPlaylists = playlists.map((playlist) => playlist["@id"]);

// #570: after "Gem og luk" the slide list showed the playlist count from
// before the save until the page was reloaded.
test.describe("Slide playlists count", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test("The list shows the saved playlist count after save and close", async ({
    page,
  }) => {
    await mockSlideApi(page, allPlaylists, async (route, ids) => {
      await route.fulfill({ json: [] });
      return ids;
    });

    const count = page
      .getByRole("row", { name: /Slide på spilleliste/ })
      .locator(".list-button");
    await expect(count).toHaveText("2");

    await openSlide(page);
    await removePlaylist(page, "Spilleliste 1");
    await page.locator("#save_slide_an_close").click();

    await page.waitForURL("**/slide/list");
    await expect(count).toHaveText("1");
  });

  test("It stays on the slide when saving its playlists fails", async ({
    page,
  }) => {
    await mockSlideApi(page, allPlaylists, async (route, ids, before) => {
      await route.fulfill({ status: 500, json: {} });
      return before;
    });

    await openSlide(page);
    await removePlaylist(page, "Spilleliste 1");
    await page.locator("#save_slide_an_close").click();

    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(page).toHaveURL(/slide\/edit/);
    await expect(page.locator("#save_slide_an_close")).toBeEnabled();
  });

  test("It stays on the slide when saving its playlists fails after an earlier save", async ({
    page,
  }) => {
    let puts = 0;
    await mockSlideApi(page, allPlaylists, async (route, ids, before) => {
      puts += 1;
      if (puts === 1) {
        await route.fulfill({ json: [] });
        return ids;
      }
      await route.fulfill({ status: 500, json: {} });
      return before;
    });

    await openSlide(page);
    await removePlaylist(page, "Spilleliste 1");
    const firstSave = page.waitForResponse(
      (response) =>
        response.request().method() === "PUT" &&
        response.url().endsWith("/playlists"),
    );
    await page.locator("#save_slide").click();
    await firstSave;

    await removePlaylist(page, "Spilleliste 2");
    await page.locator("#save_slide_an_close").click();

    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(page).toHaveURL(/slide\/edit/);
    await expect(page.locator("#save_slide_an_close")).toBeEnabled();
  });

  test("A new slide whose playlists fail to save is updated, not created again, on the next save", async ({
    page,
  }) => {
    await fulfillEmptyRoutes(page, ["**/v2/**"]);
    await fulfillDataRoute(page, "**/v2/templates*", {
      "@id": "/v2/templates",
      "hydra:member": [
        {
          "@id": `/v2/templates/${templateId}`,
          title: "Billede og tekst",
          id: templateId,
        },
      ],
      "hydra:totalItems": 1,
    });
    await fulfillDataRoute(page, `**/templates/${templateId}`, {
      "@id": `/v2/templates/${templateId}`,
      title: "Billede og tekst",
      id: templateId,
    });

    await loginTest(page, slidesListJson([]));

    await page.route(
      (url) => url.pathname === "/v2/playlists",
      (route) =>
        route.fulfill({
          json: {
            "@id": "/v2/playlists",
            "hydra:member": playlists,
            "hydra:totalItems": playlists.length,
          },
        }),
    );

    const slideRequests = [];
    await page.route(
      (url) =>
        url.pathname === "/v2/slides" ||
        url.pathname === `/v2/slides/${slideId}`,
      async (route) => {
        const method = route.request().method();
        if (method === "GET") {
          await route.fulfill({
            json:
              new URL(route.request().url()).pathname === "/v2/slides"
                ? slidesListJson([])
                : slideJson([]),
          });
          return;
        }
        slideRequests.push(method);
        await route.fulfill({ json: slideJson([]) });
      },
    );

    const playlistPuts = [];
    await page.route(
      (url) => url.pathname === `/v2/slides/${slideId}/playlists`,
      async (route) => {
        playlistPuts.push(route.request().postDataJSON());
        await route.fulfill(
          playlistPuts.length === 1 ? { status: 500, json: {} } : { json: [] },
        );
      },
    );

    await page.getByLabel("Tilføj nyt slide").first().click();
    await expect(page.getByText("Opret nyt slide:")).toBeVisible();

    const templates = page.locator(
      '.dropdown-container[aria-labelledby="templateInfo"]',
    );
    await templates.press("Enter");
    await templates
      .locator(".dropdown-content")
      .getByText("Billede og tekst")
      .click();

    const playlistSection = page.locator("#add-slide-to-playlist-section");
    await playlistSection.locator(".dropdown-container").press("Enter");
    await playlistSection
      .locator(".dropdown-content")
      .getByText("Spilleliste 1")
      .click();
    await playlistSection.locator(".dropdown-container").press("Escape");

    await page.locator("#save_slide_an_close").click();

    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(page.locator("#save_slide_an_close")).toBeEnabled();
    // The playlist picked before the failed save is still picked.
    await expect(
      playlistSection.getByRole("row", { name: /Spilleliste 1/ }),
    ).toBeVisible();

    await page.locator("#save_slide_an_close").click();
    await page.waitForURL("**/slide/list");

    expect(slideRequests).toEqual(["POST", "PUT"]);
    expect(playlistPuts).toEqual([
      [{ playlist: "01K8A000000000000PLAYLIST1" }],
      [{ playlist: "01K8A000000000000PLAYLIST1" }],
    ]);
  });
});

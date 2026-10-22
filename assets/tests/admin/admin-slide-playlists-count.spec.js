import { test, expect } from "@playwright/test";
import {
  beforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  loginTest,
} from "./test-helper.js";

const templateId = "01FP2SNGFN0BZQH03KCBXHKYHG";
const slideId = "01K8A0000000000000000SLIDE";
const playlist = {
  "@id": "/v2/playlists/01K8A00000000000000PLAYLIST",
  "@type": "Playlist",
  title: "Spilleliste",
  description: "",
  isCampaign: false,
  published: { from: null, to: null },
  slides: "/v2/playlists/01K8A00000000000000PLAYLIST/slides",
  campaignScreens: [],
  campaignScreenGroups: [],
  screens: [],
  tenants: [],
  relationsChecksum: {},
};

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

// #570: after "Gem og luk" the slide list showed the playlist count from
// before the save until the page was reloaded.
test.describe("Slide playlists count", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test("The list shows the saved playlist count after save and close", async ({
    page,
  }) => {
    // What the server holds; the slide starts on one playlist.
    let onPlaylists = [playlist["@id"]];

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
          onPlaylists = route
            .request()
            .postDataJSON()
            .map(({ playlist: id }) => `/v2/playlists/${id}`);
          await route.fulfill({ json: [] });
          return;
        }
        await route.fulfill({
          json: {
            "@id": `/v2/slides/${slideId}/playlists`,
            "hydra:member": onPlaylists.map(() => ({ playlist })),
            "hydra:totalItems": onPlaylists.length,
          },
        });
      },
    );

    const row = page.getByRole("row", { name: /Slide på spilleliste/ });
    await expect(row.locator(".list-button")).toHaveText("1");

    await row.getByRole("link", { name: "Rediger" }).click();
    await page.waitForURL("**/slide/edit/*");
    await page
      .locator("#add-slide-to-playlist-section")
      .getByRole("row", { name: /Spilleliste/ })
      .getByRole("button", { name: "Fjern" })
      .click();
    await page.locator("#save_slide_an_close").click();

    await page.waitForURL("**/slide/list");
    await expect(row.locator(".list-button")).toHaveText("0");
  });
});

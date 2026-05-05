import { test, expect } from "@playwright/test";
import {
  fulfillDataRoute,
  fulfillEmptyRoutes,
  beforeEachTest,
  loginTest,
} from "./test-helper.js";
import {
  imageTextTemplate,
  onlyImageTextListJson,
  slidesJson1,
  slideJson,
  slidesPlaylist,
} from "./data-fixtures.js";

test.describe("Test of admin fetch data hook", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    page.setViewportSize({ width: 600, height: 2600 });
    await fulfillDataRoute(
      page,
      "**/templates?itemsPerPage*",
      onlyImageTextListJson,
    );

    await page.route(
      "**/templates/000YR9PMQC0GMC1TP90V9N07WX",
      async (route) => {
        await route.fulfill(imageTextTemplate);
      },
    );

    await fulfillDataRoute(
      page,
      "**/templates/01FGC8EXSE1KCC1PTR0NHB0H3R",
      imageTextTemplate,
    );

    await fulfillDataRoute(
      page,
      "**/templates/002BAP34VD1EHG0E4J0D2Y00JW",
      imageTextTemplate,
    );

    await fulfillDataRoute(
      page,
      "**/templates/017BG9P0E0103F0TFS17FM016M",
      imageTextTemplate,
    );

    await fulfillDataRoute(
      page,
      "**/templates/016MHSNKCH1PQW1VY615JC19Y3",
      imageTextTemplate,
    );
    await fulfillDataRoute(
      page,
      "**/templates/000BGWFMBS15N807E60HP91JCX",
      imageTextTemplate,
    );

    await loginTest(page, slidesJson1);

    await fulfillDataRoute(
      page,
      "**/templates/01FP2SNGFN0BZQH03KCBXHKYHG",
      imageTextTemplate,
    );

    await fulfillDataRoute(
      page,
      "**/v2/slides/00015Y0ZVC18N407JD07SM0YCF",
      slideJson,
    );

    await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);
    await fulfillDataRoute(
      page,
      "**/slides/00015Y0ZVC18N407JD07SM0YCF/playlists?*",
      slidesPlaylist,
    );

    await Promise.all([
      page.waitForURL("**/slide/edit/*"),
      await page.locator("#edit_button").first().click({ force: true }),
    ]);

    const title = page.getByText("Rediger slide:");
    await title.waitFor();

    await expect(title).toBeVisible();
  });

  test("Test of admin fetch data hook", async ({ page }) => {
    const title = page.locator("#add-slide-to-playlist-section tr");
    // The max items per page is 30: https://github.com/os2display/display-api-service/blob/develop/config/packages/api_platform.yaml#L11
    // And the header is also a <tr
    await expect(title).toHaveCount(32);
  });
});

import { test, expect } from "@playwright/test";
import {
  slideJson,
  templateDataJson,
  fakeToken,
} from "./client-data-fixtures.js";
import {
  clientBeforeEachTest,
  mockContentPipeline,
  fulfillDataRoute,
  gotoClient,
} from "./client-test-helper.js";

test.describe("Client preview mode", () => {
  test.beforeEach(async ({ page }) => {
    await clientBeforeEachTest(page);
  });

  test("Screen preview loads with query param", async ({ page }) => {
    // Mock content pipeline (no login needed for preview).
    await mockContentPipeline(page);

    await gotoClient(page, {
      preview: "screen",
      "preview-id": "SCREEN01234567890123456789",
      "preview-token": fakeToken,
      "preview-tenant": "ABC",
    });

    // In screen preview mode, the screen component should render.
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });

  test("Slide preview renders single slide", async ({ page }) => {
    // Mock the slide endpoint for preview.
    await fulfillDataRoute(page, "**/v2/slides/*", slideJson);

    // Mock template endpoint.
    await fulfillDataRoute(page, "**/v2/templates/*", templateDataJson);

    await gotoClient(page, {
      preview: "slide",
      "preview-id": "SLIDES01234567890123456789",
      "preview-token": fakeToken,
      "preview-tenant": "ABC",
    });

    // In slide preview mode, a screen component should render
    // (slide preview wraps the slide in a fake screen).
    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });
});

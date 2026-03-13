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

  // Verifies that ?preview=screen bypasses the login flow and renders the screen.
  // Preview mode uses the preview-token for API auth instead of the bind key flow.
  // Mock: full content pipeline (no auth needed in preview mode).
  test("Screen preview loads with query param", async ({ page }) => {
    await mockContentPipeline(page);

    await gotoClient(page, {
      preview: "screen",
      "preview-id": "SCREEN01234567890123456789",
      "preview-token": fakeToken,
      "preview-tenant": "ABC",
    });

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });

  // Verifies that ?preview=slide fetches a single slide and wraps it in a
  // fake screen layout via preview.js. Only slide + template endpoints needed.
  test("Slide preview renders single slide", async ({ page }) => {
    await fulfillDataRoute(page, "**/v2/slides/*", slideJson);
    await fulfillDataRoute(page, "**/v2/templates/*", templateDataJson);

    await gotoClient(page, {
      preview: "slide",
      "preview-id": "SLIDES01234567890123456789",
      "preview-token": fakeToken,
      "preview-tenant": "ABC",
    });

    await expect(page.locator(".screen")).toBeVisible({ timeout: 10000 });
  });
});

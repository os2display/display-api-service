import { test, expect } from "@playwright/test";

test("Video 0", async ({ page }) => {
  await page.goto("template/video-0");

  const video = page.locator("video");
  const source = page.locator("source");
  await expect(video).toBeVisible();
  await expect(source).toHaveAttribute(
    "src",
    "/fixtures/template/videos/test.mp4",
  );
});

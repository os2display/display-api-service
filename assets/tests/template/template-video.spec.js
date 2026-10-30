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

test("Video 0 has a black background", async ({ page }) => {
  await page.goto("template/video-0");

  await expect(page.locator(".template-video")).toHaveCSS(
    "background-color",
    "rgb(0, 0, 0)",
  );
});

test("Video 0 background can be themed", async ({ page }) => {
  await page.goto("template/video-0");
  await page.addStyleTag({
    content: ".slide { --video-background-color: rgb(255, 0, 0); }",
  });

  await expect(page.locator(".template-video")).toHaveCSS(
    "background-color",
    "rgb(255, 0, 0)",
  );
});

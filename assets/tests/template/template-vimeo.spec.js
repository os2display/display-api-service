import { test, expect } from "@playwright/test";

test("Vimeo Player 0", async ({ page }) => {
  await page.goto("template/vimeo-player-0");

  const iframe = page.locator("iframe").first();
  await expect(iframe).toBeVisible();
  await expect(iframe).toHaveAttribute(
    "src",
    "https://player.vimeo.com/video/882393277?muted=1&autoplay=1&pip=0&controls=0&loop=1&quality=undefined&app_id=122963&texttrack=undefined",
  );
});

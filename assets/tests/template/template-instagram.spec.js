import { test, expect } from "@playwright/test";

test.describe("instagram-0: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/instagram-0");
  });

  test("Should display changing content", async ({ page }) => {
    let image = await page.locator(".image.media-contain");
    await expect(image).toHaveCSS(
      "background-image",
      new RegExp("fixtures/template/images/mountain1.jpeg"),
    );
    await expect(image).toHaveCSS(
      "animation",
      "1.5s ease 0s 1 normal none running fade-in",
    );
    let userName = await page.locator(".author");
    await expect(userName).toHaveText("username");
    let descriptionText = await page.locator(".description .text");
    await expect(descriptionText).toHaveText(
      /Sed nulla lorem, varius sodales justo ac, ultrices placerat nunc./,
    );
    let firstTag = await page.locator(".tags .tag").nth(0);
    let secondTag = await page.locator(".tags .tag").nth(1);
    await expect(firstTag).toContainText("#mountains");
    await expect(secondTag).toContainText("#horizon");
    let brandTag = await page.locator(".brand-tag");
    await expect(brandTag).toHaveText("#myhashtag");
    await page.waitForTimeout(2000);
    image = await page.locator(".image.media-contain");
    await expect(image).toHaveCSS(
      "background-image",
      new RegExp("fixtures/template/images/mountain1.jpeg"),
    );
    await expect(image).toHaveCSS(
      "animation",
      "1.5s ease 0s 1 normal none running fade-in",
    );
    userName = await page.locator(".author");
    firstTag = await page.locator(".tags .tag").nth(0);
    await expect(firstTag).toContainText("mountains");
    secondTag = await page.locator(".tags .tag").nth(1);
    await expect(secondTag).toContainText("#horizon");
    let thirdTag = await page.locator(".tags .tag").nth(2);
    await expect(thirdTag).toContainText("#sky");
    await expect(userName).toHaveText("username2");
    descriptionText = await page.locator(".description .text");
    await expect(descriptionText).toHaveText(
      /Aenean consequat sem ut tortor auctor, eget volutpat libero consequat. Donec lacinia varius quam, ut efficitur diam ultrices et. Aliquam eget augue at felis rhoncus egestas. Sed porttitor elit a tellus tempus, sed tempus sapien finibus. Nam at dapibus sem. Aliquam sit amet feugiat ex. Ut dapibus, mi eu fermentum dignissim, sem ipsum vulputate est, sit amet euismod orci odio pharetra massa./,
    );
    brandTag = await page.locator(".brand-tag");
    await expect(brandTag).toHaveText("#myhashtag");
    await page.waitForTimeout(3000);
    let video = await page.locator("video");
    await expect(video).toHaveAttribute("autoplay", "");
    await expect(video).toHaveAttribute("loop", "");
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username2");
    descriptionText = await page.locator(".description .text");
    await expect(descriptionText).toHaveText(
      /Interdum et malesuada fames ac ante ipsum primis/,
    );
    firstTag = await page.locator(".tags .tag").nth(0);
    await expect(firstTag).toContainText("#video");
    brandTag = await page.locator(".brand-tag");
    await expect(brandTag).toHaveText("#myhashtag");
  });

  test("Should display brand tag", async ({ page }) => {
    const brandTag = await page.locator(".brand-tag");
    await expect(brandTag).toHaveText("#myhashtag");
  });

  test("Should display brand shape", async ({ page }) => {
    const brandShape = await page.locator(".shape svg");
    await expect(brandShape).toBeVisible();
  });

  test("Should have vertical class set", async ({ page }) => {
    let instagramTemplate = await page.locator(".template-instagram-feed");
    await expect(instagramTemplate).toHaveClass(
      "template-instagram-feed vertical show",
    );
  });
  test("Should have image width style set", async ({ page }) => {
    let instagramTemplate = await page.locator(".template-instagram-feed");
    await expect(instagramTemplate).toHaveCSS("--percentage-wide", "20%");
    await expect(instagramTemplate).toHaveCSS("--percentage-narrow", "80%");
  });
});

test.describe("instagram-1-no-max-entries: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/instagram-1-no-max-entries");
  });

  test("Max entries not set", async ({ page }) => {
    let userName = await page.locator(".author");
    await expect(userName).toHaveText("username1");
    await page.waitForTimeout(1000);
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username2");
    await page.waitForTimeout(1000);
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username3");
    await page.waitForTimeout(1000);
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username4");
    await page.waitForTimeout(1000);
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username5");
    await page.waitForTimeout(1000);
    userName = await page.locator(".author");
    await expect(userName).toHaveText("username5");
  });

  test("Should have landscape class set", async ({ page }) => {
    let instagramTemplate = await page.locator(".template-instagram-feed");
    await expect(instagramTemplate).toHaveClass(
      "template-instagram-feed landscape hide",
    );
  });
  test("Should have image width style set", async ({ page }) => {
    let instagramTemplate = await page.locator(".template-instagram-feed");
    await expect(instagramTemplate).toHaveCSS("--percentage-wide", "40%");
    await expect(instagramTemplate).toHaveCSS("--percentage-narrow", "60%");
  });
});

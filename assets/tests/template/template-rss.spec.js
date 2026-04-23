import { test, expect } from "@playwright/test";

test.describe("rss-0-no-feed-progress: ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/rss-0-no-feed-progress");
  });

  test("Should render the outer container with correct class and background image", async ({
    page,
  }) => {
    const container = page.locator(".template-rss");
    await expect(container).toBeVisible();
    await expect(container).toHaveClass(/media-contain/);
    await expect(container).toHaveCSS("background-image", /mountain1\.jpeg/);
  });

  test("Should not render progress", async ({ page }) => {
    const progress = page.locator(".feed-info--progress-numbers");
    await expect(progress).not.toBeVisible();
  });

  test("Should display the correct title in the feed-info section first entry", async ({
    page,
  }) => {
    const feedTitle = page.locator(".feed-info--title");
    await expect(feedTitle).toBeVisible();
    await expect(feedTitle).toHaveText("Lorem Ipsum");
  });

  test("Should display the main heading inside content block first entry", async ({
    page,
  }) => {
    const heading = page.locator(".title");
    await expect(heading).toBeVisible();
    await expect(heading).toHaveText("Lorem ipsum dolor sit amet.");
  });

  test("Should display the correct description paragraph text first entry", async ({
    page,
  }) => {
    const description = page.locator(".description");
    await expect(description).toBeVisible();
    await expect(description).toHaveText(
      "Aenean scelerisque ligula ante, sed tristique tellus blandit sit amet. Vestibulum sagittis lobortis purus quis tempor. Aliquam pretium vitae risus id condimentum.",
    );
  });
  test("Should display the correct title in the feed-info section second entry", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);

    const feedTitle = page.locator(".feed-info--title");
    await expect(feedTitle).toBeVisible();
    await expect(feedTitle).toHaveText("Lorem Ipsum");
  });

  test("Should display the main heading inside content block second entry", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);

    const heading = page.locator(".title");
    await expect(heading).toBeVisible();
    await expect(heading).toHaveText(
      "Proin tempor lacinia velit, et gravida nunc faucibus eget.",
    );
  });

  test("Should display the correct description paragraph text second entry", async ({
    page,
  }) => {
    await page.waitForTimeout(2100);

    const description = page.locator(".description");
    await expect(description).toBeVisible();
    await expect(description).toHaveText(
      "Etiam lobortis diam purus, a condimentum nunc feugiat nec. Nunc porttitor tortor eget tortor fermentum, ac porttitor nulla imperdiet. Donec feugiat ipsum in purus congue semper. Cras ligula ipsum, porttitor eu neque at, interdum tincidunt tellus.",
    );
  });
});

test("Should render progress", async ({ page }) => {
  await page.goto("/template/rss-1-with-progress");
  const progress = page.locator(".feed-info--progress-numbers");
  await expect(progress).toBeVisible();
});

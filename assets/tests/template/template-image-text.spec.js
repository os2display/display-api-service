import { test, expect } from "@playwright/test";

test("Image Text 0", async ({ page }) => {
  await page.goto(
    "/template/image-text-0"
  );

  await expect(page.getByText("Overskriften er her")).toBeVisible();
  await expect(page.locator(".box")).toBeVisible();
  await expect(page.locator(".background-image")).toBeVisible();
  await expect(page.locator(".background-image")).toHaveCSS("background-image", new RegExp("fixtures/template/images/mountain1.jpeg"));
});

test("Image Text 1", async ({ page }) => {
  await page.goto(
    "template/image-text-1-multiple-images"
  );

  await expect(page.getByText("Slide 14")).toBeVisible();
  await expect(page.locator(".background-image")).toBeVisible();
  await expect(page.locator(".background-image")).toHaveCSS("background-image", new RegExp("fixtures/template/images/mountain1.jpeg"));
  await page.waitForTimeout(7500);
  await expect(page.locator(".background-image")).toHaveCSS("background-image", new RegExp("fixtures/template/images/mountain2.jpeg"));
});

test("Image Text 2", async ({ page }) => {
  await page.goto(
    "template/image-text-2-logo"
  );

  await expect(page.getByText("Slide 1")).toBeVisible();
  await expect(page.locator(".logo.logo-margin.logo-size-m.logo-position-bottom-right")).toBeVisible();
  await expect(page.locator(".logo")).toHaveAttribute("src", new RegExp("fixtures/template/images/mountain1.jpeg"));
});

test("Image Text 3", async ({ page }) => {
  await page.goto(
    "template/image-text-3-font-sizes"
  );

  await expect(page.getByText("Slide 123121")).toBeVisible();
  await expect(page.getByText("Fisk")).toBeVisible();
  await expect(page.getByText("Hest")).toBeVisible();
  await expect(page.getByText("Ræv")).toBeVisible();
  await expect(page.getByText("Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur."))
    .toBeVisible();
  await expect(page.locator(".text h1")).toBeVisible();
  await expect(page.locator(".text h2")).toBeVisible();
  await expect(page.locator(".text h3")).toBeVisible();
});

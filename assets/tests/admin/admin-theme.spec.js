import { test, expect } from "@playwright/test";
import { beforeEachTest, fulfillDataRoute, loginTest } from "./test-helper.js";
import { errorJson, themesJson, themesSingleJson } from "./data-fixtures.js";

test.describe("Theme", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/themes*", themesJson);

    await page.getByRole("link", { name: "Temaer", exact: true }).click();
    await expect(
      page.getByRole("heading", { name: "Temaer", exact: true }),
    ).toBeVisible();
  });

  test("It loads create theme page", async ({ page }) => {
    await page.getByText("Opret nyt tema").click();
    await expect(page.locator("#save_theme")).toBeVisible();
  });

  test("It display error toast on save error", async ({ page }) => {
    await fulfillDataRoute(page, "**/themes", errorJson, 500);
    await page.getByText("Opret nyt tema").click();

    // Displays error toast and stays on page
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).not.toBeVisible();
    await page.locator("#save_theme").click();
    await expect(
      page.locator(".Toastify").locator(".Toastify__toast--error"),
    ).toBeVisible();
    await expect(
      page
        .locator(".Toastify")
        .locator(".Toastify__toast--error")
        .getByText(/An error occurred/)
        .first(),
    ).toBeVisible();
    await expect(page).toHaveURL(/themes\/create/);
  });

  test("It cancels create theme", async ({ page }) => {
    await page.getByText("Opret nyt tema").click();
    await expect(page.locator("#cancel_theme")).toBeVisible();
    await page.locator("#cancel_theme").click();
    await expect(page.locator("#cancel_theme")).not.toBeVisible();
  });

  test("It loads themes list", async ({ page }) => {
    await expect(page.locator("table").locator("tbody")).not.toBeEmpty();
    await expect(page.locator("tbody").locator("tr td").first()).toBeVisible();
  });

  test("It goes to edit", async ({ page }) => {
    await expect(page.locator("#themeTitle")).not.toBeVisible();

    await fulfillDataRoute(page, "**/themes/*", themesSingleJson);

    await page.locator("tbody").locator("tr td a").first().click();
    await expect(page.locator("#themeTitle")).toBeVisible();
  });

  test("It opens delete modal", async ({ page }) => {
    await expect(page.locator("#delete-modal")).not.toBeVisible();
    await page
      .locator("tbody")
      .nth(0)
      .locator(".remove-from-list")
      .nth(1)
      .click();
    await expect(page.locator("#delete-modal")).toBeVisible();
  });

  test("The correct amount of column headers loaded", async ({ page }) => {
    await expect(page.locator("thead").locator("th")).toHaveCount(5);
  });
});

test.describe("Theme css editor", () => {
  const highlightSize = (page, name) =>
    page.evaluate((key) => CSS.highlights.get(key)?.size ?? 0, name);

  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
    await loginTest(page);
    await fulfillDataRoute(page, "**/themes*", themesJson);
    await page.getByRole("link", { name: "Temaer", exact: true }).click();
    await page.getByText("Opret nyt tema").click();
  });

  test("It highlights valid nested css without problems", async ({ page }) => {
    await page
      .getByLabel("Temaets CSS")
      .fill("#SLIDE_ID {\n  .text { color: red; }\n}");

    await expect.poll(() => highlightSize(page, "css-editor-property")).toBe(1);
    expect(await highlightSize(page, "css-editor-error")).toBe(0);
    await expect(page.locator(".css-editor__diagnostic")).toHaveCount(0);
  });

  test("It lists and underlines a problem and selects it on click", async ({
    page,
  }) => {
    const css = page.getByLabel("Temaets CSS");
    await css.fill("#SLIDE_ID { color: red; }\n/* x");

    const problem = page.locator(".css-editor__diagnostic");
    await expect(problem).toHaveCount(1);
    await expect(problem).toContainText("Kommentaren er ikke lukket");
    expect(await highlightSize(page, "css-editor-error")).toBe(1);
    await expect(css).toHaveAttribute("aria-invalid", "true");

    await problem.getByRole("button", { name: "Linje 2" }).click();
    await expect(css).toBeFocused();
    expect(await css.evaluate((el) => el.selectionStart)).toBe(26);
  });

  test("It scrolls the field to a problem far down when clicked", async ({
    page,
  }) => {
    const css = page.getByLabel("Temaets CSS");
    const rules = Array.from(
      { length: 60 },
      (_, i) => `#SLIDE_ID .r${i} { color: red; }`,
    ).join("\n");
    await css.fill(`${rules}\nh2 { margin: 0 }`);
    await css.evaluate((el) => {
      el.scrollTop = 0;
    });

    await page.getByRole("button", { name: "Linje 61" }).click();

    // Line 61 is inside the visible part of the field, and the gutter and
    // highlight mirror scrolled with it.
    const { lineTop, scrollTop, clientHeight, gutter, mirror } =
      await css.evaluate((el) => ({
        lineTop: 60 * parseFloat(getComputedStyle(el).lineHeight),
        scrollTop: el.scrollTop,
        clientHeight: el.clientHeight,
        gutter: document.querySelector(".css-editor__gutter").scrollTop,
        mirror: document.querySelector(".css-editor__mirror").scrollTop,
      }));
    expect(lineTop).toBeGreaterThanOrEqual(scrollTop);
    expect(lineTop).toBeLessThan(scrollTop + clientHeight);
    expect(gutter).toBe(scrollTop);
    expect(mirror).toBe(scrollTop);
  });

  test("It keeps problems in view above the save bar while typing", async ({
    page,
  }) => {
    const css = page.getByLabel("Temaets CSS");
    await css.click();
    await css.pressSequentially("h2 { margin: 0 }");

    const panel = page.locator(".css-editor__diagnostics");
    await expect(panel).toContainText("Linje 1");
    const panelBox = await panel.boundingBox();
    const footerBox = await page.locator(".sticky-footer").boundingBox();
    expect(panelBox.y).toBeGreaterThanOrEqual(0);
    expect(panelBox.y + panelBox.height).toBeLessThanOrEqual(footerBox.y + 1);
  });

  test("It warns about a selector without #SLIDE_ID", async ({ page }) => {
    await page.getByLabel("Temaets CSS").fill("h1 { color: red; }");

    await expect(page.locator(".css-editor__diagnostic")).toContainText(
      'Selektoren "h1" gælder hele skærmen',
    );
    expect(await highlightSize(page, "css-editor-warning")).toBe(1);
  });

  test("It still saves with problems and shows the api error", async ({
    page,
  }) => {
    await fulfillDataRoute(
      page,
      "**/themes",
      {
        "@type": "ConstraintViolationList",
        "hydra:title": "An error occurred",
        "hydra:description":
          'css: The css has a comment that is not closed: add "*/" after "/* x".',
      },
      422,
    );
    await page.getByLabel("Temaets CSS").fill("/* x");
    await expect(page.locator(".css-editor__diagnostic")).toHaveCount(1);

    await page.locator("#save_theme").click();

    await expect(
      page
        .locator(".Toastify__toast--error")
        .getByText(/comment that is not closed/)
        .first(),
    ).toBeVisible();
  });
});

import { test, expect } from "@playwright/test";

/**
 * Serve the given css as the theme of the image-text-4-test-theme fixture.
 *
 * @param {object} page The page.
 * @param {string} css The theme css.
 */
async function gotoWithTheme(page, css) {
  await page.route("**/fixtures/example.css", (route) =>
    route.fulfill({ contentType: "text/css", body: css }),
  );

  await page.goto("/template/image-text-4-test-theme");
}

test.describe("theme css", async () => {
  test("Should render a slide with a valid theme", async ({ page }) => {
    await gotoWithTheme(
      page,
      "/* A comment */\n#SLIDE_ID .text { color: rgb(0, 0, 255); }",
    );

    await expect(page.getByText("Overskriften er her")).toBeVisible();
  });

  test("Should render a slide whose theme has an unterminated comment", async ({
    page,
  }) => {
    await gotoWithTheme(
      page,
      "/* Ændre baggrundsfarve til video\n\n#SLIDE_ID .template-video {\n\nbackground-color: black\n\n}\n",
    );

    await expect(page.getByText("Overskriften er her")).toBeVisible();
  });

  test("Should apply nested theme rules", async ({ page }) => {
    await gotoWithTheme(
      page,
      "#SLIDE_ID { .text { color: rgb(0, 0, 255); } & .box { letter-spacing: 3px; } }",
    );

    await expect(page.locator(".text").first()).toHaveCSS(
      "color",
      "rgb(0, 0, 255)",
    );
    await expect(page.locator(".box").first()).toHaveCSS(
      "letter-spacing",
      "3px",
    );
  });

  test("Should let a theme override a template rule of equal specificity", async ({
    page,
  }) => {
    // image-text.scss sets `.template-image-text .box { padding: 2% }`.
    await gotoWithTheme(page, ".template-image-text .box { padding: 7px; }");

    await expect(page.locator(".box").first()).toHaveCSS("padding-top", "7px");
  });
});

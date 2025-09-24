import { test, expect } from "@playwright/test";
import {
  beforeEachTest,
  fulfillDataRoute,
  fulfillEmptyRoutes,
  loginTest,
} from "./test-helper.js";
import {
  imageTextTemplate,
  onlyImageTextListJson,
  slideJson,
  slidesJson1,
} from "./data-fixtures.js";

test.describe("Admin form ui tests", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page);

    await fulfillDataRoute(page, "**/templates*", onlyImageTextListJson);
    await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);
    await fulfillDataRoute(
      page,
      "**/templates/01FP2SNGFN0BZQH03KCBXHKYHG",
      imageTextTemplate,
    );

    await Promise.all([
      page.waitForURL("**/slide/create"),
      await page.getByLabel("Tilføj nyt slide").first().click(),
    ]);

    const header = page.getByText("Opret nyt slide:");
    await header.waitFor();
    await expect(header).toBeVisible();

    // Pick tempalte
    await page
      .locator("#template-section")
      .locator(".dropdown-container")
      .nth(0)
      .press("Enter");

    await page.locator(".dropdown-content").locator("li").nth(0).click();
    await page
      .locator("#template-section")
      .locator(".dropdown-container")
      .nth(0)
      .click();
  });

  test("Should fill title", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          title: "Test slide",
        });
      }
    });

    const title = page.locator("textarea#title");
    await expect(title).toBeVisible();
    await title.fill("Test slide");

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have visible text editor for description", async ({ page }) => {
    await expect(page.locator(".text-editor")).toBeVisible();
  });

  test("Should pick font size", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          fontSize: "font-size-m",
        });
      }
    });
    const fontSize = page.locator("#fontSize");
    expect(fontSize).toBeVisible();
    await fontSize.selectOption("font-size-m");

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have image picker visible", async ({ page }) => {
    await expect(
      page.getByText(
        "Vælg filer ved at trykke på eller trække filer ind i firkanten",
      ),
    ).toBeVisible();
  });

  test("Should have media contain visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          mediaContain: true,
        });
      }
    });

    const mediaContain = page.locator("#checkbox-mediaContain");
    await mediaContain.waitFor();
    await mediaContain.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have duration visible and interactable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          duration: 10000,
        });
      }
    });

    await expect(page.locator("#duration")).toBeVisible();
    await page.locator("#duration").fill("10");

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have box align visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          boxAlign: "right",
        });
      }
    });

    const boxAlign = await page.locator("#boxAlign");
    await boxAlign.waitFor();
    await boxAlign.selectOption("right", { force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have box margin visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          boxMargin: true,
        });
      }
    });

    const boxMargin = await page.locator("#checkbox-boxMargin");
    await boxMargin.waitFor();
    await boxMargin.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have separator visible and checkable, when checked, alternative layout should be visible", async ({
    page,
  }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          separator: true,
        });
      }
    });

    await expect(
      page.getByText("Alternativt layout uden tekstboks"),
    ).toHaveCount(0);
    const separator = await page.locator("#checkbox-separator");
    await separator.waitFor();
    await separator.check({ force: true });
    await expect(
      page.getByText("Alternativt layout uden tekstboks"),
    ).toHaveCount(1);

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have halfsize visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          halfSize: true,
        });
      }
    });

    const halfSize = await page.locator("#checkbox-halfSize");
    await halfSize.waitFor();
    await halfSize.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have shadow visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          shadow: true,
        });
      }
    });
    const shadow = await page.locator("#checkbox-shadow");
    await shadow.waitFor();
    await shadow.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have show logo visible and checkable, when checked size/position/margin should be visible", async ({
    page,
  }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON();
        expect(postData.content).toMatchObject({
          showLogo: true,
        });
      }
    });
    await expect(page.getByText("Logostørrelse")).toHaveCount(0);
    await expect(page.getByText("Logoposition")).toHaveCount(0);
    await expect(page.getByText("Margin om logo")).toHaveCount(0);

    const showLogo = await page.locator("#checkbox-showLogo");
    await showLogo.waitFor();
    await showLogo.check({ force: true });

    await expect(page.getByText("Logostørrelse")).toHaveCount(1);
    await expect(page.getByText("Logoposition")).toHaveCount(1);
    await expect(page.getByText("Margin om logo")).toHaveCount(1);

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });
});

test.describe("Admin slide values depending on other values", () => {
  test.beforeEach(async ({ page }) => {
    await beforeEachTest(page);
  });

  test.beforeEach(async ({ page }) => {
    await loginTest(page, slidesJson1);
    await fulfillDataRoute(page, "**/templates*", onlyImageTextListJson);

    await fulfillDataRoute(
      page,
      "**/templates/01FP2SNGFN0BZQH03KCBXHKYHG",
      imageTextTemplate,
    );

    await fulfillDataRoute(
      page,
      "**/v2/slides/00015Y0ZVC18N407JD07SM0YCF",
      slideJson,
    );
    await fulfillEmptyRoutes(page, ["**/playlists*", "**/themes*"]);

    await Promise.all([
      page.waitForURL("**/slide/edit/*"),
      await page.locator("#edit_button").first().click({ force: true }),
    ]);
  });

  test("Should have filled title", async ({ page }) => {
    const title = page.locator("textarea#title");
    await title.waitFor();
    await expect(title).toBeVisible();
    await expect(title).toHaveValue("Title");
  });

  test("Should have visible text editor for description", async ({ page }) => {
    await expect(page.locator(".text-editor")).toBeVisible();
  });

  test("Should pick font size", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          fontSize: "font-size-m",
        });
      }
    });
    const fontSize = page.locator("#fontSize");
    expect(fontSize).toBeVisible();
    await fontSize.selectOption("font-size-m");

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have image picker visible", async ({ page }) => {
    await expect(
      page.getByText(
        "Vælg filer ved at trykke på eller trække filer ind i firkanten",
      ),
    ).toBeVisible();
  });

  test("Should have media contain visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          mediaContain: true,
        });
      }
    });

    const mediaContain = page.locator("#checkbox-mediaContain");
    await mediaContain.waitFor();
    await mediaContain.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have duration visible and interactable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          duration: 10000,
        });
      }
    });

    await expect(page.locator("#duration")).toBeVisible();
    await page.locator("#duration").fill("10");

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have box align visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          boxAlign: "right",
        });
      }
    });

    const boxAlign = await page.locator("#boxAlign");
    await boxAlign.waitFor();
    await boxAlign.selectOption("right", { force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have box margin visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          boxMargin: true,
        });
      }
    });

    const boxMargin = await page.locator("#checkbox-boxMargin");
    await boxMargin.waitFor();
    await boxMargin.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have separator visible and checkable, when checked, alternative layout should be visible", async ({
    page,
  }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          separator: true,
        });
      }
    });

    await expect(
      page.getByText("Alternativt layout uden tekstboks"),
    ).toHaveCount(0);
    const separator = await page.locator("#checkbox-separator");
    await separator.waitFor();
    await separator.check({ force: true });
    await expect(
      page.getByText("Alternativt layout uden tekstboks"),
    ).toHaveCount(1);

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have halfsize visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          halfSize: true,
        });
      }
    });

    const halfSize = await page.locator("#checkbox-halfSize");
    await halfSize.waitFor();
    await halfSize.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have shadow visible and checkable", async ({ page }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON(); // Parses JSON body
        expect(postData.content).toMatchObject({
          shadow: true,
        });
      }
    });
    const shadow = await page.locator("#checkbox-shadow");
    await shadow.waitFor();
    await shadow.check({ force: true });

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });

  test("Should have show logo visible and checkable, when checked size/position/margin should be visible", async ({
    page,
  }) => {
    await page.route("**/v2/slides", async (route, request) => {
      if (request.method() === "POST") {
        const postData = request.postDataJSON();
        expect(postData.content).toMatchObject({
          showLogo: true,
        });
      }
    });
    await expect(page.getByText("Logostørrelse")).toHaveCount(0);
    await expect(page.getByText("Logoposition")).toHaveCount(0);
    await expect(page.getByText("Margin om logo")).toHaveCount(0);

    const showLogo = await page.locator("#checkbox-showLogo");
    await showLogo.waitFor();
    await showLogo.check({ force: true });

    await expect(page.getByText("Logostørrelse")).toHaveCount(1);
    await expect(page.getByText("Logoposition")).toHaveCount(1);
    await expect(page.getByText("Margin om logo")).toHaveCount(1);

    const saveButton = page.locator("#save_slide");
    await saveButton.waitFor();
    await saveButton.click({ force: true });
  });
});

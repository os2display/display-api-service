import { test, expect } from "@playwright/test";

test.describe("Travel-one-station: UI tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/travel-one-station");
  });

  test("Should display the correct heading", async ({ page }) => {
    const heading = page.locator(".info-box .header h1");
    await expect(heading).toHaveText("Én station");
  });

  test("Should display the info text", async ({ page }) => {
    const text = page.locator(".info-box .text p");
    await expect(text).toHaveText("Tekst på slide med én station");
  });

  test("Should display the distance correctly", async ({ page }) => {
    const label = page.locator(".info-box .distance div:first-child");
    const value = page.locator(".info-box .distance .text");
    await expect(label).toHaveText("Afstand");
    await expect(value).toHaveText("43 km");
  });

  test("Should display fast time correctly", async ({ page }) => {
    const label = page.locator(".info-box .time-fast div:first-child");
    const value = page.locator(".info-box .time-fast .text");
    await expect(label).toHaveText("Tid (hurtig)");
    await expect(value).toHaveText("3-23 minutter");
  });

  test("Should display moderate time correctly", async ({ page }) => {
    const label = page.locator(".info-box .time-moderat div:first-child");
    const value = page.locator(".info-box .time-moderat .text");
    await expect(label).toHaveText("Tid (moderat)");
    await expect(value).toHaveText("15-37 minutter");
  });

  test("Should display the map with correct background image", async ({
    page,
  }) => {
    const backgroundImage = page.locator(".map");
    await expect(backgroundImage).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should load the iframe with correct title and src", async ({
    page,
  }) => {
    const iframe = page.locator("iframe");
    await expect(iframe).toHaveAttribute("title", "iframe title");
    await expect(iframe).toHaveAttribute(
      "src",
      "https://webapp.rejseplanen.dk/bin/help.exe/mn?L=vs_tus.vs_new&station=860005301&tpl=monitor&stopFrequency=low&preview=50&offsetTime=1&maxJourneys=6&p1=letbane&p1title=Aarhus+H+%28Letbane%29&p1icons=null&monitorLayout=night",
    );
  });
});

test.describe("Travel-multiple-stations: UI tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/travel-multiple-stations");
  });

  test("Infobox not displayed", async ({ page }) => {
    const infoBox = page.locator(".info-box");
    // Test that it does not exists
    await expect(infoBox).toHaveCount(0);
  });

  test("Should not display background image", async ({ page }) => {
    const backgroundImage = page.locator(".map");
    // Test that it does not exists
    await expect(backgroundImage).toHaveCount(0);
  });

  test("Should load the iframe with correct title and src", async ({
    page,
  }) => {
    const iframe = page.locator("iframe");
    const growClass = page.locator(".iframe.grow");
    await expect(growClass).toBeVisible();
    await expect(iframe).toHaveAttribute("title", "iframe title");
    await expect(iframe).toHaveAttribute(
      "src",
      "https://webapp.rejseplanen.dk/bin/help.exe/mn?L=vs_tus.vs_new&station=751434104%40503000201%4053014%4044061%403342%403269%4041565%40813041802&tpl=monitor&stopFrequency=low&preview=50&offsetTime=1&maxJourneys=13&p1=bus&p1title=Titel+til+iframe",
    );
  });
});

test.describe("Travel-spacious-info-box: UI tests", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/travel-spacious-info-box");
  });

  test("Infobox class grow displayed", async ({ page }) => {
    const growClass = page.locator(".info-box.grow");
    await expect(growClass).toBeVisible();
  });
  test("Should display the correct heading", async ({ page }) => {
    const heading = page.locator(".info-box .header h1");
    await expect(heading).toHaveText("Stor infoboks!");
  });
  test("Should display the info text", async ({ page }) => {
    const text = page.locator(".info-box .text p");
    await expect(text).toHaveText("Tekst på slide med stor infoboks!");
  });

  test("Should display the distance correctly", async ({ page }) => {
    const label = page.locator(".info-box .distance div:first-child");
    const value = page.locator(".info-box .distance .text");
    await expect(label).toHaveText("Afstand");
    await expect(value).toHaveText("43 km");
  });

  test("Should display fast time correctly", async ({ page }) => {
    const label = page.locator(".info-box .time-fast div:first-child");
    const value = page.locator(".info-box .time-fast .text");
    await expect(label).toHaveText("Tid (hurtig)");
    await expect(value).toHaveText("3-23 minutter");
  });

  test("Should display moderate time correctly", async ({ page }) => {
    const label = page.locator(".info-box .time-moderat div:first-child");
    const value = page.locator(".info-box .time-moderat .text");
    await expect(label).toHaveText("Tid (moderat)");
    await expect(value).toHaveText("15-37 minutter");
  });
  test("Should not display background image", async ({ page }) => {
    const backgroundImage = page.locator(".map");
    // Test that it does not exists
    await expect(backgroundImage).toBeEmpty();
  });
});

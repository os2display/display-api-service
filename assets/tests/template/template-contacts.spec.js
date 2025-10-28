import { test, expect } from "@playwright/test";

test("contacts-not-underlined - ui test", async ({ page }) => {
  await page.goto("/template/contacts-not-underlined");

  const header = page.locator("h1");
  await expect(header).toHaveText("Kontakter");

  const separator = page.locator(".separator");
  await expect(separator).not.toBeVisible();
});

test.describe("contacts-underlined - ui test", async () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/template/contacts-underlined");
  });

  test("Should display separator", async ({ page }) => {
    const separator = page.locator(".separator");
    await expect(separator).toBeVisible();
  });

  test("Should display the correct header", async ({ page }) => {
    const header = await page.locator("header h1");
    await expect(header).toHaveText(/Kontakter/);
  });

  test("Should render exactly 6 contacts", async ({ page }) => {
    const contacts = await page.locator(".contact");
    await expect(contacts).toHaveCount(6);
  });

  test("Should render the first contact with correct name and title", async ({
    page,
  }) => {
    const firstContact = page.locator(".contact").nth(0);
    await expect(firstContact.locator(".contact-text div").nth(0)).toHaveText(
      "Chief of Medicine",
    );
    await expect(firstContact.locator(".contact-text div").nth(1)).toHaveText(
      "Bob Kelso",
    );
    await expect(firstContact.locator(".contact-text div").nth(2)).toHaveText(
      "kelso@@hospital.com",
    );
    await expect(firstContact.locator(".contact-text div").nth(3)).toHaveText(
      "55510001",
    );
    await expect(firstContact.locator(".contact-image")).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/author.jpg"),
    );
  });

  test("Should render the second contact with correct name and title", async ({
    page,
  }) => {
    const secondContact = page.locator(".contact").nth(1);
    await expect(secondContact.locator(".contact-text div").nth(0)).toHaveText(
      "Custodial Engineer",
    );
    await expect(secondContact.locator(".contact-text div").nth(1)).toHaveText(
      "The Janitor",
    );
    await expect(secondContact.locator(".contact-text div").nth(2)).toHaveText(
      "janitor@@hospital.com",
    );
    await expect(secondContact.locator(".contact-text div").nth(3)).toHaveText(
      "55510002",
    );
    await expect(secondContact.locator(".contact-image")).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/logo.png"),
    );
  });

  test("Should render the third contact with correct name and title", async ({
    page,
  }) => {
    const thirdContact = page.locator(".contact").nth(2);
    await expect(thirdContact.locator(".contact-text div").nth(0)).toHaveText(
      "Resident Doctor",
    );
    await expect(thirdContact.locator(".contact-text div").nth(1)).toHaveText(
      "Elliot Reid",
    );
    await expect(thirdContact.locator(".contact-text div").nth(2)).toHaveText(
      "ereid@@hospital.com",
    );
    await expect(thirdContact.locator(".contact-text div").nth(3)).toHaveText(
      "55510003",
    );
    await expect(thirdContact.locator(".contact-image")).toHaveCSS(
      "background-image",
      new RegExp("/fixtures/template/images/mountain1.jpeg"),
    );
  });

  test("Should render the fourth contact with correct name and title", async ({
    page,
  }) => {
    const fourthContact = page.locator(".contact").nth(3);
    await expect(fourthContact.locator(".contact-text div").nth(0)).toHaveText(
      "",
    );
    await expect(fourthContact.locator(".contact-text div").nth(1)).toHaveText(
      "Christopher Turk",
    );
    await expect(fourthContact.locator(".contact-text div").nth(2)).toHaveText(
      "turk@hospital.com",
    );
    await expect(fourthContact.locator(".contact-text div").nth(3)).toHaveText(
      "55510004",
    );
    await expect(fourthContact.locator(".contact-image")).toHaveCSS(
      "background-image",
      "none",
    );
  });

  test("Should render image as background image for first three contacts", async ({
    page,
  }) => {
    for (let i = 0; i < 3; i++) {
      const imageDiv = page
        .locator(".contact")
        .nth(i)
        .locator(".contact-image");
      const bgImage = await imageDiv.evaluate(
        (el) => getComputedStyle(el).backgroundImage,
      );
      expect(bgImage).toContain("/fixtures/template/images/");
    }
  });

  test("Should set media-contain class", async ({ page }) => {
    expect(page.locator(".media-contain")).toHaveCount(3);
  });

  test("Should render last 3 contacts with SVG instead of background image", async ({
    page,
  }) => {
    for (let i = 3; i < 6; i++) {
      const svg = page.locator(".contact").nth(i).locator("svg");
      await expect(svg).toBeVisible();
    }
  });
});

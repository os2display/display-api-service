import { test, expect } from "@playwright/test";

// Fixed time since the calendar template filters events older than now.
const fixTime = async (page) => {
  const newDate = new Date();
  newDate.setMonth(8);
  newDate.setDate(15);
  newDate.setHours(6);
  newDate.setMinutes(0);
  await page.clock.install({ time: newDate });
};

test("calendar-0-multiple-days: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-0-multiple-days");

  await expect(page.getByText("Kalender")).toBeVisible();
  await expect(page.getByText("Cake is a lie")).toBeVisible();
  await expect(page.getByText("Det tomme rum")).toBeVisible();
  await expect(page.getByText("Cake is gone")).toBeVisible();
  await expect(page.getByText("Det fulde rum")).toBeVisible();
  await expect(page.getByText("Se mere på localhost/events")).toBeVisible();

  await expect(page.locator(".content > section")).toHaveCount(5);
  await expect(page.locator(".font-size-m")).toHaveCount(1);

  await expect(page.locator(".calendar-multiple-days")).toHaveCSS(
    "--bg-image",
    "",
  );
});

test("calendar-1-multiple-days: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-1-multiple-days");

  await expect(page.locator(".content")).toBeEmpty();
  await expect(page.locator(".footer")).toHaveCount(0);
  await expect(page.locator(".font-size-xl")).toHaveCount(1);
  await expect(page.locator(".calendar-multiple-days")).toHaveCSS(
    "--bg-image",
    new RegExp("/fixtures/template/images/mountain1.jpeg"),
  );
});

test("calendar-0-multiple: ui test", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-0-multiple");

  // hideGrid true
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-bottom",
    "0px none rgb(0, 0, 0)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-right",
    "0px none rgb(0, 0, 0)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-left",
    "0px none rgb(0, 0, 0)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-top",
    "0px none rgb(0, 0, 0)",
  );
  await expect(page.locator(".header-title")).toHaveText(
    "Møder i dag på Bautavej",
  );

  // fontSize: "font-size-xl"
  await expect(page.locator(".font-size-xl")).toHaveCount(1);

  // hasDateAndTime: true
  // dateAsBox: false,
  await expect(page.locator(".header-date")).toHaveCount(1);
  await expect(page.locator(".header-date-box")).toHaveCount(0);
  await expect(page.locator(".header-date")).toHaveText(new RegExp("06:"));

  await expect(page.getByText("Cake is in the past")).toHaveCount(0);
  await expect(page.locator(".content-item")).toHaveCount(3);

  // headerOrder: "whatwherewhen",
  await expect(page.getByText("Hvad", { exact: true })).toHaveCSS("order", "1");
  await expect(page.getByText("Hvor", { exact: true })).toHaveCSS("order", "2");
  await expect(page.getByText("Hvornår", { exact: true })).toHaveCSS(
    "order",
    "3",
  );

  await expect(page.getByText("Det tredje rum")).toBeVisible();
  await expect(page.getByText("Coffee")).toBeVisible();
});

test("calendar-1-multiple: ui test", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-1-multiple");

  await expect(page.getByText("Cake is in the past")).toHaveCount(0);

  await expect(page.locator(".header-title")).toHaveCount(1);
  await expect(page.locator(".header-title")).toHaveText("");

  // hideGrid false
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-bottom",
    "1px solid rgb(26, 26, 26)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-right",
    "0px none rgb(0, 0, 0)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-left",
    "1px solid rgb(26, 26, 26)",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-top",
    "0px none rgb(0, 0, 0)",
  );

  // headerOrder: null, (default when what where)
  await expect(page.getByText("Hvornår", { exact: true })).toHaveCSS(
    "order",
    "1",
  );
  await expect(page.getByText("Hvad", { exact: true })).toHaveCSS("order", "2");
  await expect(page.getByText("Hvor", { exact: true })).toHaveCSS("order", "3");

  // fontSize: "font-size-xs"
  await expect(page.locator(".font-size-xs")).toHaveCount(1);

  // hasDateAndTime: true
  // dateAsBox: true,
  await expect(page.locator(".header-date")).toHaveCount(1);
  await expect(page.locator(".header-date-box")).toHaveCount(0);
  await expect(page.locator(".header-date")).toHaveText(new RegExp("06:"));

  await expect(page.locator(".content-item")).toHaveCount(3);
});

test("calendar-2-multiple: ui test", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-2-multiple");

  // hasDateAndTime: false
  // dateAsBox: false,
  await expect(page.locator(".header-date")).toHaveCount(0);
  await expect(page.locator(".header-date-box")).toHaveCount(0);
});

test("calendar-0-single: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-0-single");

  const title = page.locator(".title");
  const subtitle = page.locator(".subtitle");

  await expect(title).toHaveText("Kalender");
  await expect(subtitle).toHaveText("Underoverskrift");
  const items = page.locator(".content-item");

  await expect(items).toHaveCount(3);

  await expect(items.nth(0)).toContainText("07:30 - 08:00Cake is a lie");

  await expect(items.nth(1)).toContainText("07:30 - 08:00Cake");

  await expect(items.nth(2)).toContainText("08:00 - 09:00Det er optaget");

  await expect(page.locator(".media-contain")).toHaveCount(1);
  await expect(page.locator(".template-calendar")).toHaveCSS(
    "--bg-image",
    new RegExp("/fixtures/template/images/mountain1.jpeg"),
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-left",
    "1px solid rgb(26, 26, 26)",
  );
  await expect(page.locator(".media-contain")).toHaveCount(1);
});

test("calendar-1-single: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-1-single");

  const title = page.locator(".title");
  const subtitle = page.locator(".subtitle");

  await expect(title).toHaveText("Kalender");
  await expect(subtitle).toHaveText("Underoverskrift");
  await expect(page.locator(".media-contain")).toHaveCount(0);
});

test("calendar-0-single-booking: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-0-single-booking");
  const title = page.locator(".room-info .title");
  await expect(title).toHaveText("M2.3");
  const status = page.locator(".status");
  await expect(status).toHaveText("Ledigt");
  expect(page.locator("h3")).toContainText("Kommende begivenheder");
  const date = page.locator(".date-time > :nth-child(1)");
  const time = page.locator(".date-time > :nth-child(2)");

  await expect(date).toContainText("september");
  await expect(time).not.toBeEmpty();
  await expect(page.locator(".content-item p")).toHaveText(
    "Straksbooking ikke tilgængeligt",
  );
  await expect(page.locator(".content-item div").first()).toHaveText(
    "Mindre end et minut  til næste begivenhed",
  );
  await expect(page.locator(".content-item").nth(1)).toHaveCSS(
    "border-left",
    "2px solid rgb(0, 0, 0)",
  );
  const events = page.locator(".content .content-item");

  await expect(events.nth(1)).toContainText("There will be cake");
  await expect(events.nth(2)).toContainText("The cake is a lie");
  await expect(events.nth(3)).toContainText("Det er optaget");
});

test("calendar-1-single-booking: ui tests", async ({ page }) => {
  await fixTime(page);

  await page.goto("/template/calendar-1-single-booking");
  await expect(page.getByText("Ledigt")).toHaveCount(1);
  await expect(page.getByText("Ledigt")).toBeVisible();

  await page.waitForTimeout(5500);

  await expect(page.getByText("Optaget")).toHaveCount(1);
  await expect(page.getByText("Optaget")).toBeVisible();
});

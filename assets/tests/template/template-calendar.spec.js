import { test, expect } from "@playwright/test";

// Fixed time since calendar template filters events older than now.
const fixTime = async (page) => {
  const newDate = new Date();
  newDate.setHours(6);
  newDate.setMinutes(0);
  await page.clock.install({ time: newDate });
};

test("Calendar 0", async ({ page }) => {
  await fixTime(page);

  await page.goto(
    "/template/calendar-0-multiple-days"
  );

  await expect(page.getByText("Kalender")).toBeVisible();
  await expect(page.getByText("Cake is a lie")).toBeVisible();
  await expect(page.getByText("Det tomme rum")).toBeVisible();
  await expect(page.getByText("Cake is gone")).toBeVisible();
  await expect(page.getByText("Det fulde rum")).toBeVisible();

  await expect(page.locator('.content > section')).toHaveCount(5);
});

test("Calendar 1", async ({ page }) => {
  await fixTime(page);

  await page.goto(
    "/template/calendar-1-multiple"
  );

  await expect(page.getByText("Cake is in the past")).toHaveCount(0);

  await expect(page.locator('.header-title')).toHaveText("Møder i dag på Bautavej");
  await expect(page.locator('.header-date')).toHaveText(new RegExp("06:00"));
  await expect(page.locator('.content-item')).toHaveCount(3);
  await expect(page.getByText("Hvad")).toBeVisible();
  await expect(page.getByText("Hvornår")).toBeVisible();

  await expect(page.getByText("Det tredje rum")).toBeVisible();
  await expect(page.getByText("Coffee")).toBeVisible();
});

test("Calendar 2", async ({ page }) => {
  await fixTime(page);

  await page.goto(
    "/template/calendar-2-single"
  );

  // TODO
});

test("Calendar 3", async ({ page }) => {
  await fixTime(page);

  await page.goto(
    "/template/calendar-3-multiple-days"
  );

  // TODO
});

test("Calendar 4", async ({ page }) => {
  await fixTime(page);

  await page.goto(
    "/template/calendar-4-single-booking"
  );

  // TODO
});

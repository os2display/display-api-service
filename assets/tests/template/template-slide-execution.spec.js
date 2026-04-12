import { test, expect } from "@playwright/test";

test.describe("useBaseSlideExecution hook", () => {
  test("Should call slideDone after duration expires", async ({ page }) => {
    // book-review-0 has duration: 5000 and uses useBaseSlideExecution.
    const consoleDone = page.waitForEvent("console", {
      predicate: (msg) => msg.text() === "slide done",
      timeout: 10000,
    });

    await page.goto("/template/book-review-0");
    await expect(page.locator(".template-book-review")).toBeVisible();

    const start = Date.now();
    await consoleDone;
    const elapsed = Date.now() - start;

    // Duration is 5000ms. Allow a margin for browser/timer variance.
    expect(elapsed).toBeGreaterThan(4000);
    expect(elapsed).toBeLessThan(8000);
  });

  test("Should not call slideDone if slide is removed before duration", async ({
    page,
  }) => {
    // Navigate to a slide, then navigate away before duration (5000ms) expires.
    let slideDoneCalled = false;

    page.on("console", (msg) => {
      if (msg.text() === "slide done") {
        slideDoneCalled = true;
      }
    });

    await page.goto("/template/book-review-0");
    await expect(page.locator(".template-book-review")).toBeVisible();

    // Navigate away after 1s, well before the 5s duration.
    await page.waitForTimeout(1000);
    await page.goto("/template");

    // Wait a bit past when the timer would have fired.
    await page.waitForTimeout(6000);

    expect(slideDoneCalled).toBe(false);
  });
});

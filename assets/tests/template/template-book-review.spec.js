import { test, expect } from "@playwright/test";

test("Book review 0", async ({ page }) => {
  await page.goto("/template/book-review-0");

  await expect(page.getByText("I bølgen blå")).toBeVisible();
  await expect(page.getByText("Af Hval Ocean")).toBeVisible();
  await expect(page.locator(".author")).toHaveText("Hval Ocean");
  await expect(
    page.getByText(
      "The printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.",
    ),
  ).toBeVisible();

  await expect(page.locator(".author-image")).toHaveCSS(
    "background-image",
    new RegExp("fixtures/template/images/author.jpg"),
  );
  await expect(page.locator(".image-blurry-background")).toHaveCSS(
    "background-image",
    new RegExp("fixtures/template/images/vertical.jpg"),
  );
  await expect(page.locator(".book-image img")).toHaveAttribute(
    "src",
    new RegExp("fixtures/template/images/vertical.jpg"),
  );
});

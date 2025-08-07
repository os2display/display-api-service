import { test, expect } from "@playwright/test";
import contentString from "../../admin/components/util/helpers/content-string.jsx";
import { abortUnhandledRoutes } from "./admin-helper.js";

test.describe("Content string", () => {
  test.beforeEach(async ({ page }) => {
    await abortUnhandledRoutes(page);
  });

  test("It creates a string: 'test and test'", async ({ page }) => {
    expect(contentString([{ name: "test" }, { name: "test" }], "and")).toBe(
      "test and test",
    );
  });

  test("It creates a string: 'test, hest or test'", async ({ page }) => {
    expect(
      contentString(
        [{ name: "test" }, { label: "hest" }, { name: "test" }],
        "or",
      ),
    ).toBe("test, hest or test");
  });

  test("It creates a string: 'test'", async ({ page }) => {
    expect(contentString([{ name: "test" }], "or")).toBe("test");
  });
});

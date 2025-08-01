import { test } from "@playwright/test";
import { loginTest } from "./admin-helper.js";

test.describe("Basic app", () => {
  test("App runs", loginTest);
});

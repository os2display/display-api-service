import { test, expect } from "vitest";
import contentString from "../../admin/components/util/helpers/content-string.jsx";

test.describe("Content string", () => {
  test("It creates a string: 'test and test'", () => {
    expect(contentString([{ name: "test" }, { name: "test" }], "and")).toBe(
      "test and test",
    );
  });

  test("It creates a string: 'test, hest or test'", () => {
    expect(
      contentString(
        [{ name: "test" }, { label: "hest" }, { name: "test" }],
        "or",
      ),
    ).toBe("test, hest or test");
  });

  test("It creates a string: 'test'", () => {
    expect(contentString([{ name: "test" }], "or")).toBe("test");
  });
});

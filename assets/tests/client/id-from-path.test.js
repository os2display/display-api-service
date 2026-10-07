import { describe, it, expect } from "vitest";
import idFromPath from "../../client/util/id-from-path";

describe("idFromPath", () => {
  it("extracts a ULID from a path", () => {
    expect(idFromPath("/v2/screens/01ARZ3NDEKTSV4RRFFQ69G5FAV")).toBe(
      "01ARZ3NDEKTSV4RRFFQ69G5FAV",
    );
  });

  it("returns the first 26-char match when multiple exist", () => {
    expect(
      idFromPath(
        "/v2/screens/01ARZ3NDEKTSV4RRFFQ69G5FAV/playlists/01BRZ3NDEKTSV4RRFFQ69G5FAV",
      ),
    ).toBe("01ARZ3NDEKTSV4RRFFQ69G5FAV");
  });

  it("returns false for a string with no 26-char alphanumeric match", () => {
    expect(idFromPath("/v2/screens/short")).toBe(false);
  });

  it("returns false for an empty string", () => {
    expect(idFromPath("")).toBe(false);
  });

  it("returns false for null", () => {
    expect(idFromPath(null)).toBe(false);
  });

  it("returns false for undefined", () => {
    expect(idFromPath(undefined)).toBe(false);
  });

  it("returns false for a number", () => {
    expect(idFromPath(123)).toBe(false);
  });
});

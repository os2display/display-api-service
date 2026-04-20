import { describe, it, expect } from "vitest";
import idFromPath from "../../client/util/id-from-path";

describe("idFromPath", () => {
  it("extracts a ULID from a path", () => {
    expect(idFromPath("/v2/screens/01ARZ3NDEKTSV4RRFFQ69G5FAV")).toBe(
      "01ARZ3NDEKTSV4RRFFQ69G5FAV"
    );
  });

  it("returns the first 26-char match when multiple exist", () => {
    expect(
      idFromPath("/v2/screens/01ARZ3NDEKTSV4RRFFQ69G5FAV/playlists/01BRZ3NDEKTSV4RRFFQ69G5FAV")
    ).toBe("01ARZ3NDEKTSV4RRFFQ69G5FAV");
  });

  it("returns null for a string with no 26-char alphanumeric match", () => {
    expect(idFromPath("/v2/screens/short")).toBeNull();
  });

  it("returns null for an empty string", () => {
    expect(idFromPath("")).toBeNull();
  });

  it("returns null for null", () => {
    expect(idFromPath(null)).toBeNull();
  });

  it("returns null for undefined", () => {
    expect(idFromPath(undefined)).toBeNull();
  });

  it("returns null for a number", () => {
    expect(idFromPath(123)).toBeNull();
  });
});

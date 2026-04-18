import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import isPublished from "../../client/util/is-published";

describe("isPublished", () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date("2025-06-15T12:00:00Z"));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  describe("both from and to set", () => {
    it("returns true when current time is within window", () => {
      expect(
        isPublished({ from: "2025-06-15T10:00:00Z", to: "2025-06-15T14:00:00Z" })
      ).toBe(true);
    });

    it("returns false when current time is before from", () => {
      expect(
        isPublished({ from: "2025-06-15T13:00:00Z", to: "2025-06-15T14:00:00Z" })
      ).toBe(false);
    });

    it("returns false when current time is after to", () => {
      expect(
        isPublished({ from: "2025-06-15T08:00:00Z", to: "2025-06-15T10:00:00Z" })
      ).toBe(false);
    });
  });

  describe("only from set", () => {
    it("returns true when from is in the past", () => {
      expect(isPublished({ from: "2025-06-15T10:00:00Z" })).toBe(true);
    });

    it("returns false when from is in the future", () => {
      expect(isPublished({ from: "2025-06-15T14:00:00Z" })).toBe(false);
    });
  });

  describe("only to set", () => {
    it("returns true when to is in the future", () => {
      expect(isPublished({ to: "2025-06-15T14:00:00Z" })).toBe(true);
    });

    it("returns false when to is in the past", () => {
      expect(isPublished({ to: "2025-06-15T10:00:00Z" })).toBe(false);
    });
  });

  describe("neither from nor to set", () => {
    it("returns true for empty object", () => {
      expect(isPublished({})).toBe(true);
    });

    it("returns true for null", () => {
      expect(isPublished(null)).toBe(true);
    });

    it("returns true for undefined", () => {
      expect(isPublished(undefined)).toBe(true);
    });
  });
});

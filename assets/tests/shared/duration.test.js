import { describe, it, expect } from "vitest";

import clampDuration, {
  DEFAULT_DURATION,
} from "../../shared/slide-utils/duration";

describe("clampDuration", () => {
  it("keeps a positive finite duration", () => {
    expect(clampDuration(5000)).toBe(5000);
    expect(clampDuration(1)).toBe(1);
  });

  it.each([
    ["undefined", undefined],
    ["null", null],
    ["zero", 0],
    ["negative", -1000],
    ["NaN", NaN],
    ["Infinity", Infinity],
    ["a string", "5000"],
  ])("falls back to the default for %s", (_label, value) => {
    expect(clampDuration(value)).toBe(DEFAULT_DURATION);
  });

  it("defaults to 15s", () => {
    // setTimeout treats undefined and NaN as 0ms, so an unclamped duration
    // would flash the slide past rather than hold it.
    expect(DEFAULT_DURATION).toBe(15000);
  });
});

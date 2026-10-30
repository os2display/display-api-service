import { describe, it, expect } from "vitest";

import isRenderableSlide from "../../client/util/is-renderable-slide";

describe("isRenderableSlide", () => {
  it("accepts a slide with no invalid flag", () => {
    expect(isRenderableSlide({ "@id": "/v2/slides/a" })).toBe(true);
  });

  it("rejects a slide marked invalid", () => {
    expect(isRenderableSlide({ "@id": "/v2/slides/a", invalid: true })).toBe(
      false,
    );
  });

  it("accepts a slide whose invalid flag was cleared", () => {
    // PullStrategy deletes the flag rather than setting it false, but a false
    // must not be read as invalid either.
    expect(isRenderableSlide({ "@id": "/v2/slides/a", invalid: false })).toBe(
      true,
    );
  });

  it("rejects an absent slide", () => {
    // The optional chain this used to be written with answered true for both:
    // a region holding nothing but absent slides therefore counted as having
    // content, which suppressed the fallback image and left the screen black -
    // the same fault the invalid flag exists to prevent, reached from the other
    // side. ScheduleService.checkForEmptyContent and Region share this test, so
    // the hole showed up in both at once.
    expect(isRenderableSlide(null)).toBe(false);
    expect(isRenderableSlide(undefined)).toBe(false);
  });
});

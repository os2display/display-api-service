import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, render } from "@testing-library/react";

import poster from "../../shared/templates/poster.jsx";

// A template's own fade clock and the execution hook's entry clock have to be
// derived from the same number. The hook clamps an unusable duration to 15s; a
// fade timer computed from the raw prop went negative for the same input, fired
// at once, and left the entry sitting faded out for the whole 15s.
const slideWith = (duration) => ({
  executionId: "poster-execution",
  mediaData: {},
  feed: { configuration: {} },
  feedData: [
    {
      title: "First",
      image: { url: "/media/one.jpg" },
      startDate: "2026-09-01T10:00:00.000Z",
      endDate: "2026-09-01T11:00:00.000Z",
    },
    {
      title: "Second",
      image: { url: "/media/two.jpg" },
      startDate: "2026-09-02T10:00:00.000Z",
      endDate: "2026-09-02T11:00:00.000Z",
    },
  ],
  content: { duration },
});

const imageAnimation = (container) =>
  container.querySelector(".image-area")?.style?.animation ?? "";

describe("Poster fade timing follows the clamped entry duration", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("does not fade out immediately when duration is zero", () => {
    const { container } = render(
      poster.renderSlide(slideWith(0), "run-token", vi.fn()),
    );

    act(() => vi.advanceTimersByTime(100));

    expect(imageAnimation(container)).toContain("fade-in");
  });

  it("still fades out before the entry changes when duration is valid", () => {
    const { container } = render(
      poster.renderSlide(slideWith(3000), "run-token", vi.fn()),
    );

    act(() => vi.advanceTimersByTime(100));
    expect(imageAnimation(container)).toContain("fade-in");

    // Fade starts at duration - 500 + 50 = 2550ms.
    act(() => vi.advanceTimersByTime(2500));

    expect(imageAnimation(container)).toContain("fade-out");
  });

  it("holds the entry for the clamped duration when duration is zero", () => {
    const slideDone = vi.fn();
    render(poster.renderSlide(slideWith(0), "run-token", slideDone));

    // Two entries at the 15s fallback: the slide is not done before 30s.
    act(() => vi.advanceTimersByTime(29999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledTimes(1);
  });
});

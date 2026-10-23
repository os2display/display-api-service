import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, cleanup } from "@testing-library/react";
import { useLayoutEffect } from "react";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

// A template that finishes from its own *layout* effect. No shipped template
// does this -- video.jsx, the one that finishes while mounting, uses a passive
// effect -- but it is the case that decides where Slide may record the start of
// a run. Child effects run before parent effects within a phase, so a layout
// effect in Slide would still be too late here; only the render-phase write
// stamps the run start before this signal arrives.
vi.mock("../../shared/slide-utils/templates.js", () => {
  /**
   * Template stub that signals done from a layout effect.
   *
   * @param {object} props - Props.
   * @param {object} props.slide - The slide.
   * @param {number} props.run - Run id.
   * @param {Function} props.slideDone - Called when the slide is done.
   * @returns {object} The component.
   */
  function FinishesInLayoutEffect({ slide, run, slideDone }) {
    useLayoutEffect(() => {
      if (!run) return;

      slideDone(slide);
    }, [run]);

    return <div data-testid={`template-${slide.executionId}`} />;
  }

  return {
    renderSlide: (slide, run, slideDone) => (
      <FinishesInLayoutEffect slide={slide} run={run} slideDone={slideDone} />
    ),
    getConfig: () => ({}),
  };
});

vi.mock("../../client/components/region.scss", () => ({}));
vi.mock("../../client/components/slide.scss", () => ({}));

import Region from "../../client/components/region.jsx";
import { MIN_SLIDE_DWELL_MS } from "../../client/components/slide.jsx";

const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

const slide = {
  executionId: "EXECUTIONA",
  templateData: { id: "template" },
  mediaData: {},
  content: {},
};

describe("Region playing a template that finishes from a layout effect", () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ["setTimeout", "clearTimeout"] });

    // Deliberately not zero: it stands in for a screen that has been up a
    // while, and it is what makes this test discriminate. Move Slide's run
    // start into a layout effect and the record is still {run: null, at: 0}
    // when the template below signals, so the guard misreads the run -- it
    // either measures the dwell from 0, which against a clock reading 5000
    // makes the remaining time negative and advances at once, or it takes the
    // null run for "already handled" and swallows the signal, leaving the
    // region stuck. Both are wrong; only the render-phase write defers by the
    // floor. With the guard's clock left at 0 the arithmetic comes out right by
    // accident in both variants and this test proves nothing.
    vi.spyOn(performance, "now").mockReturnValue(5 * MIN_SLIDE_DWELL_MS);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    cleanup();
  });

  it("does not advance before the dwell floor", () => {
    const { container } = render(
      <Region
        region={{ "@id": `/v2/regions/${REGION_ID}`, gridArea: ["a"] }}
      />,
    );

    act(() => {
      document.dispatchEvent(
        new CustomEvent(`regionContent-${REGION_ID}`, {
          detail: { slides: [slide] },
        }),
      );
    });

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;
    const firstRun = runIdOf();

    expect(firstRun).toBeTruthy();

    // The template has already signalled, from a layout effect on mount.
    expect(runIdOf()).toBe(firstRun);

    act(() => vi.advanceTimersByTime(MIN_SLIDE_DWELL_MS - 1));
    expect(runIdOf()).toBe(firstRun);

    act(() => vi.advanceTimersByTime(1));
    expect(runIdOf()).not.toBe(firstRun);
  });
});

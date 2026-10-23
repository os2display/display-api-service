import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, cleanup } from "@testing-library/react";
import video from "../../shared/templates/video.jsx";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

// Only the template dispatcher is stubbed. The real video template renders, so
// this exercises the actual synchronous finish() it performs when there is
// nothing to play, rather than a stand-in for it.
vi.mock("../../shared/slide-utils/templates.js", () => ({
  renderSlide: (slide, run, slideDone) =>
    video.renderSlide(slide, run, slideDone),
  getConfig: () => ({}),
}));

vi.mock("../../client/components/region.scss", () => ({}));
vi.mock("../../client/components/slide.scss", () => ({}));

import Region from "../../client/components/region.jsx";
import { MIN_SLIDE_DWELL_MS } from "../../client/components/slide.jsx";

const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

const videoSlide = {
  executionId: "VIDEOEXECUTION",
  templateData: { id: "video" },
  mediaData: {},
  content: { video: [] },
};

describe("Region playing a video slide with no playable media", () => {
  beforeEach(() => {
    vi.useFakeTimers();

    // Hold the guard's clock still so every run is deferred by the full floor,
    // making the number of replays a function of the fake timers alone.
    vi.spyOn(performance, "now").mockReturnValue(0);
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    cleanup();
  });

  it("replays at the dwell floor rather than spinning", () => {
    // video.jsx calls finish() synchronously inside its own [run] effect when
    // there are no video urls. A single-slide region replays by handing the
    // slide a new run id, which re-runs that effect -- unguarded, this is an
    // unbounded loop that React eventually kills with "Maximum update depth
    // exceeded".
    const { container } = render(
      <Region
        region={{ "@id": `/v2/regions/${REGION_ID}`, gridArea: ["a"] }}
      />,
    );

    act(() => {
      document.dispatchEvent(
        new CustomEvent(`regionContent-${REGION_ID}`, {
          detail: { slides: [videoSlide] },
        }),
      );
    });

    const runIdOf = () =>
      Number(container.querySelector("#VIDEOEXECUTION")?.dataset.run);

    const firstRun = runIdOf();
    expect(firstRun).toBeGreaterThan(0);

    // A floor at a time: React flushes the state update when act() returns, so
    // the template only gets to re-run -- and signal again -- between ticks,
    // exactly as it would between frames in a browser.
    for (let floor = 0; floor < 5; floor += 1) {
      act(() => vi.advanceTimersByTime(MIN_SLIDE_DWELL_MS));
    }

    // One replay per floor, and the region is still running.
    expect(runIdOf()).toBe(firstRun + 5);
  });
});

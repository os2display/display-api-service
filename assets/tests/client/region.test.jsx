import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, cleanup } from "@testing-library/react";

const { slideDoneCallbacks } = vi.hoisted(() => ({
  slideDoneCallbacks: new Map(),
}));

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

// Stub the template rendering: the real templates pull in styled-components
// and media loading, which is irrelevant to run ids, transitions and dwell.
vi.mock("../../shared/slide-utils/templates.js", () => ({
  renderSlide: (slide, run, slideDone) => {
    slideDoneCallbacks.set(slide.executionId, slideDone);
    return <div data-testid={`template-${slide.executionId}`} />;
  },
  getConfig: () => ({}),
}));

vi.mock("../../client/components/region.scss", () => ({}));
vi.mock("../../client/components/slide.scss", () => ({}));

import Region from "../../client/components/region.jsx";
import { MIN_SLIDE_DWELL_MS } from "../../client/components/slide.jsx";

const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

// Slide measures the dwell floor with performance.now(). Driving that reading
// from the test keeps every case below independent of how long the test itself
// takes to run, and lets the replay case move the guard's clock without moving
// the wall clock it has deliberately frozen.
let guardClock = 0;

/**
 * Build a minimal slide.
 *
 * @param {string} executionId - The execution id.
 * @returns {object} The slide.
 */
function createSlide(executionId) {
  return {
    executionId,
    templateData: { id: "template" },
    mediaData: {},
    content: {},
  };
}

/**
 * Render a region and hand it its slides.
 *
 * @param {Array} slides - The slides the region should play.
 * @returns {object} The render result.
 */
function renderRegion(slides) {
  const rendered = render(
    <Region region={{ "@id": `/v2/regions/${REGION_ID}`, gridArea: ["a"] }} />,
  );

  act(() => {
    document.dispatchEvent(
      new CustomEvent(`regionContent-${REGION_ID}`, { detail: { slides } }),
    );
  });

  return rendered;
}

/** Let the current slide play well past the dwell floor. */
function playPastDwellFloor() {
  guardClock += MIN_SLIDE_DWELL_MS * 10;
}

/**
 * Signal that a slide finished playing.
 *
 * @param {object} slide - The slide that finished.
 */
function finishSlide(slide) {
  act(() => {
    slideDoneCallbacks.get(slide.executionId)(slide);
  });
}

beforeEach(() => {
  slideDoneCallbacks.clear();
  guardClock = 0;
  vi.spyOn(performance, "now").mockImplementation(() => guardClock);
});

afterEach(() => {
  vi.restoreAllMocks();
  cleanup();
});

describe("Region cross-fades between slides", () => {
  it("applies the transition classes when moving to the next slide", () => {
    const slideA = createSlide("EXECUTIONA");
    const slideB = createSlide("EXECUTIONB");

    const { container } = renderRegion([slideA, slideB]);

    // The first slide is playing.
    expect(container.querySelector("#EXECUTIONA")).toBeInTheDocument();

    // The first slide finishes, so the region moves on to the second one.
    playPastDwellFloor();
    finishSlide(slideA);

    // Both slides are mounted while the transition runs.
    expect(container.querySelectorAll(".slide")).toHaveLength(2);

    // The incoming slide fades in, the outgoing slide fades out.
    expect(container.querySelector("#EXECUTIONB").className).toContain(
      "slide-enter-active",
    );
    expect(container.querySelector("#EXECUTIONA").className).toContain(
      "slide-exit-active",
    );
  });
});

describe("Region replays a single slide", () => {
  beforeEach(() => {
    // Freeze the wall clock. A single-slide region does not remount the slide
    // (the execution id, and so the React key, is unchanged), so replaying
    // depends entirely on the run id changing value. With a wall-clock run id,
    // a template finishing in the same millisecond it started produced the same
    // value, React bailed out of the update, and the region locked. The run id
    // is a counter now, so a frozen clock must not stop it changing.
    //
    // Only Date is faked: the dwell floor is driven through the guard clock
    // above instead, so the wall clock stays frozen across all three runs.
    vi.useFakeTimers({ toFake: ["Date"] });
    vi.setSystemTime(new Date("2026-08-31T12:00:00.000Z"));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("changes the run id on every slideDone, even within the same millisecond", () => {
    const slide = createSlide("EXECUTIONA");

    const { container } = renderRegion([slide]);

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;

    const firstRun = runIdOf();
    expect(firstRun).toBeTruthy();

    // The slide finishes. It is the only slide, so it must start over.
    playPastDwellFloor();
    finishSlide(slide);

    const secondRun = runIdOf();
    expect(secondRun).toBeTruthy();
    expect(secondRun).not.toBe(firstRun);

    // And it keeps going, rather than advancing exactly once.
    playPastDwellFloor();
    finishSlide(slide);

    const thirdRun = runIdOf();
    expect(thirdRun).toBeTruthy();
    expect(thirdRun).not.toBe(secondRun);
  });
});

describe("Region holds a slide for a minimum dwell", () => {
  beforeEach(() => {
    // Fake only the timers the guard defers with. The guard's own clock stays
    // under the test's control through the performance.now() stub.
    vi.useFakeTimers({ toFake: ["setTimeout", "clearTimeout"] });
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("does not advance a slide that finished while mounting", () => {
    // A video slide with no playable media calls slideDone() synchronously from
    // its own effect. Advancing on that signal hands the slide a new run id,
    // which re-runs the effect, which signals again -- the region spins without
    // ever holding a slide long enough to be seen.
    const slide = createSlide("EXECUTIONA");

    const { container } = renderRegion([slide]);

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;
    const firstRun = runIdOf();

    finishSlide(slide);
    expect(runIdOf()).toBe(firstRun);

    act(() => vi.advanceTimersByTime(MIN_SLIDE_DWELL_MS - 1));
    expect(runIdOf()).toBe(firstRun);

    act(() => vi.advanceTimersByTime(1));
    expect(runIdOf()).not.toBe(firstRun);
  });

  it("advances once when a template signals repeatedly within one run", () => {
    const slide = createSlide("EXECUTIONA");

    const { container } = renderRegion([slide]);

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;
    const firstRun = Number(runIdOf());

    finishSlide(slide);
    finishSlide(slide);
    finishSlide(slide);

    act(() => vi.advanceTimersByTime(MIN_SLIDE_DWELL_MS));

    expect(Number(runIdOf())).toBe(firstRun + 1);
  });

  it("advances once when a template signals repeatedly past the floor", () => {
    // The deferred path coalesces by finding a pending timer. Past the floor
    // there is no timer to find, so without a per-run record every late signal
    // would pass straight through and bump the run id again.
    const slide = createSlide("EXECUTIONA");

    const { container } = renderRegion([slide]);

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;
    const firstRun = Number(runIdOf());

    playPastDwellFloor();

    finishSlide(slide);
    finishSlide(slide);

    expect(Number(runIdOf())).toBe(firstRun + 1);
  });

  it("does not hold back a slide that played its duration", () => {
    const slide = createSlide("EXECUTIONA");

    const { container } = renderRegion([slide]);

    const runIdOf = () => container.querySelector("#EXECUTIONA")?.dataset.run;
    const firstRun = runIdOf();

    playPastDwellFloor();
    finishSlide(slide);

    // No timer had to fire: the floor only defers a slide that finished early.
    expect(runIdOf()).not.toBe(firstRun);
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, renderHook } from "@testing-library/react";
import useMultipleEntrySlideExecution from "../../shared/slide-utils/useMultipleEntrySlideExecution.js";

const SLIDE = { executionId: "EXE-ID" };
const ENTRIES = [{ title: "one" }, { title: "two" }, { title: "three" }];

/**
 * Render the hook, exposing a rerender that merges into the current props.
 *
 * @param {object} props Overrides for the default props.
 * @returns {object} The renderHook result, with a props-merging rerender.
 */
function render(props = {}) {
  let currentProps = {
    entries: ENTRIES,
    run: "run-1",
    slide: SLIDE,
    slideDone: vi.fn(),
    entryDuration: 1000,
    ...props,
  };

  const rendered = renderHook(
    (hookProps) => useMultipleEntrySlideExecution(hookProps),
    { initialProps: currentProps },
  );

  return {
    ...rendered,
    rerender: (next) => {
      currentProps = { ...currentProps, ...next };
      act(() => rendered.rerender(currentProps));
    },
  };
}

describe("useMultipleEntrySlideExecution", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("reports nothing until the slide runs", () => {
    const slideDone = vi.fn();
    const { result } = render({ run: "", slideDone });

    expect(result.current.entryIndex).toBeNull();
    expect(result.current.currentEntry).toBeNull();

    act(() => vi.advanceTimersByTime(60000));
    expect(slideDone).not.toHaveBeenCalled();
  });

  it("shows the first entry when the slide starts", () => {
    const { result, rerender } = render({ run: "" });

    rerender({ run: "run-1" });

    expect(result.current.entryIndex).toBe(0);
    expect(result.current.currentEntry).toBe(ENTRIES[0]);
  });

  it("cycles through the entries one entryDuration apart", () => {
    const { result } = render();

    expect(result.current.entryIndex).toBe(0);

    act(() => vi.advanceTimersByTime(1000));
    expect(result.current.entryIndex).toBe(1);
    expect(result.current.currentEntry).toBe(ENTRIES[1]);

    act(() => vi.advanceTimersByTime(1000));
    expect(result.current.entryIndex).toBe(2);
    expect(result.current.currentEntry).toBe(ENTRIES[2]);
  });

  it("calls slideDone with the slide after the last entry", () => {
    const slideDone = vi.fn();
    render({ slideDone });

    act(() => vi.advanceTimersByTime(2999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledExactlyOnceWith(SLIDE);
  });

  it("falls back to 15s when entryDuration is not a positive number", () => {
    const { result } = render({ entryDuration: 0 });

    act(() => vi.advanceTimersByTime(14999));
    expect(result.current.entryIndex).toBe(0);

    act(() => vi.advanceTimersByTime(1));
    expect(result.current.entryIndex).toBe(1);
  });

  it("finishes the slide when there are no entries", () => {
    // Owned by the hook rather than each template: a template that forgot its
    // own fallback timer used to lock the playlist on an empty feed.
    const slideDone = vi.fn();
    const { result } = render({ entries: [], slideDone });

    act(() => vi.advanceTimersByTime(999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));

    expect(slideDone).toHaveBeenCalledTimes(1);
    expect(result.current.entryIndex).toBeNull();
    expect(result.current.currentEntry).toBeNull();
  });

  it("honours emptyEntriesDuration", () => {
    const slideDone = vi.fn();
    render({ entries: [], slideDone, emptyEntriesDuration: 5000 });

    act(() => vi.advanceTimersByTime(4999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledTimes(1);
  });

  it("starts cycling when entries arrive after the slide started", () => {
    // A feed that resolves after `run` flipped truthy used to be skipped by the
    // fallback: the cycle only keyed on `run`, so it never noticed the entries.
    const slideDone = vi.fn();
    const { result, rerender } = render({ entries: [], slideDone });

    act(() => vi.advanceTimersByTime(500));
    expect(result.current.currentEntry).toBeNull();

    rerender({ entries: [{ title: "late" }, { title: "later" }], slideDone });

    expect(result.current.entryIndex).toBe(0);
    expect(result.current.currentEntry).toEqual({ title: "late" });
    // The empty-entries fallback must not still be pending.
    act(() => vi.advanceTimersByTime(600));
    expect(slideDone).not.toHaveBeenCalled();
  });

  it("exposes the clamped entryDuration so template timers can match", () => {
    const { result } = render({ entryDuration: 0 });

    expect(result.current.entryDuration).toBe(15000);
  });

  it("clears its state when the slide stops running", () => {
    const { result, rerender } = render();

    act(() => vi.advanceTimersByTime(1000));
    expect(result.current.entryIndex).toBe(1);

    rerender({ run: "" });

    expect(result.current.entryIndex).toBeNull();
    expect(result.current.currentEntry).toBeNull();
  });

  it("clears the timer on unmount", () => {
    const slideDone = vi.fn();
    const { result, unmount } = render({ slideDone });

    expect(result.current.entryIndex).toBe(0);

    unmount();
    act(() => vi.advanceTimersByTime(60000));

    expect(slideDone).not.toHaveBeenCalled();
  });

  it("uses the latest slideDone without restarting the cycle", () => {
    const firstSlideDone = vi.fn();
    const secondSlideDone = vi.fn();
    const { rerender } = render({ slideDone: firstSlideDone });

    act(() => vi.advanceTimersByTime(1000));
    rerender({ slideDone: secondSlideDone });
    act(() => vi.advanceTimersByTime(2000));

    expect(firstSlideDone).not.toHaveBeenCalled();
    expect(secondSlideDone).toHaveBeenCalledExactlyOnceWith(SLIDE);
  });

  it("restarts the cycle when run changes to a new truthy value", () => {
    const slideDone = vi.fn();
    const { result, rerender } = render({ slideDone, run: 1 });

    // Cycle all the way through the entries.
    act(() => vi.advanceTimersByTime(3000));
    expect(slideDone).toHaveBeenCalledTimes(1);

    // A region holding a single slide replays it without remounting, so the
    // only signal that the slide should run again is a new run value.
    rerender({ run: 2 });

    expect(result.current.entryIndex).toBe(0);
    expect(result.current.currentEntry).toBe(ENTRIES[0]);

    act(() => vi.advanceTimersByTime(3000));
    expect(slideDone).toHaveBeenCalledTimes(2);
  });
});

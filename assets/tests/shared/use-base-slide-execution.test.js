import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, renderHook } from "@testing-library/react";
import useBaseSlideExecution from "../../shared/slide-utils/useBaseSlideExecution.js";

const SLIDE = { executionId: "EXE-ID" };

describe("useBaseSlideExecution", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("does not call slideDone before the slide runs", () => {
    const slideDone = vi.fn();

    renderHook(() =>
      useBaseSlideExecution({
        slide: SLIDE,
        run: "",
        slideDone,
        duration: 5000,
      }),
    );

    act(() => vi.advanceTimersByTime(60000));

    expect(slideDone).not.toHaveBeenCalled();
  });

  it("calls slideDone with the slide once the duration has passed", () => {
    const slideDone = vi.fn();

    renderHook(() =>
      useBaseSlideExecution({
        slide: SLIDE,
        run: "run-1",
        slideDone,
        duration: 5000,
      }),
    );

    act(() => vi.advanceTimersByTime(4999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledExactlyOnceWith(SLIDE);
  });

  it("clears the timer on unmount", () => {
    const slideDone = vi.fn();

    const { unmount } = renderHook(() =>
      useBaseSlideExecution({
        slide: SLIDE,
        run: "run-1",
        slideDone,
        duration: 5000,
      }),
    );

    act(() => vi.advanceTimersByTime(1000));
    unmount();
    act(() => vi.advanceTimersByTime(60000));

    expect(slideDone).not.toHaveBeenCalled();
  });

  it.each([
    ["undefined", undefined],
    ["zero", 0],
    ["negative", -1000],
    ["not a number", "5000"],
  ])("falls back to 15s when duration is %s", (_label, duration) => {
    const slideDone = vi.fn();

    renderHook(() =>
      useBaseSlideExecution({
        slide: SLIDE,
        run: "run-1",
        slideDone,
        duration,
      }),
    );

    act(() => vi.advanceTimersByTime(14999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledTimes(1);
  });

  it("uses the latest slideDone and duration without restarting the timer", () => {
    const firstSlideDone = vi.fn();
    const secondSlideDone = vi.fn();

    const { rerender } = renderHook(
      ({ slideDone, duration }) =>
        useBaseSlideExecution({
          slide: SLIDE,
          run: "run-1",
          slideDone,
          duration,
        }),
      { initialProps: { slideDone: firstSlideDone, duration: 5000 } },
    );

    act(() => vi.advanceTimersByTime(4000));
    rerender({ slideDone: secondSlideDone, duration: 60000 });
    act(() => vi.advanceTimersByTime(1000));

    // The timer keeps its original 5s deadline, but fires the current callback.
    expect(firstSlideDone).not.toHaveBeenCalled();
    expect(secondSlideDone).toHaveBeenCalledExactlyOnceWith(SLIDE);
  });

  it("restarts the timer when run changes to a new truthy value", () => {
    const slideDone = vi.fn();

    const { rerender } = renderHook(
      ({ run }) =>
        useBaseSlideExecution({ slide: SLIDE, run, slideDone, duration: 5000 }),
      { initialProps: { run: 1 } },
    );

    act(() => vi.advanceTimersByTime(5000));
    expect(slideDone).toHaveBeenCalledTimes(1);

    // A region holding a single slide replays it without remounting, so the
    // only signal that the slide should run again is a new run value.
    rerender({ run: 2 });

    act(() => vi.advanceTimersByTime(5000));
    expect(slideDone).toHaveBeenCalledTimes(2);
  });

  it("does not restart the timer when run is unchanged", () => {
    const slideDone = vi.fn();

    const { rerender } = renderHook(
      ({ run }) =>
        useBaseSlideExecution({ slide: SLIDE, run, slideDone, duration: 5000 }),
      { initialProps: { run: 1 } },
    );

    act(() => vi.advanceTimersByTime(5000));
    rerender({ run: 1 });
    act(() => vi.advanceTimersByTime(60000));

    expect(slideDone).toHaveBeenCalledTimes(1);
  });

  it("keeps the duration it started with when duration changes mid-run", () => {
    // The duration is read when the timer is set. Re-reading it on a later
    // render would let a content update stretch or cut short a slide that is
    // already playing.
    const slideDone = vi.fn();

    const { rerender } = renderHook(
      ({ duration }) =>
        useBaseSlideExecution({ slide: SLIDE, run: 1, slideDone, duration }),
      { initialProps: { duration: 4000 } },
    );

    act(() => vi.advanceTimersByTime(3000));
    rerender({ duration: 60000 });
    act(() => vi.advanceTimersByTime(1000));

    expect(slideDone).toHaveBeenCalledTimes(1);
  });
});

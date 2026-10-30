import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, render } from "@testing-library/react";

import imageText from "../../shared/templates/image-text.jsx";
import video from "../../shared/templates/video.jsx";

// image-text and video read `slide`, `slideDone`, images and durations from refs
// when a timer or media event fires. Those refs are written in a layout effect
// rather than during render — a render may be discarded or replayed under
// concurrent rendering, so it must not have side effects. These cases pin the
// behaviour that depends on the refs actually being current.

const imageTextSlide = (duration) => ({
  executionId: "image-text-execution",
  mediaData: {
    "/v2/media/1": { assets: { uri: "/media/one.jpg" } },
    "/v2/media/2": { assets: { uri: "/media/two.jpg" } },
  },
  content: {
    title: "T",
    text: "x",
    duration,
    image: ["/v2/media/1", "/v2/media/2"],
  },
});

describe("image-text cycles its images from refs", () => {
  beforeEach(() => vi.useFakeTimers());
  afterEach(() => vi.useRealTimers());

  it("advances through both images within the slide duration", () => {
    const { container } = render(
      imageText.renderSlide(imageTextSlide(4000), "run-token", vi.fn()),
    );

    const backgroundOf = () =>
      container.querySelector(".background-image")?.style?.backgroundImage ??
      "";

    const first = backgroundOf();
    expect(first).toContain("one.jpg");

    // Two images over 4000ms → swap at 2000ms.
    act(() => vi.advanceTimersByTime(2100));

    expect(backgroundOf()).toContain("two.jpg");
  });

  it("finishes the slide once, after the full duration", () => {
    const slideDone = vi.fn();
    render(imageText.renderSlide(imageTextSlide(4000), "run-token", slideDone));

    act(() => vi.advanceTimersByTime(3999));
    expect(slideDone).not.toHaveBeenCalled();

    act(() => vi.advanceTimersByTime(1));
    expect(slideDone).toHaveBeenCalledTimes(1);
  });
});

const videoSlide = {
  executionId: "video-execution",
  mediaData: { "/v2/media/1": { assets: { uri: "/media/test.mp4" } } },
  content: { video: ["/v2/media/1"] },
};

describe("video progression and teardown", () => {
  let play;
  let pause;

  beforeEach(() => {
    vi.useFakeTimers();
    // jsdom implements neither, and the template calls both.
    play = vi
      .spyOn(window.HTMLMediaElement.prototype, "play")
      .mockResolvedValue(undefined);
    pause = vi
      .spyOn(window.HTMLMediaElement.prototype, "pause")
      .mockImplementation(() => {});
    vi.spyOn(window.HTMLMediaElement.prototype, "load").mockImplementation(
      () => {},
    );
    // `paused` defaults to true in jsdom, which would skip the pause call.
    Object.defineProperty(window.HTMLMediaElement.prototype, "paused", {
      configurable: true,
      get: () => false,
    });
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  it("pauses the element when the slide stops running", () => {
    const { rerender } = render(
      video.renderSlide(videoSlide, "run-token", vi.fn()),
    );

    expect(play).toHaveBeenCalled();
    expect(pause).not.toHaveBeenCalled();

    rerender(video.renderSlide(videoSlide, false, vi.fn()));

    // Without this an unmuted video keeps playing behind the next slide in a
    // preview that toggles `run` instead of unmounting.
    expect(pause).toHaveBeenCalled();
  });

  it("calls the current slideDone when the metadata guard fires", () => {
    const stale = vi.fn();
    const current = vi.fn();

    const { rerender } = render(
      video.renderSlide(videoSlide, "run-token", stale),
    );

    rerender(video.renderSlide(videoSlide, "run-token", current));

    // 30s metadata guard: no duration ever arrives.
    act(() => vi.advanceTimersByTime(30000));

    expect(current).toHaveBeenCalledTimes(1);
    expect(stale).not.toHaveBeenCalled();
  });

  it("finishes only once even if several guards fire", () => {
    const slideDone = vi.fn();
    render(video.renderSlide(videoSlide, "run-token", slideDone));

    act(() => vi.advanceTimersByTime(120000));

    expect(slideDone).toHaveBeenCalledTimes(1);
  });
});

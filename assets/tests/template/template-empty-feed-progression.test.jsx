import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { act, render } from "@testing-library/react";

import rss from "../../shared/templates/rss.jsx";
import newsFeed from "../../shared/templates/news-feed.jsx";
import instagramFeed from "../../shared/templates/instagram-feed.jsx";
import poster from "../../shared/templates/poster.jsx";
import slideshow from "../../shared/templates/slideshow.jsx";

// The empty-feed fallback used to be a hand-rolled timer in each template, so a
// template that omitted it locked the playlist on every screen it loaded on.
// It now lives in useMultipleEntrySlideExecution, declared per template through
// emptyEntriesDuration. These cases assert the timing each template asks for,
// and — more importantly — that every one of them still finishes at all.
// rss reads feedData.entries; the others read feedData itself as an array.
const cases = [
  {
    name: "rss",
    template: rss,
    ms: 1000,
    content: {},
    feedData: { entries: [] },
  },
  {
    name: "news-feed",
    template: newsFeed,
    ms: 5000,
    content: {},
    feedData: [],
  },
  {
    name: "instagram-feed",
    template: instagramFeed,
    ms: 1000,
    content: {},
    feedData: [],
  },
  { name: "poster", template: poster, ms: 1000, content: {}, feedData: [] },
  {
    name: "slideshow",
    template: slideshow,
    ms: 2000,
    content: { images: [] },
    feedData: [],
  },
];

describe("empty feed never locks the playlist", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it.each(cases)(
    "$name finishes after $ms ms with no entries",
    ({ template, ms, content, feedData }) => {
      const slideDone = vi.fn();
      const slide = {
        executionId: `${template.id()}-execution`,
        mediaData: {},
        feed: { configuration: {} },
        feedData,
        content,
      };

      render(template.renderSlide(slide, "run-token", slideDone));

      act(() => vi.advanceTimersByTime(ms - 1));
      expect(slideDone).not.toHaveBeenCalled();

      act(() => vi.advanceTimersByTime(1));
      expect(slideDone).toHaveBeenCalledTimes(1);
      expect(slideDone).toHaveBeenCalledWith(slide);
    },
  );

  it.each(cases)(
    "$name does not finish before it runs",
    ({ template, content, feedData }) => {
      const slideDone = vi.fn();
      const slide = {
        executionId: `${template.id()}-execution`,
        mediaData: {},
        feed: { configuration: {} },
        feedData,
        content,
      };

      render(template.renderSlide(slide, false, slideDone));

      act(() => vi.advanceTimersByTime(60000));

      expect(slideDone).not.toHaveBeenCalled();
    },
  );
});

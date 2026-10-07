import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, cleanup } from "@testing-library/react";

const { slideDoneCallbacks } = vi.hoisted(() => ({
  slideDoneCallbacks: new Map(),
}));

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn() },
}));

// What the real templates.js does for an id with no module in this build - see
// templates-lookup.test.js. Mocked so the case can be exercised without pulling
// every template into the test.
vi.mock("../../shared/slide-utils/templates.js", () => ({
  renderSlide: (slide, run, slideDone) => {
    if (slide?.templateData?.id === MISSING) {
      throw new Error(`Cannot find module '${MISSING}'`);
    }

    slideDoneCallbacks.set(slide.executionId, slideDone);

    return <div data-testid={`template-${slide.executionId}`} />;
  },
  getConfig: () => ({}),
}));

vi.mock("../../client/components/slide.scss", () => ({}));
vi.mock("../../client/components/region.scss", () => ({}));
vi.mock("../../client/components/error-boundary.scss", () => ({}));

import Slide from "../../client/components/slide.jsx";
import Region from "../../client/components/region.jsx";

const MISSING = "01JZZZZZZZZZZZZZZZZZZZZZZZ";
const PRESENT = "01FP2SNGFN0BZQH03KCBXHKYHG";
const REGION_ID = "01JB1D9E3ZMTFBT7CYHFHGX5KA";

/**
 * Build a minimal slide.
 *
 * @param {string} executionId - The execution id.
 * @param {string} template - The template id the slide names.
 * @returns {object} The slide.
 */
function createSlide(executionId, template) {
  return {
    executionId,
    templateData: { id: template },
    mediaData: {},
    content: {},
  };
}

describe("a slide naming a template this build does not have", () => {
  beforeEach(() => {
    slideDoneCallbacks.clear();
  });

  afterEach(() => {
    cleanup();
  });

  it("shows the fallback and moves on rather than wedging the region", () => {
    // A slide that throws and never reports back would hold the region on the
    // error fallback forever, so the boundary has to end the slide's turn.
    vi.useFakeTimers();

    try {
      const slideError = vi.fn();

      const { container } = render(
        <Slide
          slide={createSlide("unresolvable", MISSING)}
          id="slide-unresolvable"
          run="2026-01-01T00:00:00.000Z"
          slideDone={vi.fn()}
          slideError={slideError}
          forwardRef={{ current: null }}
        />,
      );

      expect(container.querySelector(".error-boundary")).not.toBeNull();
      expect(slideError).not.toHaveBeenCalled();

      act(() => {
        vi.advanceTimersByTime(5000);
      });

      expect(slideError).toHaveBeenCalledTimes(1);
      expect(slideError.mock.calls[0][0].executionId).toBe("unresolvable");
    } finally {
      vi.useRealTimers();
    }
  });

  it("costs its own slide, not every slide in the region", () => {
    // renderSlide used to be called as an argument in Slide's render, so it
    // threw before Slide's own boundary mounted and hit the region's instead -
    // which has no handler and never resets, so one unrenderable slide replaced
    // the whole region with the fallback until the client was reloaded.
    vi.useFakeTimers();

    try {
      const bad = createSlide("BADSLIDE", MISSING);
      const good = createSlide("GOODSLIDE", PRESENT);

      const { container } = render(
        <Region
          region={{ "@id": `/v2/regions/${REGION_ID}`, gridArea: ["a"] }}
        />,
      );

      act(() => {
        document.dispatchEvent(
          new CustomEvent(`regionContent-${REGION_ID}`, {
            detail: { slides: [bad, good] },
          }),
        );
      });

      // Only the bad slide shows the fallback - the region is intact.
      expect(
        container.querySelector("#BADSLIDE .error-boundary"),
      ).not.toBeNull();
      expect(container.querySelector(".region > .error-boundary")).toBeNull();

      // And the region moves on, so the good slide still gets its turn.
      act(() => {
        vi.advanceTimersByTime(5000);
      });

      expect(
        container.querySelector('[data-testid="template-GOODSLIDE"]'),
      ).not.toBeNull();
    } finally {
      vi.useRealTimers();
    }
  });
});

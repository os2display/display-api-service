import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, act, cleanup, within } from "@testing-library/react";
import Region from "../../client/components/region.jsx";

vi.mock("../../client/logger/logger", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/components/region.scss", () => ({}));
vi.mock("../../client/components/slide.scss", () => ({}));
vi.mock("../../client/components/error-boundary.scss", () => ({}));
vi.mock("../../client/assets/fallback.png", () => ({
  default: "fallback.png",
}));
vi.mock("../../shared/grid-generator/grid-generator", () => ({
  createGridArea: (gridArea) => gridArea.join(" / "),
}));
vi.mock("../../client/util/id-from-path", () => ({
  default: () => "REGION01",
}));

// Mock Slide to render a simple div and expose slideDone
let capturedSlideDone = null;
vi.mock("../../client/components/slide.jsx", () => ({
  default: ({ slide, slideDone }) => {
    capturedSlideDone = () => slideDone(slide);
    return <div data-testid={`slide-${slide.executionId}`}>{slide.title}</div>;
  },
}));

// Mock react-transition-group to be pass-through
vi.mock("react-transition-group", () => ({
  TransitionGroup: ({ children }) => <>{children}</>,
  CSSTransition: ({ children }) => <>{children}</>,
}));

describe("Region", () => {
  const region = {
    "@id": "/v2/layouts/regions/REGION01",
    gridArea: ["a"],
  };

  beforeEach(() => {
    capturedSlideDone = null;
  });

  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
  });

  it("emits regionReady event on mount", () => {
    const handler = vi.fn();
    document.addEventListener("regionReady", handler);

    render(<Region region={region} />);

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler.mock.calls[0][0].detail.id).toBe("REGION01");

    document.removeEventListener("regionReady", handler);
  });

  it("emits regionRemoved event on unmount", () => {
    const handler = vi.fn();
    document.addEventListener("regionRemoved", handler);

    const { unmount } = render(<Region region={region} />);
    unmount();

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler.mock.calls[0][0].detail.id).toBe("REGION01");

    document.removeEventListener("regionRemoved", handler);
  });

  it("displays the first slide when regionContent event is dispatched", () => {
    const { container } = render(<Region region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-REGION01", {
        detail: {
          slides: [
            { executionId: "EXE-1", title: "Slide 1" },
            { executionId: "EXE-2", title: "Slide 2" },
          ],
        },
      });
      document.dispatchEvent(event);
    });

    expect(
      within(container).getByTestId("slide-EXE-1")
    ).toBeInTheDocument();
  });

  it("filters out invalid slides", () => {
    const { container } = render(<Region region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-REGION01", {
        detail: {
          slides: [
            { executionId: "EXE-1", title: "Valid", invalid: false },
            { executionId: "EXE-2", title: "Invalid", invalid: true },
          ],
        },
      });
      document.dispatchEvent(event);
    });

    expect(
      within(container).getByTestId("slide-EXE-1")
    ).toBeInTheDocument();
  });

  it("advances to next slide when slideDone is called", () => {
    const { container } = render(<Region region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-REGION01", {
        detail: {
          slides: [
            { executionId: "EXE-1", title: "Slide 1" },
            { executionId: "EXE-2", title: "Slide 2" },
          ],
        },
      });
      document.dispatchEvent(event);
    });

    act(() => {
      capturedSlideDone();
    });

    expect(
      within(container).getByTestId("slide-EXE-2")
    ).toBeInTheDocument();
  });

  it("wraps around to first slide after last", () => {
    const { container } = render(<Region region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-REGION01", {
        detail: {
          slides: [
            { executionId: "EXE-1", title: "Slide 1" },
            { executionId: "EXE-2", title: "Slide 2" },
          ],
        },
      });
      document.dispatchEvent(event);
    });

    // Advance to EXE-2
    act(() => {
      capturedSlideDone();
    });

    // Advance past EXE-2 → wraps to EXE-1
    act(() => {
      capturedSlideDone();
    });

    expect(
      within(container).getByTestId("slide-EXE-1")
    ).toBeInTheDocument();
  });

  it("emits slideDone event on document when slide completes", () => {
    const handler = vi.fn();
    document.addEventListener("slideDone", handler);

    render(<Region region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-REGION01", {
        detail: {
          slides: [{ executionId: "EXE-1", title: "Slide 1" }],
        },
      });
      document.dispatchEvent(event);
    });

    act(() => {
      capturedSlideDone();
    });

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler.mock.calls[0][0].detail.executionId).toBe("EXE-1");

    document.removeEventListener("slideDone", handler);
  });

  it("renders with correct grid area style", () => {
    const { container } = render(<Region region={region} />);
    const el = container.querySelector(".region");
    expect(el.style.gridArea).toBe("a");
  });
});

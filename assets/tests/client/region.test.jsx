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

// Mock context
const mockCallbacks = {
  current: {
    onRegionReady: vi.fn(),
    onRegionRemoved: vi.fn(),
    setScreen: vi.fn(),
    setIsContentEmpty: vi.fn(),
    updateRegionSlides: vi.fn(),
  },
};
let mockRegionSlides = {};

vi.mock("../../client/context/client-state-context.jsx", () => ({
  useClientState: () => ({
    regionSlides: mockRegionSlides,
    callbacks: mockCallbacks,
  }),
}));

describe("Region", () => {
  const region = {
    "@id": "/v2/layouts/regions/REGION01",
    gridArea: ["a"],
  };

  beforeEach(() => {
    capturedSlideDone = null;
    mockRegionSlides = {};
    mockCallbacks.current.onRegionReady.mockClear();
    mockCallbacks.current.onRegionRemoved.mockClear();
  });

  afterEach(() => {
    cleanup();
    vi.restoreAllMocks();
  });

  it("calls onRegionReady on mount", () => {
    render(<Region region={region} />);

    expect(mockCallbacks.current.onRegionReady).toHaveBeenCalledWith("REGION01");
  });

  it("calls onRegionRemoved on unmount", () => {
    const { unmount } = render(<Region region={region} />);
    unmount();

    expect(mockCallbacks.current.onRegionRemoved).toHaveBeenCalledWith("REGION01");
  });

  it("displays the first slide when regionSlides updates", () => {
    mockRegionSlides = {
      REGION01: [
        { executionId: "EXE-1", title: "Slide 1" },
        { executionId: "EXE-2", title: "Slide 2" },
      ],
    };

    const { container } = render(<Region region={region} />);

    expect(
      within(container).getByTestId("slide-EXE-1")
    ).toBeInTheDocument();
  });

  it("filters out invalid slides", () => {
    mockRegionSlides = {
      REGION01: [
        { executionId: "EXE-1", title: "Valid", invalid: false },
        { executionId: "EXE-2", title: "Invalid", invalid: true },
      ],
    };

    const { container } = render(<Region region={region} />);

    expect(
      within(container).getByTestId("slide-EXE-1")
    ).toBeInTheDocument();
  });

  it("advances to next slide when slideDone is called", () => {
    mockRegionSlides = {
      REGION01: [
        { executionId: "EXE-1", title: "Slide 1" },
        { executionId: "EXE-2", title: "Slide 2" },
      ],
    };

    const { container } = render(<Region region={region} />);

    act(() => {
      capturedSlideDone();
    });

    expect(
      within(container).getByTestId("slide-EXE-2")
    ).toBeInTheDocument();
  });

  it("wraps around to first slide after last", () => {
    mockRegionSlides = {
      REGION01: [
        { executionId: "EXE-1", title: "Slide 1" },
        { executionId: "EXE-2", title: "Slide 2" },
      ],
    };

    const { container } = render(<Region region={region} />);

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

  it("recovers playback when new slides arrive after content was cleared mid-cycle", () => {
    mockRegionSlides = {
      REGION01: [
        { executionId: "EXE-1", title: "Slide 1" },
        { executionId: "EXE-2", title: "Slide 2" },
      ],
    };

    const { container, rerender } = render(<Region region={region} />);
    expect(within(container).getByTestId("slide-EXE-1")).toBeInTheDocument();

    // Advance to slide 2.
    act(() => { capturedSlideDone(); });
    expect(within(container).getByTestId("slide-EXE-2")).toBeInTheDocument();

    // Content is removed while slide 2 is still playing.
    mockRegionSlides = { REGION01: [] };
    rerender(<Region region={region} />);

    // Slide 2 finishes — cycle wraps (nextIndex 0) and applies the empty
    // newSlides, setting currentSlide to null and slides to [].
    act(() => { capturedSlideDone(); });
    expect(container.querySelector("[data-testid^='slide-']")).toBeNull();

    // New content arrives while currentSlide is null.
    mockRegionSlides = {
      REGION01: [{ executionId: "EXE-3", title: "Slide 3" }],
    };
    rerender(<Region region={region} />);

    // The region should recover and display the new slide.
    expect(within(container).getByTestId("slide-EXE-3")).toBeInTheDocument();
  });

  it("renders with correct grid area style", () => {
    const { container } = render(<Region region={region} />);
    const el = container.querySelector(".region");
    expect(el.style.gridArea).toBe("a");
  });
});

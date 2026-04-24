import { describe, it, expect, vi, afterEach } from "vitest";
import { render, act, fireEvent, cleanup, within } from "@testing-library/react";
import TouchRegion from "../../client/components/touch-region.jsx";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/components/touch-region.scss", () => ({}));
vi.mock("../../client/components/slide.scss", () => ({}));
vi.mock("../../client/components/error-boundary.scss", () => ({}));
vi.mock("../../client/assets/fallback.png", () => ({
  default: "fallback.png",
}));
vi.mock("../../client/assets/icon-close.svg", () => ({
  default: () => <svg data-testid="icon-close" />,
}));
vi.mock("../../client/assets/icon-pointer.svg", () => ({
  default: () => <svg data-testid="icon-pointer" />,
}));
vi.mock("../../shared/grid-generator/grid-generator", () => ({
  createGridArea: (gridArea) => gridArea.join(" / "),
}));
vi.mock("../../client/util/id-from-path", () => ({
  default: () => "TOUCH01",
}));

let capturedSlideDone = null;
vi.mock("../../client/components/slide.jsx", () => ({
  default: ({ slide, slideDone }) => {
    capturedSlideDone = () => slideDone(slide);
    return <div data-testid={`slide-${slide.executionId}`}>{slide.title}</div>;
  },
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

vi.mock("../../client/client-state-context.jsx", () => ({
  useClientState: () => ({
    regionSlides: mockRegionSlides,
    callbacks: mockCallbacks,
  }),
}));

describe("TouchRegion", () => {
  const region = {
    "@id": "/v2/layouts/regions/TOUCH01",
    gridArea: ["a"],
    type: "touch-buttons",
  };

  const slides = [
    { executionId: "EXE-1", title: "Slide 1" },
    {
      executionId: "EXE-2",
      title: "Slide 2",
      content: { touchRegionButtonText: "Press Me" },
    },
  ];

  afterEach(() => {
    capturedSlideDone = null;
    mockRegionSlides = {};
    mockCallbacks.current.onRegionReady.mockClear();
    mockCallbacks.current.onRegionRemoved.mockClear();
    cleanup();
    vi.restoreAllMocks();
  });

  function renderWithSlides() {
    mockRegionSlides = { TOUCH01: slides };
    return render(<TouchRegion region={region} />);
  }

  it("calls onRegionReady on mount", () => {
    render(<TouchRegion region={region} />);

    expect(mockCallbacks.current.onRegionReady).toHaveBeenCalledWith("TOUCH01");
  });

  it("calls onRegionRemoved on unmount", () => {
    const { unmount } = render(<TouchRegion region={region} />);
    unmount();

    expect(mockCallbacks.current.onRegionRemoved).toHaveBeenCalledWith("TOUCH01");
  });

  it("renders buttons for each slide when regionSlides has data", () => {
    const { container } = renderWithSlides();

    const buttons = within(container).getAllByRole("button");
    expect(buttons.length).toBeGreaterThanOrEqual(2);
  });

  it("uses slide title for button text", () => {
    const { container } = renderWithSlides();

    expect(within(container).getByText("Slide 1")).toBeInTheDocument();
  });

  it("uses touchRegionButtonText when available", () => {
    const { container } = renderWithSlides();

    expect(within(container).getByText("Press Me")).toBeInTheDocument();
  });

  it("opens slide when button is clicked", () => {
    const { container } = renderWithSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    expect(within(container).getByTestId("slide-EXE-1")).toBeInTheDocument();
  });

  it("shows close button when slide is active", () => {
    const { container } = renderWithSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    expect(within(container).getByText("LUK")).toBeInTheDocument();
  });

  it("dismisses slide when close button is clicked", () => {
    const { container } = renderWithSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    act(() => {
      fireEvent.click(within(container).getByText("LUK"));
    });

    expect(
      within(container).queryByTestId("slide-EXE-1")
    ).not.toBeInTheDocument();
  });

  it("renders no buttons when regionSlides has empty array", () => {
    mockRegionSlides = { TOUCH01: [] };
    const { container } = render(<TouchRegion region={region} />);

    const buttons = within(container).queryAllByRole("button");
    expect(buttons).toHaveLength(0);
  });

  it("filters out invalid slides from buttons", () => {
    mockRegionSlides = {
      TOUCH01: [
        { executionId: "EXE-VALID", title: "Valid Slide" },
        { executionId: "EXE-INVALID", title: "Invalid Slide", invalid: true },
      ],
    };
    const { container } = render(<TouchRegion region={region} />);

    expect(within(container).getByText("Valid Slide")).toBeInTheDocument();
    expect(within(container).queryByText("Invalid Slide")).not.toBeInTheDocument();
  });

  it("opens slide when Enter is pressed on a button", () => {
    const { container } = renderWithSlides();

    act(() => {
      fireEvent.keyDown(within(container).getByText("Slide 1"), { key: "Enter" });
    });

    expect(within(container).getByTestId("slide-EXE-1")).toBeInTheDocument();
  });

  it("opens slide when Space is pressed on a button", () => {
    const { container } = renderWithSlides();

    act(() => {
      fireEvent.keyDown(within(container).getByText("Slide 1"), { key: " " });
    });

    expect(within(container).getByTestId("slide-EXE-1")).toBeInTheDocument();
  });

});

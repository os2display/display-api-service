import { describe, it, expect, vi, afterEach } from "vitest";
import { render, act, fireEvent, cleanup, within } from "@testing-library/react";
import TouchRegion from "../../client/components/touch-region.jsx";

vi.mock("../../client/logger/logger", () => ({
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
    cleanup();
    vi.restoreAllMocks();
  });

  function renderAndDispatchSlides() {
    const result = render(<TouchRegion region={region} />);

    act(() => {
      const event = new CustomEvent("regionContent-TOUCH01", {
        detail: { slides },
      });
      document.dispatchEvent(event);
    });

    return result;
  }

  it("emits regionReady on mount", () => {
    const handler = vi.fn();
    document.addEventListener("regionReady", handler);

    render(<TouchRegion region={region} />);

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler.mock.calls[0][0].detail.id).toBe("TOUCH01");

    document.removeEventListener("regionReady", handler);
  });

  it("emits regionRemoved on unmount", () => {
    const handler = vi.fn();
    document.addEventListener("regionRemoved", handler);

    const { unmount } = render(<TouchRegion region={region} />);
    unmount();

    expect(handler).toHaveBeenCalledTimes(1);
    document.removeEventListener("regionRemoved", handler);
  });

  it("renders buttons for each slide when regionContent arrives", () => {
    const { container } = renderAndDispatchSlides();

    const buttons = within(container).getAllByRole("button");
    expect(buttons.length).toBeGreaterThanOrEqual(2);
  });

  it("uses slide title for button text", () => {
    const { container } = renderAndDispatchSlides();

    expect(within(container).getByText("Slide 1")).toBeInTheDocument();
  });

  it("uses touchRegionButtonText when available", () => {
    const { container } = renderAndDispatchSlides();

    expect(within(container).getByText("Press Me")).toBeInTheDocument();
  });

  it("opens slide when button is clicked", () => {
    const { container } = renderAndDispatchSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    expect(within(container).getByTestId("slide-EXE-1")).toBeInTheDocument();
  });

  it("shows close button when slide is active", () => {
    const { container } = renderAndDispatchSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    expect(within(container).getByText("LUK")).toBeInTheDocument();
  });

  it("dismisses slide when close button is clicked", () => {
    const { container } = renderAndDispatchSlides();

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

  it("emits slideDone event when slide completes", () => {
    const handler = vi.fn();
    document.addEventListener("slideDone", handler);

    const { container } = renderAndDispatchSlides();

    act(() => {
      fireEvent.click(within(container).getByText("Slide 1"));
    });

    act(() => {
      capturedSlideDone();
    });

    expect(handler).toHaveBeenCalledTimes(1);
    expect(handler.mock.calls[0][0].detail.executionId).toBe("EXE-1");

    document.removeEventListener("slideDone", handler);
  });
});

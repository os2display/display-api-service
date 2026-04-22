import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, cleanup } from "@testing-library/react";
import Slide from "../../client/components/slide.jsx";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/assets/fallback.png", () => ({
  default: "fallback.png",
}));
vi.mock("../../client/components/slide.scss", () => ({}));
vi.mock("../../client/components/error-boundary.scss", () => ({}));

const mockRenderSlide = vi.fn();
vi.mock("../../shared/slide-utils/templates.js", () => ({
  renderSlide: (...args) => mockRenderSlide(...args),
}));

describe("Slide", () => {
  const slide = {
    "@id": "/v2/slides/TEST01234567890123456789",
    executionId: "EXE-ID-abc123",
    title: "Test Slide",
  };

  beforeEach(() => {
    vi.useFakeTimers();
    mockRenderSlide.mockReturnValue(<div data-testid="rendered">Content</div>);
  });

  afterEach(() => {
    cleanup();
    vi.useRealTimers();
  });

  it("renders with correct id and data-execution-id", () => {
    const { container } = render(
      <Slide
        slide={slide}
        id="slide-1"
        run="12345"
        slideDone={vi.fn()}
        slideError={vi.fn()}
      />
    );

    const el = container.querySelector("#slide-1");
    expect(el).toBeInTheDocument();
    expect(el.getAttribute("data-execution-id")).toBe("EXE-ID-abc123");
    expect(el.getAttribute("data-run")).toBe("12345");
  });

  it("calls renderSlide with slide, run, and slideDone", () => {
    const slideDone = vi.fn();
    render(
      <Slide
        slide={slide}
        id="slide-1"
        run="12345"
        slideDone={slideDone}
        slideError={vi.fn()}
      />
    );

    expect(mockRenderSlide).toHaveBeenCalledWith(slide, "12345", slideDone);
  });

  it("renders the output of renderSlide", () => {
    render(
      <Slide
        slide={slide}
        id="slide-1"
        run="12345"
        slideDone={vi.fn()}
        slideError={vi.fn()}
      />
    );

    expect(screen.getByTestId("rendered")).toBeInTheDocument();
  });

  it("calls slideError after 5 seconds when rendered template throws", () => {
    vi.spyOn(console, "error").mockImplementation(() => {});

    // Return a component that throws during its own render,
    // so ErrorBoundary can catch it (unlike throwing in renderSlide itself).
    function ThrowingTemplate() {
      throw new Error("template crash");
    }
    mockRenderSlide.mockReturnValue(<ThrowingTemplate />);

    const slideError = vi.fn();
    render(
      <Slide
        slide={slide}
        id="slide-1"
        run="12345"
        slideDone={vi.fn()}
        slideError={slideError}
      />
    );

    expect(slideError).not.toHaveBeenCalled();

    vi.advanceTimersByTime(5000);

    expect(slideError).toHaveBeenCalledWith(slide);
  });

  it("renders without crashing when slide has no executionId", () => {
    const slideNoExecId = { "@id": "/v2/slides/TEST01234567890123456789", title: "No exec" };
    const { container } = render(
      <Slide
        slide={slideNoExecId}
        id="slide-no-exec"
        run="12345"
        slideDone={vi.fn()}
        slideError={vi.fn()}
      />
    );

    const el = container.querySelector("#slide-no-exec");
    expect(el).toBeInTheDocument();
    expect(el.getAttribute("data-execution-id")).toBeNull();
  });

  it("does not call slideError if component unmounts before error timeout fires", () => {
    vi.spyOn(console, "error").mockImplementation(() => {});

    function ThrowingTemplate() {
      throw new Error("template crash");
    }
    mockRenderSlide.mockReturnValue(<ThrowingTemplate />);

    const slideError = vi.fn();
    const { unmount } = render(
      <Slide
        slide={slide}
        id="slide-unmount"
        run="12345"
        slideDone={vi.fn()}
        slideError={slideError}
      />
    );

    unmount();
    vi.advanceTimersByTime(5000);

    expect(slideError).not.toHaveBeenCalled();
  });

  it("attaches forwardRef to the slide div", () => {
    const ref = { current: null };
    render(
      <Slide
        slide={slide}
        id="slide-ref"
        run="12345"
        slideDone={vi.fn()}
        slideError={vi.fn()}
        forwardRef={ref}
      />
    );

    expect(ref.current).not.toBeNull();
    expect(ref.current.id).toBe("slide-ref");
    expect(ref.current.classList.contains("slide")).toBe(true);
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen, cleanup } from "@testing-library/react";
import ErrorBoundary from "../../client/components/error-boundary.jsx";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/assets/fallback.png", () => ({
  default: "fallback.png",
}));
vi.mock("../../client/components/error-boundary.scss", () => ({}));

function ThrowingComponent({ message }) {
  throw new Error(message);
}

describe("ErrorBoundary", () => {
  let consoleError;

  beforeEach(() => {
    // Suppress React's console.error for caught errors
    consoleError = vi.spyOn(console, "error").mockImplementation(() => {});
  });

  afterEach(() => {
    cleanup();
  });

  it("renders children when no error", () => {
    render(
      <ErrorBoundary>
        <div data-testid="child">Hello</div>
      </ErrorBoundary>
    );
    expect(screen.getByTestId("child")).toBeInTheDocument();
  });

  it("shows fallback UI when child throws", () => {
    render(
      <ErrorBoundary>
        <ThrowingComponent message="Test error" />
      </ErrorBoundary>
    );
    expect(screen.getByText("Seneste log hændelser")).toBeInTheDocument();
    expect(screen.getByText(/Test error/)).toBeInTheDocument();
  });

  it("calls errorHandler prop when child throws", () => {
    const errorHandler = vi.fn();
    render(
      <ErrorBoundary errorHandler={errorHandler}>
        <ThrowingComponent message="Test error" />
      </ErrorBoundary>
    );
    expect(errorHandler).toHaveBeenCalledTimes(1);
    expect(errorHandler.mock.calls[0][0].message).toBe("Test error");
  });

  it("works without errorHandler prop", () => {
    render(
      <ErrorBoundary>
        <ThrowingComponent message="No handler" />
      </ErrorBoundary>
    );
    expect(screen.getByText(/No handler/)).toBeInTheDocument();
  });

  it("recovers from error state when resetKey changes", () => {
    const { rerender } = render(
      <ErrorBoundary resetKey="a">
        <ThrowingComponent message="boom" />
      </ErrorBoundary>
    );
    expect(screen.getByText(/boom/)).toBeInTheDocument();

    rerender(
      <ErrorBoundary resetKey="b">
        <div data-testid="recovered">OK</div>
      </ErrorBoundary>
    );
    expect(screen.getByTestId("recovered")).toBeInTheDocument();
  });

  it("does not recover when resetKey stays the same", () => {
    const { rerender } = render(
      <ErrorBoundary resetKey="a">
        <ThrowingComponent message="boom" />
      </ErrorBoundary>
    );
    expect(screen.getByText(/boom/)).toBeInTheDocument();

    rerender(
      <ErrorBoundary resetKey="a">
        <div data-testid="child">OK</div>
      </ErrorBoundary>
    );
    expect(screen.queryByTestId("child")).not.toBeInTheDocument();
    expect(screen.getByText(/boom/)).toBeInTheDocument();
  });

  it("calls errorHandler for each error in a sequence with different resetKeys", () => {
    const errorHandler = vi.fn();
    const { rerender } = render(
      <ErrorBoundary resetKey="1" errorHandler={errorHandler}>
        <ThrowingComponent message="error 1" />
      </ErrorBoundary>
    );
    expect(errorHandler).toHaveBeenCalledTimes(1);
    expect(errorHandler.mock.calls[0][0].message).toBe("error 1");

    rerender(
      <ErrorBoundary resetKey="2" errorHandler={errorHandler}>
        <ThrowingComponent message="error 2" />
      </ErrorBoundary>
    );
    expect(errorHandler).toHaveBeenCalledTimes(2);
    expect(errorHandler.mock.calls[1][0].message).toBe("error 2");
  });
});

import { describe, it, expect, vi, beforeEach } from "vitest";
import { render, screen } from "@testing-library/react";
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
});

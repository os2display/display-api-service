import { describe, it, expect, vi, afterEach } from "vitest";
import { render, cleanup, act } from "@testing-library/react";
import Screen from "../../client/components/screen.jsx";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));
vi.mock("../../client/components/screen.scss", () => ({}));
vi.mock("../../client/components/region.scss", () => ({}));
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
  createGrid: (cols, rows) => `"${"a ".repeat(cols).trim()}"${" ".repeat(rows)}`,
  createGridArea: (gridArea) => gridArea.join(" / "),
}));

vi.mock("../../client/core/client-config-loader.js", () => ({
  default: {
    loadConfig: vi.fn().mockResolvedValue({ colorScheme: { type: "browser" } }),
  },
}));

vi.mock("suncalc", () => ({
  default: { getTimes: vi.fn() },
}));

vi.mock("../../shared/slide-utils/templates.js", () => ({
  renderSlide: vi.fn().mockReturnValue(null),
}));

vi.mock("../../client/util/id-from-path", () => ({
  default: (path) => path.split("/").pop(),
}));

vi.mock("../../client/client-state-context.jsx", () => ({
  useClientState: () => ({
    regionSlides: {},
    callbacks: {
      current: {
        onRegionReady: vi.fn(),
        onRegionRemoved: vi.fn(),
        setScreen: vi.fn(),
        setIsContentEmpty: vi.fn(),
        updateRegionSlides: vi.fn(),
      },
    },
  }),
}));

describe("Screen", () => {
  const makeScreen = (regions = [], grid = { rows: 1, columns: 1 }) => ({
    "@id": "/v2/screens/SCREEN01",
    "@type": "Screen",
    layoutData: {
      grid,
      regions,
    },
  });

  it("renders a div with class screen and the screen id", () => {
    const screen = makeScreen();
    const { container } = render(<Screen screen={screen} />);
    const el = container.querySelector(".screen");
    expect(el).toBeInTheDocument();
    expect(el.id).toBe("/v2/screens/SCREEN01");
  });

  it("renders Region components for default regions", () => {
    const screen = makeScreen([
      {
        "@id": "/v2/layouts/regions/R1",
        title: "Region 1",
        gridArea: ["a"],
      },
    ]);
    const { container } = render(<Screen screen={screen} />);
    // Region renders a div with class "region"
    expect(container.querySelector(".region")).toBeInTheDocument();
  });

  it("renders TouchRegion for touch-buttons regions", () => {
    const screen = makeScreen([
      {
        "@id": "/v2/layouts/regions/R2",
        title: "Touch Region",
        gridArea: ["a"],
        type: "touch-buttons",
      },
    ]);
    const { container } = render(<Screen screen={screen} />);
    expect(container.querySelector(".touch-region")).toBeInTheDocument();
  });

  it("renders both Region and TouchRegion when mixed", () => {
    const screen = makeScreen([
      {
        "@id": "/v2/layouts/regions/R1",
        title: "Default",
        gridArea: ["a"],
      },
      {
        "@id": "/v2/layouts/regions/R2",
        title: "Touch",
        gridArea: ["b"],
        type: "touch-buttons",
      },
    ]);
    const { container } = render(<Screen screen={screen} />);
    expect(container.querySelector(".region")).toBeInTheDocument();
    expect(container.querySelector(".touch-region")).toBeInTheDocument();
  });

  it("applies correct grid template values from layout data", () => {
    const screen = makeScreen([], { rows: 2, columns: 3 });
    const { container } = render(<Screen screen={screen} />);
    const el = container.querySelector(".screen");
    // jsdom trims trailing whitespace from CSS values.
    expect(el.style.gridTemplateColumns).toBe("1fr 1fr 1fr");
    expect(el.style.gridTemplateRows).toBe("1fr 1fr");
  });

  it("renders screen div with no child regions when regions array is empty", () => {
    const screen = makeScreen([], { rows: 1, columns: 1 });
    const { container } = render(<Screen screen={screen} />);
    expect(container.querySelector(".screen")).toBeInTheDocument();
    expect(container.querySelector(".region")).not.toBeInTheDocument();
    expect(container.querySelector(".touch-region")).not.toBeInTheDocument();
  });

  it("removes color scheme classes from documentElement on unmount", async () => {
    const screen = makeScreen();
    screen.enableColorSchemeChange = true;

    // Mock matchMedia for browser-based color scheme.
    window.matchMedia = vi.fn().mockReturnValue({ matches: true });

    let unmountFn;
    await act(async () => {
      const { unmount } = render(<Screen screen={screen} />);
      unmountFn = unmount;
    });

    // After config loads, color scheme class should be set.
    expect(
      document.documentElement.classList.contains("color-scheme-dark") ||
      document.documentElement.classList.contains("color-scheme-light"),
    ).toBe(true);

    unmountFn();

    expect(document.documentElement.classList.contains("color-scheme-dark")).toBe(false);
    expect(document.documentElement.classList.contains("color-scheme-light")).toBe(false);
  });
});

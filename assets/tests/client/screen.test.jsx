import { describe, it, expect, vi } from "vitest";
import { render } from "@testing-library/react";
import Screen from "../../client/components/screen.jsx";

vi.mock("../../client/logger/logger", () => ({
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

vi.mock("../../client/util/client-config-loader.js", () => ({
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

  it("applies grid styles from layout data", () => {
    const screen = makeScreen([], { rows: 2, columns: 3 });
    const { container } = render(<Screen screen={screen} />);
    const el = container.querySelector(".screen");
    // gridTemplateColumns and gridTemplateRows are set
    expect(el.style.gridTemplateColumns).toBeTruthy();
    expect(el.style.gridTemplateRows).toBeTruthy();
  });
});

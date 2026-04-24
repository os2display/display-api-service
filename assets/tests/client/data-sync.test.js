import { describe, it, expect, vi } from "vitest";

const mockStart = vi.fn();
const mockStop = vi.fn();

vi.mock("../../client/service/pull-strategy", () => {
  const MockPullStrategy = vi.fn(function (config, onContent) {
    this.config = config;
    this.onContent = onContent;
    this.start = mockStart;
    this.stop = mockStop;
  });
  return { default: MockPullStrategy };
});

import DataSync from "../../client/service/data-sync";
import PullStrategy from "../../client/service/pull-strategy";

describe("DataSync", () => {
  it("creates a PullStrategy with config and onContent callback", () => {
    const onContent = vi.fn();
    const config = { entryPoint: "/v2/screens/ABC", interval: 5000, onContent };

    new DataSync(config);

    expect(PullStrategy).toHaveBeenCalledWith(config, onContent);
  });

  it("delegates start to the strategy", () => {
    const config = { entryPoint: "/v2/screens/ABC", onContent: vi.fn() };
    const sync = new DataSync(config);

    sync.start();

    expect(mockStart).toHaveBeenCalled();
  });

  it("delegates stop to the strategy", () => {
    const config = { entryPoint: "/v2/screens/ABC", onContent: vi.fn() };
    const sync = new DataSync(config);

    sync.stop();

    expect(mockStop).toHaveBeenCalled();
  });

  it("stores config on the instance", () => {
    const config = { entryPoint: "/v2/screens/ABC", onContent: vi.fn() };
    const sync = new DataSync(config);

    expect(sync.config).toBe(config);
  });

  it("binds start and stop so they work when destructured", () => {
    const config = { entryPoint: "/v2/screens/ABC", onContent: vi.fn() };
    const sync = new DataSync(config);
    const { start, stop } = sync;

    start();
    stop();

    expect(mockStart).toHaveBeenCalled();
    expect(mockStop).toHaveBeenCalled();
  });
});

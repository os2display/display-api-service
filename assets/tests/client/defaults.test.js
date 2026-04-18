import { describe, it, expect } from "vitest";
import defaults from "../../client/util/defaults";

describe("defaults", () => {
  it("loginCheckTimeoutDefault is 20 seconds", () => {
    expect(defaults.loginCheckTimeoutDefault).toBe(20000);
  });

  it("refreshTokenTimeoutDefault is 15 minutes", () => {
    expect(defaults.refreshTokenTimeoutDefault).toBe(900000);
  });

  it("releaseTimestampIntervalTimeoutDefault is 10 minutes", () => {
    expect(defaults.releaseTimestampIntervalTimeoutDefault).toBe(600000);
  });
});

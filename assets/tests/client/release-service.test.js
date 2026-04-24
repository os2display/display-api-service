import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/core/client-config-loader.js", () => ({
  default: {
    loadConfig: vi
      .fn()
      .mockResolvedValue({ releaseTimestampIntervalTimeout: 600000 }),
  },
}));

vi.mock("../../client/core/app-storage.js", () => ({
  default: {
    getPreviousBoot: vi.fn().mockReturnValue("1700000000000"),
  },
}));

vi.mock("../../client/service/status-service.js", () => ({
  default: {
    error: null,
    setError: vi.fn(),
    setStatus: vi.fn(),
  },
}));

vi.mock("../../client/util/id-from-path", () => ({
  default: (path) => {
    if (!path) return null;
    const match = path.match(/[A-Za-z0-9]{26}/);
    return match ? match[0] : null;
  },
}));

const mockLoadRelease = vi.fn();
vi.mock("../../shared/release-loader.js", () => ({
  default: { loadRelease: (...args) => mockLoadRelease(...args) },
}));

import statusService from "../../client/service/status-service.js";
import constants from "../../client/util/constants";
import logger from "../../client/core/logger.js";

describe("ReleaseService", () => {
  let releaseService;
  let mockReplace;
  let mockReplaceState;

  beforeEach(async () => {
    vi.useFakeTimers();
    vi.clearAllMocks();
    vi.resetModules();

    mockReplace = vi.fn();
    mockReplaceState = vi.fn();

    vi.stubGlobal("location", {
      href: "http://localhost/?releaseTimestamp=100",
      replace: mockReplace,
    });
    vi.stubGlobal("history", { replaceState: mockReplaceState });

    const module = await import("../../client/service/release-service.js");
    releaseService = module.default;
  });

  afterEach(() => {
    releaseService.stopReleaseCheck();
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  describe("checkForNewRelease", () => {
    it("resolves when release timestamp matches current", async () => {
      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 100,
        releaseVersion: "1.0",
      });

      await expect(releaseService.checkForNewRelease()).resolves.toBeUndefined();
      expect(mockReplace).not.toHaveBeenCalled();
    });

    it("redirects when release timestamp differs", async () => {
      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 200,
        releaseVersion: "2.0",
      });

      await expect(
        releaseService.checkForNewRelease()
      ).rejects.toBeUndefined();

      expect(mockReplace).toHaveBeenCalledTimes(1);
      const redirectUrl = mockReplace.mock.calls[0][0].toString();
      expect(redirectUrl).toContain("releaseTimestamp=200");
      expect(redirectUrl).toContain("releaseVersion=2.0");
    });

    it("redirects when no current timestamp in URL", async () => {
      vi.stubGlobal("location", {
        href: "http://localhost/",
        replace: mockReplace,
      });

      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 200,
        releaseVersion: null,
      });

      // Re-import to pick up new location
      vi.resetModules();
      const mod = await import("../../client/service/release-service.js");

      await expect(mod.default.checkForNewRelease()).rejects.toBeUndefined();
      expect(mockReplace).toHaveBeenCalled();
    });

    it("sets error when release timestamp is null", async () => {
      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: null,
        releaseVersion: null,
      });

      await releaseService.checkForNewRelease();

      expect(statusService.setError).toHaveBeenCalledWith(
        constants.ERROR_RELEASE_FILE_NOT_LOADED
      );
    });

    it("clears error when release loads after previous failure", async () => {
      statusService.error = constants.ERROR_RELEASE_FILE_NOT_LOADED;

      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 100,
        releaseVersion: "1.0",
      });

      await releaseService.checkForNewRelease();

      expect(statusService.setError).toHaveBeenCalledWith(null);
    });

    it("resolves when loadRelease fails", async () => {
      mockLoadRelease.mockRejectedValue(new Error("Network"));

      await expect(releaseService.checkForNewRelease()).resolves.toBeUndefined();
      expect(logger.error).toHaveBeenCalledWith(
        expect.stringContaining("Failed to load release")
      );
    });
  });

  describe("setScreenIdInUrl", () => {
    it("sets screenId in URL search params", () => {
      releaseService.setScreenIdInUrl(
        "/v2/screens/SCREEN0001AAAAAAAAAAAAAAAA"
      );

      expect(mockReplaceState).toHaveBeenCalled();
      const url = mockReplaceState.mock.calls[0][2].toString();
      expect(url).toContain("screenId=SCREEN0001AAAAAAAAAAAAAAAA");
    });
  });

  describe("setPreviousBootInUrl", () => {
    it("sets pb param from appStorage", () => {
      releaseService.setPreviousBootInUrl();

      expect(mockReplaceState).toHaveBeenCalled();
      const url = mockReplaceState.mock.calls[0][2].toString();
      expect(url).toContain("pb=1700000000000");
    });
  });

  describe("startReleaseCheck / stopReleaseCheck", () => {
    it("sets up an interval that calls checkForNewRelease", async () => {
      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 100,
        releaseVersion: "1.0",
      });

      releaseService.startReleaseCheck();
      await vi.advanceTimersByTimeAsync(0); // let config resolve

      expect(releaseService.releaseCheckInterval).not.toBeNull();

      // Advance past one interval tick.
      await vi.advanceTimersByTimeAsync(600000);

      expect(mockLoadRelease).toHaveBeenCalled();
    });

    it("clears interval on stop", async () => {
      mockLoadRelease.mockResolvedValue({
        releaseTimestamp: 100,
        releaseVersion: "1.0",
      });

      releaseService.startReleaseCheck();
      await vi.advanceTimersByTimeAsync(0);

      releaseService.stopReleaseCheck();

      expect(releaseService.releaseCheckInterval).toBeNull();
    });

    it("does not create interval if stopped before config resolves", async () => {
      releaseService.startReleaseCheck();
      releaseService.stopReleaseCheck();

      await vi.advanceTimersByTimeAsync(0);

      expect(releaseService.releaseCheckInterval).toBeNull();
    });
  });
});

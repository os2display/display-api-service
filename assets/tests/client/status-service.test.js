import { describe, it, expect, beforeEach } from "vitest";
import statusService from "../../client/service/status-service";
import constants from "../../client/util/constants";

describe("StatusService", () => {
  beforeEach(() => {
    statusService.status = constants.STATUS_INIT;
    statusService.error = null;
    window.history.replaceState(null, "", "/");
  });

  it("has correct initial state", () => {
    expect(statusService.status).toBe("init");
    expect(statusService.error).toBeNull();
  });

  describe("setStatus", () => {
    it("updates status property", () => {
      statusService.setStatus("running");
      expect(statusService.status).toBe("running");
    });

    it("adds status to URL query params", () => {
      statusService.setStatus("running");
      expect(window.location.search).toContain("status=running");
    });

    it("removes status param when set to falsy", () => {
      statusService.setStatus("running");
      statusService.setStatus(null);
      expect(window.location.search).not.toContain("status=");
    });
  });

  describe("setError", () => {
    it("updates error property", () => {
      statusService.setError("ER101");
      expect(statusService.error).toBe("ER101");
    });

    it("adds error to URL query params", () => {
      statusService.setError("ER101");
      expect(window.location.search).toContain("error=ER101");
    });

    it("removes error param when set to null", () => {
      statusService.setError("ER101");
      statusService.setError(null);
      expect(window.location.search).not.toContain("error=");
    });
  });

  describe("setStatusInUrl", () => {
    it("includes both status and error when both are set", () => {
      statusService.setStatus("running");
      statusService.setError("ER101");
      const params = new URLSearchParams(window.location.search);
      expect(params.get("status")).toBe("running");
      expect(params.get("error")).toBe("ER101");
    });
  });
});

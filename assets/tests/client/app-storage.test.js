import { describe, it, expect, vi, beforeEach } from "vitest";

vi.mock("jwt-decode", () => ({
  default: () => ({ exp: 1700000000, iat: 1699990000 }),
}));

import appStorage from "../../client/core/app-storage";

describe("AppStorage", () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe("token", () => {
    it("setToken stores token, exp and iat", () => {
      appStorage.setToken("fake.jwt.token");
      expect(localStorage.getItem("apiToken")).toBe("fake.jwt.token");
      expect(localStorage.getItem("apiTokenExpire")).toBe("1700000000");
      expect(localStorage.getItem("apiTokenIssuedAt")).toBe("1699990000");
    });

    it("getToken returns stored token", () => {
      localStorage.setItem("apiToken", "my-token");
      expect(appStorage.getToken()).toBe("my-token");
    });

    it("getToken returns null when nothing stored", () => {
      expect(appStorage.getToken()).toBeNull();
    });

    it("getTokenExpire returns parsed integer", () => {
      localStorage.setItem("apiTokenExpire", "1700000000");
      expect(appStorage.getTokenExpire()).toBe(1700000000);
    });

    it("getTokenExpire returns null when nothing stored", () => {
      expect(appStorage.getTokenExpire()).toBeNull();
    });

    it("getTokenIssueAt returns parsed integer", () => {
      localStorage.setItem("apiTokenIssuedAt", "1699990000");
      expect(appStorage.getTokenIssueAt()).toBe(1699990000);
    });

    it("getTokenIssueAt returns null when nothing stored", () => {
      expect(appStorage.getTokenIssueAt()).toBeNull();
    });

    it("clearToken removes all token keys", () => {
      localStorage.setItem("apiToken", "t");
      localStorage.setItem("apiTokenExpire", "1");
      localStorage.setItem("apiTokenIssuedAt", "2");
      appStorage.clearToken();
      expect(localStorage.getItem("apiToken")).toBeNull();
      expect(localStorage.getItem("apiTokenExpire")).toBeNull();
      expect(localStorage.getItem("apiTokenIssuedAt")).toBeNull();
    });
  });

  describe("refreshToken", () => {
    it("round-trips set and get", () => {
      appStorage.setRefreshToken("refresh-123");
      expect(appStorage.getRefreshToken()).toBe("refresh-123");
    });

    it("clearRefreshToken removes the value", () => {
      appStorage.setRefreshToken("refresh-123");
      appStorage.clearRefreshToken();
      expect(appStorage.getRefreshToken()).toBeNull();
    });
  });

  describe("screenId", () => {
    it("round-trips set and get", () => {
      appStorage.setScreenId("screen-abc");
      expect(appStorage.getScreenId()).toBe("screen-abc");
    });

    it("clearScreenId removes the value", () => {
      appStorage.setScreenId("screen-abc");
      appStorage.clearScreenId();
      expect(appStorage.getScreenId()).toBeNull();
    });
  });

  describe("tenant", () => {
    it("setTenant stores both key and id", () => {
      appStorage.setTenant("key-1", "id-1");
      expect(appStorage.getTenantKey()).toBe("key-1");
      expect(appStorage.getTenantId()).toBe("id-1");
    });

    it("clearTenant removes both key and id", () => {
      appStorage.setTenant("key-1", "id-1");
      appStorage.clearTenant();
      expect(appStorage.getTenantKey()).toBeNull();
      expect(appStorage.getTenantId()).toBeNull();
    });
  });

  describe("fallbackImageUrl", () => {
    it("round-trips set and get", () => {
      appStorage.setFallbackImageUrl("https://example.com/img.png");
      expect(appStorage.getFallbackImageUrl()).toBe(
        "https://example.com/img.png"
      );
    });

    it("clearFallbackImageUrl removes the value", () => {
      appStorage.setFallbackImageUrl("https://example.com/img.png");
      appStorage.clearFallbackImageUrl();
      expect(appStorage.getFallbackImageUrl()).toBeNull();
    });
  });

  describe("apiUrl", () => {
    it("setApiUrl stores the value", () => {
      appStorage.setApiUrl("https://api.example.com");
      expect(localStorage.getItem("apiUrl")).toBe("https://api.example.com");
    });
  });

  describe("previousBoot", () => {
    it("returns 0 when nothing stored", () => {
      expect(appStorage.getPreviousBoot()).toBe(0);
    });

    it("round-trips set and get", () => {
      appStorage.setPreviousBoot("1700000000");
      expect(appStorage.getPreviousBoot()).toBe("1700000000");
    });
  });

  describe("clearAppStorage", () => {
    it("clears token, refreshToken, screenId, tenant, and fallbackImage", () => {
      appStorage.setToken("fake.jwt.token");
      appStorage.setRefreshToken("refresh");
      appStorage.setScreenId("screen");
      appStorage.setTenant("key", "id");
      appStorage.setFallbackImageUrl("img.png");
      appStorage.setApiUrl("https://api.example.com");

      appStorage.clearAppStorage();

      expect(appStorage.getToken()).toBeNull();
      expect(appStorage.getRefreshToken()).toBeNull();
      expect(appStorage.getScreenId()).toBeNull();
      expect(appStorage.getTenantKey()).toBeNull();
      expect(appStorage.getFallbackImageUrl()).toBeNull();
      // apiUrl is NOT cleared by clearAppStorage
      expect(localStorage.getItem("apiUrl")).toBe("https://api.example.com");
    });
  });
});

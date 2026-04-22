import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";

// --- Hoisted mocks ---
const { mockRawBaseQuery, mockReauthRef } = vi.hoisted(() => {
  const mockRawBaseQuery = vi.fn().mockResolvedValue({ data: "ok" });
  const mockReauthRef = { current: vi.fn() };
  return { mockRawBaseQuery, mockReauthRef };
});

vi.mock("@reduxjs/toolkit/query/react", () => ({
  fetchBaseQuery: () => mockRawBaseQuery,
}));

vi.mock("../../client/redux/reauthenticate-ref", () => ({
  default: mockReauthRef,
}));

import clientBaseQuery from "../../client/redux/base-query";

describe("clientBaseQuery", () => {
  const api = {};
  const extraOptions = {};

  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    mockRawBaseQuery.mockResolvedValue({ data: "ok" });
    // Default: no preview params.
    vi.stubGlobal("location", { href: "http://localhost/" });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  describe("headers", () => {
    it("should set accept to application/ld+json when no accept header", async () => {
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers.accept).toBe("application/ld+json");
    });

    it("should preserve existing accept header", async () => {
      await clientBaseQuery(
        { url: "/test", headers: { accept: "text/html" } },
        api,
        extraOptions,
      );

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers.accept).toBe("text/html");
    });

    it("should initialize headers object when args has none", async () => {
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers).toBeDefined();
      expect(passedArgs.headers.accept).toBe("application/ld+json");
    });
  });

  describe("authorization", () => {
    it("should use preview-token from URL over localStorage token", async () => {
      vi.stubGlobal("location", {
        href: "http://localhost/?preview-token=preview-jwt",
      });
      localStorage.setItem("apiToken", "stored-jwt");

      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers.authorization).toBe("Bearer preview-jwt");
    });

    it("should use localStorage apiToken when no preview-token", async () => {
      localStorage.setItem("apiToken", "stored-jwt");

      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers.authorization).toBe("Bearer stored-jwt");
    });

    it("should not set authorization when neither exists", async () => {
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers.authorization).toBeUndefined();
    });
  });

  describe("tenant key", () => {
    it("should use preview-tenant from URL over localStorage tenantKey", async () => {
      vi.stubGlobal("location", {
        href: "http://localhost/?preview-tenant=preview-tenant-key",
      });
      localStorage.setItem("tenantKey", "stored-tenant");

      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers["Authorization-Tenant-Key"]).toBe(
        "preview-tenant-key",
      );
    });

    it("should use localStorage tenantKey when no preview-tenant", async () => {
      localStorage.setItem("tenantKey", "stored-tenant");

      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(passedArgs.headers["Authorization-Tenant-Key"]).toBe(
        "stored-tenant",
      );
    });

    it("should not set tenant header when neither exists", async () => {
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      const passedArgs = mockRawBaseQuery.mock.calls[0][0];
      expect(
        passedArgs.headers["Authorization-Tenant-Key"],
      ).toBeUndefined();
    });
  });

  describe("401 handling", () => {
    it("should call reauthenticateRef.current on 401 error", async () => {
      mockRawBaseQuery.mockResolvedValue({ error: { status: 401 } });

      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      expect(mockReauthRef.current).toHaveBeenCalled();
    });

    it("should not call reauthenticateRef on non-401 errors or success", async () => {
      mockRawBaseQuery.mockResolvedValue({ error: { status: 500 } });
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      mockRawBaseQuery.mockResolvedValue({ data: "ok" });
      await clientBaseQuery({ url: "/test" }, api, extraOptions);

      expect(mockReauthRef.current).not.toHaveBeenCalled();
    });
  });
});

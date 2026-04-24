import { describe, it, expect, vi, beforeEach } from "vitest";

const { mockDispatch } = vi.hoisted(() => ({
  mockDispatch: vi.fn(),
}));

vi.mock("../../client/core/logger.js", () => ({
  default: { info: vi.fn(), warn: vi.fn(), error: vi.fn(), log: vi.fn() },
}));

vi.mock("../../client/core/app-storage.js", () => ({
  default: {
    getToken: vi.fn(),
    getTenantKey: vi.fn(),
    getTenantId: vi.fn(),
    setFallbackImageUrl: vi.fn(),
  },
}));

vi.mock("../../client/redux/store.js", () => ({
  clientStore: { dispatch: mockDispatch },
}));

vi.mock("../../client/redux/enhanced-api.ts", () => ({
  clientApi: {
    endpoints: {
      getV2TenantsById: {
        initiate: vi.fn().mockReturnValue("tenantAction"),
      },
    },
    reducerPath: "clientApi",
    reducer: (state = {}) => state,
    middleware: () => (next) => (action) => next(action),
  },
}));

vi.mock("../../client/redux/empty-api.ts", () => ({
  clientEmptySplitApi: {
    injectEndpoints: vi.fn().mockReturnValue({ endpoints: {} }),
  },
}));

import tenantService from "../../client/service/tenant-service";
import appStorage from "../../client/core/app-storage.js";
import logger from "../../client/core/logger.js";

describe("TenantService", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("dispatches getV2TenantsById when credentials are present", () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    mockDispatch.mockReturnValue({
      unwrap: () => Promise.resolve({}),
      unsubscribe: vi.fn(),
    });

    tenantService.loadTenantConfig();

    expect(mockDispatch).toHaveBeenCalled();
  });

  it("sets fallback image URL from tenant data", async () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    mockDispatch.mockReturnValue({
      unwrap: () =>
        Promise.resolve({ fallbackImageUrl: "https://example.com/bg.png" }),
      unsubscribe: vi.fn(),
    });

    tenantService.loadTenantConfig();

    // Wait for the promise chain.
    await new Promise((r) => setTimeout(r, 0));

    expect(appStorage.setFallbackImageUrl).toHaveBeenCalledWith(
      "https://example.com/bg.png"
    );
  });

  it("does not set fallback image when not present in tenant data", async () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    mockDispatch.mockReturnValue({
      unwrap: () => Promise.resolve({}),
      unsubscribe: vi.fn(),
    });

    tenantService.loadTenantConfig();
    await new Promise((r) => setTimeout(r, 0));

    expect(appStorage.setFallbackImageUrl).not.toHaveBeenCalled();
  });

  it("does nothing when token is missing", () => {
    appStorage.getToken.mockReturnValue(null);
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    tenantService.loadTenantConfig();

    expect(mockDispatch).not.toHaveBeenCalled();
  });

  it("does nothing when tenantKey is missing", () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue(null);
    appStorage.getTenantId.mockReturnValue("tenant-123");

    tenantService.loadTenantConfig();

    expect(mockDispatch).not.toHaveBeenCalled();
  });

  it("does nothing when tenantId is missing", () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue(null);

    tenantService.loadTenantConfig();

    expect(mockDispatch).not.toHaveBeenCalled();
  });

  it("logs error and unsubscribes on fetch failure", async () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    const unsubscribe = vi.fn();
    mockDispatch.mockReturnValue({
      unwrap: () => Promise.reject(new Error("Network error")),
      unsubscribe,
    });

    tenantService.loadTenantConfig();
    await new Promise((r) => setTimeout(r, 0));

    expect(logger.error).toHaveBeenCalledWith(
      expect.stringContaining("Failed to load tenant config")
    );
    expect(unsubscribe).toHaveBeenCalled();
  });

  it("unsubscribes after successful fetch", async () => {
    appStorage.getToken.mockReturnValue("jwt-token");
    appStorage.getTenantKey.mockReturnValue("tenant-key");
    appStorage.getTenantId.mockReturnValue("tenant-123");

    const unsubscribe = vi.fn();
    mockDispatch.mockReturnValue({
      unwrap: () => Promise.resolve({}),
      unsubscribe,
    });

    tenantService.loadTenantConfig();
    await new Promise((r) => setTimeout(r, 0));

    expect(unsubscribe).toHaveBeenCalled();
  });
});

import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup, waitFor } from "@testing-library/react";

const {
  capturedDropdownData,
  initiateMock,
  dispatchMock,
  useGetV2TenantsQueryMock,
} = vi.hoisted(() => ({
  capturedDropdownData: { current: null },
  initiateMock: vi.fn(),
  dispatchMock: vi.fn(),
  useGetV2TenantsQueryMock: vi.fn(),
}));

vi.mock(
  "../../admin/components/util/forms/multiselect-dropdown/tenants/tenants-dropdown",
  () => ({
    default: (props) => {
      capturedDropdownData.current = props.data;
      return null;
    },
  }),
);

vi.mock("../../admin/components/util/schedule/schedule", () => ({
  default: () => null,
}));

vi.mock("../../admin/components/util/content-body/content-body", () => ({
  default: ({ children }) => children,
}));

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

vi.mock("react-bootstrap", () => ({
  Alert: ({ children }) => children,
}));

vi.mock("react-redux", () => ({
  useDispatch: () => dispatchMock,
}));

vi.mock("../../shared/redux/enhanced-api.ts", () => ({
  enhancedApi: {
    endpoints: { getV2Tenants: { initiate: initiateMock } },
  },
  useGetV2TenantsQuery: useGetV2TenantsQueryMock,
}));

import UserContext from "../../admin/context/user-context";
import PlaylistForm from "../../admin/components/playlist/playlist-form";

function makeTenants(start, count) {
  return Array.from({ length: count }, (_, i) => ({
    "@id": `/v2/tenants/t${start + i}`,
    "@type": "Tenant",
    tenantKey: `tenant-${start + i}`,
    title: `Tenant ${start + i}`,
    description: "",
  }));
}

const page1 = makeTenants(1, 30);
const page2 = makeTenants(31, 5);
const allTenants = [...page1, ...page2];

beforeEach(() => {
  capturedDropdownData.current = null;
  initiateMock.mockReset();
  dispatchMock.mockReset();
  useGetV2TenantsQueryMock.mockReset();

  initiateMock.mockImplementation((params) => ({ __initiate: true, params }));
  dispatchMock.mockImplementation((action) => {
    const params = action?.params ?? {};
    const page = params.page ?? 1;
    if (page === 1) {
      return Promise.resolve({
        data: {
          "hydra:member": page1,
          "hydra:view": { "hydra:next": "/v2/tenants?page=2" },
        },
      });
    }
    if (page === 2) {
      return Promise.resolve({
        data: { "hydra:member": page2, "hydra:view": {} },
      });
    }
    return Promise.resolve({
      data: { "hydra:member": [], "hydra:view": {} },
    });
  });

  useGetV2TenantsQueryMock.mockReturnValue({
    data: { "hydra:member": page1 },
  });
});

afterEach(() => cleanup());

describe("PlaylistForm tenants picker pagination", () => {
  it("populates the share-target dropdown with tenants from every page", async () => {
    const playlist = { schedules: [], tenants: [] };
    const userContextValue = {
      selectedTenant: { get: { tenantKey: "current" } },
    };

    render(
      <UserContext.Provider value={userContextValue}>
        <PlaylistForm playlist={playlist} handleInput={vi.fn()} />
      </UserContext.Provider>,
    );

    await waitFor(() => {
      expect(capturedDropdownData.current).not.toBeNull();
    });

    expect(capturedDropdownData.current).toHaveLength(allTenants.length);
    expect(capturedDropdownData.current.map((t) => t.tenantKey)).toEqual(
      allTenants.map((t) => t.tenantKey),
    );
  });
});

import { test, expect } from "@playwright/test";

/**
 * Regression tests for the CampaignsButton.onClick promise chain.
 *
 * The onClick handler chains nested .then() calls. Inner promises must be
 * returned so that rejections propagate to the outer .catch() and
 * setLoading(false) is always called. Without the returns, a rejection in
 * any inner call (getAllScreenGroupCampaigns, second getAllPages,
 * getAllCampaigns) leaves the button in a permanent loading state.
 *
 * These tests replicate the promise structure from campaigns-button.jsx
 * with controllable mocks.
 */

/**
 * Replicates the onClick promise chain from CampaignsButton.
 * Inner promises are returned so rejections propagate to the outer .catch().
 */
function onClick({
  getAllPagesScreenGroups,
  getAllScreenGroupCampaigns,
  getAllPagesScreenCampaigns,
  getAllCampaigns,
  setLoading,
  setCampaigns,
}) {
  setLoading(true);

  getAllPagesScreenGroups()
    .then((screenGroups) => {
      const screenGroupIds = screenGroups
        .filter(({ campaignsLength }) => campaignsLength > 0)
        .map((group) => group.id);

      return getAllScreenGroupCampaigns(screenGroupIds).then(
        (screenGroupCampaigns) => {
          return getAllPagesScreenCampaigns().then((screenCampaigns) => {
            const campaignIds = [
              ...screenGroupCampaigns,
              ...screenCampaigns,
            ].map((c) => c.id);

            return getAllCampaigns(campaignIds).then((allCampaigns) => {
              setCampaigns(allCampaigns);
              setLoading(false);
            });
          });
        },
      );
    })
    .catch(() => setLoading(false));
}

function createMocks({ failAt } = {}) {
  const state = { loading: false, campaigns: [] };

  return {
    state,
    setLoading: (v) => {
      state.loading = v;
    },
    setCampaigns: (v) => {
      state.campaigns = v;
    },
    getAllPagesScreenGroups:
      failAt === "screenGroups"
        ? () => Promise.reject(new Error("screenGroups failed"))
        : () =>
            Promise.resolve([
              { id: "group1", campaignsLength: 2, "@id": "/v2/groups/group1" },
            ]),
    getAllScreenGroupCampaigns:
      failAt === "screenGroupCampaigns"
        ? () => Promise.reject(new Error("screenGroupCampaigns failed"))
        : () =>
            Promise.resolve([
              { id: "campaign1", campaign: { "@id": "/v2/playlists/c1" } },
            ]),
    getAllPagesScreenCampaigns:
      failAt === "screenCampaigns"
        ? () => Promise.reject(new Error("screenCampaigns failed"))
        : () =>
            Promise.resolve([
              { id: "campaign2", campaign: { "@id": "/v2/playlists/c2" } },
            ]),
    getAllCampaigns:
      failAt === "allCampaigns"
        ? () => Promise.reject(new Error("allCampaigns failed"))
        : (ids) => Promise.resolve(ids.map((id) => ({ "@id": id, title: id }))),
  };
}

test.describe("CampaignsButton onClick promise chain", () => {
  test("happy path resolves and clears loading", async () => {
    const mocks = createMocks();
    onClick(mocks);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mocks.state.loading).toBe(false);
    expect(mocks.state.campaigns.length).toBeGreaterThan(0);
  });

  test("rejection in getAllPagesScreenGroups clears loading", async () => {
    const mocks = createMocks({ failAt: "screenGroups" });
    onClick(mocks);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mocks.state.loading).toBe(false);
  });

  test("rejection in getAllScreenGroupCampaigns clears loading", async () => {
    const mocks = createMocks({ failAt: "screenGroupCampaigns" });
    onClick(mocks);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mocks.state.loading).toBe(false);
  });

  test("rejection in getAllPagesScreenCampaigns clears loading", async () => {
    const mocks = createMocks({ failAt: "screenCampaigns" });
    onClick(mocks);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mocks.state.loading).toBe(false);
  });

  test("rejection in getAllCampaigns clears loading", async () => {
    const mocks = createMocks({ failAt: "allCampaigns" });
    onClick(mocks);

    await new Promise((resolve) => setTimeout(resolve, 50));

    expect(mocks.state.loading).toBe(false);
  });
});

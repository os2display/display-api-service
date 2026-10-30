import { api as generatedApi } from "./generated-api";

// Invalidate the following tags for the given endpoints:
// endpointName: ["TagToInvalidate1", "TagToInvalidate2"]
// @see addTagTypes from generated-api.ts for available tags.
const invalidatesTagsForEndpoints = {
  postLoginInfoScreen: ["Authentication"],
  postRefreshTokenItem: ["Authentication"],

  postV2FeedSources: ["Feeds", "FeedSources"],
  putV2FeedSourcesById: ["Feeds", "FeedSources"],
  deleteV2FeedSourcesById: ["Feeds", "FeedSources"],

  postMediaCollection: ["Media"],
  deleteV2MediaById: ["Media"],

  postV2Playlists: ["Playlists"],
  putV2PlaylistsById: ["Playlists"],
  deleteV2PlaylistsById: ["Playlists"],

  putV2PlaylistsByIdSlides: ["Playlists", "Slides"],
  deleteV2PlaylistsByIdSlidesAndSlideId: ["Playlists", "Slides"],
  putV2SlidesByIdPlaylists: ["Playlists", "Slides"],

  postV2ScreenGroups: ["ScreenGroups"],
  putV2ScreenGroupsById: ["ScreenGroups"],
  deleteV2ScreenGroupsById: ["ScreenGroups"],

  putV2ScreenGroupsByIdCampaigns: ["Playlists", "ScreenGroups"],
  deleteV2ScreenGroupsByIdCampaignsAndCampaignId: ["Playlists", "ScreenGroups"],

  postV2Screens: ["Screens", "ScreenGroupCampaign", "ScreenGroups", "Playlists"],
  putV2ScreensById: ["Screens", "ScreenGroupCampaign", "ScreenGroups", "Playlists"],
  deleteV2ScreensById: ["Screens", "ScreenGroupCampaign", "ScreenGroups", "Playlists"],

  putV2ScreensByIdCampaigns: ["Screens", "ScreenGroupCampaign"],
  deleteV2ScreensByIdCampaignsAndCampaignId: ["Screens", "ScreenGroupCampaign"],

  putPlaylistScreenRegionItem: ["Screens", "Playlists"],
  deletePlaylistScreenRegionItem: ["Screens", "Playlists"],

  putV2ScreensByIdScreenGroups: ["Screens", "ScreenGroups"],
  deleteV2ScreensByIdScreenGroupsAndScreenGroupId: ["Screens", "ScreenGroups"],

  postScreenBindKey: ["Screens"],
  postScreenUnbind: ["Screens"],

  postV2Slides: ["Slides"],
  putV2SlidesById: ["Slides"],
  deleteV2SlidesById: ["Slides"],

  apiSlidePerformAction: [],

  postV2Themes: ["Themes"],
  putV2ThemesById: ["Themes"],
  deleteV2ThemesById: ["Themes"],

  postV2Users: ["User"],
  putV2UsersById: ["User"],
  deleteV2UsersById: ["User"],
  deleteV2UsersByIdRemoveFromTenant: ["User"],

  postV2UserActivationCodes: ["UserActivationCode"],
  postV2UserActivationCodesActivate: ["UserActivationCode", "User"],
  postV2UserActivationCodesRefresh: ["UserActivationCode"],
  deleteV2UserActivationCodesById: ["UserActivationCode"],
};

// Enhance the api with specifications about what should be invalidated for the
// given endpoints.
export const enhancedApi = generatedApi.enhanceEndpoints({
  // @ts-ignore
  endpoints: Object.fromEntries(
    // @ts-ignore
    Object.entries(generatedApi.endpoints).map(([key, endpoint]) => {
      const enhancedEndpoint = {
        ...endpoint,
        invalidatesTags: ["region"],
      };

      if (invalidatesTagsForEndpoints.hasOwnProperty(key)) {
        enhancedEndpoint.invalidatesTags = invalidatesTagsForEndpoints[key];
      }

      return [key, enhancedEndpoint];
    })
  )}
);

// These hooks are copied from generated-api.
// If new endpoints are added to the OpenAPI spec, this list should be updated.
export const {
  useGetOidcAuthTokenItemQuery,
  useGetOidcAuthUrlsItemQuery,
  usePostLoginInfoScreenMutation,
  usePostRefreshTokenItemMutation,
  useGetV2FeedSourcesQuery,
  usePostV2FeedSourcesMutation,
  useGetV2FeedSourcesByIdQuery,
  usePutV2FeedSourcesByIdMutation,
  useDeleteV2FeedSourcesByIdMutation,
  useGetV2FeedSourcesByIdConfigAndNameQuery,
  useGetV2FeedSourcesByIdSlidesQuery,
  useGetV2FeedsQuery,
  useGetV2FeedsByIdQuery,
  useGetV2FeedsByIdDataQuery,
  useGetV2LayoutsQuery,
  useGetV2LayoutsByIdQuery,
  useLoginCheckPostMutation,
  useGetV2MediaQuery,
  usePostMediaCollectionMutation,
  useGetv2MediaByIdQuery,
  useDeleteV2MediaByIdMutation,
  useGetV2CampaignsByIdScreenGroupsQuery,
  useGetV2CampaignsByIdScreensQuery,
  useGetV2PlaylistsQuery,
  usePostV2PlaylistsMutation,
  useGetV2PlaylistsByIdQuery,
  usePutV2PlaylistsByIdMutation,
  useDeleteV2PlaylistsByIdMutation,
  useGetV2PlaylistsByIdSlidesQuery,
  usePutV2PlaylistsByIdSlidesMutation,
  useDeleteV2PlaylistsByIdSlidesAndSlideIdMutation,
  useGetV2SlidesByIdPlaylistsQuery,
  usePutV2SlidesByIdPlaylistsMutation,
  useGetScreenGroupCampaignItemQuery,
  useGetV2ScreenGroupsQuery,
  usePostV2ScreenGroupsMutation,
  useGetV2ScreenGroupsByIdQuery,
  usePutV2ScreenGroupsByIdMutation,
  useDeleteV2ScreenGroupsByIdMutation,
  useGetV2ScreenGroupsByIdCampaignsQuery,
  usePutV2ScreenGroupsByIdCampaignsMutation,
  useDeleteV2ScreenGroupsByIdCampaignsAndCampaignIdMutation,
  useGetV2ScreenGroupsByIdScreensQuery,
  useGetV2ScreensQuery,
  usePostV2ScreensMutation,
  useGetV2ScreensByIdQuery,
  usePutV2ScreensByIdMutation,
  useDeleteV2ScreensByIdMutation,
  usePostScreenBindKeyMutation,
  useGetV2ScreensByIdCampaignsQuery,
  usePutV2ScreensByIdCampaignsMutation,
  useDeleteV2ScreensByIdCampaignsAndCampaignIdMutation,
  useGetV2ScreensByIdRegionsAndRegionIdPlaylistsQuery,
  usePutPlaylistScreenRegionItemMutation,
  useDeletePlaylistScreenRegionItemMutation,
  useGetV2ScreensByIdScreenGroupsQuery,
  usePutV2ScreensByIdScreenGroupsMutation,
  useDeleteV2ScreensByIdScreenGroupsAndScreenGroupIdMutation,
  usePostScreenUnbindMutation,
  useGetV2SlidesQuery,
  usePostV2SlidesMutation,
  useGetV2SlidesByIdQuery,
  usePutV2SlidesByIdMutation,
  useDeleteV2SlidesByIdMutation,
  useApiSlidePerformActionMutation,
  useGetV2TemplatesQuery,
  useGetV2TemplatesByIdQuery,
  useGetV2TenantsQuery,
  useGetV2TenantsByIdQuery,
  useGetV2ThemesQuery,
  usePostV2ThemesMutation,
  useGetV2ThemesByIdQuery,
  usePutV2ThemesByIdMutation,
  useDeleteV2ThemesByIdMutation,
  useGetV2UsersQuery,
  usePostV2UsersMutation,
  useGetV2UsersByIdQuery,
  usePutV2UsersByIdMutation,
  useDeleteV2UsersByIdMutation,
  useDeleteV2UsersByIdRemoveFromTenantMutation,
  useGetV2UserActivationCodesQuery,
  usePostV2UserActivationCodesMutation,
  usePostV2UserActivationCodesActivateMutation,
  usePostV2UserActivationCodesRefreshMutation,
  useGetV2UserActivationCodesByIdQuery,
  useDeleteV2UserActivationCodesByIdMutation,
} = enhancedApi;

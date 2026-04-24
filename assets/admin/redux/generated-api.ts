import { emptySplitApi as api } from "./empty-api";
export const addTagTypes = [
  "Authentication",
  "FeedSources",
  "Feeds",
  "Layouts",
  "Login Check",
  "Media",
  "Playlists",
  "ScreenGroupCampaign",
  "ScreenGroups",
  "Screens",
  "Slides",
  "Templates",
  "Tenants",
  "Themes",
  "User",
  "UserActivationCode",
] as const;
const injectedRtkApi = api
  .enhanceEndpoints({
    addTagTypes,
  })
  .injectEndpoints({
    endpoints: (build) => ({
      getOidcAuthTokenItem: build.query<
        GetOidcAuthTokenItemApiResponse,
        GetOidcAuthTokenItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/authentication/oidc/token`,
          params: {
            state: queryArg.state,
            code: queryArg.code,
          },
        }),
        providesTags: ["Authentication"],
      }),
      getOidcAuthUrlsItem: build.query<
        GetOidcAuthUrlsItemApiResponse,
        GetOidcAuthUrlsItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/authentication/oidc/urls`,
          params: {
            providerKey: queryArg.providerKey,
          },
        }),
        providesTags: ["Authentication"],
      }),
      postLoginInfoScreen: build.mutation<
        PostLoginInfoScreenApiResponse,
        PostLoginInfoScreenApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/authentication/screen`,
          method: "POST",
          body: queryArg.screenLoginInput,
        }),
        invalidatesTags: ["Authentication"],
      }),
      postRefreshTokenItem: build.mutation<
        PostRefreshTokenItemApiResponse,
        PostRefreshTokenItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/authentication/token/refresh`,
          method: "POST",
          body: queryArg.refreshTokenRequest,
        }),
        invalidatesTags: ["Authentication"],
      }),
      getV2FeedSources: build.query<
        GetV2FeedSourcesApiResponse,
        GetV2FeedSourcesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            supportedFeedOutputType: queryArg.supportedFeedOutputType,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["FeedSources"],
      }),
      postV2FeedSources: build.mutation<
        PostV2FeedSourcesApiResponse,
        PostV2FeedSourcesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources`,
          method: "POST",
          body: queryArg.feedSourceFeedSourceInputJsonld,
        }),
        invalidatesTags: ["FeedSources"],
      }),
      getV2FeedSourcesById: build.query<
        GetV2FeedSourcesByIdApiResponse,
        GetV2FeedSourcesByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/feed-sources/${queryArg.id}` }),
        providesTags: ["FeedSources"],
      }),
      putV2FeedSourcesById: build.mutation<
        PutV2FeedSourcesByIdApiResponse,
        PutV2FeedSourcesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources/${queryArg.id}`,
          method: "PUT",
          body: queryArg.feedSourceFeedSourceInputJsonld,
        }),
        invalidatesTags: ["FeedSources"],
      }),
      deleteV2FeedSourcesById: build.mutation<
        DeleteV2FeedSourcesByIdApiResponse,
        DeleteV2FeedSourcesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["FeedSources"],
      }),
      getV2FeedSourcesByIdConfigAndName: build.query<
        GetV2FeedSourcesByIdConfigAndNameApiResponse,
        GetV2FeedSourcesByIdConfigAndNameApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources/${queryArg.id}/config/${queryArg.name}`,
        }),
        providesTags: ["FeedSources"],
      }),
      getV2FeedSourcesByIdSlides: build.query<
        GetV2FeedSourcesByIdSlidesApiResponse,
        GetV2FeedSourcesByIdSlidesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/feed-sources/${queryArg.id}/slides`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            published: queryArg.published,
            order: queryArg.order,
          },
        }),
        providesTags: ["FeedSources"],
      }),
      getV2Feeds: build.query<GetV2FeedsApiResponse, GetV2FeedsApiArg>({
        query: (queryArg) => ({
          url: `/v2/feeds`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["Feeds"],
      }),
      getV2FeedsById: build.query<
        GetV2FeedsByIdApiResponse,
        GetV2FeedsByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/feeds/${queryArg.id}` }),
        providesTags: ["Feeds"],
      }),
      getV2FeedsByIdData: build.query<
        GetV2FeedsByIdDataApiResponse,
        GetV2FeedsByIdDataApiArg
      >({
        query: (queryArg) => ({ url: `/v2/feeds/${queryArg.id}/data` }),
        providesTags: ["Feeds"],
      }),
      getV2Layouts: build.query<GetV2LayoutsApiResponse, GetV2LayoutsApiArg>({
        query: (queryArg) => ({
          url: `/v2/layouts`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
          },
        }),
        providesTags: ["Layouts"],
      }),
      getV2LayoutsById: build.query<
        GetV2LayoutsByIdApiResponse,
        GetV2LayoutsByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/layouts/${queryArg.id}` }),
        providesTags: ["Layouts"],
      }),
      loginCheckPost: build.mutation<
        LoginCheckPostApiResponse,
        LoginCheckPostApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/authentication/token`,
          method: "POST",
          body: queryArg.body,
        }),
        invalidatesTags: ["Login Check"],
      }),
      getV2Media: build.query<GetV2MediaApiResponse, GetV2MediaApiArg>({
        query: (queryArg) => ({
          url: `/v2/media`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["Media"],
      }),
      postMediaCollection: build.mutation<
        PostMediaCollectionApiResponse,
        PostMediaCollectionApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/media`,
          method: "POST",
          body: queryArg.body,
        }),
        invalidatesTags: ["Media"],
      }),
      getV2MediaById: build.query<
        GetV2MediaByIdApiResponse,
        GetV2MediaByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/media/${queryArg.id}` }),
        providesTags: ["Media"],
      }),
      deleteV2MediaById: build.mutation<
        DeleteV2MediaByIdApiResponse,
        DeleteV2MediaByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/media/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Media"],
      }),
      getV2CampaignsByIdScreenGroups: build.query<
        GetV2CampaignsByIdScreenGroupsApiResponse,
        GetV2CampaignsByIdScreenGroupsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/campaigns/${queryArg.id}/screen-groups`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
          },
        }),
        providesTags: ["Playlists"],
      }),
      getV2CampaignsByIdScreens: build.query<
        GetV2CampaignsByIdScreensApiResponse,
        GetV2CampaignsByIdScreensApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/campaigns/${queryArg.id}/screens`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
          },
        }),
        providesTags: ["Playlists"],
      }),
      getV2Playlists: build.query<
        GetV2PlaylistsApiResponse,
        GetV2PlaylistsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            published: queryArg.published,
            isCampaign: queryArg.isCampaign,
            order: queryArg.order,
            sharedWithMe: queryArg.sharedWithMe,
          },
        }),
        providesTags: ["Playlists"],
      }),
      postV2Playlists: build.mutation<
        PostV2PlaylistsApiResponse,
        PostV2PlaylistsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists`,
          method: "POST",
          body: queryArg.playlistPlaylistInputJsonld,
        }),
        invalidatesTags: ["Playlists"],
      }),
      getV2PlaylistsById: build.query<
        GetV2PlaylistsByIdApiResponse,
        GetV2PlaylistsByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/playlists/${queryArg.id}` }),
        providesTags: ["Playlists"],
      }),
      putV2PlaylistsById: build.mutation<
        PutV2PlaylistsByIdApiResponse,
        PutV2PlaylistsByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists/${queryArg.id}`,
          method: "PUT",
          body: queryArg.playlistPlaylistInputJsonld,
        }),
        invalidatesTags: ["Playlists"],
      }),
      deleteV2PlaylistsById: build.mutation<
        DeleteV2PlaylistsByIdApiResponse,
        DeleteV2PlaylistsByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Playlists"],
      }),
      getV2PlaylistsByIdSlides: build.query<
        GetV2PlaylistsByIdSlidesApiResponse,
        GetV2PlaylistsByIdSlidesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists/${queryArg.id}/slides`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            published: queryArg.published,
          },
        }),
        providesTags: ["Playlists"],
      }),
      putV2PlaylistsByIdSlides: build.mutation<
        PutV2PlaylistsByIdSlidesApiResponse,
        PutV2PlaylistsByIdSlidesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists/${queryArg.id}/slides`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["Playlists"],
      }),
      deleteV2PlaylistsByIdSlidesAndSlideId: build.mutation<
        DeleteV2PlaylistsByIdSlidesAndSlideIdApiResponse,
        DeleteV2PlaylistsByIdSlidesAndSlideIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/playlists/${queryArg.id}/slides/${queryArg.slideId}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Playlists"],
      }),
      getV2SlidesByIdPlaylists: build.query<
        GetV2SlidesByIdPlaylistsApiResponse,
        GetV2SlidesByIdPlaylistsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/slides/${queryArg.id}/playlists`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            published: queryArg.published,
          },
        }),
        providesTags: ["Playlists"],
      }),
      putV2SlidesByIdPlaylists: build.mutation<
        PutV2SlidesByIdPlaylistsApiResponse,
        PutV2SlidesByIdPlaylistsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/slides/${queryArg.id}/playlists`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["Playlists"],
      }),
      getScreenGroupCampaignItem: build.query<
        GetScreenGroupCampaignItemApiResponse,
        GetScreenGroupCampaignItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups-campaigns/${queryArg.id}`,
        }),
        providesTags: ["ScreenGroupCampaign"],
      }),
      getV2ScreenGroups: build.query<
        GetV2ScreenGroupsApiResponse,
        GetV2ScreenGroupsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["ScreenGroups"],
      }),
      postV2ScreenGroups: build.mutation<
        PostV2ScreenGroupsApiResponse,
        PostV2ScreenGroupsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups`,
          method: "POST",
          body: queryArg.screenGroupScreenGroupInputJsonld,
        }),
        invalidatesTags: ["ScreenGroups"],
      }),
      getV2ScreenGroupsById: build.query<
        GetV2ScreenGroupsByIdApiResponse,
        GetV2ScreenGroupsByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/screen-groups/${queryArg.id}` }),
        providesTags: ["ScreenGroups"],
      }),
      putV2ScreenGroupsById: build.mutation<
        PutV2ScreenGroupsByIdApiResponse,
        PutV2ScreenGroupsByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}`,
          method: "PUT",
          body: queryArg.screenGroupScreenGroupInputJsonld,
        }),
        invalidatesTags: ["ScreenGroups"],
      }),
      deleteV2ScreenGroupsById: build.mutation<
        DeleteV2ScreenGroupsByIdApiResponse,
        DeleteV2ScreenGroupsByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["ScreenGroups"],
      }),
      getV2ScreenGroupsByIdCampaigns: build.query<
        GetV2ScreenGroupsByIdCampaignsApiResponse,
        GetV2ScreenGroupsByIdCampaignsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}/campaigns`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            published: queryArg.published,
          },
        }),
        providesTags: ["ScreenGroups"],
      }),
      putV2ScreenGroupsByIdCampaigns: build.mutation<
        PutV2ScreenGroupsByIdCampaignsApiResponse,
        PutV2ScreenGroupsByIdCampaignsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}/campaigns`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["ScreenGroups"],
      }),
      deleteV2ScreenGroupsByIdCampaignsAndCampaignId: build.mutation<
        DeleteV2ScreenGroupsByIdCampaignsAndCampaignIdApiResponse,
        DeleteV2ScreenGroupsByIdCampaignsAndCampaignIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}/campaigns/${queryArg.campaignId}`,
          method: "DELETE",
        }),
        invalidatesTags: ["ScreenGroups"],
      }),
      getV2ScreenGroupsByIdScreens: build.query<
        GetV2ScreenGroupsByIdScreensApiResponse,
        GetV2ScreenGroupsByIdScreensApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screen-groups/${queryArg.id}/screens`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
          },
        }),
        providesTags: ["ScreenGroups"],
      }),
      getV2Screens: build.query<GetV2ScreensApiResponse, GetV2ScreensApiArg>({
        query: (queryArg) => ({
          url: `/v2/screens`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            search: queryArg.search,
            exists: queryArg.exists,
            "screenUser.latestRequest": queryArg["screenUser.latestRequest"],
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["Screens"],
      }),
      postV2Screens: build.mutation<
        PostV2ScreensApiResponse,
        PostV2ScreensApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens`,
          method: "POST",
          body: queryArg.screenScreenInputJsonld,
        }),
        invalidatesTags: ["Screens"],
      }),
      getV2ScreensById: build.query<
        GetV2ScreensByIdApiResponse,
        GetV2ScreensByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/screens/${queryArg.id}` }),
        providesTags: ["Screens"],
      }),
      putV2ScreensById: build.mutation<
        PutV2ScreensByIdApiResponse,
        PutV2ScreensByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}`,
          method: "PUT",
          body: queryArg.screenScreenInputJsonld,
        }),
        invalidatesTags: ["Screens"],
      }),
      deleteV2ScreensById: build.mutation<
        DeleteV2ScreensByIdApiResponse,
        DeleteV2ScreensByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Screens"],
      }),
      postScreenBindKey: build.mutation<
        PostScreenBindKeyApiResponse,
        PostScreenBindKeyApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/bind`,
          method: "POST",
          body: queryArg.screenBindObject,
        }),
        invalidatesTags: ["Screens"],
      }),
      getV2ScreensByIdCampaigns: build.query<
        GetV2ScreensByIdCampaignsApiResponse,
        GetV2ScreensByIdCampaignsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/campaigns`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            published: queryArg.published,
          },
        }),
        providesTags: ["Screens"],
      }),
      putV2ScreensByIdCampaigns: build.mutation<
        PutV2ScreensByIdCampaignsApiResponse,
        PutV2ScreensByIdCampaignsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/campaigns`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["Screens"],
      }),
      deleteV2ScreensByIdCampaignsAndCampaignId: build.mutation<
        DeleteV2ScreensByIdCampaignsAndCampaignIdApiResponse,
        DeleteV2ScreensByIdCampaignsAndCampaignIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/campaigns/${queryArg.campaignId}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Screens"],
      }),
      getV2ScreensByIdRegionsAndRegionIdPlaylists: build.query<
        GetV2ScreensByIdRegionsAndRegionIdPlaylistsApiResponse,
        GetV2ScreensByIdRegionsAndRegionIdPlaylistsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/regions/${queryArg.regionId}/playlists`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            sharedWithMe: queryArg.sharedWithMe,
          },
        }),
        providesTags: ["Screens"],
      }),
      putPlaylistScreenRegionItem: build.mutation<
        PutPlaylistScreenRegionItemApiResponse,
        PutPlaylistScreenRegionItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/regions/${queryArg.regionId}/playlists`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["Screens"],
      }),
      deletePlaylistScreenRegionItem: build.mutation<
        DeletePlaylistScreenRegionItemApiResponse,
        DeletePlaylistScreenRegionItemApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/regions/${queryArg.regionId}/playlists/${queryArg.playlistId}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Screens"],
      }),
      getV2ScreensByIdScreenGroups: build.query<
        GetV2ScreensByIdScreenGroupsApiResponse,
        GetV2ScreensByIdScreenGroupsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/screen-groups`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            order: queryArg.order,
          },
        }),
        providesTags: ["Screens"],
      }),
      putV2ScreensByIdScreenGroups: build.mutation<
        PutV2ScreensByIdScreenGroupsApiResponse,
        PutV2ScreensByIdScreenGroupsApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/screen-groups`,
          method: "PUT",
          body: queryArg.body,
        }),
        invalidatesTags: ["Screens"],
      }),
      deleteV2ScreensByIdScreenGroupsAndScreenGroupId: build.mutation<
        DeleteV2ScreensByIdScreenGroupsAndScreenGroupIdApiResponse,
        DeleteV2ScreensByIdScreenGroupsAndScreenGroupIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/screen-groups/${queryArg.screenGroupId}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Screens"],
      }),
      postScreenUnbind: build.mutation<
        PostScreenUnbindApiResponse,
        PostScreenUnbindApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/screens/${queryArg.id}/unbind`,
          method: "POST",
          body: queryArg.body,
        }),
        invalidatesTags: ["Screens"],
      }),
      getV2Slides: build.query<GetV2SlidesApiResponse, GetV2SlidesApiArg>({
        query: (queryArg) => ({
          url: `/v2/slides`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            published: queryArg.published,
            order: queryArg.order,
          },
        }),
        providesTags: ["Slides"],
      }),
      postV2Slides: build.mutation<PostV2SlidesApiResponse, PostV2SlidesApiArg>(
        {
          query: (queryArg) => ({
            url: `/v2/slides`,
            method: "POST",
            body: queryArg.slideSlideInputJsonld,
          }),
          invalidatesTags: ["Slides"],
        },
      ),
      getV2SlidesById: build.query<
        GetV2SlidesByIdApiResponse,
        GetV2SlidesByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/slides/${queryArg.id}` }),
        providesTags: ["Slides"],
      }),
      putV2SlidesById: build.mutation<
        PutV2SlidesByIdApiResponse,
        PutV2SlidesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/slides/${queryArg.id}`,
          method: "PUT",
          body: queryArg.slideSlideInputJsonld,
        }),
        invalidatesTags: ["Slides"],
      }),
      deleteV2SlidesById: build.mutation<
        DeleteV2SlidesByIdApiResponse,
        DeleteV2SlidesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/slides/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Slides"],
      }),
      apiSlidePerformAction: build.mutation<
        ApiSlidePerformActionApiResponse,
        ApiSlidePerformActionApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/slides/${queryArg.id}/action`,
          method: "POST",
          body: queryArg.slideInteractiveSlideActionInputJsonld,
        }),
        invalidatesTags: ["Slides"],
      }),
      getV2Templates: build.query<
        GetV2TemplatesApiResponse,
        GetV2TemplatesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/templates`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["Templates"],
      }),
      getV2TemplatesById: build.query<
        GetV2TemplatesByIdApiResponse,
        GetV2TemplatesByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/templates/${queryArg.id}` }),
        providesTags: ["Templates"],
      }),
      getV2Tenants: build.query<GetV2TenantsApiResponse, GetV2TenantsApiArg>({
        query: (queryArg) => ({
          url: `/v2/tenants`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
          },
        }),
        providesTags: ["Tenants"],
      }),
      getV2TenantsById: build.query<
        GetV2TenantsByIdApiResponse,
        GetV2TenantsByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/tenants/${queryArg.id}` }),
        providesTags: ["Tenants"],
      }),
      getV2Themes: build.query<GetV2ThemesApiResponse, GetV2ThemesApiArg>({
        query: (queryArg) => ({
          url: `/v2/themes`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            title: queryArg.title,
            description: queryArg.description,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["Themes"],
      }),
      postV2Themes: build.mutation<PostV2ThemesApiResponse, PostV2ThemesApiArg>(
        {
          query: (queryArg) => ({
            url: `/v2/themes`,
            method: "POST",
            body: queryArg.themeThemeInputJsonld,
          }),
          invalidatesTags: ["Themes"],
        },
      ),
      getV2ThemesById: build.query<
        GetV2ThemesByIdApiResponse,
        GetV2ThemesByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/themes/${queryArg.id}` }),
        providesTags: ["Themes"],
      }),
      putV2ThemesById: build.mutation<
        PutV2ThemesByIdApiResponse,
        PutV2ThemesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/themes/${queryArg.id}`,
          method: "PUT",
          body: queryArg.themeThemeInputJsonld,
        }),
        invalidatesTags: ["Themes"],
      }),
      deleteV2ThemesById: build.mutation<
        DeleteV2ThemesByIdApiResponse,
        DeleteV2ThemesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/themes/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["Themes"],
      }),
      getV2Users: build.query<GetV2UsersApiResponse, GetV2UsersApiArg>({
        query: (queryArg) => ({
          url: `/v2/users`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
            fullName: queryArg.fullName,
            email: queryArg.email,
            createdBy: queryArg.createdBy,
            modifiedBy: queryArg.modifiedBy,
            order: queryArg.order,
          },
        }),
        providesTags: ["User"],
      }),
      postV2Users: build.mutation<PostV2UsersApiResponse, PostV2UsersApiArg>({
        query: (queryArg) => ({
          url: `/v2/users`,
          method: "POST",
          body: queryArg.userUserInputJsonld,
        }),
        invalidatesTags: ["User"],
      }),
      getV2UsersById: build.query<
        GetV2UsersByIdApiResponse,
        GetV2UsersByIdApiArg
      >({
        query: (queryArg) => ({ url: `/v2/users/${queryArg.id}` }),
        providesTags: ["User"],
      }),
      putV2UsersById: build.mutation<
        PutV2UsersByIdApiResponse,
        PutV2UsersByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/users/${queryArg.id}`,
          method: "PUT",
          body: queryArg.userUserInputJsonld,
        }),
        invalidatesTags: ["User"],
      }),
      deleteV2UsersById: build.mutation<
        DeleteV2UsersByIdApiResponse,
        DeleteV2UsersByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/users/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["User"],
      }),
      deleteV2UsersByIdRemoveFromTenant: build.mutation<
        DeleteV2UsersByIdRemoveFromTenantApiResponse,
        DeleteV2UsersByIdRemoveFromTenantApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/users/${queryArg.id}/remove-from-tenant`,
          method: "DELETE",
        }),
        invalidatesTags: ["User"],
      }),
      getV2UserActivationCodes: build.query<
        GetV2UserActivationCodesApiResponse,
        GetV2UserActivationCodesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes`,
          params: {
            page: queryArg.page,
            itemsPerPage: queryArg.itemsPerPage,
          },
        }),
        providesTags: ["UserActivationCode"],
      }),
      postV2UserActivationCodes: build.mutation<
        PostV2UserActivationCodesApiResponse,
        PostV2UserActivationCodesApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes`,
          method: "POST",
          body: queryArg.userActivationCodeUserActivationCodeInputJsonld,
        }),
        invalidatesTags: ["UserActivationCode"],
      }),
      postV2UserActivationCodesActivate: build.mutation<
        PostV2UserActivationCodesActivateApiResponse,
        PostV2UserActivationCodesActivateApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes/activate`,
          method: "POST",
          body: queryArg.userActivationCodeActivationCodeJsonld,
        }),
        invalidatesTags: ["UserActivationCode"],
      }),
      postV2UserActivationCodesRefresh: build.mutation<
        PostV2UserActivationCodesRefreshApiResponse,
        PostV2UserActivationCodesRefreshApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes/refresh`,
          method: "POST",
          body: queryArg.userActivationCodeActivationCodeJsonld,
        }),
        invalidatesTags: ["UserActivationCode"],
      }),
      getV2UserActivationCodesById: build.query<
        GetV2UserActivationCodesByIdApiResponse,
        GetV2UserActivationCodesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes/${queryArg.id}`,
        }),
        providesTags: ["UserActivationCode"],
      }),
      deleteV2UserActivationCodesById: build.mutation<
        DeleteV2UserActivationCodesByIdApiResponse,
        DeleteV2UserActivationCodesByIdApiArg
      >({
        query: (queryArg) => ({
          url: `/v2/user-activation-codes/${queryArg.id}`,
          method: "DELETE",
        }),
        invalidatesTags: ["UserActivationCode"],
      }),
    }),
    overrideExisting: false,
  });
export { injectedRtkApi as api };
export type GetOidcAuthTokenItemApiResponse =
  /** status 200 Get JWT token from OIDC code */ TokenRead;
export type GetOidcAuthTokenItemApiArg = {
  /** OIDC state */
  state?: string;
  /** OIDC code */
  code?: string;
};
export type GetOidcAuthUrlsItemApiResponse =
  /** status 200 Get authentication and end session endpoints */ OidcEndpoints;
export type GetOidcAuthUrlsItemApiArg = {
  /** The key for the provider to use. Leave out to use the default provider */
  providerKey?: string;
};
export type PostLoginInfoScreenApiResponse =
  /** status 200 Login with bindKey to get JWT token for screen */ ScreenLoginOutputRead;
export type PostLoginInfoScreenApiArg = {
  /** Get login info with JWT token for given nonce */
  screenLoginInput: ScreenLoginInput;
};
export type PostRefreshTokenItemApiResponse =
  /** status 200 Refresh JWT token */ RefreshTokenResponseRead;
export type PostRefreshTokenItemApiArg = {
  /** Refresh JWT Token */
  refreshTokenRequest: RefreshTokenRequest;
};
export type GetV2FeedSourcesApiResponse = /** status 200 OK */ Blob;
export type GetV2FeedSourcesApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  supportedFeedOutputType?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type PostV2FeedSourcesApiResponse =
  /** status 201 FeedSource resource created */ FeedSourceFeedSourceJsonldRead;
export type PostV2FeedSourcesApiArg = {
  /** The new FeedSource resource */
  feedSourceFeedSourceInputJsonld: FeedSourceFeedSourceInputJsonld;
};
export type GetV2FeedSourcesByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2FeedSourcesByIdApiArg = {
  id: string;
};
export type PutV2FeedSourcesByIdApiResponse =
  /** status 200 FeedSource resource updated */ FeedSourceFeedSourceJsonldRead;
export type PutV2FeedSourcesByIdApiArg = {
  id: string;
  /** The updated FeedSource resource */
  feedSourceFeedSourceInputJsonld: FeedSourceFeedSourceInputJsonld;
};
export type DeleteV2FeedSourcesByIdApiResponse = unknown;
export type DeleteV2FeedSourcesByIdApiArg = {
  id: string;
};
export type GetV2FeedSourcesByIdConfigAndNameApiResponse =
  /** status 200 undefined */ Blob;
export type GetV2FeedSourcesByIdConfigAndNameApiArg = {
  id: string;
  name: string;
};
export type GetV2FeedSourcesByIdSlidesApiResponse = /** status 200 OK */ Blob;
export type GetV2FeedSourcesByIdSlidesApiArg = {
  id: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  /** If true only published content will be shown */
  published?: boolean;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type GetV2FeedsApiResponse = /** status 200 OK */ Blob;
export type GetV2FeedsApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type GetV2FeedsByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2FeedsByIdApiArg = {
  id: string;
};
export type GetV2FeedsByIdDataApiResponse = /** status 200 undefined */ Blob;
export type GetV2FeedsByIdDataApiArg = {
  id: string;
};
export type GetV2LayoutsApiResponse = /** status 200 OK */ Blob;
export type GetV2LayoutsApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: number;
};
export type GetV2LayoutsByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2LayoutsByIdApiArg = {
  id: string;
};
export type LoginCheckPostApiResponse = /** status 200 User token created */ {
  token: string;
};
export type LoginCheckPostApiArg = {
  /** The login data */
  body: {
    providerId: string;
    password: string;
  };
};
export type GetV2MediaApiResponse = /** status 200 OK */ Blob;
export type GetV2MediaApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type PostMediaCollectionApiResponse =
  /** status 201 Media resource created */ MediaMediaJsonldRead;
export type PostMediaCollectionApiArg = {
  body: {
    title: string;
    description: string;
    license: string;
    file: Blob;
  };
};
export type GetV2MediaByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2MediaByIdApiArg = {
  id: string;
};
export type DeleteV2MediaByIdApiResponse = unknown;
export type DeleteV2MediaByIdApiArg = {
  id: string;
};
export type GetV2CampaignsByIdScreenGroupsApiResponse =
  /** status 200 ScreenGroupCampaign collection */ {
    "hydra:member": ScreenGroupCampaignJsonldCampaignsScreenGroupsReadRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2CampaignsByIdScreenGroupsApiArg = {
  id: string;
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
};
export type GetV2CampaignsByIdScreensApiResponse =
  /** status 200 ScreenCampaign collection */ {
    "hydra:member": ScreenCampaignJsonldCampaignsScreensReadRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2CampaignsByIdScreensApiArg = {
  id: string;
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
};
export type GetV2PlaylistsApiResponse = /** status 200 OK */ Blob;
export type GetV2PlaylistsApiArg = {
  page: number;
  /** The number of items per page */
  itemsPerPage?: number;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  /** If true only published content will be shown */
  published?: boolean;
  /** If true only campaigns will be shown */
  isCampaign?: boolean;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
  /** If true only entities that are shared with me will be shown */
  sharedWithMe?: boolean;
};
export type PostV2PlaylistsApiResponse =
  /** status 201 Playlist resource created */ PlaylistPlaylistJsonldRead;
export type PostV2PlaylistsApiArg = {
  /** The new Playlist resource */
  playlistPlaylistInputJsonld: PlaylistPlaylistInputJsonld;
};
export type GetV2PlaylistsByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2PlaylistsByIdApiArg = {
  id: string;
};
export type PutV2PlaylistsByIdApiResponse =
  /** status 200 Playlist resource updated */ PlaylistPlaylistJsonldRead;
export type PutV2PlaylistsByIdApiArg = {
  id: string;
  /** The updated Playlist resource */
  playlistPlaylistInputJsonld: PlaylistPlaylistInputJsonld;
};
export type DeleteV2PlaylistsByIdApiResponse = unknown;
export type DeleteV2PlaylistsByIdApiArg = {
  id: string;
};
export type GetV2PlaylistsByIdSlidesApiResponse = /** status 200 OK */ Blob;
export type GetV2PlaylistsByIdSlidesApiArg = {
  id: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** If true only published content will be shown */
  published?: boolean;
};
export type PutV2PlaylistsByIdSlidesApiResponse = /** status 201 Created */ {
  slide?: string;
  playlist?: string;
  weight?: number;
}[];
export type PutV2PlaylistsByIdSlidesApiArg = {
  /** PlaylistSlide identifier */
  id: string;
  body: {
    /** Slide ULID */
    slide?: string;
    weight?: number;
  }[];
};
export type DeleteV2PlaylistsByIdSlidesAndSlideIdApiResponse = unknown;
export type DeleteV2PlaylistsByIdSlidesAndSlideIdApiArg = {
  id: string;
  slideId: string;
};
export type GetV2SlidesByIdPlaylistsApiResponse = /** status 200 OK */ Blob;
export type GetV2SlidesByIdPlaylistsApiArg = {
  id: string;
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** If true only published content will be shown */
  published?: boolean;
};
export type PutV2SlidesByIdPlaylistsApiResponse =
  /** status 200 PlaylistSlide resource updated */ PlaylistSlidePlaylistSlideJsonldRead;
export type PutV2SlidesByIdPlaylistsApiArg = {
  id: string;
  body: {
    /** Playlist ULID */
    playlist?: string;
  }[];
};
export type GetScreenGroupCampaignItemApiResponse =
  /** status 200 ScreenGroupCampaign resource */ ScreenGroupCampaignJsonldRead;
export type GetScreenGroupCampaignItemApiArg = {
  /** ScreenGroupCampaign identifier */
  id: string;
};
export type GetV2ScreenGroupsApiResponse = /** status 200 OK */ Blob;
export type GetV2ScreenGroupsApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
  };
};
export type PostV2ScreenGroupsApiResponse =
  /** status 201 ScreenGroup resource created */ ScreenGroupScreenGroupJsonldRead;
export type PostV2ScreenGroupsApiArg = {
  /** The new ScreenGroup resource */
  screenGroupScreenGroupInputJsonld: ScreenGroupScreenGroupInputJsonld;
};
export type GetV2ScreenGroupsByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2ScreenGroupsByIdApiArg = {
  id: string;
};
export type PutV2ScreenGroupsByIdApiResponse =
  /** status 200 ScreenGroup resource updated */ ScreenGroupScreenGroupJsonldRead;
export type PutV2ScreenGroupsByIdApiArg = {
  id: string;
  /** The updated ScreenGroup resource */
  screenGroupScreenGroupInputJsonld: ScreenGroupScreenGroupInputJsonld;
};
export type DeleteV2ScreenGroupsByIdApiResponse = unknown;
export type DeleteV2ScreenGroupsByIdApiArg = {
  id: string;
};
export type GetV2ScreenGroupsByIdCampaignsApiResponse =
  /** status 200 OK */ Blob;
export type GetV2ScreenGroupsByIdCampaignsApiArg = {
  id: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** If true only published content will be shown */
  published?: boolean;
};
export type PutV2ScreenGroupsByIdCampaignsApiResponse =
  /** status 201 Created */ {
    playlist?: string;
    "screen-group"?: string;
  }[];
export type PutV2ScreenGroupsByIdCampaignsApiArg = {
  /** ScreenGroupCampaign identifier */
  id: string;
  body: {
    /** Screen group ULID */
    screenGroup?: string;
  }[];
};
export type DeleteV2ScreenGroupsByIdCampaignsAndCampaignIdApiResponse = unknown;
export type DeleteV2ScreenGroupsByIdCampaignsAndCampaignIdApiArg = {
  id: string;
  campaignId: string;
};
export type GetV2ScreenGroupsByIdScreensApiResponse =
  /** status 200 ScreenGroup collection */ {
    "hydra:member": ScreenGroupJsonldScreenGroupsScreensReadRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2ScreenGroupsByIdScreensApiArg = {
  id: string;
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
};
export type GetV2ScreensApiResponse = /** status 200 OK */ Blob;
export type GetV2ScreensApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** Search on both location and title */
  search?: string;
  exists?: {
    screenUser?: boolean;
  };
  "screenUser.latestRequest"?: {
    before?: string;
    strictly_before?: string;
    after?: string;
    strictly_after?: string;
  };
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type PostV2ScreensApiResponse =
  /** status 201 Screen resource created */ ScreenScreenJsonldRead;
export type PostV2ScreensApiArg = {
  /** The new Screen resource */
  screenScreenInputJsonld: ScreenScreenInputJsonld;
};
export type GetV2ScreensByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2ScreensByIdApiArg = {
  id: string;
};
export type PutV2ScreensByIdApiResponse =
  /** status 200 Screen resource updated */ ScreenScreenJsonldRead;
export type PutV2ScreensByIdApiArg = {
  id: string;
  /** The updated Screen resource */
  screenScreenInputJsonld: ScreenScreenInputJsonld;
};
export type DeleteV2ScreensByIdApiResponse = unknown;
export type DeleteV2ScreensByIdApiArg = {
  id: string;
};
export type PostScreenBindKeyApiResponse = unknown;
export type PostScreenBindKeyApiArg = {
  /** The screen id */
  id: string;
  /** Bind the screen with the bind key */
  screenBindObject: ScreenBindObject;
};
export type GetV2ScreensByIdCampaignsApiResponse = /** status 200 OK */ Blob;
export type GetV2ScreensByIdCampaignsApiArg = {
  id: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** If true only published content will be shown */
  published?: boolean;
};
export type PutV2ScreensByIdCampaignsApiResponse = /** status 201 Created */ {
  playlist?: string;
  screen?: string;
}[];
export type PutV2ScreensByIdCampaignsApiArg = {
  /** ScreenCampaign identifier */
  id: string;
  body: {
    /** Screen ULID */
    screen?: string;
  }[];
};
export type DeleteV2ScreensByIdCampaignsAndCampaignIdApiResponse = unknown;
export type DeleteV2ScreensByIdCampaignsAndCampaignIdApiArg = {
  id: string;
  campaignId: string;
};
export type GetV2ScreensByIdRegionsAndRegionIdPlaylistsApiResponse =
  /** status 200 PlaylistScreenRegion collection */ {
    "hydra:member": PlaylistScreenRegionJsonldPlaylistScreenRegionReadRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2ScreensByIdRegionsAndRegionIdPlaylistsApiArg = {
  id: string;
  regionId: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  /** If true only entities that are shared with me will be shown */
  sharedWithMe?: boolean;
};
export type PutPlaylistScreenRegionItemApiResponse = unknown;
export type PutPlaylistScreenRegionItemApiArg = {
  id: string;
  regionId: string;
  body: {
    /** Playlist ULID */
    playlist?: string;
    weight?: number;
  }[];
};
export type DeletePlaylistScreenRegionItemApiResponse = unknown;
export type DeletePlaylistScreenRegionItemApiArg = {
  id: string;
  regionId: string;
  playlistId: string;
};
export type GetV2ScreensByIdScreenGroupsApiResponse =
  /** status 200 ScreenGroup collection */ {
    "hydra:member": ScreenGroupScreenGroupJsonldScreensScreenGroupsReadRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2ScreensByIdScreenGroupsApiArg = {
  id: string;
  page: number;
  /** The number of items per page */
  itemsPerPage?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
  };
};
export type PutV2ScreensByIdScreenGroupsApiResponse = /** status 200 OK */ Blob;
export type PutV2ScreensByIdScreenGroupsApiArg = {
  id: string;
  body: string[];
};
export type DeleteV2ScreensByIdScreenGroupsAndScreenGroupIdApiResponse =
  unknown;
export type DeleteV2ScreensByIdScreenGroupsAndScreenGroupIdApiArg = {
  id: string;
  screenGroupId: string;
};
export type PostScreenUnbindApiResponse = unknown;
export type PostScreenUnbindApiArg = {
  /** The screen id */
  id: string;
  /** Unbind from machine */
  body: string;
};
export type GetV2SlidesApiResponse = /** status 200 OK */ Blob;
export type GetV2SlidesApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  /** If true only published content will be shown */
  published?: boolean;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type PostV2SlidesApiResponse =
  /** status 201 Slide resource created */ SlideSlideJsonldRead;
export type PostV2SlidesApiArg = {
  /** The new Slide resource */
  slideSlideInputJsonld: SlideSlideInputJsonld;
};
export type GetV2SlidesByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2SlidesByIdApiArg = {
  id: string;
};
export type PutV2SlidesByIdApiResponse =
  /** status 200 Slide resource updated */ SlideSlideJsonldRead;
export type PutV2SlidesByIdApiArg = {
  id: string;
  /** The updated Slide resource */
  slideSlideInputJsonld: SlideSlideInputJsonld;
};
export type DeleteV2SlidesByIdApiResponse = unknown;
export type DeleteV2SlidesByIdApiArg = {
  id: string;
};
export type ApiSlidePerformActionApiResponse =
  /** status 201 Slide resource created */ SlideSlideJsonldRead;
export type ApiSlidePerformActionApiArg = {
  id: string;
  /** The new Slide resource */
  slideInteractiveSlideActionInputJsonld: SlideInteractiveSlideActionInputJsonld;
};
export type GetV2TemplatesApiResponse = /** status 200 OK */ Blob;
export type GetV2TemplatesApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type GetV2TemplatesByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2TemplatesByIdApiArg = {
  id: string;
};
export type GetV2TenantsApiResponse = /** status 200 OK */ Blob;
export type GetV2TenantsApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
};
export type GetV2TenantsByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2TenantsByIdApiArg = {
  id: string;
};
export type GetV2ThemesApiResponse = /** status 200 OK */ Blob;
export type GetV2ThemesApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  title?: string;
  description?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    title?: "asc" | "desc";
    description?: "asc" | "desc";
    createdAt?: "asc" | "desc";
    modifiedAt?: "asc" | "desc";
  };
};
export type PostV2ThemesApiResponse =
  /** status 201 Theme resource created */ ThemeThemeJsonldRead;
export type PostV2ThemesApiArg = {
  /** The new Theme resource */
  themeThemeInputJsonld: ThemeThemeInputJsonld;
};
export type GetV2ThemesByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2ThemesByIdApiArg = {
  id: string;
};
export type PutV2ThemesByIdApiResponse =
  /** status 200 Theme resource updated */ ThemeThemeJsonldRead;
export type PutV2ThemesByIdApiArg = {
  id: string;
  /** The updated Theme resource */
  themeThemeInputJsonld: ThemeThemeInputJsonld;
};
export type DeleteV2ThemesByIdApiResponse = unknown;
export type DeleteV2ThemesByIdApiArg = {
  id: string;
};
export type GetV2UsersApiResponse = /** status 200 OK */ Blob;
export type GetV2UsersApiArg = {
  page?: number;
  /** The number of items per page */
  itemsPerPage?: string;
  fullName?: string;
  email?: string;
  createdBy?: string;
  modifiedBy?: string;
  order?: {
    createdAt?: "asc" | "desc";
  };
};
export type PostV2UsersApiResponse =
  /** status 201 User resource created */ UserUserJsonldRead;
export type PostV2UsersApiArg = {
  id: string;
  /** The new User resource */
  userUserInputJsonld: UserUserInputJsonld;
};
export type GetV2UsersByIdApiResponse = /** status 200 OK */ Blob;
export type GetV2UsersByIdApiArg = {
  id: string;
};
export type PutV2UsersByIdApiResponse =
  /** status 200 User resource updated */ UserUserJsonldRead;
export type PutV2UsersByIdApiArg = {
  id: string;
  /** The updated User resource */
  userUserInputJsonld: UserUserInputJsonld;
};
export type DeleteV2UsersByIdApiResponse = unknown;
export type DeleteV2UsersByIdApiArg = {
  id: string;
};
export type DeleteV2UsersByIdRemoveFromTenantApiResponse = unknown;
export type DeleteV2UsersByIdRemoveFromTenantApiArg = {
  id: string;
};
export type GetV2UserActivationCodesApiResponse =
  /** status 200 UserActivationCode collection */ {
    "hydra:member": UserActivationCodeUserActivationCodeJsonldRead[];
    "hydra:totalItems"?: number;
    "hydra:view"?: {
      "@id"?: string;
      "@type"?: string;
      "hydra:first"?: string;
      "hydra:last"?: string;
      "hydra:previous"?: string;
      "hydra:next"?: string;
    };
    "hydra:search"?: {
      "@type"?: string;
      "hydra:template"?: string;
      "hydra:variableRepresentation"?: string;
      "hydra:mapping"?: {
        "@type"?: string;
        variable?: string;
        property?: string | null;
        required?: boolean;
      }[];
    };
  };
export type GetV2UserActivationCodesApiArg = {
  /** The collection page number */
  page?: number;
  /** The number of items per page */
  itemsPerPage?: number;
};
export type PostV2UserActivationCodesApiResponse =
  /** status 201 UserActivationCode resource created */ UserActivationCodeUserActivationCodeJsonldRead;
export type PostV2UserActivationCodesApiArg = {
  /** The new UserActivationCode resource */
  userActivationCodeUserActivationCodeInputJsonld: UserActivationCodeUserActivationCodeInputJsonld;
};
export type PostV2UserActivationCodesActivateApiResponse =
  /** status 201 UserActivationCode resource created */ UserActivationCodeUserActivationCodeJsonldRead;
export type PostV2UserActivationCodesActivateApiArg = {
  /** The new UserActivationCode resource */
  userActivationCodeActivationCodeJsonld: UserActivationCodeActivationCodeJsonld;
};
export type PostV2UserActivationCodesRefreshApiResponse =
  /** status 201 UserActivationCode resource created */ UserActivationCodeUserActivationCodeJsonldRead;
export type PostV2UserActivationCodesRefreshApiArg = {
  /** The new UserActivationCode resource */
  userActivationCodeActivationCodeJsonld: UserActivationCodeActivationCodeJsonld;
};
export type GetV2UserActivationCodesByIdApiResponse =
  /** status 200 UserActivationCode resource */ UserActivationCodeJsonldRead;
export type GetV2UserActivationCodesByIdApiArg = {
  /** UserActivationCode identifier */
  id: string;
};
export type DeleteV2UserActivationCodesByIdApiResponse = unknown;
export type DeleteV2UserActivationCodesByIdApiArg = {
  /** UserActivationCode identifier */
  id: string;
};
export type Token = {};
export type TokenRead = {
  token?: string;
  refresh_token?: string;
  refresh_token_expiration?: any;
  tenants?: {
    tenantKey?: string;
    title?: string;
    description?: string;
    roles?: string[];
  }[];
  user?: {
    fullname?: string;
    email?: string;
  };
};
export type OidcEndpoints = {
  authorizationUrl?: string;
  endSessionUrl?: string;
};
export type ScreenLoginOutput = {};
export type ScreenLoginOutputRead = {
  bindKey?: string;
  token?: string;
};
export type ScreenLoginInput = object;
export type RefreshTokenResponse = {};
export type RefreshTokenResponseRead = {
  token?: string;
  refresh_token?: string;
};
export type RefreshTokenRequest = {
  refresh_token?: string;
};
export type FeedSourceFeedSourceJsonld = {
  title?: string;
  description?: string;
  outputType?: string;
  feedType?: string;
  secrets?: string[];
  feeds?: string[];
  admin?: string[];
  supportedFeedOutputType?: string;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type FeedSourceFeedSourceJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  outputType?: string;
  feedType?: string;
  secrets?: string[];
  feeds?: string[];
  admin?: string[];
  supportedFeedOutputType?: string;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type FeedSourceFeedSourceInputJsonld = {
  title?: string;
  description?: string;
  outputType?: string;
  feedType?: string;
  secrets?: string[];
  feeds?: string[];
  supportedFeedOutputType?: string;
};
export type CollectionJsonld = {};
export type CollectionJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  /** Checks whether the collection is empty (contains no elements). */
  empty?: boolean;
  /** Gets all keys/indices of the collection. */
  keys?: number[] | string[];
  /** Gets all values of the collection. */
  values?: string[];
  iterator?: any;
};
export type MediaMediaJsonld = {
  title?: string;
  description?: string;
  license?: string;
  media?: CollectionJsonld;
  assets?: string[];
  thumbnail?: string | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type MediaMediaJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  license?: string;
  media?: CollectionJsonldRead;
  assets?: string[];
  thumbnail?: string | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type CollectionJsonldCampaignsScreenGroupsRead = {};
export type CollectionJsonldCampaignsScreenGroupsReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
};
export type PlaylistJsonldCampaignsScreenGroupsRead = {
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldCampaignsScreenGroupsRead | null;
  campaignScreenGroups?: CollectionJsonldCampaignsScreenGroupsRead | null;
  tenants?: CollectionJsonldCampaignsScreenGroupsRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type PlaylistJsonldCampaignsScreenGroupsReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldCampaignsScreenGroupsReadRead | null;
  campaignScreenGroups?: CollectionJsonldCampaignsScreenGroupsReadRead | null;
  tenants?: CollectionJsonldCampaignsScreenGroupsReadRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type ScreenGroupJsonldCampaignsScreenGroupsRead = {
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  relationsChecksum?: object;
};
export type ScreenGroupJsonldCampaignsScreenGroupsReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  relationsChecksum?: object;
};
export type ScreenGroupCampaignJsonldCampaignsScreenGroupsRead = {
  campaign?: PlaylistJsonldCampaignsScreenGroupsRead;
  screenGroup?: ScreenGroupJsonldCampaignsScreenGroupsRead;
  relationsChecksum?: object;
};
export type ScreenGroupCampaignJsonldCampaignsScreenGroupsReadRead = {
  "@id"?: string;
  "@type"?: string;
  campaign?: PlaylistJsonldCampaignsScreenGroupsReadRead;
  screenGroup?: ScreenGroupJsonldCampaignsScreenGroupsReadRead;
  relationsChecksum?: object;
};
export type CollectionJsonldCampaignsScreensRead = {};
export type CollectionJsonldCampaignsScreensReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
};
export type PlaylistJsonldCampaignsScreensRead = {
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldCampaignsScreensRead | null;
  campaignScreenGroups?: CollectionJsonldCampaignsScreensRead | null;
  tenants?: CollectionJsonldCampaignsScreensRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type PlaylistJsonldCampaignsScreensReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldCampaignsScreensReadRead | null;
  campaignScreenGroups?: CollectionJsonldCampaignsScreensReadRead | null;
  tenants?: CollectionJsonldCampaignsScreensReadRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type ScreenJsonldCampaignsScreensRead = {
  title?: string;
  description?: string;
  size?: string;
  campaigns?: string;
  layout?: string;
  orientation?: string;
  resolution?: string;
  location?: string;
  regions?: string[];
  inScreenGroups?: string;
  screenUser?: string | null;
  enableColorSchemeChange?: boolean | null;
  status?: string[] | null;
  activeCampaignsLength?: number | null;
  campaignsLength?: number | null;
  inScreenGroupsLength?: number | null;
  relationsChecksum?: object;
};
export type ScreenJsonldCampaignsScreensReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  size?: string;
  campaigns?: string;
  layout?: string;
  orientation?: string;
  resolution?: string;
  location?: string;
  regions?: string[];
  inScreenGroups?: string;
  screenUser?: string | null;
  enableColorSchemeChange?: boolean | null;
  status?: string[] | null;
  activeCampaignsLength?: number | null;
  campaignsLength?: number | null;
  inScreenGroupsLength?: number | null;
  relationsChecksum?: object;
};
export type ScreenCampaignJsonldCampaignsScreensRead = {
  campaign?: PlaylistJsonldCampaignsScreensRead;
  screen?: ScreenJsonldCampaignsScreensRead;
  relationsChecksum?: object;
};
export type ScreenCampaignJsonldCampaignsScreensReadRead = {
  "@id"?: string;
  "@type"?: string;
  campaign?: PlaylistJsonldCampaignsScreensReadRead;
  screen?: ScreenJsonldCampaignsScreensReadRead;
  relationsChecksum?: object;
};
export type PlaylistPlaylistJsonld = {
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonld | null;
  campaignScreenGroups?: CollectionJsonld | null;
  tenants?: CollectionJsonld | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type PlaylistPlaylistJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldRead | null;
  campaignScreenGroups?: CollectionJsonldRead | null;
  tenants?: CollectionJsonldRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type PlaylistPlaylistInputJsonld = {
  title?: string;
  description?: string;
  schedules?: string[];
  tenants?: string[];
  isCampaign?: boolean;
  published?: string[];
};
export type PlaylistSlidePlaylistSlideJsonld = {
  slide?: string;
  playlist?: string;
  weight?: number;
  id?: string;
  relationsChecksum?: object;
};
export type PlaylistSlidePlaylistSlideJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  slide?: string;
  playlist?: string;
  weight?: number;
  id?: string;
  relationsChecksum?: object;
};
export type ScreenGroupCampaignJsonld = {
  campaign?: string;
  screenGroup?: string;
  id?: string;
  relationsChecksum?: object;
};
export type ScreenGroupCampaignJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  campaign?: string;
  screenGroup?: string;
  id?: string;
  relationsChecksum?: object;
};
export type ScreenGroupScreenGroupJsonld = {
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type ScreenGroupScreenGroupJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type ScreenGroupScreenGroupInputJsonld = {
  title?: string;
  description?: string;
};
export type ScreenGroupJsonldScreenGroupsScreensRead = {
  relationsChecksum?: object;
};
export type ScreenGroupJsonldScreenGroupsScreensReadRead = {
  "@id"?: string;
  "@type"?: string;
  relationsChecksum?: object;
};
export type ScreenScreenJsonld = {
  title?: string;
  description?: string;
  size?: string;
  campaigns?: string;
  layout?: string;
  orientation?: string;
  resolution?: string;
  location?: string;
  regions?: string[];
  inScreenGroups?: string;
  screenUser?: string | null;
  enableColorSchemeChange?: boolean | null;
  status?: string[] | null;
  activeCampaignsLength?: number | null;
  campaignsLength?: number | null;
  inScreenGroupsLength?: number | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type ScreenScreenJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  size?: string;
  campaigns?: string;
  layout?: string;
  orientation?: string;
  resolution?: string;
  location?: string;
  regions?: string[];
  inScreenGroups?: string;
  screenUser?: string | null;
  enableColorSchemeChange?: boolean | null;
  status?: string[] | null;
  activeCampaignsLength?: number | null;
  campaignsLength?: number | null;
  inScreenGroupsLength?: number | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type ScreenScreenInputJsonld = {
  title?: string;
  description?: string;
  size?: string;
  layout?: string;
  location?: string;
  resolution?: string;
  orientation?: string;
  enableColorSchemeChange?: boolean | null;
  regions?: string[] | null;
  groups?: string[] | null;
};
export type ScreenBindObject = {
  bindKey?: string;
};
export type CollectionJsonldPlaylistScreenRegionRead = {};
export type CollectionJsonldPlaylistScreenRegionReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
};
export type PlaylistJsonldPlaylistScreenRegionRead = {
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldPlaylistScreenRegionRead | null;
  campaignScreenGroups?: CollectionJsonldPlaylistScreenRegionRead | null;
  tenants?: CollectionJsonldPlaylistScreenRegionRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type PlaylistJsonldPlaylistScreenRegionReadRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  schedules?: string[] | null;
  slides?: string;
  campaignScreens?: CollectionJsonldPlaylistScreenRegionReadRead | null;
  campaignScreenGroups?: CollectionJsonldPlaylistScreenRegionReadRead | null;
  tenants?: CollectionJsonldPlaylistScreenRegionReadRead | null;
  isCampaign?: boolean;
  slidesLength?: number | null;
  published?: string[];
  relationsChecksum?: object;
};
export type PlaylistScreenRegionJsonldPlaylistScreenRegionRead = {
  playlist?: PlaylistJsonldPlaylistScreenRegionRead;
  weight?: number;
  relationsChecksum?: object;
};
export type PlaylistScreenRegionJsonldPlaylistScreenRegionReadRead = {
  "@id"?: string;
  "@type"?: string;
  playlist?: PlaylistJsonldPlaylistScreenRegionReadRead;
  weight?: number;
  relationsChecksum?: object;
};
export type ScreenGroupScreenGroupJsonldScreensScreenGroupsRead = {
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  relationsChecksum?: object;
};
export type ScreenGroupScreenGroupJsonldScreensScreenGroupsReadRead = {
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  campaigns?: string;
  screens?: string;
  screensLength?: number | null;
  campaignsLength?: number | null;
  relationsChecksum?: object;
};
export type SlideSlideJsonld = {
  title?: string;
  description?: string;
  templateInfo?: string[];
  theme?: string;
  onPlaylists?: CollectionJsonld;
  duration?: number | null;
  published?: string[];
  media?: CollectionJsonld;
  content?: string[];
  feed?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type SlideSlideJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  templateInfo?: string[];
  theme?: string;
  onPlaylists?: CollectionJsonldRead;
  duration?: number | null;
  published?: string[];
  media?: CollectionJsonldRead;
  content?: string[];
  feed?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
  relationsChecksum?: object;
};
export type SlideSlideInputJsonld = {
  title?: string;
  description?: string;
  templateInfo?: string[];
  theme?: string;
  duration?: number | null;
  published?: string[];
  feed?: string[] | null;
  media?: string[];
  content?: string[];
};
export type SlideInteractiveSlideActionInputJsonld = {
  action?: string | null;
  data?: string[];
};
export type ThemeThemeJsonld = {
  title?: string;
  description?: string;
  logo?: string | null;
  cssStyles?: string;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type ThemeThemeJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  title?: string;
  description?: string;
  logo?: string | null;
  cssStyles?: string;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type ThemeThemeInputJsonld = {
  title?: string;
  description?: string;
  logo?: string;
  css?: string;
};
export type UserUserJsonld = {
  fullName?: string | null;
  userType?:
    | ("OIDC_EXTERNAL" | "OIDC_INTERNAL" | "USERNAME_PASSWORD" | null)
    | ("OIDC_EXTERNAL" | "OIDC_INTERNAL" | "USERNAME_PASSWORD" | null);
  roles?: string[];
  createdAt?: string;
  id?: string;
};
export type UserUserJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  fullName?: string | null;
  userType?:
    | ("OIDC_EXTERNAL" | "OIDC_INTERNAL" | "USERNAME_PASSWORD" | null)
    | ("OIDC_EXTERNAL" | "OIDC_INTERNAL" | "USERNAME_PASSWORD" | null);
  roles?: string[];
  createdAt?: string;
  id?: string;
};
export type UserUserInputJsonld = {
  fullName?: string | null;
};
export type UserActivationCodeUserActivationCodeJsonld = {
  code?: string | null;
  codeExpire?: string | null;
  username?: string | null;
  roles?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type UserActivationCodeUserActivationCodeJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  code?: string | null;
  codeExpire?: string | null;
  username?: string | null;
  roles?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type UserActivationCodeUserActivationCodeInputJsonld = {
  displayName?: string;
  roles?: string[];
};
export type UserActivationCodeActivationCodeJsonld = {
  activationCode?: string;
};
export type UserActivationCodeJsonld = {
  code?: string | null;
  codeExpire?: string | null;
  username?: string | null;
  roles?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
export type UserActivationCodeJsonldRead = {
  "@context"?:
    | string
    | {
        "@vocab": string;
        hydra: "http://www.w3.org/ns/hydra/core#";
        [key: string]: any;
      };
  "@id"?: string;
  "@type"?: string;
  code?: string | null;
  codeExpire?: string | null;
  username?: string | null;
  roles?: string[] | null;
  modifiedBy?: string;
  createdBy?: string;
  id?: string;
  created?: string;
  modified?: string;
};
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
  useGetV2MediaByIdQuery,
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
} = injectedRtkApi;

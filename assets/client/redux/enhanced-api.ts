import { clientApi as generatedApi } from "./generated-api";

// Invalidate the following tags for the given endpoints.
// The client uses very few mutations, so this is minimal.
const invalidatesTagsForEndpoints = {
  postLoginInfoScreen: ["Authentication"],
  postRefreshTokenItem: ["Authentication"],
};

const enhancedApi = generatedApi.enhanceEndpoints({
  // @ts-ignore
  endpoints: Object.fromEntries(
    // @ts-ignore
    Object.entries(generatedApi.endpoints).map(([key, endpoint]) => {
      const enhancedEndpoint = {
        ...endpoint,
      };

      if (Object.prototype.hasOwnProperty.call(invalidatesTagsForEndpoints, key)) {
        enhancedEndpoint.invalidatesTags = invalidatesTagsForEndpoints[key];
      }

      return [key, enhancedEndpoint];
    })
  ),
});

export { enhancedApi as clientApi };

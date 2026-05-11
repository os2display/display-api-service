import { createApi } from '@reduxjs/toolkit/query/react';
import extendedBaseQuery from "./extended-base-query";

export const emptySplitApi = createApi({
  baseQuery: extendedBaseQuery,
  endpoints: () => ({}),
});

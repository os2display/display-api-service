import { createApi } from '@reduxjs/toolkit/query/react';
import extendedBaseQuery from "./base-query";

export const emptySplitApi = createApi({
  baseQuery: extendedBaseQuery,
  endpoints: () => ({}),
});

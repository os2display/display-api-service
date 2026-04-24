import { createApi } from "@reduxjs/toolkit/query/react";
import clientBaseQuery from "./base-query";

export const clientEmptySplitApi = createApi({
  reducerPath: "clientApi",
  baseQuery: clientBaseQuery,
  keepUnusedDataFor: 2592000, // 30 days
  endpoints: () => ({}),
});

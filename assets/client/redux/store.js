import { configureStore } from "@reduxjs/toolkit";
import { clientApi } from "./generated-api.ts";

/* eslint-disable-next-line import/prefer-default-export */
export const clientStore = configureStore({
  reducer: {
    [clientApi.reducerPath]: clientApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(clientApi.middleware),
});

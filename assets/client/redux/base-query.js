import { fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import localStorageKeys from "../core/local-storage-keys";
import reauthenticateRef from "./reauthenticate-ref";

const rawBaseQuery = fetchBaseQuery({ baseUrl: "/", credentials: "include" });

const clientBaseQuery = async (args, api, extraOptions) => {

  const newArgs = { ...args };

  if (!Object.prototype.hasOwnProperty.call(newArgs, "headers")) {
    newArgs.headers = {};
  }

  if (!Object.prototype.hasOwnProperty.call(newArgs.headers, "accept")) {
    newArgs.headers.accept = "application/ld+json";
  }

  // Support preview mode via URL params.
  const url = new URL(window.location.href);
  const previewToken = url.searchParams.get("preview-token");
  const previewTenant = url.searchParams.get("preview-tenant");

  // Attach api token.
  const apiToken = localStorage.getItem(localStorageKeys.API_TOKEN);
  if (previewToken) {
    newArgs.headers.authorization = `Bearer ${previewToken}`;
  } else if (apiToken) {
    newArgs.headers.authorization = `Bearer ${apiToken}`;
  }

  // Attach tenant key.
  const tenantKey = localStorage.getItem(localStorageKeys.TENANT_KEY);
  if (previewTenant) {
    newArgs.headers["Authorization-Tenant-Key"] = previewTenant;
  } else if (tenantKey) {
    newArgs.headers["Authorization-Tenant-Key"] = tenantKey;
  }

  const baseResult = await rawBaseQuery(newArgs, api, extraOptions);

  // Handle authentication errors.
  if (baseResult?.error?.status === 401) {
    reauthenticateRef.current();
  }

  return {
    ...baseResult,
  };
};

export default clientBaseQuery;

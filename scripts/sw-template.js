/* eslint-disable no-restricted-globals */
// Service worker for the OS2Display client app.
// This file is a template — placeholders are replaced at build time by scripts/generate-sw.js.

const CACHE_VERSION = "__BUILD_HASH__";
const CACHE_NAME = `os2display-client-${CACHE_VERSION}`;
const PRECACHE_ASSETS = __PRECACHE_MANIFEST__;

const CLIENT_NAVIGATION_PATTERN = /^\/client(\/|$|\?)/;
const BUILD_ASSETS_PATTERN = /^\/build\/assets\//;
const RELEASE_JSON_PATTERN = /^\/release\.json$/;
const CLIENT_HTML_CACHE_KEY = "/client";

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches
      .open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_ASSETS))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter(
              (key) =>
                key.startsWith("os2display-client-") && key !== CACHE_NAME,
            )
            .map((key) => caches.delete(key)),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

const networkFirst = async (request, cacheKey) => {
  const cache = await caches.open(CACHE_NAME);
  try {
    const response = await fetch(request);
    if (response && response.ok) {
      cache.put(cacheKey || request, response.clone());
    }
    return response;
  } catch (error) {
    const cached = await cache.match(cacheKey || request);
    if (cached) return cached;
    throw error;
  }
};

const cacheFirst = async (request) => {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response && response.ok) {
    cache.put(request, response.clone());
  }
  return response;
};

self.addEventListener("fetch", (event) => {
  const { request } = event;

  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (
    request.mode === "navigate" &&
    CLIENT_NAVIGATION_PATTERN.test(url.pathname)
  ) {
    event.respondWith(networkFirst(request, CLIENT_HTML_CACHE_KEY));
    return;
  }

  if (BUILD_ASSETS_PATTERN.test(url.pathname)) {
    event.respondWith(cacheFirst(request));
    return;
  }

  if (RELEASE_JSON_PATTERN.test(url.pathname)) {
    event.respondWith(networkFirst(request));
  }
});

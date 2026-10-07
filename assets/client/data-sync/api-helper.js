import logger from "../logger/logger";
import appStorage from "../util/app-storage";
import fetchWithTimeout, {
  REQUEST_TIMEOUT,
} from "../util/fetch-with-timeout.js";

// Statuses that mean "try again later" rather than "this failed".
// 429 is the rate limit response from the reverse proxy, 502/503/504 are
// emitted while the backend is busy or restarting.
const RETRYABLE_STATUSES = [429, 502, 503, 504];

// Number of retries after the initial attempt.
const MAX_RETRIES = 3;

// Base for the exponential backoff, in ms. The actual wait is a random value in
// [0, base * 2^attempt] - "full jitter". A screen pull is a burst of requests
// that all fail together when the rate-limit bucket is empty, and a narrow
// jitter band would let them retry in near-lockstep and empty it again.
const RETRY_BASE_DELAY = 500;

// Ceiling for a single backoff wait, and for a Retry-After the server sends.
// Without a clamp a proxy answering `Retry-After: 3600` would park the client
// for an hour inside a poll that runs every few minutes.
const MAX_RETRY_DELAY = 30000;

// Spread applied on top of a Retry-After the server sent. Every client rejected
// in the same second is handed the same value, so honouring it verbatim would
// re-synchronise the burst the header exists to spread. Added rather than
// subtracted: RFC 9110 makes Retry-After a minimum, not a target.
const RETRY_AFTER_JITTER = 250;

// Backstop so a misbehaving collection cannot page indefinitely. Matches the
// limit used by the admin's get-all-pages helper.
const MAX_PAGES = 100;

class ApiHelper {
  endpoint = "";

  /**
   * Constructor.
   *
   * @param {string} endpoint The API endpoint.
   */
  constructor(endpoint) {
    this.endpoint = endpoint;
  }

  /**
   * Wait for the given number of ms.
   *
   * @param {number} ms Milliseconds to wait.
   * @returns {Promise<void>} Promise that resolves when the wait is over.
   */
  static delay(ms) {
    return new Promise((resolve) => {
      setTimeout(resolve, ms);
    });
  }

  /**
   * Number of ms to wait before the given attempt, honouring Retry-After.
   *
   * @param {object} response The response that was rejected.
   * @param {number} attempt Zero based index of the attempt that failed.
   * @returns {number} Milliseconds to wait.
   */
  static retryDelay(response, attempt) {
    // Retry-After may also be an HTTP-date, which parseInt turns into NaN. That
    // falls through to the backoff below, which is an acceptable degradation.
    const retryAfter = parseInt(
      response?.headers?.get?.("Retry-After") ?? "",
      10,
    );

    if (!Number.isNaN(retryAfter) && retryAfter > 0) {
      const asked = Math.min(retryAfter * 1000, MAX_RETRY_DELAY);

      return asked + Math.floor(Math.random() * RETRY_AFTER_JITTER);
    }

    // Full jitter: anywhere in [0, capped]. Spreads a burst that was rejected
    // together instead of moving it forward intact.
    const capped = Math.min(MAX_RETRY_DELAY, RETRY_BASE_DELAY * 2 ** attempt);

    return Math.floor(Math.random() * capped);
  }

  /**
   * Get result from path.
   *
   * Retryable failures (rate limiting, temporarily unavailable backend) are
   * retried with backoff. Everything else returns null on the first failure.
   *
   * @param {string} path Path to the resource.
   * @returns {Promise<any>} Promise with data.
   */
  async getPath(path) {
    if (!path) {
      throw new Error("No path");
    }

    /* eslint-disable no-await-in-loop */
    for (let attempt = 0; ; attempt += 1) {
      const result = await this.fetchPath(path);

      // Anything not explicitly marked retryable is final, so a result that
      // forgot to set the flag fails rather than being retried.
      if (!result.retry || attempt === MAX_RETRIES) {
        return result.data;
      }

      logger.info(
        `Retrying (status: ${result.status}): ${this.endpoint + path}`,
      );

      await ApiHelper.delay(ApiHelper.retryDelay(result.response, attempt));
    }
    /* eslint-enable no-await-in-loop */
  }

  /**
   * Perform a single request against the path.
   *
   * @param {string} path Path to the resource.
   * @returns {Promise<object>} The data, and whether a retry makes sense.
   */
  async fetchPath(path) {
    let headers;

    // Credential and URL lookup is local work: if it fails, it fails the same
    // way on every attempt, so it must not be inside the retried block.
    try {
      const url = new URL(window.location.href);
      const previewToken = url.searchParams.get("preview-token");
      const previewTenant = url.searchParams.get("preview-tenant");

      const token = appStorage.getToken();
      const tenantKey = appStorage.getTenantKey();

      if ((!token || !tenantKey) && (!previewToken || !previewTenant)) {
        logger.error("Token or tenantKey not set.");

        return { data: null, retry: false };
      }

      headers = {
        authorization: `Bearer ${previewToken ?? token}`,
        "Authorization-Tenant-Key": previewTenant ?? tenantKey,
      };
    } catch (err) {
      logger.error(
        `Could not build request for ${this.endpoint + path}: ${err.message}`,
      );

      return { data: null, retry: false };
    }

    logger.log("info", `Fetching: ${this.endpoint + path}`);

    let response;

    try {
      // A request that never answers would otherwise sit in the fan-out
      // forever, holding a worker and stalling the pull.
      response = await fetchWithTimeout(this.endpoint + path, { headers });
    } catch (err) {
      const timedOut = err.name === "AbortError";

      logger.error(
        timedOut
          ? `Timed out after ${REQUEST_TIMEOUT}ms: ${this.endpoint + path}`
          : `Failed to fetch ${this.endpoint + path}: ${err.message}`,
      );

      // A transport error and a timeout are both worth another attempt.
      return { data: null, retry: true, status: null, response: null };
    }

    if (response.ok === false) {
      // TODO: Change to a better strategy for triggering reauthenticate.
      if (response.status === 401) {
        document.dispatchEvent(new Event("reauthenticate"));
      }

      logger.error(
        `Failed to fetch (status: ${response.status}): ${this.endpoint + path}`,
      );

      return {
        data: null,
        retry: RETRYABLE_STATUSES.includes(response.status),
        status: response.status,
        response,
      };
    }

    try {
      return { data: await response.json(), retry: false };
    } catch (parseError) {
      // A body we cannot parse will not parse on a retry either.
      logger.error(
        `Failed to parse response from ${this.endpoint + path}: ${parseError.message}`,
      );

      return { data: null, retry: false };
    }
  }

  /**
   * Gets all resources from the given path.
   *
   * Pages by following `hydra:view['hydra:next']`, the same termination the
   * admin uses in components/util/helpers/get-all-pages.js. A missing
   * `hydra:next` always means there is nothing more to fetch: API Platform
   * omits it on the last page, on out-of-range pages, and on single-page
   * collections. Deciding from the links rather than comparing the collected
   * count against `hydra:totalItems` means a count that disagrees with the rows
   * actually returned can no longer produce an endless run of requests.
   *
   * Answers `{path, results, keys}` only for a collection it paged to the end.
   * Anything short of that - a failed page, a throw, or the `MAX_PAGES` backstop
   * - answers a bare `{}`. Callers test for the `results`/`path` key to tell the
   * two apart, so a partial collection must never be handed back in the shape of
   * a complete one.
   *
   * @param {string} path Path to the resources.
   * @param {object} keys Keys that should be passed along with the result.
   * @returns {Promise<object>} All resources from the path, or `{}` if the whole
   *   collection could not be read.
   */
  async getAllResultsFromPath(path, keys = {}) {
    const results = [];
    let nextPath = path;
    let pagesFetched = 0;

    try {
      while (nextPath !== null && pagesFetched < MAX_PAGES) {
        // eslint-disable-next-line no-await-in-loop
        const responseData = await this.getPath(nextPath);
        pagesFetched += 1;

        // getPath() logs and returns null when a request fails, after
        // exhausting its retries. Give the whole collection up rather than
        // handing back part of it as if it were complete.
        if (responseData === null) {
          return {};
        }

        const members = responseData["hydra:member"] ?? [];
        results.push(...members);

        // An empty page also stops us, so a collection that offers a next link
        // without delivering members cannot keep the loop alive.
        nextPath =
          members.length === 0
            ? null
            : (responseData["hydra:view"]?.["hydra:next"] ?? null);
      }

      // Hitting the backstop means the collection is still offering more pages,
      // so what we hold is a prefix of it. Give it up the same way a failed page
      // is given up: a short collection returned in the shape of a complete one
      // is treated as a healthy pull by every caller, and the pull then stores
      // the server's checksum for it and serves the truncated data from cache
      // until an editor happens to change the content (#507).
      if (nextPath !== null) {
        logger.error(
          `Stopped paginating ${path} after ${MAX_PAGES} pages; giving up the collection rather than returning a prefix of it.`,
        );

        return {};
      }
    } catch (err) {
      // Never let a data-sync failure surface on a screen; log and move on.
      logger.error(`Failed to page through ${path}: ${err.message}`);

      return {};
    }

    return { path, results, keys };
  }
}

export default ApiHelper;

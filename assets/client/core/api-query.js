import logger from "./logger.js";
import { clientStore } from "../redux/store.js";
import { clientApi } from "../redux/generated-api.ts";

/**
 * Dispatch an RTK Query endpoint and return the unwrapped result.
 *
 * @param {string} endpoint The endpoint name.
 * @param {object} args The endpoint args.
 * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
 * @returns {Promise<any>} The result data.
 */
export function query(endpoint, args, forceRefetch = false) {
  const request = clientStore.dispatch(
    clientApi.endpoints[endpoint].initiate(args, { forceRefetch }),
  );
  return request
    .unwrap()
    .catch((err) => {
      const cached = clientApi.endpoints[endpoint].select(args)(
        clientStore.getState(),
      );
      if (cached?.data) {
        logger.warn(`Using cached data for ${endpoint} after fetch failure.`);
        return cached.data;
      }
      throw err;
    })
    .finally(() => {
      request.unsubscribe();
    });
}

/**
 * Fetch all pages from a paginated endpoint.
 *
 * @param {string} endpoint The endpoint name.
 * @param {object} args The endpoint args (page will be added).
 * @param {boolean} forceRefetch Whether to bypass RTK Query cache.
 * @returns {Promise<Array>} All hydra:member results concatenated.
 */
// Upper bound on pagination — intentionally capped. Content types served to
// screens should never exceed this number of pages.
const MAX_PAGES = 50;

export async function queryAllPages(endpoint, args, forceRefetch = false) {
  let results = [];
  let page = 1;

  do {
    try {
      const responseData = await query(endpoint, { ...args, page }, forceRefetch);

      if (responseData === null || responseData === undefined) {
        logger.error(`Failed to fetch page ${page} for ${endpoint}`);
        return results;
      }

      results = results.concat(responseData["hydra:member"] ?? []);

      if (responseData["hydra:view"]?.["hydra:next"]) {
        page += 1;
      } else {
        break;
      }
    } catch (err) {
      logger.error(
        `Failed to fetch all pages for ${endpoint}: ${err.message}`,
      );
      return results;
    }
  } while (page <= MAX_PAGES);

  if (page > MAX_PAGES) {
    logger.warn(`Reached max page limit (${MAX_PAGES}) for ${endpoint}`);
  }

  return results;
}

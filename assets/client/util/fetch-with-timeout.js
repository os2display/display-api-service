// Give up on a single request after this long. A socket that never answers
// neither fails nor succeeds, so it cannot be waited on: it would hold a worker
// in the pull fan-out, or - for the client config - leave a pull awaiting a
// promise that never settles and end the poll chain (#507).
export const REQUEST_TIMEOUT = 15000;

/**
 * fetch() that gives up rather than waiting forever.
 *
 * Rejects with the usual AbortError once the ceiling passes, so callers can tell
 * a timeout from a transport error by `err.name` and decide whether to retry.
 *
 * @param {string} resource The resource to fetch.
 * @param {object} options Options passed on to fetch, minus `signal`.
 * @param {number} timeout Milliseconds to wait before aborting.
 * @returns {Promise<Response>} The response, if one arrives in time.
 */
export default async function fetchWithTimeout(
  resource,
  options = {},
  timeout = REQUEST_TIMEOUT,
) {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeout);

  try {
    return await fetch(resource, { ...options, signal: controller.signal });
  } finally {
    // Also on the success path: an uncleared timer would keep the event loop
    // busy and, in tests, accumulate across every request.
    clearTimeout(timer);
  }
}

/**
 * Bounded-concurrency equivalent of Promise.allSettled.
 *
 * A screen pull fans out one request per region, playlist, slide, template,
 * media item and feed. Handing all of those to Promise.allSettled at once puts
 * hundreds of requests in flight, which is what exhausts the reverse proxy's
 * rate-limit bucket in the first place (#507). Running them through a fixed
 * number of workers keeps the burst below the bucket, so the retry layer stays
 * a safety net rather than the thing that carries the pull.
 *
 * Takes thunks rather than promises: a promise has already started, so an array
 * of them would have fanned out before this function ever saw it.
 *
 * @param {Array<Function>} tasks Functions that each return a promise.
 * @param {number} limit Maximum number of tasks in flight at once.
 * @returns {Promise<Array<object>>} Settled results, in the order of `tasks`,
 *   shaped exactly like Promise.allSettled: `{status: "fulfilled", value}` or
 *   `{status: "rejected", reason}`.
 */
// eslint-disable-next-line import/prefer-default-export
export async function settleWithConcurrency(tasks, limit) {
  const results = new Array(tasks.length);
  const workerCount = Math.max(1, Math.min(limit, tasks.length));
  let next = 0;

  const runWorker = async () => {
    // Each worker claims the next index and runs it to completion, so one slow
    // task holds up only its own worker. Claiming is safe without a lock: the
    // read and the increment are not separated by an await.
    /* eslint-disable no-await-in-loop */
    while (next < tasks.length) {
      const index = next;
      next += 1;

      try {
        results[index] = { status: "fulfilled", value: await tasks[index]() };
      } catch (reason) {
        results[index] = { status: "rejected", reason };
      }
    }
    /* eslint-enable no-await-in-loop */
  };

  await Promise.all(Array.from({ length: workerCount }, () => runWorker()));

  return results;
}

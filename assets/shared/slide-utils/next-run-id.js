/**
 * Next run id.
 *
 * A pure state updater, not a shared sequence: it holds no counter of its own,
 * so each caller derives an independent run of numbers from its own runId
 * state. Pass it straight to the setter: setRunId(nextRunId).
 *
 * A slide is keyed by its execution id, so a region holding a single slide does
 * not remount when that slide replays — the replay depends entirely on the run
 * id changing value. Counting rather than reading a clock because a template
 * that calls slideDone() synchronously while mounting can land in the same
 * millisecond as the run that started it, and no finer clock fixes that:
 * browsers deliberately coarsen timer resolution as a fingerprinting
 * mitigation. React would then bail out of the state update, run would never
 * change, no slide execution effect would re-run, and the region would stop
 * advancing.
 *
 * @param {number|null} previous The current run id.
 * @returns {number} The next run id. Always truthy, so `!run` still means "not
 *   started".
 */
const nextRunId = (previous) => (previous ?? 0) + 1;

export default nextRunId;

// Fallback when a slide or entry duration is missing or unusable. A
// misconfigured slide should hold for a readable moment, not flash past:
// setTimeout treats both undefined and NaN as 0 ms.
export const DEFAULT_DURATION = 15000;

/**
 * Clamp a duration to something a timer can use.
 *
 * Exported so a template's own animation clocks can be derived from the same
 * value the execution hook uses. Deriving one from the raw prop and the other
 * from the clamp puts them out of step — e.g. a `duration` of 0 leaves the hook
 * cycling at 15s while a `duration - animationDuration` fade timer goes
 * negative and fires immediately.
 *
 * @param {number} duration Duration in ms, possibly missing or invalid.
 * @returns {number} The duration if it is a positive finite number, else
 *   DEFAULT_DURATION.
 */
export default function clampDuration(duration) {
  return Number.isFinite(duration) && duration > 0
    ? duration
    : DEFAULT_DURATION;
}

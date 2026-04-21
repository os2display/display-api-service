const defaults = {
  // Every 20 seconds.
  loginCheckTimeoutDefault: 20 * 1000,
  // Every 15 minutes.
  refreshTokenTimeoutDefault: 15 * 60 * 1000,
  // Every 10 minutes.
  releaseTimestampIntervalTimeoutDefault: 10 * 60 * 1000,
  // Every 60 seconds. Fallback for scheduling interval.
  schedulingIntervalDefault: 60 * 1000,
  // Every 5 minutes. Fallback for pull strategy interval.
  pullStrategyIntervalDefault: 5 * 60 * 1000,
  // Every 15 minutes. Fallback for config fetch interval.
  configFetchIntervalDefault: 15 * 60 * 1000,
  // 30 seconds. Timeout for individual API requests.
  queryTimeoutDefault: 30 * 1000,
  // 2 minutes. Max execution time for a full getScreen cycle.
  getScreenTimeoutDefault: 2 * 60 * 1000,
};

export default defaults;

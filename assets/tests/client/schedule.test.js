import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import ScheduleUtils from "../../client/util/schedule";

describe("ScheduleUtils.occursNow", () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it("returns true when within a daily occurrence + duration", () => {
    // Fake local time to 10:30 on a Sunday
    vi.setSystemTime(new Date(2025, 5, 15, 10, 30, 0));

    // Daily at 10:00 with 1 hour duration
    const rrule = "DTSTART:20250601T100000Z\nRRULE:FREQ=DAILY";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(true);
  });

  it("returns false when after the duration has ended", () => {
    // Fake local time to 11:30
    vi.setSystemTime(new Date(2025, 5, 15, 11, 30, 0));

    const rrule = "DTSTART:20250601T100000Z\nRRULE:FREQ=DAILY";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(false);
  });

  it("returns false when before the occurrence", () => {
    // Fake local time to 09:30
    vi.setSystemTime(new Date(2025, 5, 15, 9, 30, 0));

    const rrule = "DTSTART:20250601T100000Z\nRRULE:FREQ=DAILY";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(false);
  });

  it("returns true for a weekly rule on the correct day", () => {
    // 2025-06-16 is a Monday. Fake local time to Monday 14:30
    vi.setSystemTime(new Date(2025, 5, 16, 14, 30, 0));

    // Weekly on Mondays at 14:00 with 1 hour duration
    const rrule = "DTSTART:20250602T140000Z\nRRULE:FREQ=WEEKLY;BYDAY=MO";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(true);
  });

  it("returns false for a weekly rule on the wrong day", () => {
    // 2025-06-15 is a Sunday. Fake local time to Sunday 14:30
    vi.setSystemTime(new Date(2025, 5, 15, 14, 30, 0));

    // Weekly on Mondays at 14:00
    const rrule = "DTSTART:20250602T140000Z\nRRULE:FREQ=WEEKLY;BYDAY=MO";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(false);
  });

  it("handles the escaped newline replacement (\\\\n to \\n)", () => {
    vi.setSystemTime(new Date(2025, 5, 15, 10, 30, 0));

    // Use literal \\n as the code replaces it with \n
    const rrule = "DTSTART:20250601T100000Z\\nRRULE:FREQ=DAILY";
    expect(ScheduleUtils.occursNow(rrule, 3600)).toBe(true);
  });

  it("returns false with zero duration when not exactly at occurrence time", () => {
    // Fake local time to 10:01
    vi.setSystemTime(new Date(2025, 5, 15, 10, 1, 0));

    const rrule = "DTSTART:20250601T100000Z\nRRULE:FREQ=DAILY";
    expect(ScheduleUtils.occursNow(rrule, 0)).toBe(false);
  });
});

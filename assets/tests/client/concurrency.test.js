import { describe, it, expect } from "vitest";

import { settleWithConcurrency } from "../../client/util/concurrency";

describe("settleWithConcurrency", () => {
  it("never runs more than the limit at once", async () => {
    // The point of the limiter: a screen pull must not put its whole fan-out
    // in flight, because that is what empties the rate-limit bucket (#507).
    let inFlight = 0;
    let peak = 0;

    const tasks = Array.from({ length: 25 }, () => async () => {
      inFlight += 1;
      peak = Math.max(peak, inFlight);

      await new Promise((resolve) => {
        setTimeout(resolve, 1);
      });

      inFlight -= 1;

      return "done";
    });

    const results = await settleWithConcurrency(tasks, 6);

    expect(peak).toBeLessThanOrEqual(6);
    expect(results).toHaveLength(25);
    expect(results.every((r) => r.status === "fulfilled")).toBe(true);
  });

  it("returns results in task order, not completion order", async () => {
    // Callers map results back to their inputs positionally, so ordering is
    // part of the contract.
    const tasks = [50, 1, 25].map(
      (ms, index) => () =>
        new Promise((resolve) => {
          setTimeout(() => resolve(index), ms);
        }),
    );

    const results = await settleWithConcurrency(tasks, 3);

    expect(results.map((r) => r.value)).toEqual([0, 1, 2]);
  });

  it("reports a rejected task without losing the others", async () => {
    const tasks = [
      async () => "first",
      async () => {
        throw new Error("nope");
      },
      async () => "third",
    ];

    const results = await settleWithConcurrency(tasks, 2);

    expect(results[0]).toEqual({ status: "fulfilled", value: "first" });
    expect(results[1].status).toBe("rejected");
    expect(results[1].reason.message).toBe("nope");
    expect(results[2]).toEqual({ status: "fulfilled", value: "third" });
  });

  it("does not start a task before it is scheduled", async () => {
    // Thunks, not promises: an array of promises would already have fanned out
    // before the limiter saw it.
    const started = [];
    let release;
    const gate = new Promise((resolve) => {
      release = resolve;
    });

    const tasks = Array.from({ length: 4 }, (_, i) => async () => {
      started.push(i);
      await gate;
    });

    const pending = settleWithConcurrency(tasks, 2);

    await Promise.resolve();
    expect(started).toEqual([0, 1]);

    release();
    await pending;

    expect(started).toEqual([0, 1, 2, 3]);
  });

  it("handles an empty task list", async () => {
    await expect(settleWithConcurrency([], 6)).resolves.toEqual([]);
  });
});

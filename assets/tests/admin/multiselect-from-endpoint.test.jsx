import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { cleanup, render, waitFor } from "@testing-library/react";
import MultiselectFromEndpoint from "../../admin/components/slide/content/multiselect-from-endpoint";
import { toast } from "react-toastify";
import translations from "../../admin/translations/da/common.json";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({
    t: (key, options) =>
      key.split(".").reduce((value, part) => value?.[part], translations) ??
      options?.defaultValue ??
      key,
  }),
}));

vi.mock("react-toastify", () => ({ toast: { error: vi.fn() } }));

vi.mock(
  "../../admin/components/util/forms/multiselect-dropdown/multi-dropdown",
  () => ({
    default: ({ options }) => (
      <div data-testid="options">{JSON.stringify(options)}</div>
    ),
  }),
);

describe("MultiselectFromEndpoint", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
    vi.stubGlobal("fetch", vi.fn());
  });

  afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
  });

  const renderSelector = () =>
    render(
      <MultiselectFromEndpoint
        name="resources"
        optionsEndpoint="/v2/feed-sources/1/config/resources"
        onChange={vi.fn()}
      />,
    );

  it("shows successful options without an error", async () => {
    fetch.mockResolvedValue({
      ok: true,
      json: async () => [{ value: "room@example.com", title: "Room" }],
    });
    const { getByTestId } = renderSelector();

    await waitFor(() =>
      expect(getByTestId("options")).toHaveTextContent("room@example.com"),
    );
    expect(toast.error).not.toHaveBeenCalled();
  });

  it("treats an empty list as a successful lookup", async () => {
    fetch.mockResolvedValue({ ok: true, json: async () => [] });
    const { getByTestId } = renderSelector();

    await waitFor(() => expect(getByTestId("options")).toHaveTextContent("[]"));
    expect(toast.error).not.toHaveBeenCalled();
  });

  it("passes safe server errors to the standard error toast", async () => {
    const data = {
      "hydra:description": "Microsoft Graph nægtede adgang til lokalerne.",
    };
    fetch.mockResolvedValue({ ok: false, json: async () => data });
    const { queryByTestId } = renderSelector();

    await waitFor(() =>
      expect(toast.error).toHaveBeenCalledWith(
        expect.stringMatching(
          /^Der opstod en fejl kl\. \d{2}:\d{2}:\d{2}\. Microsoft Graph nægtede adgang til lokalerne\.$/,
        ),
        { autoClose: false },
      ),
    );
    expect(queryByTestId("options")).toBeNull();
  });

  it.each([
    "authentication",
    "bad-request",
    "invalid-token",
    "access-denied",
    "throttled",
    "connection",
  ])("translates the MS Graph %s error", async (error) => {
    fetch.mockResolvedValue({
      ok: false,
      json: async () => ({
        "hydra:description": `ms-graph-feed-type.errors.${error}`,
      }),
    });
    renderSelector();

    await waitFor(() =>
      expect(toast.error).toHaveBeenCalledWith(
        expect.stringContaining(
          translations["ms-graph-feed-type"].errors[error],
        ),
        { autoClose: false },
      ),
    );
  });

  it.each([
    ["legacy null response", { ok: false, json: async () => null }],
    [
      "non-JSON response",
      {
        ok: false,
        json: async () => {
          throw new SyntaxError("Invalid JSON");
        },
      },
    ],
    ["invalid successful response", { ok: true, json: async () => ({}) }],
  ])("reports a %s without rendering options", async (_name, response) => {
    fetch.mockResolvedValue(response);
    const { queryByTestId } = renderSelector();

    await waitFor(() => expect(toast.error).toHaveBeenCalledTimes(1));
    expect(queryByTestId("options")).toBeNull();
  });

  it("reports network failures using the same toast", async () => {
    const error = new TypeError("Failed to fetch");
    fetch.mockRejectedValue(error);
    renderSelector();

    await waitFor(() =>
      expect(toast.error).toHaveBeenCalledWith(
        expect.stringMatching(/^Der opstod en fejl kl\. \d{2}:\d{2}:\d{2}\.$/),
        { autoClose: false },
      ),
    );
  });
});

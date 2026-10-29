import { describe, it, expect, vi, afterEach } from "vitest";
import { render, fireEvent, cleanup } from "@testing-library/react";
import RichText from "../../admin/components/util/forms/rich-text/rich-text";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

/**
 * Render the editor and return the latest html it reported.
 *
 * @param {string} value The initial html.
 * @returns {object} The render result and a getter for the latest html.
 */
function renderRichText(value) {
  const onChange = vi.fn();
  const result = render(
    <RichText name="bookText" value={value} onChange={onChange} />,
  );

  return {
    ...result,
    latestHtml: () => onChange.mock.lastCall[0].target.value,
  };
}

describe("RichText", () => {
  afterEach(() => {
    cleanup();
  });

  it("turns a heading back into normal text", () => {
    const { getByLabelText, getByRole, latestHtml } =
      renderRichText("<h1>Titel</h1>");

    fireEvent.click(getByRole("button", { name: "toggle-heading-1" }));
    fireEvent.click(getByLabelText("normal"));

    expect(latestHtml()).toBe("<p>Titel</p>");
  });

  it("marks the heading dropdown label active only for headings", () => {
    const normal = renderRichText("<p>Tekst</p>");
    expect(normal.container.querySelector(".toggle-text")).not.toBeNull();
    expect(normal.container.querySelector(".toggle-text.is-active")).toBeNull();
    cleanup();

    const heading = renderRichText("<h2>Titel</h2>");
    expect(
      heading.container.querySelector(".toggle-text.is-active"),
    ).not.toBeNull();
  });

  it("offers headings 1 to 3 and normal, as in v2", () => {
    const { getByRole, queryByLabelText } = renderRichText("<p>Tekst</p>");

    fireEvent.click(getByRole("button", { name: "normal" }));

    ["toggle-heading-1", "toggle-heading-2", "toggle-heading-3"].forEach(
      (label) => expect(queryByLabelText(label)).not.toBeNull(),
    );
    expect(queryByLabelText("toggle-heading-4")).toBeNull();
  });

  it("formats a paragraph as a quote", () => {
    const { getByLabelText, latestHtml } = renderRichText("<p>Citat</p>");

    fireEvent.click(getByLabelText("toggle-blockquote"));

    expect(latestHtml()).toBe("<blockquote><p>Citat</p></blockquote>");
  });
});

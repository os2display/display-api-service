import { afterEach, describe, it, expect, vi } from "vitest";
import { useState } from "react";
import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import CssEditor from "../../admin/components/util/forms/css-editor";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({
    t: (key, params = {}) => `${key} ${JSON.stringify(params)}`,
  }),
}));

function Form({ onChange = () => {} }) {
  const [css, setCss] = useState("#SLIDE_ID { color: red; }");
  return (
    <CssEditor
      name="cssStyles"
      label="CSS"
      value={css}
      onChange={(event) => {
        onChange(event);
        setCss(event.target.value);
      }}
    />
  );
}

// jsdom has no Custom Highlight API, so this covers the plain textarea path.
describe("CssEditor", () => {
  // Every render uses the id "cssStyles"; leftovers would shadow the next.
  afterEach(cleanup);

  it("passes change events through with the field name", () => {
    const onChange = vi.fn();
    render(<Form onChange={onChange} />);

    fireEvent.change(screen.getByLabelText("CSS"), {
      target: { value: "#SLIDE_ID {}" },
    });

    expect(onChange).toHaveBeenCalledOnce();
    expect(onChange.mock.calls[0][0].target).toMatchObject({
      name: "cssStyles",
      value: "#SLIDE_ID {}",
    });
  });

  it("lists problems after typing and clears them when fixed", async () => {
    render(<Form />);
    const textarea = screen.getByLabelText("CSS");
    expect(textarea).toHaveAttribute("aria-invalid", "false");

    fireEvent.change(textarea, { target: { value: "#SLIDE_ID {}\n/* x" } });

    expect(await screen.findByText(/unterminated_comment/)).toBeInTheDocument();
    expect(screen.getByRole("button", { name: /line/ })).toHaveTextContent(
      '"line":2',
    );
    expect(textarea).toHaveAttribute("aria-invalid", "true");
    expect(textarea).toHaveAccessibleDescription(/unterminated_comment/);

    fireEvent.change(textarea, { target: { value: "#SLIDE_ID {}\n/* x */" } });

    await vi.waitFor(() =>
      expect(screen.queryByText(/unterminated_comment/)).toBeNull(),
    );
    expect(textarea).toHaveAttribute("aria-invalid", "false");
  });

  it("selects the problem in the textarea when clicked", async () => {
    render(<Form />);
    const textarea = screen.getByLabelText("CSS");

    fireEvent.change(textarea, { target: { value: "h1 { color: red; }" } });
    fireEvent.click(await screen.findByRole("button", { name: /line/ }));

    expect(textarea).toHaveFocus();
    expect([textarea.selectionStart, textarea.selectionEnd]).toEqual([0, 2]);
  });

  it("describes the field with the help text", () => {
    render(
      <CssEditor
        name="cssStyles"
        label="CSS"
        helpText="Use #SLIDE_ID"
        onChange={() => {}}
      />,
    );
    expect(screen.getByLabelText("CSS")).toHaveAccessibleDescription(
      /^Use #SLIDE_ID/,
    );
  });

  it("keeps a visually hidden label as the field's name", () => {
    render(
      <CssEditor name="cssStyles" label="CSS" hideLabel onChange={() => {}} />,
    );
    expect(screen.getByText("CSS")).toHaveClass("visually-hidden");
    expect(screen.getByLabelText("CSS")).toHaveAttribute("id", "cssStyles");
  });

  it("does not render the highlight mirror without the highlight API", () => {
    const { container } = render(<Form />);
    expect(container.querySelector(".css-editor__mirror")).toBeNull();
  });
});

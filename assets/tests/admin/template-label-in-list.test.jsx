import { describe, it, expect, vi, afterEach } from "vitest";
import { render, cleanup, screen } from "@testing-library/react";

vi.mock("react-i18next", () => ({
  useTranslation: () => ({ t: (key) => key }),
}));

import TemplateLabelInList from "../../admin/components/util/template-label-in-list";

// image-text, from assets/shared/templates/image-text.json.
const BUNDLED_TEMPLATE = "01FP2SNGFN0BZQH03KCBXHKYHG";

// The templates themselves are not mocked: the point of the component is that
// it reads the title out of the bundle the build already carries, so the real
// glob lookup is what needs testing.
describe("TemplateLabelInList", () => {
  afterEach(() => {
    cleanup();
  });

  it("names the template from the bundled config", () => {
    render(
      <TemplateLabelInList
        templateInfo={{
          "@id": `/v2/templates/${BUNDLED_TEMPLATE}`,
          options: [],
        }}
      />,
    );

    expect(screen.getByText("Billede og tekst")).toBeTruthy();
  });

  it("falls back for a template this build does not bundle", () => {
    render(
      <TemplateLabelInList
        templateInfo={{ "@id": "/v2/templates/01JZZZZZZZZZZZZZZZZZZZZZZZ" }}
      />,
    );

    expect(screen.getByText("unknown-template")).toBeTruthy();
  });

  // A row with no templateInfo used to throw on the unguarded ["@id"], taking
  // the render of the whole table with it.
  it("falls back rather than throwing when the slide names no template", () => {
    expect(() =>
      render(<TemplateLabelInList templateInfo={{}} />),
    ).not.toThrow();
    expect(screen.getByText("unknown-template")).toBeTruthy();

    cleanup();

    expect(() => render(<TemplateLabelInList />)).not.toThrow();
    expect(screen.getByText("unknown-template")).toBeTruthy();
  });
});

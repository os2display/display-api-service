import { describe, it, expect } from "vitest";
import templateDataFromSlide from "../../client/util/template-data-from-slide";

const TEMPLATE = "01FP2SNGFN0BZQH03KCBXHKYHG";

describe("templateDataFromSlide", () => {
  it("reads the template id off the slide's template IRI", () => {
    expect(
      templateDataFromSlide({
        templateInfo: { "@id": `/v2/templates/${TEMPLATE}` },
      }),
    ).toEqual({ id: TEMPLATE });
  });

  it("ignores the options the IRI is delivered alongside", () => {
    expect(
      templateDataFromSlide({
        templateInfo: {
          "@id": `/v2/templates/${TEMPLATE}`,
          options: { fade: false },
        },
      }),
    ).toEqual({ id: TEMPLATE });
  });

  it("returns null when the IRI carries no id", () => {
    expect(
      templateDataFromSlide({ templateInfo: { "@id": "/v2/templates/" } }),
    ).toBeNull();
  });

  it("returns null when the slide names no template", () => {
    expect(templateDataFromSlide({ templateInfo: {} })).toBeNull();
    expect(templateDataFromSlide({})).toBeNull();
  });

  it("returns null rather than throwing on a missing slide", () => {
    expect(templateDataFromSlide(undefined)).toBeNull();
    expect(templateDataFromSlide(null)).toBeNull();
  });
});

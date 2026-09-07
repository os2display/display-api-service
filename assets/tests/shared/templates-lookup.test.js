import { describe, it, expect } from "vitest";
import { getTitle, renderSlide } from "../../shared/slide-utils/templates.js";

// The client reads a template id off the slide and looks the bundled module up
// by it - it no longer asks the API for anything. An id with no module in this
// build is therefore the one remaining way a template can be missing, and it
// has to fail loudly rather than render a blank slide: Slide's ErrorBoundary
// turns the throw into the fallback image and moves the region on.
describe("renderSlide template lookup", () => {
  it("throws for an id no bundled template has", () => {
    expect(() =>
      renderSlide(
        { templateData: { id: "01JZZZZZZZZZZZZZZZZZZZZZZZ" } },
        "run",
        () => {},
      ),
    ).toThrow(/Cannot find module/);
  });

  it("renders nothing when the slide names no template", () => {
    expect(renderSlide({}, "run", () => {})).toBe("");
    expect(renderSlide({ templateData: null }, "run", () => {})).toBe("");
  });
});

// Naming a template is not rendering it. The admin lists slides, and one slide
// whose template this build no longer bundles must not take a whole list down
// with it - so this lookup returns null where renderSlide throws.
describe("getTitle template lookup", () => {
  it("names a bundled template", () => {
    expect(getTitle("01FP2SNGFN0BZQH03KCBXHKYHG")).toBe("Billede og tekst");
  });

  it("returns null for an id no bundled template has", () => {
    expect(getTitle("01JZZZZZZZZZZZZZZZZZZZZZZZ")).toBeNull();
  });

  it("returns null when there is no id", () => {
    expect(getTitle("")).toBeNull();
    expect(getTitle(undefined)).toBeNull();
  });
});

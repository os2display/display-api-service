import { describe, it, expect, afterEach } from "vitest";
import { cleanup, render } from "@testing-library/react";

import { hasText } from "../../shared/slide-utils/slide-util";
import imageText from "../../shared/templates/image-text.jsx";
import travel from "../../shared/templates/travel.jsx";

// The rich text editor stores an emptied field as "<p></p>" (#590). Templates
// must treat that as no text, as they do "".
describe("hasText", () => {
  it.each([
    [undefined],
    [null],
    [""],
    ["<p></p>"],
    ["<p><br></p>"],
    ["<p> &nbsp; </p>\n<p></p>"],
    ["<ul><li><p></p></li></ul>"],
  ])("is false for %j", (html) => {
    expect(hasText(html)).toBe(false);
  });

  it.each([["<p>Tekst</p>"], ["<h1>Titel</h1><p></p>"], ["Tekst"]])(
    "is true for %j",
    (html) => {
      expect(hasText(html)).toBe(true);
    },
  );
});

describe("templates hide an emptied rich text field", () => {
  afterEach(() => {
    cleanup();
  });

  const renderTemplate = (template, content) =>
    render(
      template.renderSlide(
        {
          executionId: `${template.id()}-execution`,
          mediaData: {},
          feed: { configuration: {} },
          feedData: [],
          content,
        },
        null,
        () => {},
      ),
    );

  it("image-text shows no text box", () => {
    const { container } = renderTemplate(imageText, { text: "<p></p>" });

    expect(container.querySelector(".box")).toBeNull();
    expect(container.querySelector(".text")).toBeNull();
  });

  it("travel shows no info box", () => {
    const { container } = renderTemplate(travel, { text: "<p></p>" });

    expect(container.querySelector(".header")).toBeNull();
  });
});

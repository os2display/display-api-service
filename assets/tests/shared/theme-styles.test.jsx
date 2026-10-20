import { describe, it, expect, afterEach, vi } from "vitest";
import { cleanup, render } from "@testing-library/react";
import { __PRIVATE__ } from "styled-components";

// Node resolves styled-components' server build, which never touches the
// browser stylesheet's bookkeeping. Use the build the screens run.
vi.mock(
  "styled-components",
  async () => import("styled-components/dist/styled-components.browser.esm.js"),
);

import {
  compileThemeCss,
  ThemeStyles,
} from "../../shared/slide-utils/slide-util";

// Theme css used to go through styled-components' createGlobalStyle, created
// on every render: each render rewrote the stylesheet and leaked an entry in
// styled-components' bookkeeping. It is now flattened with stylis and rendered
// as one plain <style>.
describe("compileThemeCss", () => {
  it.each([
    [
      "nested selector",
      "#E1 { .text { color: red; } }",
      "#E1 .text{color:red;}",
    ],
    [
      "& selector",
      "#E1 { &.reversed { color: red; } }",
      "#E1.reversed{color:red;}",
    ],
    [
      "nested @media",
      "#E1 { @media (min-width: 10px) { .x { color: red; } } }",
      "@media (min-width: 10px){#E1 .x{color:red;}}",
    ],
    [
      "selector list",
      "#E1 .a, #E1 .b { color: red; }",
      "#E1 .a,#E1 .b{color:red;}",
    ],
    ["// line comment", "// note\n#E1 { color: red; }", "#E1{color:red;}"],
    ["block comment", "/* note */ #E1 { color: red; }", "#E1{color:red;}"],
    ["unterminated comment", "#E1 { color: red; } /* note", "#E1{color:red;}"],
  ])("flattens %s", (_, css, expected) => {
    expect(compileThemeCss(css)).toBe(expected);
  });
});

describe("ThemeStyles", () => {
  afterEach(() => {
    cleanup();
  });

  it("renders the theme as one stable <style> with the slide id", () => {
    const css = "#SLIDE_ID .text { color: red; }";
    const { rerender } = render(<ThemeStyles id="E1" css={css} />);
    const node = document.querySelector("style[data-theme-styles]");

    expect(node?.textContent).toBe("#E1 .text{color:red;}");

    for (let i = 0; i < 5; i += 1) {
      rerender(<ThemeStyles id="E1" css={css} />);
    }
    expect(document.querySelectorAll("style[data-theme-styles]")).toHaveLength(
      1,
    );
    expect(document.querySelector("style[data-theme-styles]")).toBe(node);

    rerender(<ThemeStyles id="E2" css={css} />);
    expect(
      document.querySelector("style[data-theme-styles]")?.textContent,
    ).toBe("#E2 .text{color:red;}");
  });

  it("leaves styled-components' stylesheet alone", () => {
    const { names } = __PRIVATE__.masterSheet;
    const before = names.size;
    const { rerender } = render(
      <ThemeStyles id="E1" css="#SLIDE_ID { color: red; }" />,
    );

    for (let i = 0; i < 50; i += 1) {
      rerender(<ThemeStyles id="E1" css="#SLIDE_ID { color: red; }" />);
    }

    expect(names.size).toBe(before);
  });

  it.each([[null], [""]])("renders nothing for %j", (css) => {
    const { container } = render(<ThemeStyles id="E1" css={css} />);

    expect(container.innerHTML).toBe("");
    expect(document.querySelector("style[data-theme-styles]")).toBeNull();
  });
});

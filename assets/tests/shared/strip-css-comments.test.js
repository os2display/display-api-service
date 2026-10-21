import { describe, it, expect } from "vitest";

import { stripCssComments } from "../../shared/slide-utils/slide-util";

// styled-components' css parser (@emotion/stylis 0.8.5) loops forever on an
// unterminated block comment (#572), so no comment may reach it. Other syntax
// errors (unclosed braces, quotes, parentheses) do not hang it.
describe("stripCssComments", () => {
  it("removes closed comments, also across lines", () => {
    expect(stripCssComments("/* a\n b */ .x { color: /* c */ red }")).toBe(
      " .x { color:  red }",
    );
  });

  it.each([
    ["unterminated", "/* a\n b\n.x { color: red }"],
    ["ending in * without /", "/* a *\n.x { color: red }"],
    ["after a rule", ".y { color: blue }\n/* a\n.x { color: red }"],
  ])(
    "drops an %s comment and the rest of the sheet, as a browser does",
    (_, css) => {
      const stripped = stripCssComments(css);

      expect(stripped).not.toContain("/*");
      expect(stripped).not.toContain(".x");
    },
  );

  it.each([
    ["double", '.x::after { content: "/* not a comment */" }'],
    ["single", ".x::after { content: '/*' }"],
    ["escaped quotes in", '.x::after { content: "a\\"/*" }'],
  ])("keeps /* inside %s quoted strings", (_, css) => {
    expect(stripCssComments(css)).toBe(css);
  });

  it.each([
    [".a { color: /* note */ red }", ".a { color:  red }"],
    ["/* multi\nline */ .a { color: red }", " .a { color: red }"],
    [".a { color: red } /* .b { color: x }", ".a { color: red } "],
    ['.a::after { content: "/*" }', '.a::after { content: "/*" }'],
    [
      ".a::after { content: 'it\\'s /*' }",
      ".a::after { content: 'it\\'s /*' }",
    ],
    [
      ".a { background: url(https://x/a) }",
      ".a { background: url(https://x/a) }",
    ],
  ])("matches the documented example %j", (css, expected) => {
    expect(stripCssComments(css)).toBe(expected);
  });

  it("removes a comment that stripping another one creates", () => {
    // Removing /**/ joins the / before it and the * after it into a new /*.
    expect(stripCssComments(".a { color: red }//**/* u")).toBe(
      ".a { color: red }",
    );
  });

  it("does not take an apostrophe in an unquoted url() for a string", () => {
    expect(
      stripCssComments(".a { background: url(it's.png) } /* note 'z'"),
    ).toBe(".a { background: url(it's.png) } ");
  });

  it("leaves css without comments unchanged", () => {
    const css =
      "#SLIDE_ID { @media (min-width: 1921px) { .x { background: url(https://example.com/a.png) } } }";

    expect(stripCssComments(css)).toBe(css);
  });
});

import { describe, it, expect } from "vitest";
import {
  lintThemeCss,
  themeCssTokens,
} from "../../shared/theme/theme-css-lint";
import cases from "../../../tests/fixtures/theme-css-cases.json";

const codes = (css) => lintThemeCss(css).map((d) => d.code);

// The cases App\Tests\Validator\ThemeCssValidatorTest runs too, so the editor
// and the API agree.
describe("shared theme css cases", () => {
  it.each(cases.map((c) => [c.name, c.css, c.error]))(
    "%s",
    (name, css, error) => {
      const firstError = lintThemeCss(css).find((d) => d.severity === "error");
      expect(firstError?.code ?? null).toBe(error);
    },
  );
});

describe("lintThemeCss", () => {
  it.each([
    ["empty css", ""],
    ["a scoped rule", "#SLIDE_ID .text { color: red; }"],
    [
      "native nesting",
      "#SLIDE_ID { --c: red; .text { color: var(--c); } &.reversed { color: blue; } }",
    ],
    [
      "nested @media",
      "#SLIDE_ID { @media (min-width: 10px) { .text { color: red; } } }",
    ],
    [
      "scoped rules in a top-level @media",
      "@media (orientation: portrait) { #SLIDE_ID h1 { color: red; } }",
    ],
    [
      "@import and @font-face",
      '@import url("https://example.com/f.css");\n@font-face { font-family: "f"; src: url("https://example.com/f.woff2"); }',
    ],
    [
      "@keyframes",
      "@keyframes fade { from { opacity: 0; } to { opacity: 1; } }",
    ],
    ["a comment holding braces", "/* { */ #SLIDE_ID { color: red; }"],
    ["a string holding braces", '#SLIDE_ID::after { content: "}"; }'],
    [
      "constructs browsers no longer support",
      '#SLIDE_ID { width: expression(1); background: url("javascript:x"); }',
    ],
  ])("accepts %s", (name, css) => {
    expect(lintThemeCss(css)).toEqual([]);
  });

  it("reports an unterminated comment where it starts", () => {
    const css = "#SLIDE_ID { color: red; }\n/* Ændre baggrund\n#SLIDE_ID { }";
    expect(lintThemeCss(css)).toEqual([
      {
        from: 26,
        to: css.length,
        line: 2,
        severity: "error",
        code: "unterminated_comment",
        params: {},
      },
    ]);
  });

  it("reports an unterminated string", () => {
    const [d] = lintThemeCss('#SLIDE_ID::after {\n  content: "x;\n}');
    expect(d).toMatchObject({ code: "unterminated_string", line: 2 });
  });

  it("reports a } with nothing to close where it is", () => {
    const [d] = lintThemeCss("#SLIDE_ID { color: red; }\n}");
    expect(d).toMatchObject({ code: "unexpected_brace", line: 2, from: 26 });
  });

  it("reports a stray } even when the totals match", () => {
    const [d] = lintThemeCss("#SLIDE_ID { } } #SLIDE_ID .x { }");
    expect(d).toMatchObject({ code: "unexpected_brace", line: 1, from: 14 });
  });

  it("reports a { that is never closed where it is", () => {
    const [d] = lintThemeCss("#SLIDE_ID {\n  .x { color: red; }\n");
    expect(d).toMatchObject({ code: "unclosed_brace", line: 1, from: 10 });
    expect(codes("#SLIDE_ID { color: red;")).toEqual(["unclosed_brace"]);
  });

  it("reports a syntax error once per line", () => {
    const diagnostics = lintThemeCss("#SLIDE_ID ( { color: red; }");
    expect(diagnostics.map((d) => [d.code, d.line])).toEqual([["syntax", 1]]);
  });

  it("reports a missing colon after the property name", () => {
    const css = "#SLIDE_ID {\n  padding-left: 30px;\n  padding-top 10px;\n}";
    expect(lintThemeCss(css)).toEqual([
      {
        from: css.indexOf("padding-top") + "padding-top".length,
        to: css.indexOf("padding-top") + "padding-top".length + 1,
        line: 3,
        severity: "error",
        code: "missing_colon",
        params: { property: "padding-top" },
      },
    ]);
  });

  it("reports a // comment as a syntax error", () => {
    const [d] = lintThemeCss("// not a comment\n#SLIDE_ID { color: red; }");
    expect(d).toMatchObject({ code: "syntax", line: 1 });
  });

  it("warns once per selector without #SLIDE_ID", () => {
    const css =
      "#SLIDE_ID h1, h2 { color: red; }\n@media print { .text { color: red; } }";
    expect(
      lintThemeCss(css).map((d) => [d.severity, d.code, d.params.selector]),
    ).toEqual([
      ["warning", "unscoped", "h2"],
      ["warning", "unscoped", ".text"],
    ]);
  });

  it("warns about selectors without #SLIDE_ID in @container and @layer", () => {
    const css =
      "@container (min-width: 1px) { h1 { color: red; } }\n@layer base { #SLIDE_ID h2 { color: red; } .x { color: red; } }";
    expect(lintThemeCss(css).map((d) => [d.code, d.params.selector])).toEqual([
      ["unscoped", "h1"],
      ["unscoped", ".x"],
    ]);
  });

  it("does not take a comment in a selector list for a selector", () => {
    expect(lintThemeCss("#SLIDE_ID h1 /* c */, #SLIDE_ID h2 {}")).toEqual([]);
  });

  it("does not warn about nested rules", () => {
    expect(lintThemeCss("#SLIDE_ID { h1 { color: red; } }")).toEqual([]);
  });
});

describe("themeCssTokens", () => {
  it("classifies tokens", () => {
    const css =
      '/* c */ #SLIDE_ID .t { color: red; content: "x"; width: 2px; }';
    const tokens = themeCssTokens(css).map((t) => [
      css.slice(t.from, t.to),
      t.type,
    ]);
    expect(tokens).toEqual([
      ["/* c */", "comment"],
      ["#SLIDE_ID", "selector"],
      ["t", "selector"],
      ["color", "property"],
      ["red", "value"],
      ["content", "property"],
      ['"x"', "string"],
      ["width", "property"],
      ["2px", "number"],
    ]);
  });

  it("does not colour a statement the parser could not read", () => {
    // padding-top without a colon parses as a nested rule's selector.
    const css = "#SLIDE_ID {\n  padding-left: 30px;\n  padding-top 10px;\n}";
    const tokens = themeCssTokens(css).map((t) => css.slice(t.from, t.to));
    expect(tokens).toEqual(["#SLIDE_ID", "padding-left", "30px"]);
  });
});

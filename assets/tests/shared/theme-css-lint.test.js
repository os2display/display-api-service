import { describe, it, expect } from "vitest";
import {
  lintThemeCss,
  themeCssTokens,
} from "../../shared/theme/theme-css-lint";

const codes = (css) => lintThemeCss(css).map((d) => d.code);

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
      "forbidden constructs named in comments",
      "/* never use expression( or -moz-binding */ #SLIDE_ID { color: red; /* no javascript: urls */ }",
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

  it("reports unbalanced braces at the first unopened brace", () => {
    const [d] = lintThemeCss("#SLIDE_ID { color: red; }\n}");
    expect(d).toMatchObject({ code: "unbalanced_braces", line: 2, from: 26 });
  });

  it("reports an unclosed brace at the end", () => {
    expect(codes("#SLIDE_ID { color: red;")).toEqual(["unbalanced_braces"]);
  });

  it("reports a forbidden construct, case-insensitively", () => {
    const [d] = lintThemeCss("#SLIDE_ID { width: EXPRESSION(1); }");
    expect(d).toMatchObject({
      code: "forbidden",
      from: 19,
      params: { construct: "expression(" },
    });
  });

  it("reports a forbidden construct after one named in a comment", () => {
    const css =
      '/* javascript: */ #SLIDE_ID { background: url("javascript:x"); }';
    const [d] = lintThemeCss(css);
    expect(d).toMatchObject({
      code: "forbidden",
      from: css.lastIndexOf("javascript:"),
      params: { construct: "javascript:" },
    });
  });

  it("reports a syntax error once per line", () => {
    const diagnostics = lintThemeCss(
      "#SLIDE_ID {\n  padding-left: 30px;\n  padding-top 10px;\n}",
    );
    expect(diagnostics.map((d) => [d.code, d.line])).toEqual([["syntax", 3]]);
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
});

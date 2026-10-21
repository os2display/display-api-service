import { parser } from "@lezer/css";

const TOKEN_CLASSES = {
  Comment: "comment",
  StringLiteral: "string",
  NumberLiteral: "number",
  ColorLiteral: "number",
  PropertyName: "property",
  VariableName: "property",
  IdName: "selector",
  ClassName: "selector",
  TagName: "selector",
  PseudoClassName: "selector",
  NestingSelector: "selector",
  AtKeyword: "keyword",
  import: "keyword",
  media: "keyword",
  supports: "keyword",
  keyframes: "keyword",
  charset: "keyword",
  namespace: "keyword",
  ValueName: "value",
  CallTag: "value",
};

const lineOf = (css, offset) => css.slice(0, offset).split("\n").length;

const diagnostic = (css, from, to, severity, code, params = {}) => ({
  from,
  to: Math.max(to, from + 1),
  line: lineOf(css, from),
  severity,
  code,
  params,
});

/**
 * The checks of App\Validator\ThemeCssValidator, with positions, so the
 * editor and the API agree.
 *
 * @param {string} css The theme css.
 * @returns {object | null} The first violation, or null.
 */
function structuralCheck(css) {
  // Offsets of the "{" still open.
  const open = [];
  let i = 0;
  while (i < css.length) {
    if (css.startsWith("/*", i)) {
      const end = css.indexOf("*/", i + 2);
      if (end === -1) {
        return diagnostic(css, i, css.length, "error", "unterminated_comment");
      }
      i = end + 2;
    } else if (css[i] === '"' || css[i] === "'") {
      // A css string ends at its closing quote; a raw newline ends it
      // unclosed. A backslash escapes the next character.
      const quote = css[i];
      let j = i + 1;
      while (j < css.length && css[j] !== quote && css[j] !== "\n") {
        j += css[j] === "\\" ? 2 : 1;
      }
      if (j >= css.length || css[j] !== quote) {
        return diagnostic(css, i, j, "error", "unterminated_string");
      }
      i = j + 1;
    } else {
      if (css[i] === "{") {
        open.push(i);
      } else if (css[i] === "}") {
        if (open.length === 0) {
          return diagnostic(css, i, i + 1, "error", "unexpected_brace");
        }
        open.pop();
      }
      i += 1;
    }
  }

  if (open.length > 0) {
    const at = open.at(-1);
    return diagnostic(css, at, at + 1, "error", "unclosed_brace");
  }

  return null;
}

/**
 * Selectors that do not contain #SLIDE_ID, at the top level or directly
 * inside a top-level @media or @supports. Nested rules inherit the scope.
 *
 * @param {string} css The theme css.
 * @param {object} tree The parsed css.
 * @returns {Array} Warnings.
 */
function unscopedSelectors(css, tree) {
  const warnings = [];
  const checkRuleSet = (ruleSet) => {
    for (let child = ruleSet.firstChild; child; child = child.nextSibling) {
      if (child.name === "Block") break;
      if (child.name === ",") continue;
      const selector = css.slice(child.from, child.to);
      if (!selector.includes("#SLIDE_ID")) {
        warnings.push(
          diagnostic(css, child.from, child.to, "warning", "unscoped", {
            selector,
          }),
        );
      }
    }
  };

  for (let node = tree.topNode.firstChild; node; node = node.nextSibling) {
    if (node.name === "RuleSet") {
      checkRuleSet(node);
    } else if (
      node.name === "MediaStatement" ||
      node.name === "SupportsStatement"
    ) {
      const block = node.getChild("Block");
      block?.getChildren("RuleSet").forEach(checkRuleSet);
    }
  }

  return warnings;
}

/**
 * Check theme css as the admin editor shows it.
 *
 * Structural errors (the rules the API enforces) come first; when there are
 * none, syntax errors from the parser, at most one per line. Only css that
 * parses is checked for selectors missing #SLIDE_ID.
 *
 * @param {string} css The theme css.
 * @returns {Array} Diagnostics: {from, to, line, severity, code, params}.
 */
export function lintThemeCss(css) {
  if (!css) return [];

  const structural = structuralCheck(css);
  if (structural) return [structural];

  const tree = parser.parse(css);
  const errors = [];
  tree.iterate({
    enter: (node) => {
      if (!node.type.isError) return;
      const error = diagnostic(css, node.from, node.to, "error", "syntax", {
        text: css.slice(node.from, Math.max(node.to, node.from + 1)).trim(),
      });
      if (errors.at(-1)?.line !== error.line) errors.push(error);
    },
  });
  if (errors.length > 0) return errors;

  return unscopedSelectors(css, tree);
}

/**
 * Token ranges for syntax highlighting.
 *
 * @param {string} css The theme css.
 * @returns {Array} Ranges: {from, to, type}.
 */
export function themeCssTokens(css) {
  const tokens = [];
  parser.parse(css).iterate({
    enter: (node) => {
      const type = TOKEN_CLASSES[node.name];
      if (type) {
        tokens.push({ from: node.from, to: node.to, type });
        return false;
      }
      return undefined;
    },
  });
  return tokens;
}

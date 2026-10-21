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

// At-rules the parser has no statement of its own for, whose block holds
// rules that apply to the whole screen like top-level ones.
const GROUPING_AT_RULES = ["@container", "@layer"];

// A declaration without its colon: a name, whitespace, then a value.
const MISSING_COLON = /^(-{0,2}[a-z][\w-]*)\s+[^\s:{}]/i;

// The editor asks for tokens and lint of the same css, so the last parse is
// kept.
let lastParse = { css: null, tree: null };
const parse = (css) => {
  if (lastParse.css !== css) lastParse = { css, tree: parser.parse(css) };
  return lastParse.tree;
};

/**
 * Statements holding a parse error, each from after the previous "{", "}" or
 * ";" up to the next one, with the first error in it.
 *
 * @param {string} css The theme css.
 * @param {object} tree The parsed css.
 * @returns {Array} Statements: {from, to, end, error}.
 */
function brokenStatements(css, tree) {
  const statements = [];
  tree.iterate({
    enter: (node) => {
      if (!node.type.isError) return;
      let from = node.from;
      while (from > 0 && !"{};".includes(css[from - 1])) from -= 1;
      if (statements.at(-1)?.from === from) return;
      let to = node.from;
      while (to < css.length && !"{};".includes(css[to])) to += 1;
      statements.push({
        from,
        to,
        end: css[to],
        error: { from: node.from, to: node.to },
      });
    },
  });
  return statements;
}

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
 * inside a top-level @media, @supports, @container or @layer. Nested rules
 * inherit the scope.
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
      if (child.name === "," || child.name === "Comment") continue;
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
      node.name === "SupportsStatement" ||
      (node.name === "AtRule" &&
        GROUPING_AT_RULES.includes(
          css.slice(node.from, node.firstChild.to).toLowerCase(),
        ))
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

  const tree = parse(css);
  const errors = [];
  brokenStatements(css, tree).forEach(({ from, to, end, error }) => {
    const text = css.slice(from, to);
    const property =
      end === ";" && !text.includes(":") && MISSING_COLON.exec(text.trim());
    let found;
    if (property) {
      // Right after the name, where the colon belongs.
      const at =
        from + text.length - text.trimStart().length + property[1].length;
      found = diagnostic(css, at, at + 1, "error", "missing_colon", {
        property: property[1],
      });
    } else {
      found = diagnostic(css, error.from, error.to, "error", "syntax", {
        text: css.slice(error.from, Math.max(error.to, error.from + 1)).trim(),
      });
    }
    if (errors.at(-1)?.line !== found.line) errors.push(found);
  });
  if (errors.length > 0) return errors;

  return unscopedSelectors(css, tree);
}

/**
 * Token ranges for syntax highlighting.
 *
 * Statements the parser could not read are left uncoloured: their tokens are
 * guesses, such as a property without its colon read as a selector.
 *
 * @param {string} css The theme css.
 * @returns {Array} Ranges: {from, to, type}.
 */
export function themeCssTokens(css) {
  const tree = parse(css);
  const broken = brokenStatements(css, tree);
  const tokens = [];
  tree.iterate({
    enter: (node) => {
      const type = TOKEN_CLASSES[node.name];
      if (type) {
        if (!broken.some((s) => node.from < s.to && node.to > s.from)) {
          tokens.push({ from: node.from, to: node.to, type });
        }
        return false;
      }
      return undefined;
    },
  });
  return tokens;
}

import { useMemo } from "react";
import { compile, middleware, prefixer, serialize, stringify } from "stylis";

/**
 * Get the first media url of a media field.
 *
 * @param {object} mediaData The object of media objects.
 * @param {Array} field The field to get the first media url from.
 * @returns {string | any | null} The first media url of the field.
 */
function getFirstMediaUrlFromField(mediaData, field) {
  if (Array.isArray(field) && field.length > 0) {
    const media = mediaData[field[0]];

    if (media?.assets?.uri) {
      return media.assets.uri;
    }
    if (media?.url) {
      return media.url;
    }
    return null;
  }

  return null;
}

/**
 * Get the all media urls of a media field.
 *
 * @param {object} mediaData The object of media objects.
 * @param {Array} field The field to get the all media urls from.
 * @returns {string | any | null} Media urls for the given field.
 */
function getAllMediaUrlsFromField(mediaData, field) {
  if (Array.isArray(field)) {
    return field.reduce((previous, current) => {
      const media = mediaData[current];

      if (media?.assets?.uri) {
        previous.push(media.assets.uri);
      } else if (media?.url) {
        previous.push(media.url);
      }

      return previous;
    }, []);
  }

  return [];
}

/**
 * Remove css comments.
 *
 * An unterminated comment hung styled-components' css parser (#572); stripping
 * keeps it out of every css parser. Like a browser, an unterminated comment
 * runs to the end of the sheet. Quoted strings are kept as they are, so a "/*"
 * inside one is not taken for a comment.
 *
 * @param {string} css Css as a string.
 * @returns {string} The css without comments.
 */
function stripCssComments(css) {
  // The pattern has two alternatives, tried left to right at each position:
  //
  //   ("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')  a quoted string, captured:
  //       "  then any run of escaped characters (\\.) or characters that
  //       are neither " nor \ ([^"\\]), then the closing ". Same for '.
  //   \/\*[\s\S]*?(?:\*\/|$)  a comment: /* then as few characters as
  //       possible, newlines included ([\s\S]*?), up to the first */ or,
  //       if there is none, the end of the css ($).
  //
  // Strings are matched first, so a /* inside one is consumed as part of the
  // string and never starts a comment. The callback keeps captured strings
  // and replaces comments with "".
  //
  // Examples:
  //   ".a { color: /* note */ red }"         -> ".a { color:  red }"
  //   "/* multi\nline */ .a { color: red }"  -> " .a { color: red }"
  //   ".a { color: red } /* .b { color: x }" -> ".a { color: red } "
  //   '.a::after { content: "/*" }'          -> unchanged
  //   ".a::after { content: 'it\\'s /*' }"   -> unchanged
  //   ".a { background: url(https://x/a) }"  -> unchanged (// is no comment)
  return css.replace(
    /("(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*')|\/\*[\s\S]*?(?:\*\/|$)/g,
    (match, string) => string ?? "",
  );
}

// Lines that are a // comment. styled-components removed these before parsing,
// so themes written with them keep working.
const LINE_COMMENTS = /^\s*\/\/.*$/gm;

/**
 * Flatten theme css to plain css.
 *
 * Themes may nest rules SCSS-style (`#SLIDE_ID { .text { … } &.reversed { … } }`),
 * which browsers on older screens cannot read. stylis expands the nesting and
 * adds vendor prefixes, as styled-components did with theme css before.
 *
 * @param {string} css Css as a string.
 * @returns {string} Flat css.
 */
function compileThemeCss(css) {
  return serialize(
    compile(css.replace(LINE_COMMENTS, "")),
    middleware([prefixer, stringify]),
  );
}

/**
 * Create a theme style for a slide.
 *
 * Rendered as a plain <style>, memoised on the css and slide id. Created with
 * styled-components' createGlobalStyle on every render, it rewrote the
 * stylesheet on each render and leaked an entry in styled-components'
 * bookkeeping.
 *
 * @param {object} props Props.
 * @param {string} props.id Slide execution id.
 * @param {string | null} props.css Css as a string.
 * @returns {object} The component.
 */
function ThemeStyles({ id, css = null }) {
  const slideCss = useMemo(
    () =>
      css
        ? compileThemeCss(
            stripCssComments(css).replaceAll("#SLIDE_ID", `#${id}`),
          )
        : "",
    [css, id],
  );

  if (!slideCss) return null;

  return <style data-theme-styles={id}>{slideCss}</style>;
}

export {
  compileThemeCss,
  getAllMediaUrlsFromField,
  getFirstMediaUrlFromField,
  stripCssComments,
  ThemeStyles,
};

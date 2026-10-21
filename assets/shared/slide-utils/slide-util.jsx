import { useMemo } from "react";

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
 * Whether html has visible text.
 *
 * The rich text editor stores an emptied field as "<p></p>", which is truthy,
 * so templates check this instead of the value itself.
 *
 * @param {string | null | undefined} html Html as a string.
 * @returns {boolean} True if the html has text other than whitespace.
 */
function hasText(html) {
  if (!html) return false;

  return (
    new DOMParser()
      .parseFromString(html, "text/html")
      .body.textContent.trim() !== ""
  );
}

/**
 * Remove css comments.
 *
 * An unterminated comment hung styled-components' css parser (#572). Like a
 * browser, an unterminated comment runs to the end of the sheet. Quoted strings
 * and unquoted url() are kept as they are, so a "/*" inside one is not taken
 * for a comment.
 *
 * @param {string} css Css as a string.
 * @returns {string} The css without comments.
 */
function stripCssComments(css) {
  // The pattern has two alternatives, tried left to right at each position:
  //
  //   ("(?:\\[\s\S]|[^"\\])*"|'(?:\\[\s\S]|[^'\\])*'|url\((?!\s*["'])[^)]*\))
  //       a token to keep, captured: a quoted string ("  then any run of
  //       escaped characters or characters that are neither " nor \, then
  //       the closing "; same for '), or an unquoted url(...), whose
  //       contents may hold a ' that does not start a string.
  //   \/\*[\s\S]*?(?:\*\/|$)  a comment: /* then as few characters as
  //       possible, newlines included ([\s\S]*?), up to the first */ or,
  //       if there is none, the end of the css ($).
  //
  // Tokens are matched first, so a /* inside one is consumed as part of it
  // and never starts a comment. The callback keeps captured tokens and
  // replaces comments with "". Removing a comment can join the / before it
  // and the * after it into a new /*, so the replace repeats until nothing
  // changes.
  //
  // Examples:
  //   ".a { color: /* note */ red }"         -> ".a { color:  red }"
  //   "/* multi\nline */ .a { color: red }"  -> " .a { color: red }"
  //   ".a { color: red } /* .b { color: x }" -> ".a { color: red } "
  //   '.a::after { content: "/*" }'          -> unchanged
  //   ".a::after { content: 'it\\'s /*' }"   -> unchanged
  //   ".a { background: url(https://x/a) }"  -> unchanged (// is no comment)
  //   ".a { color: red }//**/* u"            -> ".a { color: red }"
  const pattern =
    /("(?:\\[\s\S]|[^"\\])*"|'(?:\\[\s\S]|[^'\\])*'|url\((?!\s*["'])[^)]*\))|\/\*[\s\S]*?(?:\*\/|$)/gi;

  let previous;
  do {
    previous = css;
    css = css.replace(pattern, (match, kept) => kept ?? "");
  } while (css !== previous);

  return css;
}

/**
 * Create a theme style for a slide.
 *
 * Rendered as written in a plain <style>, memoised on the css and slide id.
 * Nesting is the browser's native css nesting; operators make sure their
 * screens' browser supports what their theme uses. Created with
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
    () => (css ? stripCssComments(css).replaceAll("#SLIDE_ID", `#${id}`) : ""),
    [css, id],
  );

  if (!slideCss) return null;

  return <style data-theme-styles={id}>{slideCss}</style>;
}

export {
  getAllMediaUrlsFromField,
  getFirstMediaUrlFromField,
  hasText,
  stripCssComments,
  ThemeStyles,
};

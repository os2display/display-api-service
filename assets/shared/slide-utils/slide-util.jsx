import { createGlobalStyle } from "styled-components";

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
 * An unterminated comment hangs styled-components' css parser, so none may
 * reach it. Like a browser, an unterminated comment runs to the end of the
 * sheet. Quoted strings are kept as they are, so a "/*" inside one is not taken
 * for a comment.
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

/**
 * Create a theme style for a slide.
 *
 * @param {object} props Props.
 * @param {string} props.id Slide execution id.
 * @param {string | null} props.css Css as a string.
 * @returns {object} The component.
 */
function ThemeStyles({ id, css = null }) {
  if (!css) return <></>;

  const slideCss = stripCssComments(css).replaceAll("#SLIDE_ID", `#${id}`);

  const ThemeComponent = createGlobalStyle`${slideCss}`;

  return <ThemeComponent />;
}

export {
  getAllMediaUrlsFromField,
  getFirstMediaUrlFromField,
  hasText,
  stripCssComments,
  ThemeStyles,
};

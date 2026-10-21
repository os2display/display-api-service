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
 * Create a theme style for a slide.
 *
 * Rendered as written in a plain <style>, memoised on the css and slide id.
 * Nesting is the browser's native css nesting; operators make sure their
 * screens' browser supports what their theme uses. The browser parses the
 * css, so an unterminated comment runs to the end of the sheet, as in any
 * stylesheet; through styled-components' parser it hung the client (#572).
 * Created with styled-components' createGlobalStyle on every render, it also
 * rewrote the stylesheet on each render and leaked an entry in
 * styled-components' bookkeeping.
 *
 * @param {object} props Props.
 * @param {string} props.id Slide execution id.
 * @param {string | null} props.css Css as a string.
 * @returns {object} The component.
 */
function ThemeStyles({ id, css = null }) {
  const slideCss = useMemo(
    () => (css ? css.replaceAll("#SLIDE_ID", `#${id}`) : ""),
    [css, id],
  );

  if (!slideCss) return null;

  return <style data-theme-styles={id}>{slideCss}</style>;
}

export {
  getAllMediaUrlsFromField,
  getFirstMediaUrlFromField,
  hasText,
  ThemeStyles,
};

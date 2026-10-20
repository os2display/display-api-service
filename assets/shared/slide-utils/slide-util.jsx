import { createGlobalStyle } from "styled-components";

let screenPixels = null;

/**
 * The screen size in physical pixels, measured once.
 *
 * @returns {{width: number, height: number}} Width and height.
 */
function getScreenPixels() {
  if (screenPixels === null) {
    const ratio = window.devicePixelRatio || 1;
    screenPixels = {
      width: Math.round((window.screen?.width ?? 0) * ratio),
      height: Math.round((window.screen?.height ?? 0) * ratio),
    };
  }

  return screenPixels;
}

// How far a variant may be scaled up to fill the screen. Keeps a 1920×1037
// variant on a 1920×1080 screen instead of one with 4× the pixels.
const MAX_UPSCALE = 1.1;

/**
 * Get the url of a media object for a screen of the given size.
 *
 * Picks the smallest scaled variant that covers both sides of the screen, so
 * an image filling the slide stays sharp whatever its orientation, allowing up
 * to MAX_UPSCALE. Falls back to the original without variants, without a known
 * screen size, or when no variant is large enough.
 *
 * @param {object} media The media object.
 * @param {{width: number, height: number}} screen Screen size in pixels.
 * @returns {string | null} The media url.
 */
function getMediaUrl(media, screen = getScreenPixels()) {
  if (media?.assets?.uri) {
    const covering =
      screen.width > 0 && screen.height > 0
        ? Object.values(media.assets.variants ?? {})
            .filter(
              ({ width, height }) =>
                Math.max(screen.width / width, screen.height / height) <=
                MAX_UPSCALE,
            )
            .sort((a, b) => a.width - b.width)
        : [];

    return covering[0]?.uri ?? media.assets.uri;
  }

  return media?.url ?? null;
}

/**
 * Get the first media url of a media field.
 *
 * @param {object} mediaData The object of media objects.
 * @param {Array} field The field to get the first media url from.
 * @returns {string | any | null} The first media url of the field.
 */
function getFirstMediaUrlFromField(mediaData, field) {
  if (Array.isArray(field) && field.length > 0) {
    return getMediaUrl(mediaData[field[0]]);
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
      const url = getMediaUrl(mediaData[current]);

      if (url) {
        previous.push(url);
      }

      return previous;
    }, []);
  }

  return [];
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

  const slideCss = css.replaceAll("#SLIDE_ID", `#${id}`);

  const ThemeComponent = createGlobalStyle`${slideCss}`;

  return <ThemeComponent />;
}

export {
  getAllMediaUrlsFromField,
  getFirstMediaUrlFromField,
  getMediaUrl,
  ThemeStyles,
};

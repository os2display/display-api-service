import idFromPath from "./id-from-path";

/**
 * The template data a slide renders with.
 *
 * There is nothing to fetch here. Every template's render code and config is
 * bundled into this build (see the glob import in shared/slide-utils/templates.js),
 * and the only thing rendering wants from templateData is the ULID it looks the
 * bundled module up by - which is the last segment of the IRI the slide already
 * carries. Requesting /v2/templates/{ulid} to read it back cost a request per
 * template per pull and, when that request was throttled, a region that emptied
 * itself once its playlist wrapped (#507).
 *
 * @param {object} slide The slide.
 * @returns {object|null} The template data, or null if the slide names no template.
 */
export default function templateDataFromSlide(slide) {
  const id = idFromPath(slide?.templateInfo?.["@id"]);

  return id ? { id } : null;
}

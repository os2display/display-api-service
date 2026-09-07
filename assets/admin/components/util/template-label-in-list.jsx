import { useTranslation } from "react-i18next";
import { getTitle } from "../../../shared/slide-utils/templates";
import idFromUrl from "./helpers/id-from-url";

/**
 * The name of the template a slide renders with.
 *
 * There is nothing to fetch: every template's config is bundled into this build,
 * and the title the API served was copied verbatim out of that bundled JSON by
 * TemplateService, so requesting /v2/templates/{ulid} cost a round trip per row
 * to read back a value already in the page.
 *
 * @param {object} props The props.
 * @param {object} props.templateInfo The slide's template info, carrying the template IRI.
 * @returns {string} The template title.
 */
function TemplateLabelInList({ templateInfo }) {
  const { t } = useTranslation("common", {
    keyPrefix: "template-label-in-list",
  });

  return getTitle(idFromUrl(templateInfo?.["@id"])) ?? t("unknown-template");
}

export default TemplateLabelInList;

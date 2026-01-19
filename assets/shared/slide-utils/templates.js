// Load templates.
// @see https://vite.dev/guide/features.html#glob-import
// @see docs/custom-templates.md
// Eager loading because no other code piece imports the templates otherwise.
const templateModules = import.meta.glob("../templates/*.jsx", { eager: true });
const customTemplatesModules = import.meta.glob("../custom-templates/*.jsx", {
  eager: true,
});

/**
 * Check if the module implements the template interface.
 *
 * @param {object} module The module to check.
 * @return {boolean}
 */
function duckTypingTemplateModule(module) {
  return (
    typeof module.id === "function" &&
    typeof module.config === "function" &&
    typeof module.renderSlide === "function" &&
    typeof module.renderAdminForm === "function"
  );
}

/**
 * Find the module by the template ULID.
 *
 * @param {Array} modules Array of modules.
 * @param {string} templateUlid The ULID of the template.
 * @return {*|null}
 */
function findModule(modules, templateUlid) {
  for (const key of Object.keys(modules)) {
    const module = modules[key].default;

    if (duckTypingTemplateModule(module)) {
      if (module.id() === templateUlid) {
        return module;
      }
    } else {
      throw new Error(
        "Template with ulid: " + templateUlid + " should implement functions: id, config, renderSlide and renderAdminForm.",
      );
    }
  }

  return null;
}

function getTemplateModule(templateUlid) {
  if (!templateUlid) {
    return null;
  }

  const module =
    findModule(templateModules, templateUlid) ??
    findModule(customTemplatesModules, templateUlid) ??
    null;

  if (module === null) {
    throw new Error(`Cannot find module '${templateUlid}'`);
  }

  return module;
}

/**
 * Get the config of the template.
 *
 * @param templateUlid The ULID of the template.
 * @return object
 */
function getConfig(templateUlid) {
  return getTemplateModule(templateUlid).config();
}

/**
 * Render slide.
 *
 * @param {object} slide The slide object.
 * @param {string} run The run id.
 * @param {Function} slideDone The function to invoke when the slide is done.
 * @return {JSXElement|string}
 */
function renderSlide(slide, run, slideDone) {
  const templateUlid = slide?.templateData?.id;
  const module = getTemplateModule(templateUlid);

  if (!module) {
    return "";
  }

  return module.renderSlide(slide, run, slideDone);
}

/**
 * Render admin form.
 *
 * @param {string} templateUlid Ulid of the template.
 * @param {object} formStateObject The object to change.
 * @param {Function} onChange Function to invoke when the form changes.
 * @param {Function} handleMedia Function to invoke when media is changed.
 * @param {Array} mediaData Array of media data.
 * @return {*|null}
 */
function renderAdminForm(
  templateUlid,
  formStateObject,
  onChange,
  handleMedia,
  mediaData,
) {
  const module = getTemplateModule(templateUlid);

  if (!module) {
    return null;
  }

  return module.renderAdminForm(
    formStateObject,
    onChange,
    handleMedia,
    mediaData,
  );
}

export { getConfig, renderSlide, renderAdminForm };

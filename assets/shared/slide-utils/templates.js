// Load templates.
// @see https://vite.dev/guide/features.html#glob-import
// @see docs/custom-templates.md
// Eager loading because no other code piece imports the templates otherwise.
const templateModules = import.meta.glob("../templates/*.jsx", { eager: true });
const customTemplatesModules = import.meta.glob("../custom-templates/*.jsx", {
  eager: true,
});

function duckTypingTemplateModule(module) {
  return (
    typeof module.id === "function" &&
    typeof module.config === "function" &&
    typeof module.renderSlide === "function"
  );
}

function findModule(modules, templateUlid) {
  for (const key of Object.keys(modules)) {
    const module = modules[key].default;

    if (duckTypingTemplateModule(module)) {
      if (module.id() === templateUlid) {
        return module;
      }
    } else {
      throw new Error(
        "Template should implement functions: id(), config(), renderSlide(slide, run, slideDone)",
      );
    }
  }

  return null;
}

/**
 * Find the bundled module for a template ULID.
 *
 * @param {string} templateUlid The ULID of the template.
 * @returns {object|null} The module, or null if this build bundles no such template.
 */
function findTemplateModule(templateUlid) {
  if (!templateUlid) {
    return null;
  }

  return (
    findModule(templateModules, templateUlid) ??
    findModule(customTemplatesModules, templateUlid) ??
    null
  );
}

function getTemplateModule(templateUlid) {
  const module = findTemplateModule(templateUlid);

  if (module === null && templateUlid) {
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
 * The title of a template, for naming it in a list.
 *
 * Deliberately forgiving where getConfig() and renderSlide() are not: a slide can
 * outlive the template it names - a custom template dropped from a build, or a
 * ULID from an older install - and one such slide must not take a whole admin
 * list down with it. Rendering that slide still has to fail loudly, so the throw
 * stays on the render path where an error boundary can catch it (#507).
 *
 * @param {string} templateUlid The ULID of the template.
 * @returns {string|null} The title, or null if this build bundles no such template.
 */
function getTitle(templateUlid) {
  return findTemplateModule(templateUlid)?.config()?.title ?? null;
}

/**
 * Render slide.
 *
 * @param {object} slide The slide object.
 * @param {number} run Run id. Changes each time the slide should run.
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

export { getConfig, getTitle, renderSlide };

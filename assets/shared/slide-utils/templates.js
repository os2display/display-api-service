// Load all templates.
// @see https://vite.dev/guide/features.html#glob-import
const templateModules = import.meta.glob('../templates/*.jsx', { eager: true })
const customTemplatesModules = import.meta.glob('../custom-templates/*.jsx', { eager: true })

function findModule(modules, templateUlid) {
  for (const key of Object.keys(modules)) {
    const module = modules[key].default;

    if (typeof(module.id) === "function" &&
        typeof(module.config) == "function" &&
        typeof(module.renderSlide) == "function") {
      if (module.id() === templateUlid) {
        return module;
      }
    } else {
      throw new Error("Template should have functions id(), config(), and renderSlide()");
    }
  }

  return null;
}

function getTemplateModule(templateUlid) {
  if (!templateUlid) {
    return null;
  }

  const module = findModule(templateModules, templateUlid) ??
    findModule(customTemplatesModules, templateUlid) ?? null;

  if (module === null) {
    throw new Error(`Cannot find module '${templateUlid}'`);
  }

  return module;
}

function getSlideConfig(templateUlid) {
  return getTemplateModule(templateUlid).config();
}

function renderSlide(slide, run, slideDone) {
  const templateUlid = slide?.templateData?.id;
  const module = getTemplateModule(templateUlid);

  if (!module) {
    return '';
  }

  return module.renderSlide(slide, run, slideDone);
}

export {
  getSlideConfig,
  renderSlide
}

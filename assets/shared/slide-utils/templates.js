// Load all templates
const templateModules = import.meta.glob('../templates/*.jsx', { eager: true })
const customTemplatesModules = import.meta.glob('../custom-templates/*.jsx', { eager: true })

const idToModule = {};

Object.keys(templateModules).map((path) => {
  const module = templateModules[path].default;
  idToModule[module.id()] = module;
});

Object.keys(customTemplatesModules).map((path) => {
  const module = customTemplatesModules[path].default;
  idToModule[module.id()] = module;
});

function getTemplateModule(templateUlid) {
  return idToModule[templateUlid];
}

function getSlideConfig(templateUlid) {
  return getTemplateModule(templateUlid).config();
}

function renderSlide(slide, run, slideDone) {
  const templateUlid = slide?.templateData?.id;
  return getTemplateModule(templateUlid).renderSlide(slide, run, slideDone);
}

export {
  getSlideConfig,
  renderSlide
}

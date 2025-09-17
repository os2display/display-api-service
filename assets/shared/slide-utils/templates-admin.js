// Load templates.
// @see https://vite.dev/guide/features.html#glob-import
// @see docs/custom-templates.md
// Eager loading because no other code piece imports the templates otherwise.
const templateModules = import.meta.glob("../templates/*.jsx", { eager: true });
const customTemplatesModules = import.meta.glob("../custom-templates/*.jsx", {
  eager: true,
});

function duckTypingAdminFormModule(module) {
  return (
    typeof module.id === "function" &&
    typeof module.config === "function" &&
    typeof module.renderAdminForm === "function"
  );
}

function findAdminFormModule(modules, templateUlid) {
  for (const key of Object.keys(modules)) {
    const module = modules[key].default;

    if (duckTypingAdminFormModule(module)) {
      if (module.id() === templateUlid) {
        return module;
      }
    }
  }

  return null;
}

function getAdminModule(templateUlid) {
  if (!templateUlid) {
    return null;
  }

  const module =
    findAdminFormModule(templateModules, templateUlid) ??
    findAdminFormModule(customTemplatesModules, templateUlid) ??
    null;

  if (module === null) {
    return null;
  }

  return module;
}

function renderAdminForm(
  templateUlid,
  formStateObject,
  onChange,
  handleMedia,
  mediaData,
) {
  const module = getAdminModule(templateUlid);

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

export { renderAdminForm };

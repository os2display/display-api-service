# Custom Templates

It is possible to include your own templates in your installation.

## Location

Custom templates should be placed in the folder `assets/shared/custom-templates/`.
This folder is in .gitignore so the contents will not be added to the git repository.

How you populate this folder with your custom templates is up to you:

* A git repository with root in the `assets/shared/custom-templates/` folder.
* A symlink from another folder.
* Maintaining a fork of the display repository.
* ...

## Files

The following files are required for a custom template:

* custom-template-name.jsx - A javascript module for the template.
* custom-template-name.json - A configuration file for the template.

Replace `custom-template-name` with a unique name for the template.

### custom-template-name.jsx

The `.jsx` should expose the following functions:

* id() - The ULID of the template. Generate a ULID for your custom template.
* config() - Should contain the following keys: id (as above), title (the titel displayed in the admin), options,
  adminForm.
* renderSlide(slide, run, slideDone) - Should return the JSX for the template.

For an example of a custom template see `assets/shared/custom-templates-example/`.

## Contributing template

If you think the template could be used by other, consider contributing the template to the project as a Pull Request.

### Guide for contributing template

* Fork the `os2display/display` repository.
* Move your custom template files (the .json and .jsx files and other required files) from the
  `assets/shared/custom-templates/` folder to the `assets/shared/templates/` folder.
* Create a PR to `os2display/display` repository.

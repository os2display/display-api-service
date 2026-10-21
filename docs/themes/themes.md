# Theme development

## Description

In the admin it is possible to create custom themes for templates.

For the styles to have effect you will need to use the `#SLIDE_ID` to all styling.

`#SLIDE_ID` will be replaced with an actual id in the Client to isolate the styling on the current slide.

When a theme is saved, its css is checked for an unterminated comment, which could hang every screen
showing the theme, and for an unterminated string or unbalanced braces, which break the theme's own
rules. Outside comments it must not use `expression(`, `-moz-binding` or `javascript:`. Other mistakes,
such as an unknown property, are not checked; the browser ignores them.

## Editor

The css field in the admin highlights the css and lists problems while typing. Click a problem's line
number to select it in the field. Problems never block saving; the checks above decide what is saved.

- Errors: the checks above, and syntax errors such as a missing colon. `//` does not start a comment
  in css; use `/* */`. The syntax check may flag newer css it does not know yet, such as
  `:nth-child(2 of .x)`; if the screens' browser supports it, save anyway.
- Warnings: a selector without `#SLIDE_ID`, at the top level or directly inside a top-level `@media`
  or `@supports`. It applies to the whole screen, not just the slide.

Highlighting needs a browser with the CSS Custom Highlight API (Chrome/Edge 105, Firefox 140,
Safari 17.2). Other browsers show a plain field with the same problem list.

## Examples

An example file can be found in this directory: [example.css](example.css).

Below is an example of variables on the slide container.

```css

#SLIDE_ID {
  /* Default variables */
  --bg-light: #f6f6f6;
  --text-dark: #333333;
  --color-primary: #3761d9;
  --color-secondary: #ef0043;
  --font-family-base: Arial, "sans-serif";
  /* Darkmode overrides */
  --bg-dark: #333333;
  --text-light: #ffffff;
}

```

Below is a example of simple background color on the whole slide using the template Text and Image.

```css

#SLIDE_ID .template-text-image {
  background-color: hsl(240, 100, 99);
}

```

Below is a example of setting the background color of the template Video (default black).

```css

#SLIDE_ID {
  --video-background-color: #ffffff;
}

```

Below is a example of setting a color on `H1` elements.

```css

#SLIDE_ID h1 {
  color: hsl(240, 3, 7);
}

```

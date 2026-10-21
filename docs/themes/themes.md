# Theme development

## Description

In the admin it is possible to create custom themes for templates.

For the styles to have effect you will need to use the `#SLIDE_ID` to all styling.

`#SLIDE_ID` will be replaced with an actual id in the Client to isolate the styling on the current slide.

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

#SLIDE_ID .template-image-text {
  background-color: hsl(240 100% 99%);
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
  color: hsl(240 3% 7%);
}

```

## Nesting

The theme is applied as written, so rules can be nested with native
[css nesting](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_nesting), where `&` refers to the
parent selector. The browser on the screens must support it: Chromium 112 or newer, and 120 or newer
for nested element selectors such as `h1 { … }` without `&`. Operators make sure their screens'
browser supports the css their themes use. SCSS-only syntax, such as `// …` comments or `&-suffix`
selectors, is not supported.

```css

#SLIDE_ID {
  .text {
    color: hsl(240 3% 7%);
  }

  & .box {
    padding: 1rem;
  }
}

```

Declarations must be valid css: one with a missing colon, such as `padding-top 10px;`, is ignored, as
in a browser stylesheet.

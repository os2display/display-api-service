import { defineConfig } from "vitest/config";
import svgr from "vite-plugin-svgr";

export default defineConfig({
  // Mirrors the svgr setup in vite.config.js. Without it an `import Logo from
  // "./logo.svg"` resolves to a data-URI string instead of a component, and
  // rendering the template throws "did not match the Name production" in jsdom
  // — so any template importing an SVG could not be tested at all.
  plugins: [
    svgr({
      svgrOptions: {
        exportType: "default",
        ref: true,
        svgo: false,
        titleProp: true,
      },
      include: "**/*.svg",
    }),
  ],
  test: {
    environment: "jsdom",
    include: ["assets/**/*.test.{js,jsx}"],
    setupFiles: ["./assets/tests/setup.js"],
  },
});

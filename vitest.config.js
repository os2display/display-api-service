import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    environment: "jsdom",
    include: ["assets/**/*.test.{js,jsx}"],
    setupFiles: ["./assets/tests/setup.js"],
  },
});

import {defineConfig} from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import react from "@vitejs/plugin-react-swc";
import svgr from "vite-plugin-svgr";

export default defineConfig(({command}) => {
  return {
    base: command === 'serve' ? '/vite' : '/build',
    css: {
      preprocessorOptions: {
        scss: {
          quietDeps: true
        },
      }
    },
    plugins: [
      react(),
      symfonyPlugin(),
      svgr({
        // svgr options: https://react-svgr.com/docs/options/
        svgrOptions: {exportType: "default", ref: true, svgo: false, titleProp: true},
        include: "**/*.svg",
      }),

    ],
    build: {
      outDir: "./public/build",
      emptyOutDir: true,
      path: "./public/build",
      chunkSizeWarningLimit: 1500,
      rollupOptions: {
        output: {
          manualChunks: {
            'react': [
              "react",
              "react-dom",
              "react-router",
              "react-router-dom",
            ],
            'react-libraries': [
              "react-bootstrap",
              "react-color-palette",
              "react-dropzone",
              "react-i18next",
              "react-images-uploading",
              "react-intl",
              "react-multi-select-component",
              "react-paginate",
              "react-quill",
              "react-redux",
              "react-select",
              "react-table",
              "react-toastify",
              "react-tooltip",
              "react-transition-group",
            ],
            'libraries': [
              "@hello-pangea/dnd",
              "@popperjs/core",
              "@reduxjs/toolkit",
              "@u-wave/react-vimeo",
              "bootstrap",
              "crypto-js",
              "dayjs",
              "dompurify",
              "focus-trap-react",
              "html-react-parser",
              "i18next",
              "jwt-decode",
              "lodash.clonedeep",
              "lodash.get",
              "lodash.last",
              "lodash.set",
              "lodash.uniqwith",
              "pino",
              "prop-types",
              "qrcode",
              "query-string",
              "rrule",
              "styled-components",
              "suncalc",
              "ulid",
            ],
            'fortawesome': ["@fortawesome/fontawesome-svg-core", "@fortawesome/free-solid-svg-icons", "@fortawesome/react-fontawesome"]
          },
        },
        treeshake: 'smallest',
        input: {
          admin: "./assets/admin/index.jsx",
          client: "./assets/client/index.jsx",
          template: "./assets/shared/template/index.jsx"
        },
      },
    },
    server: {
      strictPort: true,
      port: 3000,
      host: "0.0.0.0",
      hmr: {
        host: "display.local.itkdev.dk",
        protocol: "wss",
        clientPort: 443,
      }
    },
  }
});

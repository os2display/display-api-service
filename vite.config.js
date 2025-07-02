import {defineConfig} from "vite";
import symfonyPlugin from "vite-plugin-symfony";
import react from "@vitejs/plugin-react-oxc";
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
    experimental: {
      enableNativePlugin: true,
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
      chunkSizeWarningLimit: 5000,
      rollupOptions: {
        treeshake: true,
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
      },
      cors: true,
    },
  }
});

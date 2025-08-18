import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

// https://vitejs.dev/config/
export default defineConfig({
  base: "/modules/contrib/ai_engine/modules/ai_engine_chat/react/static",
  plugins: [react()],
  build: {
    outDir: "static",
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      output: {
        entryFileNames: `assets/[name].js`,
        chunkFileNames: `assets/[name].js`,
        assetFileNames: `assets/[name].[ext]`,
      },
    },
  },
  server: {
    proxy: {
      "/ask": "http://localhost:5000",
      "/chat": "http://localhost:5000",
    },
  },
});

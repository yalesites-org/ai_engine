import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import path from 'path';

const currentDir = path.basename(process.cwd());
const isNamedReact = currentDir === 'react';


let config = {
  plugins: [react()],
  build: {
    outDir: '../static',
    emptyOutDir: true,
    sourcemap: true
  },
  server: {
    proxy: {
      '/ask': 'http://localhost:5000',
      '/chat': 'http://localhost:5000'
    }
  }
};

if (isNamedReact) {
  config = {
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
        '/ask': 'http://localhost:5000',
        '/chat': 'http://localhost:5000'
      }
    }
  }
}

// https://vitejs.dev/config/
export default defineConfig(config)

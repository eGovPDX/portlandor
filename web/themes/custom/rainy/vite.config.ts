import tailwind from "@tailwindcss/vite";
import { defineConfig } from "vite";

export default defineConfig((configEnv) => {
  const entry = {
    index: "src/css/index.css",
  };

  return {
    build: {
      outDir: "dist",
      emptyOutDir: true,
      lib: {
        name: "rainy",
        entry,
        formats: ["es"],
      },
      cssCodeSplit: true,
    },
    css: {
      transformer: "lightningcss",
    },
    plugins: [tailwind()],
  };
});

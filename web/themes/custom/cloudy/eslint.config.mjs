import globals from "globals";
import pluginJs from "@eslint/js";
import eslintConfigPrettier from "eslint-config-prettier";

/** @type {import('eslint').Linter.Config[]} */
export default [
  {
    languageOptions: {
      globals: { ...globals.browser, Drupal: "readonly", once: "readonly", jQuery: "readonly" },
    },
  },
  pluginJs.configs.recommended,
  eslintConfigPrettier,
];

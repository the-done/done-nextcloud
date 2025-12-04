/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
module.exports = {
  ignorePatterns: [
    "src/**/*.d.ts",
    "vendor/**/*",
    "node_modules/**/*",
    "build/**/*",
    "dist/**/*",
    ".git/**/*",
  ],
  globals: {
    Vue: true,
    appName: true,
    appVersion: true,
  },
  extends: ["plugin:vue/vue2-essential" /* "plugin:vue/recommended" */],
  rules: {
    indent: ["warn", 2],
    "linebreak-style": ["warn", "unix"],
    quotes: ["warn", "double", "avoid-escape"],
    semi: ["warn", "always"],
    eqeqeq: ["off"],
    "no-plusplus": ["off"],
    camelcase: ["warn"],
    "no-alert": ["error"],
    // "no-console"      : ["error", { "allow": ["clear", "warn", "error", "trace"] }],
    "max-len": [
      "error",
      {
        code: 120,
        tabWidth: 2,
        ignorePattern: "data:image",
        ignoreRegExpLiterals: true,
      },
    ],
  },
};

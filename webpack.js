const webpackConfig = require("@nextcloud/webpack-vue-config");
const StyleLintPlugin = require("stylelint-webpack-plugin");
const path = require("path");

webpackConfig.entry = {
  main: {
    import: path.join(__dirname, "src/admin", "main.js"),
    filename: "main.js",
  },
};

webpackConfig.plugins.push(
  new StyleLintPlugin({
    files: "src/**/*.{css,scss,vue}",
  })
);

webpackConfig.module.rules.push({
  test: /\.svg$/i,
  type: "asset/source",
});

webpackConfig.resolve = {
  alias: {
    "@": path.resolve(__dirname, "src/"),
  },
};

module.exports = webpackConfig;

const path = require("path");
const ArcGISPlugin = require("@arcgis/webpack-plugin");

module.exports = {
  chainWebpack: config => {
    config.resolve.symlinks(false);
  },
  configureWebpack: {
    entry: {
      widget: [path.join(__dirname, "./src/portland.geofield.widget.js")],
      formatter: [path.join(__dirname, "./src/portland.geofield.formatter.js")]
    },
    devtool: "source-map",
    plugins: [new ArcGISPlugin()],
    // plugins: [
    //   new PurgecssPlugin({
    //     // Specify the locations of any files you want to scan for class names.
    //     paths: glob.sync([
    //       path.join(__dirname, "public/index.html"),
    //       path.join(__dirname, "src/**/*.vue"),
    //       path.join(__dirname, "src/**/*.js")
    //     ]),
    //     extractors: [
    //       {
    //         extractor: TailwindExtractor,

    //         // Specify the file extensions to include when scanning for
    //         // class names.
    //         extensions: ["html", "js", "php", "vue"]
    //       }
    //     ]
    //   })
    // ],
    externals: {
      jquery: "jQuery"
    }
  }
};

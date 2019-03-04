var path = require("path");
var webpack = require("webpack");
const VueLoaderPlugin = require("vue-loader/lib/plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = (env, argv) => ({
  entry: {
    widget: "./src/portland.geofield.widget.js",
    formatter: "./src/portland.geofield.formatter.js"
  },
  output: {
    path: path.resolve(__dirname, "../lib"),
    filename: "js/[name].bundle.js"
  },
  plugins: [
    new VueLoaderPlugin(),
    new MiniCssExtractPlugin({
      filename: "css/[name].bundle.css",
      chunkFilename: "css/[id].bundle.css"
    })
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "src"),
      vue$: "vue/dist/vue.runtime.esm.js"
    },
    extensions: ["*", ".js", ".vue", ".json"]
  },
  performance: {
    hints: false
  },
  devtool: "source-map",
  externals: {
    jquery: "jQuery"
  },
  module: {
    rules: [
      {
        test: /\.css$/,
        use: [
          "vue-style-loader",
          MiniCssExtractPlugin.loader,
          "css-loader",
          {
            loader: "postcss-loader",
            options: {
              sourceMap: true,
              config: {
                path: "./postcss.config.js",
                ctx: {
                  mode: process.env.NODE_ENV
                }
              }
            }
          }
        ]
      },
      {
        test: /\.scss$/,
        use: [
          "vue-style-loader",
          MiniCssExtractPlugin.loader,
          "css-loader",
          {
            loader: "postcss-loader",
            options: {
              sourceMap: true,
              config: {
                path: "./postcss.config.js",
                ctx: {
                  mode: process.env.NODE_ENV
                }
              }
            }
          },
          "sass-loader"
        ]
      },
      {
        test: /\.sass$/,
        use: [
          "vue-style-loader",
          MiniCssExtractPlugin.loader,
          "css-loader",
          {
            loader: "postcss-loader",
            options: {
              sourceMap: true,
              config: {
                path: "./postcss.config.js",
                ctx: {
                  mode: process.env.NODE_ENV
                }
              }
            }
          },
          "sass-loader?indentedSyntax"
        ]
      },
      {
        test: /\.vue$/,
        loader: "vue-loader",
        options: {
          loaders: {
            js: "babel-loader",
            // Since sass-loader (weirdly) has SCSS as its default parse mode, we map
            // the "scss" and "sass" values for the lang attribute to the right configs here.
            // other preprocessors should work out of the box, no loader config like this necessary.
            scss: [
              "vue-style-loader",
              "css-loader",
              {
                loader: "postcss-loader",
                options: {
                  sourceMap: true,
                  config: {
                    path: "./postcss.config.js",
                    ctx: {
                      mode: argv.mode
                    }
                  }
                }
              },
              "sass-loader"
            ],
            sass: [
              "vue-style-loader",
              "css-loader",
              {
                loader: "postcss-loader",
                options: {
                  sourceMap: true,
                  config: {
                    path: "./postcss.config.js",
                    ctx: {
                      mode: process.env.NODE_ENV
                    }
                  }
                }
              },
              "sass-loader?indentedSyntax"
            ]
          }
          // other vue-loader options go here
        }
      },
      {
        test: /\.js$/,
        loader: "babel-loader",
        exclude: /node_modules/
      },
      {
        test: /\.(png|jpg|gif|svg)$/,
        loader: "file-loader",
        options: {
          name: "[name].[ext]?[hash]"
        }
      }
    ]
  }
});

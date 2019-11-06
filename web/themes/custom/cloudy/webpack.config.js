const path = require('path');
const globby = require('globby');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

module.exports = (env, argv) => ({
  entry: {
    main: globby.sync(['./js/**/*.js', './scss/style.scss'])
  },
  devtool: 'source-map',
  mode: process.env.NODE_ENV,
  output: {
    path: path.resolve(__dirname),
    filename: 'dist-cloudy/js/[name].bundle.js'
  },
  watchOptions: {
    ignored: ['images/**/*.*', 'dist-cloudy/**/*.*', 'templates/**/*.*', 'node_modules']
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'dist-cloudy/css/style.bundle.css',
      chunkFilename: 'dist-cloudy/css/[id].bundle.css'
    }),
    // new BrowserSyncPlugin(
    //   // BrowserSync options
    //   {
    //     // browse to https://localhost:3000/ during development
    //     host: 'localhost',
    //     port: 3000,
    //     // proxy the Lando endpoint
    //     // through BrowserSync
    //     proxy: 'https://portlandor.lndo.site/',
    //     // Open the proxied site
    //     open: 'local'
    //   },
    //   // plugin options
    //   {}
    // )
  ],
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.(sa|sc)ss$/,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              sourceMap: true,
              url: false
            }
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: true,
              config: {
                path: './postcss.config.js',
                ctx: {
                  mode: argv.mode
                }
              }
            }
          },
          {
            loader: 'resolve-url-loader'
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: true
            }
          }
        ]
      }
    ]
  },
  performance: {
    assetFilter: function (assetFilename) {
      // only give warnings about size for javascript files
      return assetFilename.endsWith('.js');
    }
  },
  externals: {
    jquery: 'jQuery'
  }
});

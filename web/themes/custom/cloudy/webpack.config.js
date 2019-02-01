const path = require('path');
const globby = require('globby');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = (env, argv) => ({
  entry: {
    main: globby.sync([
      './js/src/**/*.js',
      './scss/bootstrap.scss',
      './scss/style.scss'
    ])
  },
  mode: process.env.NODE_ENV,
  output: {
    path: path.resolve(__dirname),
    filename: 'js/[name].bundle.js'
  },
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
        test: /\.svg$/,
        loader: 'file-loader'
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: [
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
              loader: 'sass-loader',
              options: {
                sourceMap: true
              }
            }
          ]
        })
      }
    ]
  },
  performance: {
    assetFilter: function(assetFilename) {
      // only give warnings about size for javascript files
      return assetFilename.endsWith('.js');
    }
  },
  externals: {
    jquery: 'jQuery'
  },
  plugins: [
    new ExtractTextPlugin({
      filename: 'css/style.bundle.css'
    })
  ]
});

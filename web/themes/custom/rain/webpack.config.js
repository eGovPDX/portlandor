// webpack v4

const path = require('path');
const globby = require('globby');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

module.exports = {
  devtool: 'source-map',
  entry: {
    main: globby.sync(['./source/_patterns/**/*.js', './source/_patterns/style.scss'])
  },
  output: {
    path: path.resolve(__dirname, 'source'),
    filename: 'js/[name].bundle.js'
  },
  module: {
    rules: [
      {
        enforce: 'pre',
        test: /\.scss$/,
        loader: 'import-glob-loader'
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader'
        }
      },
      {
        test: /\.scss$/,
        use: ExtractTextPlugin.extract(
          {
            fallback: 'style-loader',
            use: [
              {
                loader: 'css-loader',
                options: {
                  sourceMap: true,
                }
              },
              {
                loader: 'postcss-loader',
                options: {
                  sourceMap: true,
                  config: {
                    path: './postcss.config.js'
                  }
                }
              },
              {
                loader: 'sass-loader',
                options: {
                  sourceMap: true,
                  includePaths: require('node-normalize-scss').with(['./node_modules'])
                }
              }],
          })
      }
    ]
  },
  plugins: [
    new ExtractTextPlugin(
      {
        filename: 'css/styles.bundle.css'
      }
    )
  ]
};
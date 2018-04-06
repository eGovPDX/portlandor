'use strict';
const path = require('path');
const webpack = require('webpack');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

let config = {
  entry: {
    style: path.join(__dirname, 'scss', 'style.scss'),
  },
  output: {
    path: path.resolve(__dirname, 'css'),
  },
  module: {
    rules: [
      {
        test: /\.(s*)css$/,
        exclude: /node_modules/,
        use: ExtractTextPlugin.extract({
          fallback: 'style-loader',
          use: ['css-loader', 'sass-loader']
        }),
      },
      {
        test: /\.(svg|eot|ttf|woff|woff2)$/,
        use: [
          {
            loader: 'file-loader',
            options: {
              name: '[name].[ext]',
            }
          }
        ]
      }
    ]
  },
  plugins: [
    new ExtractTextPlugin({
      filename: path.join('style.css'),
    })
  ],
  watch: true,
};

module.exports = config;

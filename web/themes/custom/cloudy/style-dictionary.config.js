const { join, resolve, relative } = require('path');
const StyleDictionary = require('style-dictionary');

const tokenSrcDir = join(__dirname, 'src/design-tokens');
const tokenDistDir = join(tokenSrcDir, 'dist/'); //Build path must end in a trailing slash or you will get weird file names
const plDataPath = join(__dirname, 'pattern-lab/_data');

StyleDictionary.registerFormat({
  name: 'pl-demo',
  formatter(dictionary) {
    /**
     * @typedef {Object} DesignToken
     * @prop {string} value
     * @prop {string} name
     * @prop {string} [comment]
     * @prop {{ category: string, type: string, item: string }} attributes
     */

    /** @type {DesignToken[]} */
    const tokens = dictionary.allProperties;

    const colors = tokens.filter(
      token => token.attributes.category === 'color',
    );

    const designTokens = {
      colors: {},
    };

    colors.forEach(color => {
      const { category, type, item } = color.attributes;
      designTokens.colors[type] = designTokens.colors[type] || {};
      designTokens.colors[type][item] = designTokens.colors[type][item] || [];
      designTokens.colors[type][item].push(color);
    });

    return JSON.stringify({ designTokens }, null, '  ');
  },
});

module.exports = {
  source: [join(tokenSrcDir, '*.{json,js}')],
  platforms: {
    css: {
      prefix: 'cloudy',
      transforms: ['attribute/cti', 'name/cti/kebab', 'color/css'],
      buildPath: tokenDistDir,
      files: [
        {
          destination: '_design-tokens.scss',
          format: 'scss/map-deep',
          mapName: 'design-tokens',
        },
        {
          destination: join(
            relative(tokenDistDir, plDataPath),
            'design-tokens.json',
          ),
          format: 'pl-demo',
        },
      ],
    },
    js: {
      transforms: ['name/cti/camel'],
      buildPath: tokenDistDir,
      prefix: 'cloudy',
      files: [
        {
          destination: 'design-tokens.js',
          format: 'javascript/es6',
        },
      ],
    },
  },
};

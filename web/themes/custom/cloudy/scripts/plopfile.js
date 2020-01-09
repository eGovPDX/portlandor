const { relative, join } = require('path');

// Case Modifiers
// camelCase: changeFormatToThis
// snakeCase: change_format_to_this
// dashCase/kebabCase: change-format-to-this
// dotCase: change.format.to.this
// pathCase: change/format/to/this
// properCase/pascalCase: ChangeFormatToThis
// lowerCase: change format to this
// sentenceCase: Change format to this,
// constantCase: CHANGE_FORMAT_TO_THIS
// titleCase: Change Format To This

/**
 * @param {import('node-plop').NodePlopAPI} plop
 * @link https://plopjs.com/documentation/
 */
function go(plop) {
  plop.setGenerator('pattern', {
    description: 'Create a new pattern along with demo',
    // https://github.com/SBoudrias/Inquirer.js/#question
    prompts: [
      {
        name: 'name',
      },
      {
        name: 'type',
        type: 'list',
        choices: ['elements', 'components', 'layouts'],
        default: 'components',
      },
      {
        name: 'useJs',
        type: 'confirm',
        message: 'Use JavaScript?',
        default: false,
      },
    ],
    actions: (answers) => {
      // console.log({ answers });
      let plFolder;
      switch (answers.type) {
        case 'elements':
          plFolder = '00-elements';
          break;
        case 'components':
          plFolder = '01-components';
          break;
        case 'layouts':
          plFolder = '02-layouts';
          break;
      }

      return [
        `
To edit this script open ${relative(process.cwd(), __filename)}
To edit the source templates for the files created, visit these directories:
${relative(process.cwd(), join(__dirname, 'new-demo'))}
${relative(process.cwd(), join(__dirname, 'new-src'))}
`.trim(),
        {
          type: 'addMany',
          destination: '../src/{{ dashCase type }}/{{ dashCase name }}',
          templateFiles: [
            './new-src/*',
            `${answers.useJs ? '' : '!'}./new-src/*.js.hbs`,
          ],
          stripExtensions: ['hbs'],
          verbose: true,
          skipIfExists: true,
        },
        {
          type: 'append',
          path: '../src/css/_{{ dashCase type }}.scss',
          template: '@import \'../{{ dashCase type }}/{{ dashCase name }}/{{ dashCase name }}\';\n',
          separator: '',
          verbose: true,
        },
        answers.useJs
          ? {
            type: 'append',
            path: '../src/cloudy.js',
            template: 'import \'./{{ dashCase type }}/{{ dashCase name }}/{{ dashCase name }}\';\n',
            separator: '',
            verbose: true,
          }
        : null,
        {
          type: 'addMany',
          destination: `../pattern-lab/_patterns/${plFolder}/{{ dashCase name }}`,
          templateFiles: './new-demo/*',
          stripExtensions: ['hbs'],
          verbose: true,
          skipIfExists: false,
        },
      ].filter(Boolean); // removes any `null`
    },

  });

}

module.exports = go;

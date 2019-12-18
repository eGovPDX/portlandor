#! /usr/bin/env node
const { join } = require('path');
const { writeFileSync, readdirSync, readFileSync } = require('fs');
const yaml = require('js-yaml');

const svgDir = join(__dirname, '../src/elements/icon/svgs');
const svgDataPath = join(__dirname, '../pattern-lab/_patterns/00-elements/icon/15-all-icons.json');
const faDataPath = join(__dirname, '../pattern-lab/_patterns/00-elements/icon/60-all-font-awesome-icons.json');

async function go() {
  console.log('Start: turning svg folder into JSON data for Pattern Lab...');

  const faIconDataPath = require.resolve('@fortawesome/fontawesome-free/metadata/icons.yml');
  const faIconsDataString = readFileSync(faIconDataPath);
  const faIconsData = yaml.safeLoad(faIconsDataString);
  const faIcons = [];
  Object.entries(faIconsData).forEach(([iconName, iconData]) => {
    if (iconData.styles.includes('solid')) {
      faIcons.push(iconName);
    }
  });

  const iconNames = readdirSync(svgDir)
    .filter(file => file.endsWith('svg'))
    .map(file => file.replace('.svg', ''));

  writeFileSync(svgDataPath, JSON.stringify({ iconNames }));
  writeFileSync(faDataPath, JSON.stringify({ faIcons }));
  console.log('Done');
}

try {
  go();
} catch (e) {
  console.error(e);
  process.exit(1);
}

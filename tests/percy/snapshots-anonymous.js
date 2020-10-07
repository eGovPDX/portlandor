const PercyScript = require('@percy/script');
const { expect } = require('chai');

const SITE_NAME = process.env.SITE_NAME;
const SUPERADMIN_LOGIN = process.env.SUPERADMIN_LOGIN;
const ALLY_LOGIN = process.env.ALLY_LOGIN;
const MARTY_LOGIN = process.env.MARTY_LOGIN;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const LOGOUT = `${HOME_PAGE}/user/logout`;
const MY_GROUPS = `${HOME_PAGE}/my-groups`;
const MY_CONTENT = `${HOME_PAGE}/my-content`;

// A script to navigate our app and take snapshots with Percy.
PercyScript.run(async (page, percySnapshot) => {
  await page.goto(HOME_PAGE);
  await page.waitFor('nav');
  let text_content = await page.evaluate(() => document.querySelector('nav').textContent);
  expect(text_content).to.have.string('Services');

  text_content = await page.evaluate(() => document.querySelector('div.content h2.h6').textContent);
  expect(text_content).to.equal('City of Portland, Oregon');

  await percySnapshot('Anonymous - Home page');

  // Search page
  await page.goto(`${HOME_PAGE}/search?keys=tax`);
  await percySnapshot('Anonymous - Search "tax"');
});

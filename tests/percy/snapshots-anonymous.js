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

  // Home
  await percySnapshot('Anonymous - Home page');

  // Search page
  await page.goto(`${HOME_PAGE}/search?keys=tax`);
  await percySnapshot('Anonymous - Search "tax"');

  // 404
  await page.goto(`${HOME_PAGE}/search?keys=powr-test`);
  await percySnapshot('Anonymous - 404 "powr-test"');

  // Elected Officials
  await page.goto(`${HOME_PAGE}/wheeler`);
  await percySnapshot('Anonymous - Elected "Mayor"');

  // Advisory Group
  await page.goto(`${HOME_PAGE}/omf/toc`);
  await percySnapshot('Anonymous - Advisory "Technology Oversight"');

  // Program
  await page.goto(`${HOME_PAGE}/help`);
  await percySnapshot('Anonymous - Program "POWR Help"');

  // Bureau
  await page.goto(`${HOME_PAGE}/omf`);
  await percySnapshot('Anonymous - Bureau "Mangagement and Finance"');

  // Project
  await page.goto(`${HOME_PAGE}/powr`);
  await percySnapshot('Anonymous - Project "POWR"');

  // Page
  await page.goto(`${HOME_PAGE}/powr/about-powr-project`);
  await percySnapshot('Anonymous - Page "POWR About"');

  // Service
  await page.goto(`${HOME_PAGE}/police/services/police-report-or-record-online-request`);
  await percySnapshot('Anonymous - Service "Police Report or Record"');

  // News
  await page.goto(`${HOME_PAGE}/powr/news/2019/11/4/implementation-plan-redirect-portlandoregongov-portlandgov`);
  await percySnapshot('Anonymous - News "Home Page Redirect"');

  // Event
  await page.goto(`${HOME_PAGE}/powr/events/2020/4/28/powr-office-hours`);
  await percySnapshot('Anonymous - Event "Office Hours"');

}, 

{
  // Ignore HTTPS errors in Lando
  ignoreHTTPSErrors: (typeof process.env.LANDO_CA_KEY !== 'undefined')
});

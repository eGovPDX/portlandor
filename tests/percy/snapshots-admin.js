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
  await page.setDefaultNavigationTimeout(0);
  await page.goto(SUPERADMIN_LOGIN);
  await percySnapshot('Site Admin - Account profile');

  let text_content = '';
  await page.goto(`${HOME_PAGE}/admin/reports/status`);
  await page.waitFor('.system-status-report');
  text_content = await page.evaluate(() => document.querySelector('.system-status-report').textContent);
  expect(text_content).to.not.have.string('Errors found');
  expect(text_content).to.not.have.string('The following changes were detected in the entity type and field definitions.');
  
  await page.goto(`${HOME_PAGE}/admin/config/development/configuration`);
  await page.waitFor('.region-content'); 
  text_content = await page.evaluate(() => document.querySelector('.region-content').textContent);
  expect(text_content).to.have.string('There are no configuration changes to import.');
 
  // Add a new group
  await page.goto(`${HOME_PAGE}/group/add/bureau_office`);
  await page.waitFor('.page-title'); 
  text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
  expect(text_content).to.have.string('Add Bureau/office');

  await page.type('#edit-label-0-value', 'Percy Test Group');
  await page.type('#edit-field-official-organization-name-0-value', 'Official name of Percy test group');
  await page.select('#edit-field-migration-status', 'Complete')
  await page.type('#edit-field-summary-0-value', 'This is a test summary for the Percy Test group');
  await page.type('#edit-field-group-path-0-value', 'percy-test-group');

  let selector = 'input#edit-submit';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();

  await percySnapshot('Site Admin - Group created');

  text_content = await page.evaluate(() => document.querySelector('h1.page-title').textContent);
  expect(text_content).to.have.string('Percy Test Group');

  // Add member
  await page.goto(`${HOME_PAGE}/percy-test-group/members`);
  await page.waitFor('.button-action'); 
  text_content = await page.evaluate(() => document.querySelector('.button-action').textContent);
  expect(text_content).to.equal('Add member');
  // await page.click('.button-action');
  selector = '.button-action';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();

  text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
  expect(text_content).to.have.string('Add Bureau/office: Group membership');
  await page.type('#edit-entity-id-0-target-id', 'Ally Admin (62)');
  selector = '#edit-group-roles-bureau-office-admin';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);

  await page.keyboard.press('Enter');
  await page.waitForNavigation();

  text_content = await page.evaluate(() => document.querySelector('td.views-field-name').textContent);
  expect(text_content).to.have.string('Ally Admin');

  // Delete the new group
  await page.goto(`${HOME_PAGE}/percy-test-group/delete`);
  await page.waitFor('input#edit-submit'); 
  // await page.$eval('#edit-submit', elem => elem.click());
  selector = 'input#edit-submit';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();
  
  text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
  expect(text_content).to.have.string('has been deleted');
},
{
  // Ignore HTTPS errors in Lando
  ignoreHTTPSErrors: (typeof process.env.LANDO_CA_KEY !== 'undefined')
});

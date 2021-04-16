const percySnapshot = require('@percy/puppeteer')
const puppeteer = require('puppeteer')
const util = require('util');
const exec = util.promisify(require('child_process').exec);

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const timeout = 30000;

var browser, page, login_url;
beforeAll(async () => {
  browser = await puppeteer.launch({
    ignoreHTTPSErrors: true,
    args: ['--no-sandbox'],
    executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    headless: false,
    // slowMo: 100,
    defaultViewport: null,
  })
  page = await browser.newPage()
  await page.setDefaultTimeout(30000)

  if (process.env.CIRCLECI) {
    // On CI, the CI script will call terminus to retrieve login URL
    login_url = process.env.SUPERADMIN_LOGIN;
  }
  else {
    var drush_uli_result = await exec('lando drush uli');
    login_url = drush_uli_result.stdout.replace('http://default', 'https://portlandor.lndo.site');
    // Log in once for all tests to save time
    await page.goto(login_url);
  }
}, timeout)

afterAll(async () => {
  await browser.close()
}, timeout)

describe('SuperAdmin user test', () => {
  it(
    'The site is in good status',
    async () => {
      // Capture user profile page
      // await percySnapshot(page, 'Site Admin - Account profile');
      let text_content = '';
      await page.goto(`${HOME_PAGE}/admin/reports/status`);
      await page.waitForSelector('.system-status-report');
      text_content = await page.evaluate(() => document.querySelector('.system-status-report').textContent);
      // Negative test
      expect(text_content).toEqual(expect.not.stringContaining('Errors found'));
      expect(text_content).toEqual(expect.not.stringContaining('The following changes were detected in the entity type and field definitions.'));
    },
    timeout
  );

  // it(
  //   'All configurations are imported',
  //   async function () {
  //     try {
  //       let text_content = '';
  //       await page.goto(`${HOME_PAGE}/admin/config/development/configuration`);
  //       await page.waitFor('.region-content');
  //       text_content = await page.evaluate(() => document.querySelector('.region-content').textContent);
  //       expect(text_content).toEqual(expect.stringContaining('There are no configuration changes to import.'));
  //     } catch (e) {
  //       // Capture the screenshot when test fails and re-throw the exception
  //       await page.screenshot({
  //         path: "./config-import-error.jpg",
  //         type: "jpeg",
  //         fullPage: true
  //       });
  //       throw e;
  //     }
  //   },
  //   timeout
  // );


  it(
    'superAdmin manages group',
    async function () {
      try {
        let text_content = '', selector='';

        // If a previous test failed without deleting the test group, delete it first
        await page.goto(`${HOME_PAGE}/percy-test-group/delete`);
        // await page.$eval('#edit-submit', elem => elem.click());
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        if(text_content.indexOf('Percy Test Group') > 0) {
          selector = 'input#edit-submit';
          await page.evaluate((selector) => document.querySelector(selector).click(), selector);
          await page.waitForNavigation();
        }

        // Create the test group
        await page.goto(`${HOME_PAGE}/group/add/bureau_office`);
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Add Bureau/office'));
        await page.type('#edit-label-0-value', 'Percy Test Group');
        await page.type('#edit-field-official-organization-name-0-value', 'Official name of Percy test group');
        await page.select('#edit-field-migration-status', 'Complete')
        await page.type('#edit-field-summary-0-value', 'This is a test summary for the Percy Test group');
        await page.type('#edit-field-group-path-0-value', 'percy-test-group');

        selector = 'input#edit-submit';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        await percySnapshot(page, 'Site Admin - Group created');
        text_content = await page.evaluate(() => document.querySelector('h1.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Percy Test Group'));

        // Add Ally Admin as a group admin to the test group
        await page.goto(`${HOME_PAGE}/percy-test-group/members`);
        text_content = await page.evaluate(() => document.querySelector('.button-action').textContent);
        expect(text_content).toEqual('Add member');
        // await page.click('.button-action');
        selector = '.button-action';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Add Bureau/office: Group membership'));
        await page.type('#edit-entity-id-0-target-id', 'Ally Admin (62)');
        selector = '#edit-group-roles-bureau-office-admin';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);

        await page.keyboard.press('Enter');
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('td.views-field-name').textContent);
        expect(text_content).toEqual(expect.stringContaining('Ally Admin'));

        // Delete the new group
        await page.goto(`${HOME_PAGE}/percy-test-group/delete`);
        // await page.$eval('#edit-submit', elem => elem.click());
        selector = 'input#edit-submit';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
        expect(text_content).toEqual(expect.stringContaining('has been deleted'));
      } catch (e) {
        // Capture the screenshot when test fails and re-throw the exception
        await page.screenshot({
          path: "./manage-group-error.jpg",
          type: "jpeg",
          fullPage: true
        });
        throw e;
      }
    },
    timeout * 10
  );
});

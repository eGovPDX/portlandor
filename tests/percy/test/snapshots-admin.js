const percySnapshot = require('@percy/puppeteer')
const puppeteer = require('puppeteer')
const util = require('util');
const exec = util.promisify(require('child_process').exec);

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;
const timeout = 60000 * 2;

var browser, page, login_url;
beforeAll(async () => {
  browser = await puppeteer.launch({
    ignoreHTTPSErrors: true,
    args: ['--no-sandbox'],
    // Uncomment these lines to watch test locally
    // headless: false,
    // slowMo: 100,
    // defaultViewport: null,
  })
  page = await browser.newPage()
  await page.setDefaultTimeout(timeout)

  if (process.env.CIRCLECI) {
    // On CI, the CI script will call terminus to retrieve login URL
    login_url = process.env.SUPERADMIN_LOGIN;
    await page.goto(login_url);
    await page.screenshot({
      path: `${ARTIFACTS_FOLDER}admin-profile.jpg`,
      type: "jpeg",
      fullPage: true
    });
  }
  else {
    var drush_uli_result = await exec('lando drush uli');
    login_url = drush_uli_result.stdout.replace('http://default', 'https://portlandor.lndo.site');
    // Log in once for all tests to save time
    await page.goto(login_url);
  }

  // Print browser version
  // await page.browser().version().then(function(version) {
  //   console.log(version);
  //   });
  // console.log(browser.process().spawnfile);

}, timeout)

afterAll(async () => {
  await browser.close()
}, timeout * 2)

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

  it(
    'All configurations are imported',
    async function () {
      try {
        let text_content = '';
        await page.goto(`${HOME_PAGE}/admin/config/development/configuration`);
        await page.waitFor('.region-content');
        text_content = await page.evaluate(() => document.querySelector('.region-content').textContent);
        expect(text_content).toEqual(expect.stringContaining('There are no configuration changes to import.'));
      } catch (e) {
        // Capture the screenshot when test fails and re-throw the exception
        await page.screenshot({
          path: `${ARTIFACTS_FOLDER}config-import-error.jpg`,
          type: "jpeg",
          fullPage: true
        });
        throw e;
      }
    },
    timeout
  );

});

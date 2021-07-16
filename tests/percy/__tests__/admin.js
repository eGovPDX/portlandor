const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
  defaultViewport: null,
};

describe('SuperAdmin user test', () => {
  var browser, page, login_url;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION)
    page = await browser.newPage()
    await page.setDefaultTimeout(30000)

    if (process.env.CIRCLECI) {
      // On CI, the CI script will call terminus to retrieve login URL
      login_url = process.env.SUPERADMIN_LOGIN;
      await page.goto(login_url);
    }
    else {
      var drush_uli_result = fs.readFileSync("superAdmin_uli.log").toString();
      login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
      // Log in once for all tests to save time
      await page.goto(login_url);
    }
  })

  afterAll(async () => {
    await browser.close()
  })
  
  it(
    'The site is in good status',
    async () => {
      try {

        let text_content = '';
        await page.goto(`${HOME_PAGE}/admin/reports/status`);
        await page.waitForSelector('.system-status-report');
        text_content = await page.evaluate(() => document.querySelector('.system-status-report').textContent);
        // Negative test
        expect(text_content).toEqual(expect.not.stringContaining('Errors found'));
        expect(text_content).toEqual(expect.not.stringContaining('The following changes were detected in the entity type and field definitions.'));
      } catch (e) {
        // Capture the screenshot when test fails and re-throw the exception
        await page.screenshot({
          path: `${ARTIFACTS_FOLDER}site-status-error.jpg`,
          type: "jpeg",
          fullPage: true
        });
        throw e;
      }
    }
  );

  it(
    'All configurations are imported',
    async function () {
      try {
        let text_content = '';
        await page.setDefaultNavigationTimeout(60000);
        await page.goto(`${HOME_PAGE}/admin/config/development/configuration`);
        await page.waitForSelector('.region-content');
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
    60000 // 60s timeout
  );
});

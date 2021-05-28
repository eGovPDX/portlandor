const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (process.env.CIRCLECI) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (process.env.CIRCLECI) ? `/home/circleci/artifacts/` : `./`;

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

describe('Full regression test suite for Admin', () => {
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
          path: `${ARTIFACTS_FOLDER}full-site-status-error.jpg`,
          type: "jpeg",
          fullPage: true
        });
        throw e;
      }
    }
  );

});

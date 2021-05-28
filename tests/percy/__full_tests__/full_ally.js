const puppeteer = require('puppeteer')
var fs = require('fs');
const { fail } = require('assert');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;
let text_content = '', selector = '';

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

describe("Full regression test suite for Ally", () => {
  var browser, page, login_url;

  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION);
    page = await browser.newPage();
    await page.setDefaultTimeout(30000)

    if (process.env.CIRCLECI) {
      // On CI, the CI script will call terminus to retrieve login URL
      login_url = process.env.ALLY_LOGIN;
      await page.goto(login_url);
    }
    else {
      var drush_uli_result = fs.readFileSync("ally_uli.log").toString();
      login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
      // Log in once for all tests to save time
      await page.goto(login_url);
    }
  })

  afterAll(async () => {
    await browser.close();
  })

  it('Ally can view My Groups', async function () {
    try {
      let text_content = '';
      await page.goto(`${HOME_PAGE}/my-groups`);
      text_content = await page.evaluate(() => document.querySelector('div.view-my-groups td.views-field-label-1').textContent);
      expect(text_content).toEqual(expect.stringContaining('Portland Oregon Website Replacement'));
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-my-groups-error.jpg`,
        type: "jpeg",
        fullPage: true
      });
      throw e;
    }
  });
});

const percySnapshot = require('@percy/puppeteer')
const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;
const timeout = 60000 * 2;

var BROWSER_OPTION = { 
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

if (!SITE_NAME) {
  BROWSER_OPTION.executablePath = "/usr/bin/google-chrome";
}
var browser, page, login_url;
beforeAll(async () => {
  browser = await puppeteer.launch(BROWSER_OPTION)
  page = await browser.newPage()
  await page.setDefaultTimeout(timeout)

  if(process.env.CIRCLECI) {
    // On CI, the CI script will call terminus to retrieve login URL
    login_url = process.env.ALLY_LOGIN;
    await page.goto(login_url);
    await percySnapshot(page, 'Ally Admin - Account profile');
  }
  else {
    var drush_uli_result = fs.readFileSync("ally_uli.log").toString();
    // expect(drush_uli_result.stdout).toEqual(expect.stringContaining('http'));
    login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
    // Log in once for all tests to save time
    await page.goto(login_url);
  }
}, timeout)

describe('Ally Admin user test', () => {
  it('The site is in good status', async function () {
    let text_content = '';
    await page.goto(`${HOME_PAGE}/my-groups`);
    await percySnapshot(page, 'Ally - My groups');
  }, timeout)
});

afterAll(async () => {
  await browser.close()
}, timeout)

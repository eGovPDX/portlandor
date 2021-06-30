const { time } = require('console');
const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (process.env.CIRCLECI) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (process.env.CIRCLECI) ? `/home/circleci/artifacts/` : `./`;
let text_content = '', selector = '';

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
  defaultViewport: null,
};

describe('Full regression test suite for Marty', () => {
  var browser, page, login_url;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION)
    page = await browser.newPage();
    await page.setDefaultTimeout(30000);

    var drush_uli_result;
    if (process.env.CIRCLECI) {
      // On CI, the CI script will call terminus to retrieve login URL
      login_url = process.env.MARTY_LOGIN;
      await page.goto(login_url);
    }
    else {
      var drush_uli_result = fs.readFileSync("marty_uli.log").toString();
      // expect(drush_uli_result.stdout).toEqual(expect.stringContaining('http'));
      login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
      // Log in once for all tests to save time
      await page.goto(login_url);
    }
  })

  afterAll(async () => {
    await browser.close()
  })

  it('Marty can create and edit a page', async function () {
    try {
      // Add page content
      await page.goto(`${HOME_PAGE}/powr`);
      text_content = await page.evaluate(() => document.querySelector('ul.primary').textContent);
      expect(text_content).toEqual(expect.stringContaining('+ Add Content'));
      expect(text_content).toEqual(expect.stringContaining('+ Add Media'));
      await page.goto(`${HOME_PAGE}/powr/node/create`);
      text_content = await page.evaluate(() => document.querySelector('div.region-content dl').textContent);
      expect(text_content).toEqual(expect.stringContaining('Add Page'));

      await page.goto(`${HOME_PAGE}/powr/content/create/group_node:page`, { waitUntil: 'networkidle2' });
      const ckeditor = await page.waitForSelector('iframe');
      text_content = await page.evaluate(() => document.querySelector('#node-page-form').textContent);
      expect(text_content).toEqual(expect.stringContaining('Title'));
      expect(text_content).toEqual(expect.stringContaining('Page type'));
      expect(text_content).toEqual(expect.stringContaining('Summary'));
      expect(text_content).toEqual(expect.stringContaining('Body content'));
      expect(text_content).toEqual(expect.stringContaining('Legacy path'));

      await page.type('#edit-title-0-value', 'Test page');
      await page.type('#edit-field-summary-0-value', 'Summary for the test page');
      await page.type('#edit-field-menu-link-text-0-value', 'Test page');
      await ckeditor.type('body p', 'Body content for the test page', { delay: 100 });
      await page.type('#edit-revision-log-0-value', 'Test revision message');

      // Click submit button and wait for page load
      selector = 'input#edit-submit';
      await page.evaluate((selector) => document.querySelector(selector).click(), selector);
      await page.waitForNavigation();
      text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
      expect(text_content).toEqual(expect.stringContaining('has been created'));

      // Edit the newly created page
      await page.goto(`${HOME_PAGE}/powr/test-page/edit`);
      text_content = await page.evaluate(() => document.querySelector('div.form-item--title-0-value').textContent);
      expect(text_content).toEqual(expect.stringContaining('Title'));

      // Delete the page
      await page.goto(`${HOME_PAGE}/powr/test-page/delete`);
      text_content = await page.evaluate(() => document.querySelector('form.node-page-delete-form').textContent);
      expect(text_content).toEqual(expect.stringContaining('This action cannot be undone'));

      selector = 'input#edit-submit';
      await page.evaluate((selector) => document.querySelector(selector).click(), selector);
      await page.waitForNavigation();

      // Verify deletion message
      text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
      expect(text_content).toEqual(expect.stringContaining('has been deleted'));

    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}full-marty-edit-page-error.jpg`,
        type: "jpeg",
        fullPage: true
      });
      throw e;
    }
  });

});

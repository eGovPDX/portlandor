const puppeteer = require('puppeteer')
var fs = require('fs');
const util = require('../lib/util')

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (process.env.CIRCLECI) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (process.env.CIRCLECI) ? `/home/circleci/artifacts/` : `./`;

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

describe('Full regression test suite for Admin', () => {
  var browser, page, login_url, link;
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
        let text_content = '', selector = '';

        // If a previous test failed without deleting the test group, delete it first
        await page.goto(`${HOME_PAGE}/full-regression-test-group/delete`);
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        if(text_content.indexOf('Full Regression Test Group') > 0) {
          selector = 'input#edit-submit';
          await page.focus(selector)
          await page.evaluate((selector) => document.querySelector(selector).click(), selector);
          await page.waitForNavigation();
        }
        
        // Create a new group
        await page.goto(`${HOME_PAGE}/group/add/bureau_office`);
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Add Bureau/office'));
        await page.type('#edit-label-0-value', 'Full Regression Test Group');
        await page.type('#edit-field-official-organization-name-0-value', 'Official name of Full Regression test group');
        await page.select('#edit-field-migration-status', 'Complete')
        await page.type('#edit-field-summary-0-value', 'This is a test summary for the Full Regression Test group');
        // Must expand the admin fields group in order to input Group Path
        await page.click('details#edit-group-administrative-fields');
        await page.type('#edit-field-group-path-0-value', 'full-regression-test-group');

        selector = '#edit-submit';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('h1.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Full Regression Test Group'));

        // Add Ally to the new group as the group admin
        await page.goto(`${HOME_PAGE}/full-regression-test-group/content/add/group_membership?destination=/full-regression-test-group/members`);
        text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
        expect(text_content).toEqual(expect.stringContaining('Add Bureau/office: Group membership'));
        await page.type('#edit-entity-id-0-target-id', 'Ally Admin (62)');
        selector = '#edit-group-roles-bureau-office-admin';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        // Submit form
        await page.keyboard.press('Enter');
        await page.waitForNavigation();
        text_content = await page.evaluate(() => document.querySelector('td.views-field-name').textContent);
        expect(text_content).toEqual(expect.stringContaining('Ally Admin'));

        // Masquerade as Ally to create content
        await util.masqueradeAs('ally.admin@portlandoregon.gov', page, HOME_PAGE)
        text_content = await page.evaluate(() => document.querySelector('#toolbar-item-user').textContent);
        expect(text_content).toEqual(expect.stringMatching('Ally Admin'));

        //TODO: create content in the new group

        await util.unmasquerade(page, HOME_PAGE);
        text_content = await page.evaluate(() => document.querySelector('#toolbar-item-user').textContent);
        expect(text_content).toEqual(expect.stringMatching('superAdmin'));

        // Delete the new group
        await page.goto(`${HOME_PAGE}/full-regression-test-group/delete`);
        selector = 'input#edit-submit';
        await page.evaluate((selector) => document.querySelector(selector).click(), selector);
        await page.waitForNavigation();

        text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
        expect(text_content).toEqual(expect.stringContaining('has been deleted'));
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

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
  defaultViewport: null,
};

describe('Ally Admin user test', () => {
  var browser, page, login_url;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION)
    page = await browser.newPage()
    await page.setDefaultTimeout(30000)

    // Print browser version
    // await page.browser().version().then(function (version) {
    //   console.log(version);
    // });
    // console.log(browser.process().spawnfile);

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
    await browser.close()
  })

  it('Ally can view My Groups', async function () {
    try {
      let text_content = '';
      await page.goto(`${HOME_PAGE}/my-groups`);
      text_content = await page.evaluate(() => document.querySelector('div.view-my-groups table').textContent);
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

  it('Ally can manage POWR', async function () {
    try {
      await page.goto(`${HOME_PAGE}/powr/content/add/group_membership?destination=/powr/members`);
      await page.type('#edit-entity-id-0-target-id', 'Oliver Outsider');
      await page.click('#edit-group-roles-project-editor');
      selector = 'input#edit-submit';
      await page.evaluate((selector) => document.querySelector(selector).click(), selector);
      await page.waitForNavigation();

      // Verify Oliver is listed
      let member_link = await page.$('div.view-group-members a[href="/oliver-outsider"]');
      if (member_link != null) {
        expect(await member_link.evaluate(node => node.textContent)).toEqual(expect.stringContaining('Oliver Outsider'));
      }
      else {
        fail('Cannot find Oliver Outsider in the group members table.');
      }

      // Find the Remove link and click it
      let remove_link = await page.evaluate(() => document.querySelector('div.view-group-members a[href="/oliver-outsider"]').parentNode.parentNode.querySelector('td.views-field-delete-group-content a').getAttribute('href'));
      if (remove_link == '') {
        fail('Cannot find the link to delete Oliver Outsider from POWR.');
      }
      await page.goto(`${HOME_PAGE}${remove_link}`);

      text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
      expect(text_content).toEqual(expect.stringContaining('Are you sure you want to delete'));
      selector = 'input#edit-submit';
      await page.evaluate((selector) => document.querySelector(selector).click(), selector);
      await page.waitForNavigation();

      // Verify Oliver is not listed
      text_content = await page.evaluate(() => document.querySelector('div.view-group-members td.views-field-name').textContent);
      expect(text_content).toEqual(expect.not.stringContaining('Oliver Outsider'));
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-manage-group-error.jpg`,
        type: "jpeg",
        fullPage: true
      });
      throw e;
    }
  })
});

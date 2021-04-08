const percySnapshot = require('@percy/puppeteer')
const puppeteer = require('puppeteer')
const assert = require('assert');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';

var browser, page;
before(async () => {
  browser = await puppeteer.launch({
    ignoreHTTPSErrors: true,
    args: ['--no-sandbox'],
  })
  page = await browser.newPage()
  await page.setDefaultTimeout(30000);
})

describe('Anonymous user test', () => {
  it('has correct text on home page', async function () {
    try {
      await page.goto(HOME_PAGE);
      await page.waitForSelector('nav');

      // Verify the home page has text "Services"
      let text_content = await page.evaluate(() => document.querySelector('nav').textContent);
      assert(text_content.includes('asdfServices'), 'Cannot find "Services" on Home page')

      // Verify the home page has text "Services"
      text_content = await page.evaluate(() => document.querySelector('div.content h2.h6').textContent);
      assert(text_content === 'City of Portland, Oregon', 'Cannot find "City of Portland, Oregon" on Home page')

      await percySnapshot(page, 'Anonymous - Home page');

      // Search page
      await page.goto(`${HOME_PAGE}/search?keys=tax`);
      await percySnapshot(page, 'Anonymous - Search "tax"');

      // 404
      await page.goto(`${HOME_PAGE}/search?keys=powr-test`);
      await percySnapshot(page, 'Anonymous - 404 "powr-test"');

      // Elected Officials
      await page.goto(`${HOME_PAGE}/wheeler`);
      await percySnapshot(page, 'Anonymous - Elected "Mayor"');

      // Advisory Group
      await page.goto(`${HOME_PAGE}/omf/toc`);
      await percySnapshot(page, 'Anonymous - Advisory "Technology Oversight"');

      // Program
      await page.goto(`${HOME_PAGE}/help`);
      await percySnapshot(page, 'Anonymous - Program "POWR Help"');

      // Bureau
      await page.goto(`${HOME_PAGE}/omf`);
      await percySnapshot(page, 'Anonymous - Bureau "Mangagement and Finance"');

      // Project
      await page.goto(`${HOME_PAGE}/powr`);
      await percySnapshot(page, 'Anonymous - Project "POWR"');

      // Service
      await page.goto(`${HOME_PAGE}/police/services/police-report-or-record-online-request`);
      await percySnapshot(page, 'Anonymous - Service "Police Report or Record"');
    } catch (e) {
      if(process.env.CIRCLECI) {
        screenshot_path = "/var/www/html/artifacts/failedtests";
        await page.screenshot({
          path: screenshot_path + "/screenshot_anonymous_test_failure.jpg",
          type: "jpeg",
          fullPage: true
        });
      }
      throw e; // re-throw the exception
    }
  })
});

after(async () => {
  await browser.close()
})

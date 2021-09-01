const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
  defaultViewport: null,
};

describe("Homepage", () => {
  let browser, page;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION);
    page = await browser.newPage();

    // Print browser version
    // await page.browser().version().then(function (version) {
    //   console.log(version);
    // });
    // console.log(browser.process().spawnfile);
  })

  afterAll(async () => {
    await browser.close();
  })

  it(
    "h1 text",
    async () => {
      await page.goto(HOME_PAGE);
      // Remove sitewide alert
      await page.evaluate(() =>
        document
          .querySelector('#block-cloudy-views-block-alerts-block-1')
          .remove()
      );
      // Gets page title
      const title = await page.evaluate(
        () => document.querySelector("h1").textContent
      );
      // Compares it with the intended behavior
      expect(title).toBe("Welcome to Portland, Oregon");
      await percySnapshot(page, "Anonymous - Home page");
    }
  );

  it(
    "Menu open",
    async () => {
      await page.goto(HOME_PAGE, { waitUntil: "load" });
      await page.click("button.cloudy-header__toggle--menu");
      await page.waitForSelector(".cloudy-header__menu-wrapper.show");
      await percySnapshot(page, "Anonymous - Home page Menu");
    }
  );

  it(
    "Search Auto-complete powr",
    async () => {
      await page.goto(HOME_PAGE, { waitUntil: "load" });
      await page.type("#edit-keys", "powr");
      await page.waitForSelector("#ui-id-1", { visible: true });
      await percySnapshot(page, "Anonymous - Home Autocomplete");
    }
  );

  it(
    "Take snapshots of Group home pages",
    async () => {
      await page.goto(`${HOME_PAGE}/omf`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Bureau \"Mangagement and Finance\"");
      await page.goto(`${HOME_PAGE}/powr`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Project \"POWR\"");
      await page.goto(`${HOME_PAGE}/help`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Program \"POWR Help\"");
      await page.goto(`${HOME_PAGE}/omf/toc`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Advisory \"Technology Oversight\"");
      await page.goto(`${HOME_PAGE}/mayor`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Elected \"Mayor\"");
    }
  );

  it(
    "Take snapshots of search results and content pages",
    async () => {
      await page.goto(`${HOME_PAGE}/search?keys=tax&op=`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Search \"tax\"");
      await page.goto(`${HOME_PAGE}/search?keys=powr-test&op=`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - 404 \"powr-test\"");
      await page.goto(`${HOME_PAGE}/police/report-or-record-request`, { waitUntil: "load" });
      await percySnapshot(page, "Anonymous - Service \"Police Report or Record\"");
    }
  );
});

const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";
const timeout = 30000;

var BROWSER_OPTION = { 
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

if (!SITE_NAME) {
  BROWSER_OPTION.executablePath = "/usr/bin/google-chrome";
}

let browser;
let page;

beforeAll(async () => {
  browser = await puppeteer.launch(BROWSER_OPTION);
  page = await browser.newPage();

  // Print browser version
  // await page.browser().version().then(function(version) {
  //   console.log(version);
  //   });
  // console.log(browser.process().spawnfile);
}, timeout)

afterAll(async () => {
  await browser.close();
}, timeout)

describe("Homepage", () => {
  it(
    "h1 text",
    async () => {
      await page.goto(HOME_PAGE);
      // Gets page title
      const title = await page.evaluate(
        () => document.querySelector("h1").textContent
      );
      // Compares it with the intended behavior
      expect(title).toBe("Welcome to Portland, Oregon");
      await percySnapshot(page, "Anonymous - Homepage h1 text");
    },
    timeout
  );

  it(
    "Menu open",
    async () => {
      await page.goto(HOME_PAGE, { waitUntil: "load" });
      await page.click("button.cloudy-header__toggle--menu");
      await page.waitForSelector(".cloudy-header__menu-wrapper.show");
      await percySnapshot(page, "Anonymous - Home page Menu");
    },
    timeout
  );

  it(
    "Search Auto-complete powr",
    async () => {
      await page.goto(HOME_PAGE, { waitUntil: "load" });
      await page.type("#edit-keys", "powr");
      await page.waitForSelector("#ui-id-1", { visible: true });
      await percySnapshot(page, "Anonymous - Home Autocomplete");
    },
    timeout
  );
});

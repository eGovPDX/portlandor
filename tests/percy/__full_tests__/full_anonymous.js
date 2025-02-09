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
  headless: "new",
};

describe("Full regression test suite for anonymous", () => {
  let browser, page;

  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION);
    page = await browser.newPage();
    await page.setDefaultTimeout(30000)
  })

  afterAll(async () => {
    await browser.close();
  })

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
      // await percySnapshot(page, "Anonymous - Home page");
    }
  );
 
  // it(
  //   "Search Auto-complete powr",
  //   async () => {
  //     await page.goto(HOME_PAGE, { waitUntil: "load" });
  //     await page.type("#edit-keys", "powr");
  //     await page.waitForSelector("#ui-id-1", { visible: true });

  //     // Get the first autocomplete text
  //     const text = await page.evaluate(
  //       () => document.querySelector("span.autocomplete-suggestion-label").textContent
  //     );
  //     expect(text).toBe("Portland Oregon Website Replacement");
  //   }
  // ); 
});

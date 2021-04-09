const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";
const timeout = process.env.SLOWMO ? 30000 : 10000;

describe("Google", () => {
  let browser;
  let page;
  beforeAll(async () => {
    browser = await puppeteer.launch({
      // headless: false,
      ignoreHTTPSErrors: true,
      args: ["--no-sandbox"],
    });
    page = await browser.newPage();
  });

  afterAll(async () => {
    await browser.close();
  });

  it(
    'title should display "City of Portland, Oregon | Portland.gov" text on homepage',
    async () => {
      await page.goto(HOME_PAGE);
      let text_content = await page.evaluate(
        () => document.querySelector("h1").textContent
      );
      expect(text_content).toEqual("Welcome to Portland, Oregon");
      await percySnapshot(page, "Anonymous - Home page");
    },
    timeout
  );
});

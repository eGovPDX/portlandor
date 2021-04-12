const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");
// const fs = require("fs");

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";
const timeout = process.env.SLOWMO ? 30000 : 10000;

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
      // if (!fs.existsSync("screenshots")) {
      //   fs.mkdirSync("screenshots");
      // }
      await page.goto(HOME_PAGE);
      await page.click("button.cloudy-header__toggle--menu");
      await page.waitForSelector(".cloudy-header__menu-wrapper.show");
      // await page.screenshot({
      //   path: "./screenshots/screenshot.png",
      //   fullPage: true,
      // });
      await percySnapshot(page, "Anonymous - Home page Menu");
    },
    timeout
  );

  it(
    "Search Auto-complete powr",
    async () => {
      // if (!fs.existsSync("screenshots")) {
      //   fs.mkdirSync("screenshots");
      // }
      await page.goto(HOME_PAGE);
      await page.type('#edit-keys', 'powr');
      await page.waitForSelector("#ui-id-1.show");
      // await page.screenshot({
      //   path: "./screenshots/screenshot.png",
      //   fullPage: true,
      // });
      await percySnapshot(page, "Anonymous - Search Auto-complete");
    },
    timeout
  );
});

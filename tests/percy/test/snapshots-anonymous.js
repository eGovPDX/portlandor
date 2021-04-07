const percySnapshot = require("@percy/puppeteer");
const puppeteer = require("puppeteer");
const assert = require("assert");
const fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";


  let browser, page;
  before(async () => {
    browser = await puppeteer.launch({
      headless: false,
      slowMo: 450,
      ignoreHTTPSErrors: true,
      args: ["--no-sandbox"],
    });
    page = await browser.newPage();
    await page.setDefaultTimeout(30000);
  });

// describe("Anonymous page screenshots", () => {
//   // Homepage
//   it("has correct text on home page", async function () {
//     await page.goto(HOME_PAGE);
//     await page.waitForSelector("nav");

//     // Verify the home page has text "Services"
//     let text_content = await page.evaluate(
//       () => document.querySelector("nav").textContent
//     );
//     assert(
//       text_content.includes("Services"),
//       'Cannot find "Services" on Home page'
//     );

//     // Verify the home page has text "Services"
//     text_content = await page.evaluate(
//       () => document.querySelector("div.content h2.h6").textContent
//     );
//     assert(
//       text_content === "City of Portland, Oregon",
//       'Cannot find "City of Portland, Oregon" on Home page'
//     );

//     await percySnapshot(page, "Anonymous - Home page");
//   });

//   // Search page
//   it("Search page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/search?keys=tax`);
//     await percySnapshot(page, 'Anonymous - Search "tax"');
//   });

//   // 404
//   it("404 page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/search?keys=powr-test`);
//     await percySnapshot(page, 'Anonymous - 404 "powr-test"');
//   });

//   // Elected Officials
//   it("Elected Official page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/wheeler`);
//     await percySnapshot(page, 'Anonymous - Elected "Mayor"');
//   });

//   // Advisory Group
//   it("Advisory Group page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/omf/toc`);
//     await percySnapshot(page, 'Anonymous - Advisory "Technology Oversight"');
//   });

//   // Program
//   it("Program Group page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/help`);
//     await percySnapshot(page, 'Anonymous - Program "POWR Help"');
//   });

//   // Bureau
//   it("Bureau Group page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/omf`);
//     await percySnapshot(page, 'Anonymous - Bureau "Mangagement and Finance"');
//   });

//   // Project
//   it("Project Group page screenshot", async function () {
//     await page.goto(`${HOME_PAGE}/powr`);
//     await percySnapshot(page, 'Anonymous - Project "POWR"');
//   });

//   // Service
//   it("Service Group page screenshot", async function () {
//     await page.goto(
//       `${HOME_PAGE}/police/services/police-report-or-record-online-request`
//     );
//     await percySnapshot(page, 'Anonymous - Service "Police Report or Record"');
//   });
// });


// describe("Anonymous UI element screenshots", () => {
//   // Homepage menu toggle
//   it("toggles site menu open", async function () {
//     await page.goto(HOME_PAGE);
//     await page.waitForSelector("nav");
//     await page.click("button.cloudy-header__toggle--menu");
//     await percySnapshot(page, "Anonymous - Site Menu Open");
//     // Should we close the menu bar and take another screenshot
//     await page.click("button.cloudy-header__toggle--menu");
//     await percySnapshot(page, "Anonymous - Site Menu close");
//   });
//   // Home page autocomplete
//   it("Home search auto-complete", async function () {
//      // if screenshots directory is not exist then create one
//      if (!fs.existsSync("screenshots")) {
//       fs.mkdirSync("screenshots");
//     }
//     // await page.type('#edit-keys', 'POWR', {delay: 100});
//     await page.waitForSelector("ui-menu-item");
//     // await percySnapshot(page, "Search Auto-Complete")
//     await page.screenshot({
//       path: "./screenshots/screenshot.png",
//       fullPage: true,
//     });
// });

describe("Anonymous UI element screenshots", () => {
  // Homepage search autocomplete
  it("triggers search autocomplete", async function () {
    // if screenshots directory is not exist then create one
    if (!fs.existsSync("screenshots")) {
      fs.mkdirSync("screenshots");
    }
    await page.goto(HOME_PAGE);
    await page.waitForSelector("input[type=search]");
    await page.focus("input[type=search]");
    await page.keyboard.type("powr", { delay: 400 });
    await page.waitForSelector("#edit-keys", { visible: true });
    await page.screenshot({
      path: "./screenshots/screenshot.png",
      fullPage: true,
    });
    await percySnapshot(page, "Anonymous - Homepage autocomplete Open");
  });
});

after(async () => {
  await browser.close();
});

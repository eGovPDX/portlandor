const puppeteer = require("puppeteer");
var fs = require("fs");
const util = require("../lib/util");

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = process.env.CIRCLECI
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";
const ARTIFACTS_FOLDER = process.env.CIRCLECI
  ? `/home/circleci/artifacts/`
  : `./`;
const TEST_GROUP_PATH = "full-regression-test-group";
const TEST_GROUP_NAME = "Full Regression Test Group";

var BROWSER_OPTION = {
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
  defaultViewport: null,
};

describe("Full regression test suite for Admin", () => {
  var browser, page, login_url, link;
  beforeAll(async () => {
    browser = await puppeteer.launch(BROWSER_OPTION);
    page = await browser.newPage();
    await page.setDefaultTimeout(30000);

    if (process.env.CIRCLECI) {
      // On CI, the CI script will call terminus to retrieve login URL
      login_url = process.env.SUPERADMIN_LOGIN;
      await page.goto(login_url);
    } else {
      var drush_uli_result = fs.readFileSync("superAdmin_uli.log").toString();
      login_url = drush_uli_result.replace(
        "http://default",
        "https://portlandor.lndo.site"
      );
      // Log in once for all tests to save time
      await page.goto(login_url);
    }
  });

  afterAll(async () => {
    await browser.close();
  });

  it("Admin can create a group and add Ally as admin", async () => {
    try {
      let text_content = "",
        selector = "";

      // If a previous test failed without deleting the test group, delete it first
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/delete`);
      text_content = await page.evaluate(
        () => document.querySelector(".page-title").textContent
      );
      if (text_content.indexOf(TEST_GROUP_NAME) > 0) {
        selector = "input#edit-submit";
        await page.focus(selector);
        await page.evaluate(
          (selector) => document.querySelector(selector).click(),
          selector
        );
        await page.waitForNavigation();
      }

      // Create a new group
      await page.goto(`${HOME_PAGE}/group/add/advisory_group`);
      text_content = await page.evaluate(
        () => document.querySelector(".page-title").textContent
      );
      expect(text_content).toEqual(
        expect.stringContaining("Add Advisory group")
      );
      await page.type("#edit-label-0-value", TEST_GROUP_NAME);
      await page.select("#edit-field-migration-status", "Complete");
      await page.type(
        "#edit-field-summary-0-value",
        `This is a test summary for the ${TEST_GROUP_PATH}`
      );
      // Must expand the admin fields group in order to input Group Path
      await page.click("details#edit-group-administrative-fields-site");
      await page.type("#edit-field-group-path-0-value", TEST_GROUP_PATH);

      selector = "#edit-submit";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      await page.waitForNavigation();

      text_content = await page.evaluate(
        () => document.querySelector("h1.page-title").textContent
      );
      expect(text_content).toEqual(expect.stringContaining(TEST_GROUP_NAME));

      // Add Ally to the new group as the group admin
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/add/group_membership?destination=/${TEST_GROUP_PATH}/members`
      );
      text_content = await page.evaluate(
        () => document.querySelector(".page-title").textContent
      );
      expect(text_content).toEqual(
        expect.stringContaining("Add Advisory group: Group membership")
      );
      await page.type("#edit-entity-id-0-target-id", "Ally Admin (62)");
      selector = "#edit-group-roles-advisory-group-admin";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      // Submit form
      await page.keyboard.press("Enter");

      await page.waitForNavigation();
      text_content = await page.evaluate(
        () =>
          document.querySelector("td.views-field-name a[href='/ally-admin']")
            .textContent
      );

      expect(text_content).toEqual(expect.stringContaining("Ally Admin"));

      // Masquerade as Ally to create content
      await util.masqueradeAs("ally.admin@portlandoregon.gov", page, HOME_PAGE);
      text_content = await page.evaluate(
        () => document.querySelector("#toolbar-item-user").textContent
      );
      expect(text_content).toEqual(expect.stringMatching("Ally Admin"));
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}add-ally-to-new-group-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create Page", async () => {
    try {
      let text_content = "",
        selector = "";

      // Add page content
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}`);
      text_content = await page.evaluate(
        () => document.querySelector("ul.primary").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("+ Add Content"));
      expect(text_content).toEqual(expect.stringContaining("+ Add Media"));
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/node/create`);
      text_content = await page.evaluate(
        () => document.querySelector("div.region-content dl").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Add Page"));

      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:page`,
        { waitUntil: "networkidle2" }
      );
      const ckeditor = await page.waitForSelector("iframe");
      text_content = await page.evaluate(
        () => document.querySelector("#node-page-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Page type"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));
      expect(text_content).toEqual(expect.stringContaining("Body content"));
      expect(text_content).toEqual(expect.stringContaining("Legacy path"));

      await page.type("#edit-title-0-value", "Test page");
      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test page"
      );
      await page.type("#edit-field-menu-link-text-0-value", "Test page");
      await ckeditor.type("body p", "Body content for the test page", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Test revision message");

      // Click submit button and wait for page load
      selector = "input#edit-submit";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      await page.waitForNavigation();
      text_content = await page.evaluate(
        () => document.querySelector("div.messages--status").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("has been created"));

      // Edit the newly created page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/test-page/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/test-page/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-page-delete-form").textContent
      );
      expect(text_content).toEqual(
        expect.stringContaining("This action cannot be undone")
      );

      selector = "input#edit-submit";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      await page.waitForNavigation();

      // Verify deletion message
      text_content = await page.evaluate(
        () => document.querySelector("div.messages--status").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("has been deleted"));
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-page-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create service", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-service-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create construction", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-construction-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create contact", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-contact-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create event", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-event-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create resource", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-resource-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create news", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-news-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create notification", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-notification-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create document", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-document-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create image", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-image-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create map", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-map-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create video", async () => {
    try {
      let text_content = "",
        selector = "";
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-video-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  it("Admin can delete group", async () => {
    try {
      let text_content = "",
        selector = "";
      await util.unmasquerade(page, HOME_PAGE);
      text_content = await page.evaluate(
        () => document.querySelector("#toolbar-item-user").textContent
      );
      expect(text_content).toEqual(expect.stringMatching("superAdmin"));

      // Delete the new group
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/delete`);
      selector = "input#edit-submit";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      await page.waitForNavigation();

      text_content = await page.evaluate(
        () => document.querySelector("div.messages--status").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("has been deleted"));
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}admin-delete-group-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });
});

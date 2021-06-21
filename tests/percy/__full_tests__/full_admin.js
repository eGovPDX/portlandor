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
  // executablePath: "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
  // headless: false,
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
      await page.goto(`${HOME_PAGE}/group/add/bureau_office`);
      text_content = await page.evaluate(
        () => document.querySelector(".page-title").textContent
      );
      expect(text_content).toEqual(
        expect.stringContaining("Add Bureau/office")
      );
      await page.type("#edit-label-0-value", TEST_GROUP_NAME);
      await page.type('#edit-field-official-organization-name-0-value', `Official name of ${TEST_GROUP_NAME}`);
      await page.select("#edit-field-migration-status", "Complete");
      await page.type(
        "#edit-field-summary-0-value",
        `This is a test summary for the ${TEST_GROUP_PATH}`
      );
      // Must expand the admin fields group in order to input Group Path
      await page.click("details#edit-group-administrative-fields");
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
        expect.stringContaining("Add Bureau/office: Group membership")
      );
      await page.type("#edit-entity-id-0-target-id", "Ally Admin (62)");
      selector = "#edit-group-roles-bureau-office-admin";
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

      await page.type("#edit-title-0-value", "Full regression test page");
      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test page"
      );
      await page.type("#edit-field-menu-link-text-0-value", "Full regression test page");
      await ckeditor.type("body p", "Body content for the test page", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-page/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-page/delete`);
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

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-page`);
      text_content = await page.evaluate(
        () => document.querySelector(".page-title").textContent
      );
      if(text_content !== "Page not found") {
        await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-page/delete`);

        selector = "input#edit-submit";
        await page.evaluate(
          (selector) => document.querySelector(selector).click(),
          selector
        );
        await page.waitForNavigation();
      }
      throw e;
    }
  });

  // Masquerade as Ally
  it("Ally can create service", async () => {
    try {
      let text_content = "",
        selector = "";

      // Add service content
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:city_service`,
        { waitUntil: "networkidle2" }
      );
      const ckeditor = await page.waitForSelector("iframe");
      text_content = await page.evaluate(
        () => document.querySelector("#node-city-service-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Short Title"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));
      expect(text_content).toEqual(expect.stringContaining("Body content"));

      await page.type("#edit-title-0-value", "Full regression test service");
      // Short title
      await page.type("#edit-field-menu-link-text-0-value", "Full regression test Service");

      // Click on "Actions"
      //TODO: Need a more robust way to work with Select2 options
      selector = "ul.select2-selection__rendered";
      await page.evaluate(
        (selector) => document.querySelector(selector).click(),
        selector
      );
      // Select the first option "Apply"
      await page.keyboard.press('Enter');

      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test page"
      );

      await ckeditor.type("body p", "Body content for the test service", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-service/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-service/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-city-service-delete-form").textContent
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

      // Add service content
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:construction_project`,
        { waitUntil: "networkidle2" }
      );
      const ckeditor = await page.waitForSelector("iframe");
      text_content = await page.evaluate(
        () => document.querySelector("#node-construction-project-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Construction type"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));
      expect(text_content).toEqual(expect.stringContaining("Body content"));

      await page.type("#edit-title-0-value", "Full regression test construction");
      // Select "Water" as type
      await page.select('#edit-field-construction-type', '342') 
      // Select "Active" as status
      await page.select('#edit-field-project-status', '52') 

      await page.type("input#edit-field-start-date-0-value-date", "06042021")

      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test construction"
      );

      await ckeditor.type("body p", "Body content for the test construction", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/construction/full-regression-test-construction/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/construction/full-regression-test-construction/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-construction-project-delete-form").textContent
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

      // Add service content
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:contact`,
        { waitUntil: "networkidle2" }
      );
      text_content = await page.evaluate(
        () => document.querySelector("#node-contact-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Contact Name"));
      expect(text_content).toEqual(expect.stringContaining("Contact Title"));
      expect(text_content).toEqual(expect.stringContaining("Contact Type"));

      await page.type("#edit-title-0-value", "Full regression test contact");
      await page.type("#edit-field-contact-title-0-value", "Full regression test contact title");
      // "Chair"
      await page.select('#edit-field-contact-type', '620');
      await page.type("#cleave-telephone--2", "5035551212");

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

      // Edit the newly created contact
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-contact/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Contact Name"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/full-regression-test-contact/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-contact-delete-form").textContent
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

      // Add event
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:event`,
        { waitUntil: "networkidle2" }
      );
      const ckeditor = await page.waitForSelector("iframe");
      text_content = await page.evaluate(
        () => document.querySelector("#node-event-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Event type"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));
      expect(text_content).toEqual(expect.stringContaining("Body content"));

      await page.type("#edit-title-0-value", "Full regression test event");
      // Select "Meeting" as type
      await page.select('#edit-field-event-type', '332') 
      // Select "Rescheduled" as status
      await page.select('#edit-field-event-status', 'Rescheduled');
      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test event"
      );

      await page.type("input#edit-field-start-time-0-value", "02:30pm");
      await page.type("input#edit-field-end-time-0-value", "04:00pm");

      await ckeditor.type("body p", "Body content for the test event", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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

      let now = new Date();
      let year = now.getFullYear();
      let month = now.getMonth() + 1; // 0-11
      let day = now.getDate();
      // Edit the newly created page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/events/${year}/${month}/${day}/full-regression-test-event/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/events/${year}/${month}/${day}/full-regression-test-event/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-event-delete-form").textContent
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

      // Add resource
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:external_resource`,
        { waitUntil: "networkidle2" }
      );
      text_content = await page.evaluate(
        () => document.querySelector("#node-external-resource-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Resource Type"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));

      await page.type("#edit-title-0-value", "Full regression test resource");
      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test resource"
      );

      await page.type("#edit-field-destination-url-0-uri", "https://www.oregon.gov");
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/resources/full-regression-test-resource/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/resources/full-regression-test-resource/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-external-resource-delete-form").textContent
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

      // Add news
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:news`,
        { waitUntil: "networkidle2" }
      );
      const ckeditor = await page.waitForSelector("iframe");
      text_content = await page.evaluate(
        () => document.querySelector("#node-news-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("News Type"));
      expect(text_content).toEqual(expect.stringContaining("Summary"));
      expect(text_content).toEqual(expect.stringContaining("Body content"));

      await page.type("#edit-title-0-value", "Full regression test news");
      await page.type(
        "#edit-field-summary-0-value",
        "Summary for the test news"
      );

      await ckeditor.type("body p", "Body content for the test news", {
        delay: 100,
      });
      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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

      let now = new Date();
      let year = now.getFullYear();
      let month = now.getMonth() + 1; // 0-11
      let day = now.getDate();
      // Edit the newly created page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/news/${year}/${month}/${day}/full-regression-test-news/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/news/${year}/${month}/${day}/full-regression-test-news/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-news-delete-form").textContent
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

      // Add notification
      await page.goto(
        `${HOME_PAGE}/${TEST_GROUP_PATH}/content/create/group_node:notification`,
        { waitUntil: "networkidle2" }
      );
      text_content = await page.evaluate(
        () => document.querySelector("#node-notification-form").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));
      expect(text_content).toEqual(expect.stringContaining("Notification text"));

      await page.type("#edit-title-0-value", "Full regression test notification");

      // Update the CKEditor content
      await page.evaluate(()=> {
        document.querySelector('iframe').contentDocument.querySelector('body p').textContent = "Body content for the test notification"
      });

      await page.type("#edit-revision-log-0-value", "Full regression test revision message");
      await page.select('#edit-moderation-state-0-state', 'published');

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

      let now = new Date();
      let year = now.getFullYear();
      let month = ("0" + (now.getMonth() + 1)).slice(-2);
      let day = ("0" + now.getDate()).slice(-2);
      // Edit the newly created page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/notifications/${year}-${month}-${day}/full-regression-test-notification/edit`);
      text_content = await page.evaluate(
        () => document.querySelector("div.form-item--title-0-value").textContent
      );
      expect(text_content).toEqual(expect.stringContaining("Title"));

      // Delete the page
      await page.goto(`${HOME_PAGE}/${TEST_GROUP_PATH}/notifications/${year}-${month}-${day}/full-regression-test-notification/delete`);
      text_content = await page.evaluate(
        () => document.querySelector("form.node-notification-delete-form").textContent
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

      // Must delete all content nodes before deleting the group
      await page.goto(`${HOME_PAGE}/admin/content?title=&body_content=&moderation_state=All&status=All&is_locked=All&revision_uid=&uid=&has_reviewer=All&group_op=contains&group=full+regression+test`);
      await page.waitForSelector('#view-title-table-column');

      let tableIsEmpty = await page.evaluate( () => {
        if(document.querySelector('td.views-empty') == null) return false;
        if(document.querySelector('td.views-empty').textContent.indexOf('No content available') >= 0) return true;
        return false;
      });

      if( ! tableIsEmpty ) {
        await page.evaluate(() => {
          document.querySelector('input[title="Select all rows in this table"]').click();
        });
        await page.select('#edit-action', '16');
        // Apply to selected items
        await page.evaluate(() => {
          document.querySelector('#edit-submit--2').click();
        });
        await page.waitForNavigation();
        // Execute action button
        await page.evaluate(() => {
          document.querySelector('#edit-submit').click();
        });
        // Wait for and verify the batch processing result
        await page.waitForSelector('div.messages__content', { timeout: 60000 })
        text_content = await page.evaluate(
          () => document.querySelector('div.messages__content').textContent
        );
        expect(text_content).toEqual(expect.stringContaining("Action processing results: Delete"));
      }

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

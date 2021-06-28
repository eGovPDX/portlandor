const puppeteer = require("puppeteer");
var fs = require("fs");
const util = require("../lib/util");
const path = require("path");

const ContentTester = util.ContentTester;
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
  // To watch tests locally on MacOS:
  // 1. Uncomment these two settings below
  // 2. In CLI, go into folder "tests/percy"
  // 3. Run "lando drush uli > superAdmin_uli.log && npm run jest-full"
  executablePath:
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
  headless: false,
  slowMo: 100,
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
      await page.type(
        "#edit-field-official-organization-name-0-value",
        `Official name of ${TEST_GROUP_NAME}`
      );
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
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}create-new-group-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Assigned to Ronnie
  // TODO: add tests for site wide content like Alerts, Code, Charter, Policy, Location, etc
  // Admin create site wide content
  it("Admin can create alert", async () => {
    try {
      var alertTester = Object.create(ContentTester);
      alertTester.init({
        entityType: "node",
        contentType: "alert",
        page: page,
        fieldLabelArray: ["Title", "Severity", "Alert text"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      alertTester.gotoContentCreatePage = async function () {
        //Add content
        await this.page.goto(
          `${this.homepageUrl}/node/add/${this.contentType}`,
          { waitUntil: "networkidle2" }
        );
        // alertTester.inputFieldValues = async function () {
        //   // Title
        //   await this.page.type(
        //     "#edit-title-0-value",
        //     "Full regression test alert"
        //   );
        //   //Severity
        //   await this.page.select("#edit-field-severity", "20");
        //   //Alert Text
        //   await this.page.evaluate(() => {
        //     document
        //       .querySelector("iframe.cke_wysiwyg_frame")
        //       .contentDocument.querySelector("body p").textContent =
        //       "Full regression test alert text";
        //   });
        //   //Revision text
        //   await this.page.type(
        //     "#edit-revision-log-0-value",
        //     "Full regression test revision message"
        //   );
        //   await this.page.select("#edit-moderation-state-0-state", "published");
        //   await alertTester.runTest();
        // };
      };
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
  it("Add Ally to the new group and masquerade as Ally", async () => {
    try {
      let text_content = "",
        selector = "";

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
      var pageTester = Object.create(ContentTester);
      pageTester.init({
        entityType: "node",
        contentType: "page",
        page: page,
        fieldLabelArray: [
          "Title",
          "Page type",
          "Summary",
          "Body content",
          "Legacy path",
        ],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });
      // Override the function that inputs test values into form
      pageTester.inputFieldValues = async function () {
        // Title
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test page"
        );
        // Short title
        await this.page.type(
          "#edit-field-menu-link-text-0-value",
          "Full regression test page"
        );

        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test page"
        );
        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };

      await pageTester.runTest();
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

  // Ally creates a service in the group
  it("Ally can create service", async () => {
    try {
      var cityServiceTester = Object.create(ContentTester);
      cityServiceTester.init({
        entityType: "node",
        contentType: "city_service",
        page: page,
        fieldLabelArray: ["Title", "Short Title", "Summary", "Body content"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      cityServiceTester.inputFieldValues = async function () {
        let text_content, selector;
        // Title
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test city service"
        );
        // Short title
        await this.page.type(
          "#edit-field-menu-link-text-0-value",
          "Full regression test city Service"
        );

        // Click on "Actions"
        //TODO: Need a more robust way to work with Select2 options
        selector = "ul.select2-selection__rendered";
        await this.page.evaluate(
          (selector) => document.querySelector(selector).click(),
          selector
        );
        // Select the first option "Apply"
        await this.page.keyboard.press("Enter");

        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test service"
        );

        // Update the CKEditor content
        await this.page.evaluate(() => {
          document
            .querySelector("iframe.cke_wysiwyg_frame")
            .contentDocument.querySelector("body p").textContent =
            "Body content for the test city service";
        });
        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };

      await cityServiceTester.runTest();
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

  // Ally create a construction project in the group
  it("Ally can create construction", async () => {
    try {
      var constructionTester = Object.create(ContentTester);
      constructionTester.init({
        entityType: "node",
        contentType: "construction_project",
        page: page,
        fieldLabelArray: [
          "Title",
          "Construction type",
          "Summary",
          "Body content",
        ],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });
      // Override the function that inputs test values into form
      constructionTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test construction project"
        );
        // Select "Water" as type
        await this.page.select("#edit-field-construction-type", "342");
        // Select "Active" as status
        await this.page.select("#edit-field-project-status", "52");

        await this.page.type(
          "input#edit-field-start-date-0-value-date",
          "06042021"
        );

        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test construction"
        );

        // Update the CKEditor content
        await this.page.evaluate(() => {
          document
            .querySelector("iframe.cke_wysiwyg_frame")
            .contentDocument.querySelector("body p").textContent =
            "Body content for the test construction";
        });

        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };

      await constructionTester.runTest();
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-construction-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });

      console.log(page.url());
      throw e;
    }
  });

  // Ally creates contact in the group
  it("Ally can create contact", async () => {
    try {
      var contactTester = Object.create(ContentTester);
      contactTester.init({
        entityType: "node",
        contentType: "contact",
        page: page,
        fieldLabelArray: ["Contact Name", "Contact Title", "Contact Type"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      contactTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test contact"
        );
        await this.page.type(
          "#edit-field-contact-title-0-value",
          "Full regression test contact title"
        );
        // "Chair"
        await this.page.select("#edit-field-contact-type", "620");
        await this.page.type("#cleave-telephone--2", "5035551212");
      };

      await contactTester.runTest();
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

  // Ally creates event in the group
  it("Ally can create event", async () => {
    try {
      var eventTester = Object.create(ContentTester);
      eventTester.init({
        entityType: "node",
        contentType: "event",
        page: page,
        fieldLabelArray: ["Title", "Event type", "Summary", "Body content"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      eventTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test event"
        );
        // Select "Meeting" as type
        await this.page.select("#edit-field-event-type", "332");
        // Select "Rescheduled" as status
        await this.page.select("#edit-field-event-status", "Rescheduled");
        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test event"
        );

        await this.page.type("input#edit-field-start-time-0-value", "02:30pm");
        await this.page.type("input#edit-field-end-time-0-value", "04:00pm");

        await this.page.evaluate(() => {
          document
            .querySelector("iframe.cke_wysiwyg_frame")
            .contentDocument.querySelector("body p").textContent =
            "Body content for the test event";
        });
        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };

      await eventTester.runTest();
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-event-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      console.log(page.url());
      throw e;
    }
  });

  // Ally creates resource in the group
  it("Ally can create resource", async () => {
    try {
      let text_content = "",
        selector = "";

      var resourceTester = Object.create(ContentTester);
      resourceTester.init({
        entityType: "node",
        contentType: "external_resource",
        page: page,
        fieldLabelArray: ["Title", "Resource Type", "Summary"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      resourceTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test resource"
        );
        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test resource"
        );

        await this.page.type(
          "#edit-field-destination-url-0-uri",
          "https://www.oregon.gov"
        );
        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };

      await resourceTester.runTest();
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

  // Ally creates news in the group
  it("Ally can create news", async () => {
    try {
      var newsTester = Object.create(ContentTester);
      newsTester.init({
        entityType: "node",
        contentType: "news",
        page: page,
        fieldLabelArray: ["Title", "News Type", "Summary", "Body content"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      newsTester.inputFieldValues = async function () {
        await this.page.waitForSelector("iframe");
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test news"
        );
        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test news"
        );

        await this.page.evaluate(() => {
          document
            .querySelector("iframe.cke_wysiwyg_frame")
            .contentDocument.querySelector("body p").textContent =
            "Body content for the test news";
        });
        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };
      await newsTester.runTest();
    } catch (e) {
      // Capture the screenshot when test fails and re-throw the exception
      console.log(await page.url());
      await page.screenshot({
        path: `${ARTIFACTS_FOLDER}ally-add-news-error.jpg`,
        type: "jpeg",
        fullPage: true,
      });
      throw e;
    }
  });

  // Ally creates notification in the group
  it("Ally can create notification", async () => {
    try {
      var notificationTester = Object.create(ContentTester);
      notificationTester.init({
        entityType: "node",
        contentType: "notification",
        page: page,
        fieldLabelArray: ["Title", "Notification text"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });

      // Override the function that inputs test values into form
      notificationTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-title-0-value",
          "Full regression test notification"
        );

        // Update the CKEditor content
        await this.page.evaluate(() => {
          document
            .querySelector("iframe.cke_wysiwyg_frame")
            .contentDocument.querySelector("body p").textContent =
            "Body content for the test notification";
        });

        await this.page.type(
          "#edit-revision-log-0-value",
          "Full regression test revision message"
        );
        await this.page.select("#edit-moderation-state-0-state", "published");
      };
      await notificationTester.runTest();
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

  // Ally creates document in the group
  // TODO: include both file upload and eFiles link
  it("Ally can create document", async () => {
    try {
      var documentTester = Object.create(ContentTester);
      documentTester.init({
        entityType: "media",
        contentType: "document",
        page: page,
        fieldLabelArray: ["Name", "Document type"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });
      // Override the function that inputs test values into form
      documentTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-name-0-value",
          "Full regression test document"
        );
        // "Meeting materials" as document type
        await this.page.select("#edit-field-document-type", "335");

        await this.page.type(
          "#edit-field-summary-0-value",
          "Summary for the test document"
        );

        // Upload a file
        const fileElement = await this.page.$(
          'div.form-managed-file__main input[type="file"]'
        );
        const filePath = path.relative(
          process.cwd(),
          __dirname + "/assets/upload_test.txt"
        );
        await fileElement.uploadFile(filePath);
        await this.page.waitForSelector(
          "div.form-managed-file__main span.file"
        );

        await this.page.select("#edit-moderation-state-0-state", "published");
      };
      await documentTester.runTest();
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

  // Ally creates image in the group
  it("Ally can create image", async () => {
    try {
      var imageTester = Object.create(ContentTester);
      imageTester.init({
        entityType: "media",
        contentType: "image",
        page: page,
        fieldLabelArray: ["Name", "Caption"],
        homepageUrl: HOME_PAGE,
        testGroupPath: TEST_GROUP_PATH,
      });
      // Override the function that inputs test values into form
      imageTester.inputFieldValues = async function () {
        await this.page.type(
          "#edit-name-0-value",
          "Full regression test image"
        );
        // Upload a file
        const fileElement = await this.page.$(
          'div.form-managed-file__main input[type="file"]'
        );
        const filePath = path.relative(
          process.cwd(),
          __dirname + "/assets/upload_test.jpg"
        );
        await fileElement.uploadFile(filePath);
        // await this.page.waitForSelector('div.form-managed-file__main span.file');
        await this.page.waitForSelector(
          'div.form-item--image-0-alt input[type="text"]'
        );
        await this.page.type(
          'div.form-item--image-0-alt input[type="text"]',
          "Alternative text for the test image"
        );

        await this.page.type(
          "#edit-field-caption-0-value",
          "Caption for the test image"
        );

        await this.page.select("#edit-moderation-state-0-state", "published");
      };
      await imageTester.runTest();
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

  // Ally creates map in the group
  // Assigned to Brit
  // TODO: shape files, embed map, etc
  //
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

  // Ally creates video in the group
  // Assigned to Brit
  // TODO: may need to upload the preview image
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
      await page.goto(
        `${HOME_PAGE}/admin/content?title=&body_content=&moderation_state=All&status=All&is_locked=All&revision_uid=&uid=&has_reviewer=All&group_op=contains&group=full+regression+test`
      );
      await page.waitForSelector("#view-title-table-column");

      let tableIsEmpty = await page.evaluate(() => {
        if (document.querySelector("td.views-empty") == null) return false;
        if (
          document
            .querySelector("td.views-empty")
            .textContent.indexOf("No content available") >= 0
        )
          return true;
        return false;
      });

      if (!tableIsEmpty) {
        await page.evaluate(() => {
          document
            .querySelector('input[title="Select all rows in this table"]')
            .click();
        });
        await page.select("#edit-action", "16");
        // Apply to selected items
        await page.evaluate(() => {
          document.querySelector("#edit-submit--2").click();
        });
        await page.waitForNavigation();
        // Execute action button
        await page.evaluate(() => {
          document.querySelector("#edit-submit").click();
        });
        // Wait for and verify the batch processing result
        await page.waitForSelector("div.messages__content", { timeout: 60000 });
        text_content = await page.evaluate(
          () => document.querySelector("div.messages__content").textContent
        );
        expect(text_content).toEqual(
          expect.stringContaining("Action processing results: Delete")
        );
      }

      // Must delete all media nodes before deleting the group
      await page.goto(
        `${HOME_PAGE}/admin/content/media?keyword=&type=All&status=All&langcode=All&label=full+regression+test&field_efiles_link_uri=All`
      );
      await page.waitForSelector("#view-name-table-column");

      tableIsEmpty = await page.evaluate(() => {
        if (document.querySelector("td.views-empty") == null) return false;
        if (
          document
            .querySelector("td.views-empty")
            .textContent.indexOf("No content available") >= 0
        )
          return true;
        return false;
      });

      if (!tableIsEmpty) {
        await page.evaluate(() => {
          document
            .querySelector('input[title="Select all rows in this table"]')
            .click();
        });
        await page.select("#edit-action", "7");
        // Apply to selected items
        await page.evaluate(() => {
          document.querySelector("#edit-submit--2").click();
        });
        await page.waitForNavigation();
        // Execute action button
        await page.evaluate(() => {
          document.querySelector("#edit-submit").click();
        });
        // Wait for and verify the batch processing result
        await page.waitForSelector("div.messages__content", { timeout: 60000 });
        text_content = await page.evaluate(
          () => document.querySelector("div.messages__content").textContent
        );
        expect(text_content).toEqual(
          expect.stringContaining("Action processing results: Delete")
        );
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

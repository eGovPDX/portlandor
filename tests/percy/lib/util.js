const { promisify } = require('util');
const path = require ('path');

exports.masqueradeAs = async function(email, page, HOME_PAGE) {
  var encodedEmail = encodeURI(email);
  // Find the user in people list
  await page.goto(`${HOME_PAGE}/admin/people?user=${encodedEmail}&status=All&role=All&permission=All`);
  var link = await page.evaluate(() => document.querySelector('li.masquerade a').getAttribute('href'));
  await page.goto(`${HOME_PAGE}${link}`);
}

exports.unmasquerade = async function(page, HOME_PAGE) {
  await page.goto(`${HOME_PAGE}/my-groups`);
  var link = await page.evaluate(() => document.querySelector('div.toolbar-tab a[href^="/unmasquerade').getAttribute('href'));
  await page.goto(`${HOME_PAGE}${link}`);
}

exports.ContentTester = {
  init: function ( testSettings ) {
    this.entityType = testSettings.entityType; // node or media
    this.contentType = testSettings.contentType; // page, city_service, document, etc.
    this.page = testSettings.page; // Puppeteer page object
    this.fieldLabelArray = testSettings.fieldLabelArray; // Field labels to be verified
    this.homepageUrl = testSettings.homepageUrl, // The site URL
    this.testGroupPath = testSettings.testGroupPath // URL path to the group created for testing
  },
  runTest: async function () {
    // Add content
    await this.page.goto(
      `${this.homepageUrl}/${this.testGroupPath}/content/create/group_${this.entityType}:${this.contentType}`,
      { waitUntil: "networkidle2" }
    );
    // Verify the add content form field titles
    await this.verifyFieldLabels();
    await this.inputFieldValues();
    let contentUrl = await this.submitForm();
    if(contentUrl.indexOf(this.homepageUrl) != 0) contentUrl = this.homepageUrl + contentUrl;
    await this.editContent(`${contentUrl}/edit`);
    contentUrl = await this.submitForm();
    if(contentUrl.indexOf(this.homepageUrl) != 0) contentUrl = this.homepageUrl + contentUrl;
    await this.deleteContent(`${contentUrl}/delete`);
    await this.submitForm();
  },
  verifyFieldLabels: async function () {
    let text_content = "", selector = "";
    let addFormId = (this.entityType == 'media') ?
      `#${this.entityType}-${this.contentType.replace("_", "-")}-add-form` :
      `#${this.entityType}-${this.contentType.replace("_", "-")}-form`;
    text_content = await this.page.evaluate(
      (addFormId) => {
        return document.querySelector(addFormId).textContent
      },
      addFormId
    );
    this.fieldLabelArray.every((fieldLabel) => {
      expect(text_content).toEqual(expect.stringContaining(fieldLabel));
    });
  },
  inputFieldValues: async function () {
    // This function will be overriden by other testers
  },

  submitForm: async function () {
    let text_content = "", selector = "";

    // Click submit button and wait for page load
    selector = "input#edit-submit";
    await this.page.evaluate(
      (selector) => document.querySelector(selector).click(),
      selector
    );
    await this.page.waitForNavigation();
    text_content = await this.page.evaluate(
      () => {
        return document.querySelector("div.messages--status").textContent
      }
    );
    expect(text_content).toEqual(expect.stringContaining("has been"));
    let contentUrl = await this.page.evaluate(
      () => {
        // Get the URL to the media
        if(document.querySelector("div.messages__content a"))
          return document.querySelector("div.messages__content a").getAttribute('href');
        return ;
      }
    );
    return contentUrl ? contentUrl : this.page.url();
  },

  editContent: async function (editContentUrl) {
    // Edit the newly created page
    await this.page.goto(editContentUrl);
    if(this.entityType == "media") {
      await this.page.type("#edit-revision-log-message-0-value", "Full regression test revision message");
    }
    else {
      await this.page.type("#edit-revision-log-0-value", "Full regression test revision message");
    }
  },
  deleteContent: async function (deleteContentUrl) {
    let text_content = "", selector = "";
    // Delete the content
    await this.page.goto(deleteContentUrl);

    let deleteFormId = `#${this.entityType}-${this.contentType.replace("_", "-")}-delete-form`;
    console.log(deleteFormId);
    text_content = await this.page.evaluate((deleteFormId) => {
        return document.querySelector(deleteFormId).textContent
      },
      deleteFormId
    );
    expect(text_content).toEqual(
      expect.stringContaining("This action cannot be undone")
    );
  },
}

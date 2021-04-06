const percySnapshot = require('@percy/puppeteer')
const puppeteer = require('puppeteer')
const assert = require('assert');
const util = require('util');
const exec = util.promisify(require('child_process').exec);

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';

var browser, page, login_url;
before(async () => {
  browser = await puppeteer.launch({
    ignoreHTTPSErrors: true,
  })
  page = await browser.newPage()
  await page.setDefaultTimeout(30000)

  var drush_uli_result;
  if(process.env.CIRCLECI) {
    login_url = process.env.MARTY_LOGIN;
  }
  else {
    drush_uli_result = await exec('lando drush uli --mail marty.member@portlandoregon.gov');
    assert(drush_uli_result.stderr === '', `Failed to retrieve login URL ${drush_uli_result.stderr}`)
    login_url = drush_uli_result.stdout.replace('http://default', 'https://portlandor.lndo.site')
  }
})

describe('Marty Member user test', () => {
  it('The site is in good status', async function () {
    // Capture user profile page
    await page.goto(login_url);
    await percySnapshot(page, 'Marty Member - Account profile');

    let text_content = '';
    await page.goto(`${HOME_PAGE}/admin/reports/status`);
    await page.waitForSelector('.system-status-report');
    text_content = await page.evaluate(() => document.querySelector('.system-status-report').textContent);
    assert( ! text_content.includes('Errors found'), 'Found the text "Errors found" on Status page')
    assert( ! text_content.includes('The following changes were detected in the entity type and field definitions.'), 'Found the text "The following changes were detected in the entity type and field definitions." on Status page')

  })
});

after(async () => {
  await browser.close()
})

/*
// A script to navigate our app and take snapshots with Percy.
PercyScript.run(async (page, percySnapshot) => {
  // Uncomment this line to see browser console log
  // page.on('console', msg => console.log('PAGE LOG:', page.url(), msg.text()));

  await page.goto(MARTY_LOGIN);
  await page.goto(MY_CONTENT);
  await percySnapshot('Marty - My content');

  let text_content = '';

  await page.goto(`${HOME_PAGE}/pay`);
  await page.waitFor('.page-title');
  text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
  expect(text_content).to.have.string('Pay');

  // Verify fix for POWR-889. Use "Given I am on" in order to allow non-200 response code
  await page.goto(`${HOME_PAGE}/taxonomy/term/100000`);
  await page.waitFor('.page-title');
  text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
  expect(text_content).to.have.string('Page not found');

  // Add page content
  await page.goto(`${HOME_PAGE}/powr`);
  text_content = await page.evaluate(() => document.querySelector('ul.primary').textContent);
  expect(text_content).to.have.string('+ Add Content');
  expect(text_content).to.have.string('+ Add Media');
  await page.goto(`${HOME_PAGE}/powr/node/create`);
  text_content = await page.evaluate(() => document.querySelector('div.content dl').textContent);
  expect(text_content).to.have.string('Add Page');

  await page.goto(`${HOME_PAGE}/powr/content/create/group_node:page`, { waitUntil: 'networkidle2' });
  const ckeditor = await page.waitForSelector('iframe');
  await percySnapshot('Marty - Add content');

  text_content = await page.evaluate(() => document.querySelector('#node-page-form').textContent);
  expect(text_content).to.have.string('Title');
  expect(text_content).to.have.string('Page type');
  expect(text_content).to.have.string('Summary');
  expect(text_content).to.have.string('Body content');
  expect(text_content).to.have.string('Legacy path');

  await page.type('#edit-title-0-value', 'Test page');
  await page.type('#edit-field-summary-0-value', 'Summary for the test page');
  // await page.focus('#cke_edit-field-body-content-0-value');
  // await page.keyboard.type('Body content for the test page');
  await ckeditor.type('body p', 'Body content for the test page', { delay: 100 });
  await page.type('#edit-revision-log-0-value', 'Test revision message');

  // Click submit button and wait for page load
  let selector = 'input#edit-submit';
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.evaluate((selector) => document.querySelector(selector).click(), selector),
  ])
  await percySnapshot('Marty - Added content');
  text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
  expect(text_content).to.have.string('Page Test page has been created');

  // Edit the newly created page
  await page.goto(`${HOME_PAGE}/powr/test-page/edit`);
  text_content = await page.evaluate(() => document.querySelector('div.form-item-title-0-value').textContent);
  expect(text_content).to.have.string('Title');
  await percySnapshot('Marty - Edit content');

  // Delete the page
  await page.goto(`${HOME_PAGE}/powr/test-page/delete`);
  text_content = await page.evaluate(() => document.querySelector('form.node-page-delete-form').textContent);
  expect(text_content).to.have.string('This action cannot be undone');

  selector = 'input#edit-submit';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();

  // Verify deletion message
  text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
  expect(text_content).to.have.string('has been deleted');

  // Add media
  await page.goto(`${HOME_PAGE}/powr/media/create`);
  text_content = await page.evaluate(() => document.querySelector('div.content dl').textContent);
  expect(text_content).to.have.string('Add Video');
  await page.goto(`${HOME_PAGE}/powr/content/create/group_media%3Avideo`);
  await percySnapshot('Marty - Add video');

  text_content = await page.evaluate(() => document.querySelector('#media-video-add-form').textContent);
  expect(text_content).to.have.string('Name');
  expect(text_content).to.have.string('Video URL');

  await page.type('#edit-name-0-value', 'A test video');
  await page.type('#edit-field-media-video-embed-field-0-value', 'https://www.youtube.com/watch?v=Deguep26G7M');

  selector = 'input#edit-submit';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();

  // Verify creation message
  text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
  expect(text_content).to.have.string('Video A test video has been created');

  // Delete the video
  await page.goto(`${HOME_PAGE}/powr/media`);
  text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
  expect(text_content).to.have.string('Manage media');
  // Click on the first delete dropdown button. Assume the first media item is the new Video just created
  selector = 'ul.dropbutton li.delete a';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();
  await percySnapshot('Marty - Confirm deleting video');

  // Confirm deletion
  text_content = await page.evaluate(() => document.querySelector('h1.page-title').textContent);
  expect(text_content).to.have.string('Are you sure you want to delete the media item');

  selector = 'input#edit-submit';
  await page.evaluate((selector) => document.querySelector(selector).click(), selector);
  await page.waitForNavigation();

  // Verify deletion message
  text_content = await page.evaluate(() => document.querySelector('div.messages--status').textContent);
  expect(text_content).to.have.string('The media item A test video has been deleted');
},
{
  // Ignore HTTPS errors in Lando
  ignoreHTTPSErrors: (typeof process.env.LANDO_CA_KEY !== 'undefined'),
  // headless: false,
});
*/

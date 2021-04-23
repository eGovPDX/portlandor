const percySnapshot = require('@percy/puppeteer');
const { time } = require('console');
const puppeteer = require('puppeteer')
var fs = require('fs');

const SITE_NAME = process.env.SITE_NAME;
const HOME_PAGE = (SITE_NAME) ? `https://${SITE_NAME}-portlandor.pantheonsite.io` : 'https://portlandor.lndo.site';
const ARTIFACTS_FOLDER = (SITE_NAME) ? `/home/circleci/artifacts/` : `./`;
const timeout = 60000 * 2;

var BROWSER_OPTION = { 
  ignoreHTTPSErrors: true,
  args: ["--no-sandbox"],
};

if (!SITE_NAME) {
  BROWSER_OPTION.executablePath = "/usr/bin/google-chrome";
}
var browser, page, login_url;
beforeAll(async () => {
  browser = await puppeteer.launch(BROWSER_OPTION)
  page = await browser.newPage();
  await page.setDefaultTimeout(timeout);

  var drush_uli_result;
  if(process.env.CIRCLECI) {
    // On CI, the CI script will call terminus to retrieve login URL
    login_url = process.env.MARTY_LOGIN;
    await page.goto(login_url);
    await percySnapshot(page, 'Marty Member - Account profile');
  }
  else {
    var drush_uli_result = fs.readFileSync("marty_uli.log").toString();
    // expect(drush_uli_result.stdout).toEqual(expect.stringContaining('http'));
    login_url = drush_uli_result.replace('http://default', 'https://portlandor.lndo.site');
    // Log in once for all tests to save time
    await page.goto(login_url);
  }
}, timeout)

describe('Marty Member user test', () => {
  it('The site is in good status', async function () {
    let text_content = '';

    // Visit a topic page
    await page.goto(`${HOME_PAGE}/pay`);
    await page.waitForSelector('.page-title');
    text_content = await page.evaluate(() => document.querySelector('.page-title').textContent);
    expect(text_content).toEqual(expect.stringContaining('Pay'));
  }, timeout)
});

afterAll(async () => {
  await browser.close()
}, timeout)

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

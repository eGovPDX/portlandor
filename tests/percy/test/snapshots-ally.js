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
    args: [ '--no-sandbox'],
  })
  page = await browser.newPage()
  await page.setDefaultTimeout(30000)

  var drush_uli_result;
  if(process.env.CIRCLECI) {
    login_url = process.env.ALLY_LOGIN;
  }
  else {
    drush_uli_result = await exec('lando drush uli --mail ally.admin@portlandoregon.gov');
    assert(drush_uli_result.stderr === '', `Failed to retrieve login URL ${drush_uli_result.stderr}`)
    login_url = drush_uli_result.stdout.replace('http://default', 'https://portlandor.lndo.site')
  }
})

describe('Ally Admin user test', () => {
  it('The site is in good status', async function () {
    // Capture user profile page
    await page.goto(login_url);
    await percySnapshot(page, 'Ally Admin - Account profile');

    let text_content = '';
    await page.goto(`${HOME_PAGE}/my-groups`);
    await percySnapshot(page, 'Ally - My groups');
  })
});

after(async () => {
  await browser.close()
})


/*
// A script to navigate our app and take snapshots with Percy.
PercyScript.run(async (page, percySnapshot) => {
  await page.goto(ALLY_LOGIN);
  let text_content = '';
  await page.goto(HOME_PAGE);
  await page.waitFor('.menu--main');
  text_content = await page.evaluate(() => document.querySelector('.menu--main').textContent);
  expect(text_content).to.have.string('Services');
  text_content = await page.evaluate(() => document.querySelector('div.content h2.h6').textContent);
  expect(text_content).to.equal('City of Portland, Oregon');

  await page.goto(MY_GROUPS);
  await percySnapshot('Ally - My groups');
},
{
  // Ignore HTTPS errors in Lando
  ignoreHTTPSErrors: (typeof process.env.LANDO_CA_KEY !== 'undefined')
});
*/
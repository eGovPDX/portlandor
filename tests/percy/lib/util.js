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

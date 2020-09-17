const PercyScript = require("@percy/script");

PercyScript.run(async (page, percySnapshot) => {
  await page.goto("http://portlandor.lndo.site/");
  // ensure the page has loaded before capturing a snapshot
  await page.waitFor(".menu");
  await percySnapshot("homepage");
});

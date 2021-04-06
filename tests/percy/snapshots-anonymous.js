const SITE_NAME = process.env.SITE_NAME;
const SUPERADMIN_LOGIN = process.env.SUPERADMIN_LOGIN;
const ALLY_LOGIN = process.env.ALLY_LOGIN;
const MARTY_LOGIN = process.env.MARTY_LOGIN;
const HOME_PAGE = SITE_NAME
  ? `https://${SITE_NAME}-portlandor.pantheonsite.io`
  : "https://portlandor.lndo.site";
const LOGOUT = `${HOME_PAGE}/user/logout`;
const MY_GROUPS = `${HOME_PAGE}/my-groups`;
const MY_CONTENT = `${HOME_PAGE}/my-content`;
const percySnapshot = require("@percy/puppeteer");
const timeout = 10000;

describe("Test homepage title", () => {
  beforeAll(async () => {
    await page.goto(HOME_PAGE, {
      waitUntil: "domcontentloaded",
    });
  });

  it(
    "Title of the page",
    async () => {
      const h1Handle = await page.$(".cloudy-homepage__title");
      const html = await page.evaluate(
        (h1Handle) => h1Handle.innerHTML,
        h1Handle
      );
      expect(html).toBe("Welcome to Portland, Oregon");
      await percySnapshot(page, "Anonymous - Home page");
    },
    timeout
  );
});

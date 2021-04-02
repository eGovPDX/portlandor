module.exports = {
  preset: "jest-puppeteer",
  globals: {
    SITE_NAME: process.env.SITE_NAME
  },
  testMatch: ["**/*.test.js"],
  verbose: true
};

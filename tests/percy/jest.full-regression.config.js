module.exports = {
  globals: {
    SITE_NAME: process.env.SITE_NAME,
  },
  testMatch: ["**/__full_tests__/*.js"],
  verbose: true,
  testTimeout: 3*60*1000, // 3 minutes
};

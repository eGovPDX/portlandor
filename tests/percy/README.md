## Quick start
1. Run one-time setup to install dependencies: `lando rebuild -y`
1. Run test locally:
    - Run all `visual_regression` tests (takes ~2 minutes): `lando jest`
    - Run a single `visual_regression` test like `admin.js`: `lando jest admin`
    - Run the `full_regression` tests (takes ~5 minutes): `lando jest-full`
1. No user interaction is needed to run tests in CI environment, the `visual_regression` tests will be run during each build. The `full_regression` tests is configured to only run on certain branch like Release or Master.

## Intro
The test code is written in JavaScript and relies on Puppeteer to interact with the browser and Jest to validate test result and run tests. In CI environment, it also uses Percy to capture and analyse visual differences. It's recommended to learn about [Puppeteer](https://developers.google.com/web/tools/puppeteer/get-started) and [Jest](https://jestjs.io/docs/getting-started) before writing tests.

Tests are divided into two groups. The first group "visual_regression" contains essential tests that must be run during each build. Currently it duplicates the orginal build tests written in Behat. To reduce build time, the test runtime should be limited to under 3 minutes. The second group "full_regression" is a full regression test suite that meant to give the developer or build lead higher confidence that the code change didn't introduce any major regressions. The first implementation of full regression test suite covers CRUD operations on all group content types and media types in a bureau/office group.
## Add test in visual_regression
Tests in the `visual_regression` group are under the folder `__tests__` and organized by users: anonymous, ally, marty, and admin. In CI environment, they are run in parallel to save time, which requires each test **must** be independent and have no side effect on other tests. For example, a test can't depend on other tests to create the content it'll be testing on. The tests for admin was split into `admin.js` and `admin-group.js` to satisfy the build time constraint.

To add a new test:
1. Identify which user the test should run as.
1. Edit the test file. Copy an existing test `it()` and modify the test code inside `async () => {...}`
1. Save file and run the single build test.
1. If the new test passes locally, push it to GitHub and verify it also works in CI. 

## Add test in full_regression
Tests in the `full_regression` group are inside the folder `__full_tests__` and also organized by users: `full_anonymous.js`, `full_ally.js`, `full_marty.js`, `full_admin.js`. Since the full regression test suite must support more complex test scenrios like run a series of tests under different users, most tests are implemented in `full_admin.js` to allow the admin to masquerade as different users without the need to generate a new ULI every time.

`full_admin.js` has these stages:

1. Log in as the site admin.
1. Create a new bureau/office group and add Ally as a member.
1. Run site wide content test like `alert`
1. Masquerade as Ally
1. Run group content test like `page` and `map` as Ally
1. Unmasquerade as Ally and delete the test group created in step 2.

To add a new test:

1. If you'd like to add a test for the admin user. Copy the `alert` test and make it part of stage 3.
1. If you'd like to add a test for Ally, copy a test in stage 5.
1. If you need to add a test for another user, you'll need to:
    - Make sure you are logged in as admin first
    - Masquerade as the new user
    - Run the test
    - Unmasquerade

## Utility file
All utility code are inside `lib/util.js`. Currently there are helper functions to masquerade and unmasquerade a user by email and a JavaScript object `ContentTester` that serves as the prototype object for all tester in `__full_tests__/full_admin.js`. Further code refactoring will be needed as more tests are added.

## Debugging tips
Jest allows users to include or exclude tests. To only run certain test, use `it.only`. To exclude a test, use `it.skip`.

In order to help diagonize failed test, all test code are wrapped in try-catch block to generate a screenshot when an assertions fails or exception being thrown.

When the screenshot is not sufficient to root cause the issue, you can watch the test run locally to see how the test failed. At the top of `__full_tests__/full_admin.js`, you can find some instructions in `BROWSER_OPTION` on how to do this in MacOS. It assumes that both node/npm and Chrome are installed natively.

If watching the test doesn't help, you may need to consult the [official Puppeteer debugging tips](https://developers.google.com/web/tools/puppeteer/debugging) in order to step through your code.
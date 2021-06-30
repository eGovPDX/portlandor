## Quick start

### Installing dependencies
Run one-time setup to install dependencies
```
lando rebuild -y
```

### Running tests
Runs all `visual_regression` tests locally (takes ~2 minutes)
```
lando jest
```

Runs a single `visual_regression` test like `admin.js`
```
lando jest admin
```

Runs the `full_regression` tests locally (takes ~5 minutes)
```
lando jest-full
```

The `visual_regression` tests will be run during each build.

The `full_regression` test is configured to only run on certain branch like Release or Master.

## Intro
The test code is written in JavaScript and relies on Puppeteer to interact with the browser and Jest to validate test result and run tests. In CI environment, it also uses Percy to capture and analyse visual differences. It's recommended to learn about [Puppeteer](https://developers.google.com/web/tools/puppeteer/get-started) and [Jest](https://jestjs.io/docs/getting-started) before writing tests.

Tests are divided into two groups:

- `visual_regression`
- `full_regression`

The first group `visual_regression` contains essential tests that must be run during each build. Currently it duplicates the original build tests written in Behat. To reduce build time, the test runtime should be limited to under 3 minutes.

The second group `full_regression` is a full test suite that is meant to give the developer or build lead higher confidence that the code change didn't cause any major regressions. The first implementation covers the CRUD operations on all group content types and media types in a bureau/office. More tests will be added in the future.

IMPORTANT: Tests **must** be independent and have no side effect in other tests.
## Writing tests
Tests in the `visual_regression` group are under the folder `__tests__` and organized by the following users:
- anonymous
- ally
- marty
- admin

### Add visual_regression test
To add new test:
1. Identify which user the test should run as.
2. Edit the test file. Copy an existing test `it()` and modify the test code inside `async () => {...}`
3. Save file and run the single build test `lando jest [filename]`
4. If the new test passes locally, push it to GitHub and verify it also works in CI.

### Add full_regression test
Tests in the `full_regression` group are inside the folder `__full_tests__` and also organized by the following users:
- anonymous
- ally
- marty
- admin

Since the full regression test suite must support more complex test scenarios like run a series of tests under different users, most tests are implemented in `full_admin.js` to allow the admin to masquerade as different users without the need to generate a new ULI every time.

`full_admin.js` test performs the following:

1. Log in as the site admin.
2. Create a new bureau/office test group and add Ally as a member.
3. Run site wide content test like creating an `alert`
4. Masquerade as Ally
5. Run group content test like creating a `page` and `map` as Ally
6. Unmasquerade as Ally and delete the test group created in step 2.

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

In order to help diagnose a failed test, all test code is wrapped inside try-catch blocks which generates a screenshot when an assertion fails or an exception is thrown.

When the screenshot is not sufficient to root cause the issue, you can watch the test run inside your natively installed Chrome browser.

At the top of `__full_tests__/full_admin.js`, you can find instructions inside `BROWSER_OPTION` on how to enable this in MacOS.

IMPORTANT: both node/npm and Chrome must be installed natively to launch tests in watch mode.

If watching the test doesn't help, you may need to consult the [official Puppeteer debugging tips](https://developers.google.com/web/tools/puppeteer/debugging) in order to step through your code.

## Continuous Integration

In CI environment, tests are run in parallel to save time. Note that admin tests are split into `admin.js` and `admin-group.js` to keep individual test execution time under 3 minutes.

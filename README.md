[![CircleCI](https://circleci.com/gh/eGovPDX/portlandor.svg?style=svg)](https://circleci.com/gh/eGovPDX/portlandor)

# Portland.gov Drupal Site

## Get the code

`git clone git@github.com:eGovPDX/portlandor.git` will create a folder called `portlandor` whereever you run it and pull down the code from the repo.

## Installing Lando

Follow the steps at https://docs.devwithlando.io/installation/installing.html

If developing on Windows, using Docker Desktop + WSL2 is strongly recommended.

## Uninstalling Lando

Follow the steps at https://docs.devwithlando.io/installation/uninstalling.html
To completely remove all traces of Lando, follow the "removing lingering Lando configuration" steps

## Using Lando

The .lando.yml file included in this repo will set you up to connect to the correct Pantheon dev environment for the Portland Oregon website. To initialize your local site for the first time, follow the steps below:

1. From within your project root directory, run `lando start` to initialize your containers.
2. Log in to Pantheon and generate a machine token from My Dashboard > Account > Machine Tokens.
3. Run `lando terminus auth:login --machine-token=[YOUR MACHINE TOKEN]`, this logs your Lando instance into our Pantheon account.
4. To make sure you don't hit rate limits with composer, log into Github and generate a personal access token and add it to your lando instance by using `lando composer config --global --auth github-oauth.github.com "$COMPOSER_TOKEN"`. (You should replace \$COMPOSER_TOKEN with your generated token.)
   1. Generate a token here: https://github.com/settings/personal-access-tokens/new
   2. Name the token something descriptive like "portlandor composer token."
   3. Set the expiration as long as you want, preferably a year.
   4. Under Repository Access, you can select *Public Repositories (read-only)*. The token doesn't need any access but just authenticates you to GitHub.
5. **If this is a new clone of the repo:** before continuing to the next step you must run `lando composer install` and `lando yarn install` to install the appropriate dependencies.
6. You have three options to get your database and files set up:
   1. If you're logged in with Terminus, run `lando latest` to automaticaly download and import the latest DB from Dev.
   2. Manually import the database
      1. Download the latest database from the Dev environment on Pantheon. (https://dashboard.pantheon.io/sites/5c6715db-abac-4633-ada8-1c9efe354629#dev/backups/backup-log)
      2. Move your database export into a folder in your project root called `/artifacts`. (We added this to our .gitignore, so the directory won't be there until you create it.)
      3. Run `lando db-import artifacts/portlandor_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
    3. (optional) Download the latest files from the Dev environment on Pantheon. (https://dashboard.pantheon.io/sites/5c6715db-abac-4633-ada8-1c9efe354629#dev/backups/backup-log)
        1. Move your files backup into `web/sites/default/files`
        2. *This step should only be required if you are doing specific development that requires the filesystem to be copied locally. Otherwise, stage_file_proxy will handle it. The files backup is many GBs.*
7. Run `git checkout master` and `lando refresh` to build your local environment to match the `master` branch. (This runs composer install, drush updb, drush cim, and drush cr.)
8. You should now be able to visit https://portlandor.lndo.site in your browser.
9. To enable XDebug, run `lando xdebug-on`. Run `lando xdebug-off` to turn it off for increased performance.
10. When you are done with your development for the day, run `lando stop` to shut off your development containers or `lando poweroff` if you want to stop all lando containers.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Local development mode

By default the site runs in "development" mode locally, which means that caching is off and twig debugging is on, etc. These settings are managed in web/sites/default/local.services.yml. While it is possible to update these settings if the developer wishes to run the site with caching on and twig debug off, updates to this file should never be committed in the repo, so that developers are always working in dev mode by default.

## Workflow for this repository

We are using a modified version of GitHub Flow to keep things simple. While you don't need to fork the repo if you are on the eGov dev team, you will need to issue pull requests from a feature branch in order to get your code into our `master` branch. Master is a protected branch and is the default branch for new PRs. We use `master` to build up commits until we're ready to deploy everything from `master` (Pantheon Dev) into the Test environment using the Pantheon dashboard.

To best work with Pantheon Multidev, we are going to keep feature branch names simple, lowercase, and under 11 characters.

### Start new work in your local environment

```bash
git checkout master
git pull origin master
lando latest
lando refresh
git checkout -b pgov-[ID]
```

<details>
<summary>Explainer:</summary>
1. Verify you are on the master branch with `git checkout master`.
2. On the master branch, run `git pull origin master` or just `git pull` if you only have the one remote. This will make sure you have the latest changes from the remote `master` branch. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
3. Use the issue ID from Jira for a new feature branch name to start work: `git checkout -b pgov-[ID]` to create and checkout a new branch. (We use lowercase for the branch name to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout pgov-[ID]` to switch to your branch. If you need to create multiple multidevs for your story, name your additional branches `pgov-[ID]-[a-z or 1-9]` (but continue to use just `PGOV-[ID]` in the git commits and PR titles for all branches relating to that Jira story).

    **TLDR:**

    - New feature branch
        ```
        git checkout -b pgov-[ID]
        ```
    - New branch based off current feature branch
        ```
        `pgov-[ID]-[a-z or 1-9]
        ```
    - Use base issue ID for all commits and PR titles
        ```
        PGOV-[ID]

        // on base branch pgov-123
        git commit -m "PGOV-123 ..."

        // on pgov-123-a branched from pgov-123
        git commit -m "PGOV-123 ..."
        ```
4. Run `lando latest` at the start of every sprint to update your local database with a copy of the database from Dev.
5. Run `lando refresh` to refresh your local environment's dependencies and config. (This runs composer install, drush updb, drush cim, and drush cr.)
6. You are now ready to develop on your feature branch.

</details>

### Commit code and push to Github

1. In addition to any custom module or theme files you may have edited, you need to export any configuration changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local environment to create/update/delete the necessary config files. You will need to commit these to git.
   1. If you are updating any modules through a `composer update` command, you will need to run `lando drush updb` and then `lando drush cex` to capture any config schema changes. After creating these changes, you need to commit them.
2. To commit your work, run `git add -A` to add all of the changes to your local repo. (If you prefer to be a little more precise, you can `git add [filename]` for each file or several files separated by a space.
3. Then create a commit with a comment, such as `git commit -m "PGOV-[ID] description of your change."` **Every commit message should be prefixed with the Jira story ID.** This ties those commits to the Jira issue and makes it easier to review git history.
4. Just before you push to GitHub, you make sure your local branch is up to date with `master`.
   1. The easiest way to do this is by running `git pull --rebase=interactive origin master`. This will fetch the latest master branch, and rebase your branch on top of it.
   2. If there are conflicts, it will let you "interactively" replay your change on the tip of the current master branch. You'll need to pick, squash or drop your changes and resolve any conflicts to get a clean commit that can be pushed to release. You may need to `git rebase --continue` until all of your changes have been replayed and your branch is clean.
5. Run `lando refresh` to refresh your local environment with any changes you just pulled from `master`. This will help you identify if you completed your `rebase` or `merge` correctly.
6. You can now run `git push -u origin pgov-[ID]`. This will push your feature branch and set its remote to a branch of the same name on origin (GitHub).

### Create a pull request

When your work is ready for code review and merge:

1. Create a Pull Request (PR) on GitHub for your feature branch. This will default to the `master` branch—but you may also choose to create a PR against a long running feature branch that will later have a PR to `master`. Work with the build lead to determine the strategy for your story.
2. Make sure to include PGOV-[ID] and a short description of the feature in your PR title so that Jira can relate that PR to the correct issue. For example, "PGOV-123 Create taxonomy term for neighborhoods."

### Continuous integration on CircleCI

1. The PR creation triggers an automated CircleCI build, deployment, and test process that will:
   - Check out code from your working branch on GitHub.
   - Run `composer install`.
   - Deploy the site to a multidev feature branch site on Pantheon.
   - Run `drush cim` to import config changes.
   - Run `drush updb` to update the database.
   - Run `drush cr` to rebuild the caches.
   - Run smoke tests against the feature branch site to make sure the build was successful.
2. If the build fails, you can go back to your local project and correct any issues and repeat the process of getting a clean commit pushed to GitHub. Once a PR exists, every commit to that branch will trigger a new CircleCI build. You only need to run `git push` from your branch if it is already connected to the remote, but you'll probably want to rebase/merge master again if the time between pushes is anything more than a day.
3. The CI job `visual_regression` runs tests under different users in parallel. When a functional test fails, click on the `Details` link next to the job `ci/circleci: visual_regression` to review CI log for error messages and remediate.

### Prep Pantheon MultiDev site and create a test plan

1. Once the CircleCI build is complete, a multidev site will exist at `pgov-xxxx-portlandor.pantheonsite.io` - `xxxx` being your Jira issue ID.
2. You'll need to prep anything on the multidev site that is needed for QA to complete the test plan. For example, create any necessary content or groups.
3. In Jira, create/update the test plan for your issue and include the URL to the multidev.
4. As the developer, run through your full test plan on the multidev site. This is a chance to see if anything functions differently than your local environment, and ensure your test plan is correct. This can reduce unnecessary back and forth and get your branch merged faster!
5. Assuming your own QA went well and you've remediated any issues, move the issue to "QA" in Jira for another member of the team to pick up.

### Taking an issue for QA

If you're picking up someone else's issue to QA, here are the steps:

1. Execute the test plan step by step, and any of your own tests you can think of! (As Rick likes to say, "try and break it! Be creative!")
2. If defects are found, communicate with the developer and move the issue back to "Todo" in Jira and assign it back to the developer. Document your findings as a comment on the issue and decide with the developer if you need to open bugs to address in the future or if they should be addressed before merging.
3. If no defect is found, move the issue to "UAT" (if needed) or directly to "PR Review" in Jira and assign it to the build lead.

### Move to Merge and Accept

Once your QA has passed, go back to your PR in GitHub and assign at least one team member as a reviewer. Reviews are required before the code can be merged. We are mostly checking to make sure the merge is going to be clean, but if we are adding custom code, it is nice to have a second set of eyes checking for our coding standards.

## Build lead

There are a few extra steps for the assigned build lead. This person is the final sanity check before merging in changes to the Dev, Test and Live instances on Pantheon. Basically the Dev and Test deploys are an opportunity to practice and test the deployment of the feature.

## Bundling a release and deploying to Pantheon Dev site

1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the "Squash and merge" button and commit the work to the `master` branch.
    - Make sure the PR has `master` set as the base branch and that the merge message is prepended with the Jira issue ID (e.g. "PGOV-123 Adding the super duper feature")
   - The merge triggers an automated CircleCI build on the Dev environment.
2. Test that everything still works on the Dev site. This is just a sanity check since a QA has already been performed.
   - Can you confirm the expected code changes were deployed?
   - If all work from the issue is merged and the build was successful, you can move the issue to the Done column on our Jira board, assign it back to the developer who did the work, and delete the feature branch from Github.
3. Repeat steps 1-2 to merge additional PRs until you've bundled all of the changes together that you want to go into the next "deployment" to Test, and Live.
4. Before the last merge to `master` for the desired deployment. Clone the `live` database to `dev` using the following command: `lando terminus env:clone-content employees.live dev`
5. After the clone is complete, merging to master will trigger an automated CircleCI build, deployment, and test process on the Dev environment similar to the multidev deployment.
    - Verify that the CircleCI build on Dev is successful and passes our automated tests.

### Releases to Test (or Production)

We are using the Dev environment to bundle all the approved code together into a single release which can then be deployed to Test, and Live to make sure things are solid. At least once per sprint, or more frequently as needed, our combined changes should be deployed to the Test and Live environments. The test deployment is essentially the last check to see if our code will be safe on Production and build correctly as the Pantheon Quicksilver scripts operate in a slightly different environment than CircleCI's Terminus commands.

1. Go to the Pantheon dashboard and navigate to the Test environment.
2. Under Deploys, you should see that the code you just deployed to Dev is ready for Test. Check the box to clone the Live database to Test and then merge that code and run the build on Test. You should make sure and provide a handy release message that tells us a little about what is included. Copy/paste the PR titles from the merged feature branches to construct release notes. E.g.:
   - **Portland.gov Sprint 120 Release 1**
   - \- PGOV-123 added super duper feature (#1111)
3. After clicking deploy, smoke test your deployment by visiting the configuration sync and status report pages under administration. If config is not imported, it may be necessary to synchronize the configuration by running `lando terminus drush portlandor.test cim -y`. Never use the Drupal configuration synchronization admin UI to run the config import. (There be dragons... and the Drush timeout is significantly longer than the UI timeout. The UI timeout can lead to config coruption and data loss.)
4. Verify that everything still works on Test.

Once a deployment to Test has been tested and passes, the same set of changes should be promptly deployed to Production by following the same basic procedure above.

## Theme

Note: The theme build process is automatically triggered everytime your Lando containers start up and whenever you run `lando refresh` so you should only need to manually run it if you're editing the .scss or .js source files.

You can run `lando npm install` if you need to install/update your Node dependencies.

### Quick Start

Here are some commands you may find useful:

- Run `lando npm run watch` to watch for changes and automatically build, when actively developing the theme.
- Run `lando npm run build` to build the theme minified as it would be in production.

### Cloudy's asset files

Note: Make modifications to the desired SCSS and JavaScript files in the theme. Never modify `cloudy.bundle.css`, `cloudy.bundle.js`, or anything in the `dist` direction directly. We build these files as part of our CI, you should run the watch or build script locally to compile these files.

### Webpack output

The following is a snippet of Webpack build output from a successful build:

```
hidden assets 922 KiB 8 assets
assets by path . 1.22 MiB
  assets by path *.js 97.9 KiB
    asset bootstrap.bundle.js 82.2 KiB [compared for emit] (name: bootstrap) 1 related asset
    asset cloudy.bundle.js 10.2 KiB [compared for emit] (name: cloudy) 1 related asset
    asset header-dropdown-toggle.bundle.js 2.05 KiB [compared for emit] (name: header-dropdown-toggle) 1 related asset
    asset back-to-top.bundle.js 1.34 KiB [compared for emit] (name: back-to-top) 1 related asset
    asset search-autocomplete.bundle.js 1.04 KiB [compared for emit] (name: search-autocomplete) 1 related asset
    asset search-field.bundle.js 1020 bytes [compared for emit] (name: search-field) 1 related asset
    asset cloudy-ckeditor.bundle.js 204 bytes [compared for emit] (name: cloudy-ckeditor) 1 related asset
  assets by path *.css 1.13 MiB
    asset cloudy-rtl.bundle.css 404 KiB [compared for emit] [minimized] (name: cloudy, cloudy-ckeditor)
    asset cloudy-ckeditor.bundle.css 404 KiB [compared for emit] [minimized] (name: cloudy-ckeditor) 1 related asset
    asset cloudy.bundle.css 346 KiB [compared for emit] [minimized] (name: cloudy) 1 related asset
webpack 5.77.0 compiled successfully in 10581 ms
```

The output lists all of the assets that webpack has output. It can be helpful to look at the bundle sizes and see if there's any opportunity to optimize or trim out unused code/styles.

### Troubleshooting

The following is an example of a Webpack build that fails:

```
assets by status 1 MiB [cached] 15 assets

ERROR in ./src/cloudy-ckeditor.scss (./src/cloudy-ckeditor.scss.webpack[javascript/auto]!=!./node_modules/css-loader/dist/cjs.js??ruleSet[1].rules[1].use[1]!./node_modules/postcss-loader/dist/cjs.js??ruleSet[1].rules[1].use[2]!./node_modules/sass-loader/dist/cjs.js??ruleSet[1].rules[1].use[3]!./src/cloudy-ckeditor.scss)
Module build failed (from ./node_modules/sass-loader/dist/cjs.js):
SassError: expected "{".
   ╷
10 │     padding-left: ($cloudy-space-4 * 1.5);
   │                                          ^
   ╵
  src/components/_file.scss 10:42  @import
  src/css/_components.scss 15:9    @import
  src/_theme-imports.scss 67:9     load-css()
  src/cloudy-ckeditor.scss 30:3    root stylesheet
 @ ./src/cloudy-ckeditor.scss
```


Often the last few lines with the stacktrace are the most important, and tell you where the error is found. Here, we can see the source of the error at the top of the stacktrace: a missing bracket in `src/components/_file.scss`.

## Using Composer

Composer is built into our Lando implementation for package management. We use it primarily to manage Drupal contrib modules and libraries.

Here is a good guide to using Composer with Drupal 8: https://www.lullabot.com/articles/drupal-8-composer-best-practices

Composer cheat sheet: https://devhints.io/composer

### Installing contrib modules

Use `lando composer require drupal/[module name]` to download contrib modules and add them to the composer.json file. This ensures they're installed in each environment where the site is built. Drupal modules that are added this way must also be enabled using the `lando drush pm:enable [module name]` command.

### Updating contrib modules and lock file

In general it's a good practice to keep dependencies updated to latest versions, but that introduces the risk of new bugs from upstream dependencies. Updating all dependencies should be done judiciously, and usually only at the beginning of a sprint, when there's adequate time to regression test and resolve issues. Individual packages can be updated as needed, without as much risk to the project.

To update all dependencies, run `lando composer update`. To update a specific package, for example the Devel module, run `lando composer update --with-dependencies drupal/devel`. After updating, make sure to commit the updated composer.lock file.

The composer.lock file contains a commit hash that identifies the exact commit version of the lock file and all dependencies' dependencies. You can think of it as a tag for the exact combination of dependencies being committed, and it's used to determine whether composer.lock is out of date.

When something changes in composer.json, but the lock hash has not been updated, you may receive the warning:
...
The lock file is not up to date with the latest changes in composer.json. You may be getting outdated dependencies. Run update to update them.
...

To resolve this, run `lando composer update --lock`, which will generate a new hash. If you encounter a conflict on the hash value when you merge or rebase, use the most recent (yours) version of the hash.

## Run Jest tests
Tests can be found under `tests/percy/__tests__/`. In order to reduce the time spent in switching user sessions, tests are generally organized by users. The same tests can be run in both local and CI environments.

### Run tests locally
Run `lando yarn-percy` to install or update all dependencies required by Jest.

Run all tests at once: `lando jest`

Run a single test: `lando jest [YOUR_TEST_NAME]`. Currently there are 5 tests available: admin, admin_group, marty, ally, and anonymous.

You can run the full regression text as done by the Dev CI build by running: `lando jest-full`.

### Run tests in CI
All tests will be run in a CI job. When a test fails, a screenshot of the last visited page can be found in the artifacts.


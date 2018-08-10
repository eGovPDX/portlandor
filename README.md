# Portland Oregon Drupal 8 Site

## Get the code

`git clone git@github.com:eGovPDX/portlandor.git` will create a folder called `portlandor` whereever you run it and pull down the code from the repo.

## Git setup for our Windows developers

Windows handles line endings differently than *nix based systems (Unix, Linux, macOS). To make sure our code is interoperable with the Linux servers to which they are deployed and to the local Linux containers where you develop, you will need to make sure your git configuration is set to properly handle line endings.

We want the repo to correctly pull down symlinks for use in the Lando containers.git. To do this, we will enable symlinks as part of the cloning of the repo.

`git clone -c core.symlinks=true git@github.com:eGovPDX/portlandor.git`

`git clone` and `git checkout` must be run as an administrator in order to create symbolic links

Run `git config core.autocrlf false` to make sure this repository does not try to convert line endings for your Windows machine.

## Installing Lando

1. Follow the steps at https://docs.devwithlando.io/installation/installing.html

## Using Lando

The .lando.yml file included in this repo will set you up to connect to the correct Pantheon dev environment for the Portland Oregon website. To initialize your local site for the first time, follow the steps below:

1. From within your project root directory, run `lando start` to initialize your containers.
2. Log in to Pantheon and generate a machine token from My Dashboard > Account > Machine Tokens.
3. Run `lando terminus auth:login --machine-token=[YOUR MACHINE TOKEN]`, this logs your Lando instance into our Pantheon account.
4. To make sure you don't hit rate limits with composer, log into Github and generate a personal access token and add it to your lando instance by using `lando composer config --global --auth github-oauth.github.com "$COMPOSER_TOKEN"`. (You should replace $COMPOSER_TOKEN with your generated token.) There is a handy tutorial for this at https://coderwall.com/p/kz4egw/composer-install-github-rate-limiting-work-around
6. Add a settings.local.php
    - Ask a teammate for a copy of a sane file to put at `/web/sites/default/settings.local.php`. Depending on whether you do a lot of theming you may want that file to turn on twig debugging and turn off several layers of caching.
5. You have two options to get your database and files set up:
    1. Lando quick start:
        1. Run `lando pull` to get the DB and files from pantheon. This process takes a while. #grabsomecoffee
    1. Manually import the database and files
        1. Download the latest database and files backup from the Dev environment on Pantheon. (https://dashboard.pantheon.io/sites/5c6715db-abac-4633-ada8-1c9efe354629#dev/backups/backup-log)
        2. Move your database export into a folder in your project root called `/artifacts`. (We added this to our .gitignore, so the directory won't be there until you create it.)
        3. From the within the artifacts directory, run `lando db-import portlandor_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.) 
        4. Move your files backup into `web/sites/default/files`
7. Build your local environment to match master by running the following commands
    1. `lando composer install` tells composer to build all of your dependencies that we don't keep in our repo. `vendor`, `web/modules/contrib`, `web/themes/contrib` are some examples of files that we pull from their sources rather than keep a really large git repo.
    1. `lando drush cim` (or `lando drush config-import`) tells drush to import configuration changes that are in the codebase, but not in the development database you just pulled down.
8. You should now be able to visit https://portlandor.lndo.site in your browser.
9. When you are done with your development for the day, run `Lando stop` to shut off your development containers.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Workflow for this repository

We are using a modified version of GitHub Flow to keep things simple. While you don't need to fork the repo if you are on the eGov dev team, you will need to issue pull requests from a feature branch in order to get your code into `master`. Master is a protected branch. 

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

### Start new work in your local environment

1. Verify your are on the master `git checkout master`.
1. On the master branch, run `git pull origin master`. This will make sure you have the latest changes from the remote master. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
1. Use the issue ID from Jira for a new feature branch name to start work: `git checkout -b powr-[ID]` to create and checkout a new branch. (We use lowercase for the branch name to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout powr-[ID]` to switch to your branch.
1. At the start of every sprint you should update your local database with a copy of the database from Dev.
    1. Go to the Pantheon dashboard for portlandor
    1. Got to Backups for the Dev environment
    1. Download the latest database backup to the /artifacts directory of your local project
    1. From within the artifacts directory, run `lando db-import portlandor_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
1. Build your local environment to match master by running the following commands
    1. `lando composer install`
    1. `lando drush cim` (or `lando drush config-import`)
1. You are now ready to develop on your feature branch.

### Commit code and push to Github

1. In addition to any custom modules or theming files you may have created, you need to export any configuraiton changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local envionrment to create/update/delete the necessary config files. You will need to commit these add to git.
1. To commit your work, run `git add -A` to add all of the changes to your local repo. (If you prefer to be a little more precise, you can `git add [filename]` for each file or several files separated by a space.
1. Then create a commit with a comment, such as `git commit -m "POWR-[ID] description of your change."`
1. Just before you push to GitHub, you should rebase your feature branch on the latest in the remote master. To do this run `git fetch origin master` then `git rebase -i origin/master`. This lets you "interatively" replay your change on the tip of a current master branch. You'll need to pick, squash or drop your changes and resolve any conflicts to get a clean commit that can be pushed to master. You may need to `git rebase --continue` until all of your changes have been replayed and your branch is clean.
1. You can now run `git push -u origin powr-[ID]`. This will push your feature branch and set its remote to a branch of the same name on origin (GitHub).

### Create a pull request
When your work is ready for code review and merge:
- Create a Pull Request (PR) on GitHub for your working branch.
- Make sure to include POWR-[ID] and a short description of the feature in your PR title so that Jira can relate that PR to the correct issue.

### Continuous integration on CircleCI
   
1. The PR creation triggers an automated CircleCI build, deployment, and test process that will:
    - Check out code from your working branch on GitHub.
    - Run `composer install`.
    - Deploy the site to a multidev feature branch site on Pantheon. 
    - Run `drush cim` to import config changes.
    - Run `drush updb` to update the database.
    - Run `drush cr` to rebuild the caches.
    - Run smoke tests against the feature branch site to make sure the build was successful.
1. If the build fails, you can go back to your local project and correct any issues and repeat the process of getting a clean commit pushed to GitHub. Once a PR exists, every commit to that branch will trigger a new CircleCI build. You only need to run `git push` from your branch if it is already connected to the remote, but you'll probably want to step through the rebase steps if the time between pushes is anything less than a couple of minutes.

### Pantheon MultiDev site

1. You'll need to prep anything on the multidev site that is needed for QA to complete the test plan. This is also a chance to see if you need to address any issues with an additional commit.
1. In Jira, update the test plan of your issue including the URL to the feature branch. Move the issue to "QA" and assign the issue to the QA team.

### QA
 
1. Executes the test plan step by step. (As Rick likes to say, "try and break it! Be creative!")
1. If defects are found, communicate with the developer and move the issue back to "Todo" in Jira and assign it back to the developer. Document what you find as comments on the issue and decide with the developer if you need to open bugs to address in the future or if you should address the issue now.
1. If no defect is found, move the issue to "Merge and Accept" in Jira and assign it to the build master.

### Move to Merge and Accept

Go back to your PR in GitHub and make sure to assign at least one team member as a reviewer. Reviews are required before the code can be merged. We are mostly checking to make sure the merge is going to be clean, but if we are adding custom code, it is nice to have a second set of eyes checking for our coding standards.

## Build master

There are a few extra steps for the assigned build master. This person is the final sanity check before merging in changes to the Dev, Test and Live instances on Pantheon. Basically the Dev and Test deploys are an opportunity to practice and test the deployment of the feature.

### Merge on Github and deploy to Pantheon DEV site

1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the "Merge" button and commit the work to the master. Make sure the merge message is prepended with the Jira issue ID (e.g. "POWR-42 Adding the super duper feature")
    - The merge triggers an automated CircleCI build, deployment, and test process on the Dev environment similar to the multidev deployment.
1. Test that everything still works Dev. This is just a sanity check since a QA has already been performed.
    - Did all the config get imported?
    - Can you confirm the expected code changes were deployed?
    - Is the status report clean on Dev? Any database updates not trigger?
    - Do permissions need to be rebuilt?
1. If all work from the issue is merged and the issue has been moved to the done column on our Jira board, you may delete the feature branch from Github.

### Releases to test (or production)
We are using the dev environment to integrate all the approved code from master and make sure things are solid. At least once per week, or more frequently as needed, our combined changes should be pushed to the test environment. This deployment essentially tells us if our code will be safe on production.

1. Go to the Pantheon dashboard and navigate to the Test environment.
1. Under Deploys, you should see that the code you just deployed to Dev is ready for Test. Merge that code and run the build on Test. You should make sure and provide a handy release message that tells us a little about what is included. As we get more mature with this process, we'll include instructions for naming the release.
1. Go to the Test website and synchronize the configuration at /admin/config/development/configuration or run a `terminus remote:drush -- cim -y` if you have Terminus installed and if you are not on the City network. (We are working to get this part fixed.)
1. Test that everything still works on Test.

Once production is live, we should plan to deploy to production at least once per sprint—if not more frequently.

## Local build process for theme files

There is a separate build process that is run in local development environments, to compile template files such as CSS and JS. The resulting files, in addition to the modified source files, are committed to the repo and deployed to upstream environments. They are always built locally, never as part of the main build process we've implemented with CircleCI.

### Install dependencies 

1. Install Node.js using the Mac or Windows installer available at https://nodejs.org.
1. Open a terminal window (PowerShell on Windows) and run `npm install -g gulp-cli` to install gulp.js. Administrator privileges are not required.
1. Navigate to the project theme directory (portlandor/web/themes/custom/cloudy)
1. Install project dependencies by running `npm install` 

### Using gulp.js to build CSS files

1. Make modifications to the desired scss files in the theme. Never modify styles.css directly.
1. In the root directory of the theme, run `gulp sass` to build just the CSS a single time, or run `gulp js` to build just the JS files.
1. OR, in the root directory of the theme, run `gulp` to launch the gulp server, which watches for changes and rebuilds the CSS and JS whenever the source files are updated.
1. Commit both the updated source files and the rebuilt CSS and JS files using the standard project workflow.

#### Troubleshooting

**ERROR (Windows): "The term 'gulp' is not recognized as the name of a cmdlet, function, script file, or operable program..."**
This indicates one of two potential problems:
1. Node.js was not added to the Windows Path. See: https://stackoverflow.com/questions/8768549/node-js-doesnt-recognize-system-path#8768567
1. There is a conflict between your normal and _adm profiles on your Windows workstation relating to where gulp.js files were installed. Even if you installed gulp while running PowerShell as administrator, you may need to launch a new PowerShell window without administrator rights to get gulp to run.



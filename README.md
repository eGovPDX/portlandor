# Portland Oregon Drupal 8 Site

## Git setup for our Windows developers

Windows handles line endings differently than *nix based systems (Unix, Linux, macOS). To make sure our code is interoperable with the Linux servers to which they are deployed and to the local Linux containers where you develop, you will need to make sure your git configuration is set to properly handle line endings.

Run `git config core.autocrlf false` to make sure this repository does not try to convert line endings for your Windows machine.

Additionally, we want the repo to correctly pull down symlinks for use in the Lando containers.git. To do this, we will enable symlinks as part of the cloning of the repo.

`git clone -c core.symlinks=true git@github.com:eGovPDX/portlandor.git`

## Using this repo

1. Clone this repo.
2. You should now be able to issue `git push` to commit to the remote GitHub repo.

Keep in mind, that you want to follow the development workflow below in order to keep our repo clean and master continously ready for deploy to production.

## Installing Lando

1. Follow the steps at https://docs.devwithlando.io/installation/installing.html

## Using Lando

The .lando.yml file included in this repo will set you up to connect to the correct Pantheon dev environment for Portland Oregon Alpha. To initialize your local site for the first time, follow the steps below:

1. Run `lando start` to initialize your containers.
2. Log in to Pantheon and generate a machine token from My Dashboard > Account > Machine Tokens.
3. Run `lando terminus auth:login --machine-token=[YOUR MACHINE TOKEN]`, this logs your Lando instance into our Pantheon account.
4. To make sure you don't hit rate limits with composer, log into Github and generate a personal access token and add it to your lando instance by using `lando composer config --global --auth github-oauth.github.com "$COMPOSER_TOKEN"`. (You should replace $COMPOSER_TOKEN with your generated token.) There is a handy tutorial for this at https://coderwall.com/p/kz4egw/composer-install-github-rate-limiting-work-around
5. Run `lando pull` to get the DB and files from pantheon. This process takes a while. #grabsomecoffee
6. You should now be able to visit https://portlandor.lndo.site in your browser.
7. When you are done with your development for the day, run `Lando stop` to shut off your development containers.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Workflow for this repository

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

1. ### Start new work in your local environment
    1. Verify your are on the master branch by `git branch`. Switch to master if needed `git checkout master`.
    1. On the master branch, run `git pull origin`. This will make sure you have the latest changes. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
    1. Use the issue ID from Jira for a new feature branch name to start work., from the master branch pulled from `github` run `git checkout -b powr-[ID]` to create and checkout a new branch. (We use lowercase to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout powr-[ID]` to switch to your branch.
    1. At the start of every sprint you should update your local database with a copy of the database from Dev. 
        1. Go to the Pantheon dashboard for portlandor
        1. Got to Backups for the Dev environment
        1. Download the latest database backup to the /artifacts directory of your local project
        1. From the artifacts directory run `lando db-import portlandor_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
    1. Develop to you heart's content. 
2. ### Commit code to Github
    1. In addition to any custom modules or theming files you may have created, you need to export any configuraiton changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local envionrment to create/update/delete the necessary config files.
    1. To commit your work, run `git add -A` to add all of the changes to your local repo. Then create a commit with a comment, such as `git commit -m "POWR-[ID] description of your change."`
3. ### Continuous integration on CircleCI
    1. When your work is ready for code review and merge:
        - Create a Pull Request (PR) on GitHub for your working branch.
        - Make sure to include POWR-[ID] in your PR title so that Jira can relate that PR to the correct issue.
    1. The PR creation triggers an automated CircleCI build, deployment, and test process:
        - Check out code from your working branch on GitHub.
        - Run `composer install`.
        - Deploy the site to a multidev feature branch site on Pantheon. 
        - Run Behat tests against the feature branch site.
    1. In Jira, move the issue to "QA" and assign the issue to the QA team. Update the test plan when needed. Put the URL to the feature branch site in the test plan.
4. ### Test on Pantheon MultiDev site
    1. The QA team executes the test plan.
    1. If no defect is found, move the issue to "Merge and Accept" in Jira and assign it to the build master.
    1. If defects are found, communicate with the developer and move the issue back to "Todo" in Jira and assign it back to the developer.
5. ### Merge on Github and deploy to Pantheon DEV site
    1. Make sure to assign at least one team member as a reviewer. Reviews are required before the code can be merged.
    1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the merge button and commit the work to the repo. Make sure the merge message is prepended with the Jira issue ID (e.g. "POWR-42 Adding the super duper feature")
    1. The merge triggers an automated CircleCI build, deployment, and test process:
        - Check out code from the master branch on GitHub.
        - Run `composer install`.
        - Deploy the site to the Pantheon DEV site.
        - Delete the Pantheon Multidev site for the feature branch that is merged into the master branch.
        - Run Behat tests against the Pantheon DEV site.
    
## Build master

There are a few extra steps for the assigned build master. This person is the final sanity check before merging in changes to the Dev, Test and Live instances on Pantheon. Basically the Dev and Test deploys are an opportunity to practice and test the deployment of the feature.

1. Test that everything still works Dev
1. Go to the Pantheon dashboard and navigate to the Test environment.
1. Under Deploys, you should see that the code you just deployed to Dev is ready for Test. Merge that code and run the build on Test.
1. Go to the Test website and synchronize the configuration at /admin/config/development/configuration
1. Test that everything still works on Test
1. If all work from the issue is merged and the issue has been moved to the done column on our Jira board, you may delete the feature branch from Github.

TODO: some of this can be automated further—and should be for the build master's santity.
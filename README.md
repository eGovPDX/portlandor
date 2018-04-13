# Portland Oregon Drupal 8 Site

## Git setup for our Windows developers

Windows handles line endings differently than *nix based systems (Unix, Linux, macOS). To make sure our code is interoperable with the Linux servers to which they are deployed and to the local Linux containers where you develop, you will need to make sure your git configuration is set to properly handle line endings.

Run `git config core.autocrlf false` to make sure this repository does not try to convert line endings for your Windows machine.

Additionally, we want the repo to correctly pull down symlinks for use in the Lando containers.git. To do this, we will enable symlinks as part of the cloning of the repo.

`git clone -c core.symlinks=true git@github.com:eGovPDX/portlandor.git`

## Using this repo

1. Clone this repo.
2. Add the Pantheon remote `git remote add pantheon ssh://codeserver.dev.5c6715db-abac-4633-ada8-1c9efe354629@codeserver.dev.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:2222/~/repository.git`
3. Configure `origin` as a multi-remote destination in your `.git/config`
```
[remote "origin"]
    url = ssh://codeserver.dev.5c6715db-abac-4633-ada8-1c9efe354629@codeserver.dev.5c6715db-abac-4633-ada8-1c9efe354629.drush.in:2222/~/repository.git
    url = git@github.com:eGovPDX/portlandor.git
```
4. You should now be able to issue `git push origin` and update both repos.

Keep in mind, that you want to follow the development workflow below in order to keep our repo clean and master continously ready for deploy to production.

## Installing Lando

1. Follow the steps at https://docs.devwithlando.io/installation/installing.html

## Using Lando

The .lando.yml file included in this repo will set you up to connect to the correct Pantheon dev environment for Portland Oregon Alpha. To initialize your local site for the first time, follow the steps below:

1. Run `lando start` to initialize your containers.
2. Log in to Pantheon and generate a machine token from My Dashboard > Account > Machine Tokens.
3. Run `lando terminus auth:login --machine-token=[YOUR MACHINE TOKEN]`, this logs your Lando instance into our Pantheon account.
4. Run `lando pull` to get the DB and files from pantheon. This process takes a while. #grabsomecoffee
5. You should now be able to visit https://portlandor.lndo.site in your browser.
6. When you are done with your development for the day, run `Lando stop` to shut off your development containers.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Workflow for this repository

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

1. ### Start new work
    1. Always start by running `git pull origin` from your master branch. This will make sure you have the latest changes. Optionally, running `git pull -p origin` will prune any local branches not on the remote to help keep your local repo clean.
    1. Use the issue ID from Jira for a new feature branch name to start work., from the master branch pulled from `github` run `git checkout -b powr-[ID]` to create and checkout a new branch. (We use lowercase to help create Pantheon multidev environments correctly.) If the branch already exists, you may use `git checkout powr-[ID]` to switch to your branch.
    1. At the start of every sprint you should update your local database with a copy of the database from Dev. 
        1. Go to the Pantheon dashboard for portlandor
        1. Got to Backups for the Dev environment
        1. Download the latest database backup to the /artifacts directory of your local project
        1. From the artifacts directory run `lando db-import portlandor_dev_2018-04-12T00-00-00_UTC_database.sql.gz`. (This is just an example, you'll need to use the actual filename of the database dump you downloaded.)
    1. Develop to you heart's content. 
2. ### Getting your code ready to share to Github
    1. In addition to any custom modules or theming files you may have created, you need to export any configuraiton changes to the repo in order for those changes to be synchronized. Run `lando drush cex` (config-export) in your local envionrment to create/update/delete the necessary config files.
    1. To commit your work, run `git add -A` to add all of the changes to your local repo. Then create a commit with a comment, such as `git commit -m "POWR-[ID] description of your change."`
3. ### Pantheon
    1. When you are ready to preview a feature on Pantheon or create a PR on Github, you may push the latest commit in your local feature branch via `git push -u origin powr-[id]` and it will trigger the creation of a branch on both Github and Pantheon instance.
    1. To make that branch on Pantheon a multidev site for testing your work, go to the Pantheon dashboard for portlandor, select "Multidev" then "Git Branches" then "Create Environment" from the appropriate git branch. This will trigger Pantheon to make a copy of the Dev environment database and build a site with your branch.
    1. When your work has been set up on the test environment and it is ready for QA, move your issue to the QA column in our Jira board and update the test plan with any additional details that you feel will be helpful.
4. ### Github
    1. When your work is ready to be merged with the master branch, create a pull request using the UI in Github at https://github.com/eGovPDX/portlandor/pulls by clicking "New pull request". Make sure to include POWR-[ID] in your PR title so that Jira can relate that PR to the correct issue.
    1. Make sure to assign at least one team member as a reviewer. Reviews are required before the code can be merged.
    1. After a team member has provided an approval, which may be after responding to feedback and resolving review issues, the build master will be able to push the merge button and commit the work to the repo. Make sure the merge message is prepended with the Jira issue ID (e.g. "POWR-42 Adding the super duper feature")
    
## Build master

There are a few extra steps for the assigned build master. This person is the final sanity check before merging in changes to the Dev, Test and Live instances on Pantheon. Basically the Dev and Test deploys are an opportunity to practice and test the deployment of the feature.

1. Run `git pull origin master` from your local repo to pull down the changes that were just merged into the Github master branch.
1. Run `git push origin master` to push those changes into Pantheon. (We won't need this when CircleCI is properly integrated.)
1. Go to the Dev website and synchronize the configuration at /admin/config/development/configuration
1. Test that everything still works Dev
1. Go to the Pantheon dashboard and navigate to the Test environment.
1. Under Deploys, you should see that the code you just deployed to Dev is ready for Test. Merge that code and run the build on Test.
1. Go to the Test website and synchronize the configuration at /admin/config/development/configuration
1. Test that everything still works on Test
1. If all work from the issue is merged and the issue has been moved to the done column on our Jira board, you may delete the feature branch from Github.
1. At the end of each sprint, we should clean up any completed multidev sites/branches from the Pantheon dashboard.

TODO: some of this can be automated further—and should be for the build master's santity.
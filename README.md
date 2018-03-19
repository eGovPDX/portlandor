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
5. You should now be able to visit https://portland-alpha.lndo.site in your browser.
6. When you are done with your development for the day, run `Lando stop` to shut off your development containers.

See other Lando with Pantheon commands at https://docs.devwithlando.io/tutorials/pantheon.html.

## Development workflow for this repository

To best work with Pantheon Multidev, we are going to keep feature branch names simple and use the master branch as our integration point that builds to the Pantheon Dev environment.

1. Use the issue ID from Jira for a new feature branch name to start work., from the master branch pulled from `github` run `git branch POWR-[ID]`.
2. Develop to you heart's content.
3. When you want to preview your work, you have your local environment. If you want to preview a feature on Pantheon, you may push a feature branch via `git push origin` and it will trigger the creation of a Multidev instance.
4. When your work is ready to be merged with the master branch, create a pull request using the UI in Github. [TODO: add some pictures of these steps]
5. After two team members have provided an approval, which may be after responding to feedback and resolving review issues, you will be able to push the merge button and commit the work to the repo. Make sure you merge message is prepended with the Jira issue ID (e.g. "POWR-42 Adding the super duper feature")
6. TODO: Need to make sure a PR merged to master via Github will also update the Pantheon repo.
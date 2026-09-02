# Sandbox Environment 

## Description

The `sandbox` branch creates a long-living multidev that provides a permanently available sandbox environment that mirrors production where editors can safely practice and use the CMS without fear of messing up the live site. 

This environment can be used for guided training classes, self learning, or experimentation, etc.

## Environment Updates

### Code Syncing

This environment should be a replica of the live site so it needs to be updated as part of the deployment process in order to stay in sync with the live site.

This happens automatically thanks to a Pantheon Quicksilver script that runs on deployments to Live and triggers a GitHub action that rebases the sandbox branch with master.

If you need to do this manually, run these commands to update this branch:

```
git checkout sandbox
git rebase origin/master
git push -f
```

If the master branch is already ahead of the Live site, you'll need to find the commit ID belonging to the Live site deployment. This can be found in the Code section of the Pantheon dashboard. (Ignore the first "CircleCI deployment" commit&mdash;it's the second commit ID you want.) Use this rebase command instead of the above one: `git rebase <commit-ID>`

### Content Syncing

The database content also needs to be periodically updated by cloning the database from Live. (Please note that this will destroy any existing "test content" previously created on the site.)

This happens automatically thanks to a CircleCI trigger that is scheduled to run every Monday morning. This runs the "Update Sandbox Database" CircleCI workflow which executes a Terminus command to clone the Live database to Sandbox, runs `drush deploy`, reindexes Solr, and executes a Percy test that adds the sandbox alert notification to the site.

If you need to do this manually, follow these steps:

1. Run `lando terminus env:clone-content portlandor.live sandbox --db-only`
2. Run `lando terminus drush portlandor.sandbox deploy`
3. Reindex the Solr index(es)
4. Create and publish an informational `Sandbox Site` alert with the following text:

```This sandbox site is a copy of portland.gov and is intended to provide a safe environment for editor training classes, self learning, or experimentation, etc. Changes made here will not affect the live site and cannot be imported into the live site.```

## Sandbox Branch Code Changes

This branch should only have one code change from the `master` branch:
* `sandbox.md` (This file)

Sometimes due to certain types of code changes in master, the automated rebase that keeps this branch's code up to date with master doesn't work perfectly and this branch ends up with inadvertent additional code changes to other files besides `sandbox.md`. Sometimes these extra changes will cause Sandbox builds to fail.

If the Sandbox PR in Github shows that there are more than one files changed, you need to do the following manual cleaup steps in VS Code to remove these extraneous code changes:

1. Run `git checkout sandbox`
2. Run `git pull --rebase`
3. In the VS Code source control panel, click the &hellip; "More actions" icon and select Commit->Undo Last Commit
4. The source control panel will now show all of the changed files under the "Staged Changes" section. Click the &mdash; icon next to each file to unstage all changes except `sandbox.md`. This moves the changed files to the "Changes" section.
5. Click the &#8634; icon to discard all of the changes in the "Changes" section. This should leave the future commit with only one staged change: `sandbox.md`. Commit the change.
6. Run `git push -f`

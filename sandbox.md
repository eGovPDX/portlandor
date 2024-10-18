# Sandbox Environment 

## Description

The `sandbox` branch creates a long-living multidev that provides a permanently available sandbox environment that mirrors production where editors can safely practice and use the CMS without fear of messing up the live site. 

This environment can be used for guided training classes, self learning, or experimentation, etc.

## Environment Updates

### Code Syncing

This environment should be a replica of the live site so it needs to be updated as part of the deployment process in order to stay in sync with the live site.

Run these commands to keep this branch up to date:

```
git checkout sandbox
git rebase origin/master
git push -f
```

If the master branch is already ahead of the Live site, you'll need to find the commit ID belonging to the Live site deployment. This can be found in the Code section of the Pantheon dashboard. (Ignore the first "CircleCI deployment" commit&mdash;it's the second commit ID you want.) Use this rebase command instead of the above one: `git rebase <commit-ID>`

### Content Syncing

The content also needs to be periodically updated by cloning the database from Live by following these steps. (Please note that this will destroy any existing "test content" previously created on the site.)

1. Run `lando terminus env:clone-content portlandor.live sandbox --db-only`
2. Run `lando terminus drush portlandor.sandbox deploy`
3. Create and publish an informational `Sandbox Site` alert with the following text:

```This sandbox site is a copy of portland.gov and is intended to provide a safe environment for editor training classes, self learning, or experimentation, etc. Changes made here will not affect the live site and cannot be imported into the live site.```

## Sandbox Branch Code Changes

This branch should only have a few code changes from the `master` branch:
1. `sandbox.md` (This file)
2. `web/modules/custom/portland/modules/portland_openid_connect/src/Routing/RouteSubscriber.php` (Enabling Office 365 login for this site)
3. `web/sites/default/settings.php` (Add sandbox domain and environment indicator settings)

## 2.5.0
* Security updated Drupal core to 8.5.3.

## 2.4.0
* Security updated Drupal core to 8.5.2.

## 2.3.0
* Fixed an incompatibility with Search API which would cause fatal errors under
  certain circumstances. (Issue #2961547 and GitHub #46)
* The Basic page content type provided by Lightning Page will now be moderated
  only if and when Content Moderation is installed. (GitHub #40)
* Lightning Core is now compatible with Drupal Extension 3.4 or later only.
  (GitHub #43 and #44)

## 2.2.0
* Security updated Drupal core to 8.5.1. (SA-2018-002)
* When renaming the configuration which stores extension's version numbers,
  Lightning Core will no longer assume configuration by the same name does not
  already exist. (Issue #2955072) 

## 2.1.0
* Behat contexts used for testing were moved into the
  `Acquia\LightningExtension\Context` namespace.

## 2.0.0
* Updated core to 8.5.x.

## 1.0.0-rc3
* Fixed a problem in the 8006 update that caused problems for users that had an
  existing `lightning.versions` config object.

## 1.0.0-rc2
* Behat contexts used for testing have been moved into Lightning Core.
* The `lightning.versions` config object is now `lightning_core.versions`.

## 1.0.0-rc1
* The `update:lightning` command can now be run using either Drupal Console or
  Drush 9.
* Component version numbers are now recorded on install (and via an update hook
  on existing installations) so that the `version` argument is no longer needed
  with the `update:lightning` command. 

## 1.0.0-alpha3
* Updated core to 8.4.4.

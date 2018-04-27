## 2.1.0
* Behat contexts used for testing were moved into the
  `Acquia\LightningExtension\Context` namespace.

## 2.0.0
* Provided an optional update to rename the "Source" filter on the Media
  overview page to "Type".
* Updated Crop API to RC1 and no longer pin it to a specific release.
* Media Entity is no longer used, provided, or patched by Lightning Media.
* In keeping with recent changes in Drupal core, Lightning Media provides an
  update hook that modifies any configured Media-related actions to use the
  new, generic action plugins provided by core.

## 1.0.0-rc3
* Lightning Media will only set up developer-specific settings when our
  internal developer tools are installed.

## 1.0.0-rc2
* Removed legacy update code.

## 1.0.0-rc1
* Allow Media types to be configured without a Source field. (Issue #2928658)

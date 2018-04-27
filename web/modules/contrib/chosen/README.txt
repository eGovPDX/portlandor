-- SUMMARY --

  Chosen uses the Chosen jQuery plugin to make your <select> elements
  more user-friendly.


-- INSTALLATION --

  1. Download the Chosen jQuery plugin
     (http://harvesthq.github.io/chosen/ version 1.5 or higher is recommended)
     and extract the file under "libraries".
  2. Download and enable the module.
  3. Configure at Administer > Configuration > User interface > Chosen
     (requires administer site configuration permission)

-- INSTALLATION VIA COMPOSER --
  It is assumed you are installing Drupal through Composer using the Drupal
  Composer facade. See https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#drupal-packagist

  The Chosen JavaScript library does not support composer so manual steps are
  required in order to install it through this method.

  First, copy the following snippet into your project's composer.json file so
  the correct package is downloaded:

  "repositories": [
    {
      "type": "package",
      "package": {
        "name": "harvesthq/chosen",
        "version": "1.8.2",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/harvesthq/chosen/releases/download/v1.8.2/chosen_v1.8.2.zip",
          "type": "zip"
        },
        "require": {
            "composer/installers": "^1.2.0"
        }
      }
    }
  ]

  Next, the following snippet must be added into your project's composer.json
  file so the javascript library is installed into the correct location:

  "extra": {
      "installer-paths": {
          "libraries/{$name}": ["type:drupal-library"]
      }
  }

  If there are already 'repositories' and/or 'extra' entries in the
  composer.json, merge these new entries with the already existing entries.

  After that, run:

  $ composer require harvesthq/chosen
  $ composer require drupal/chosen

  The first uses the manual entries you made to install the JavaScript library,
  the second adds the Drupal module.

  Note: the requirement on the library is not in the module's composer.json
  because that would cause problems with automated testing.

-- INSTALLATION VIA DRUSH --

  A Drush command is provided for easy installation of the Chosen plugin.

  drush chosenplugin

  The command will download the plugin and unpack it in "libraries".
  It is possible to add another path as an option to the command, but not
  recommended unless you know what you are doing.

  If you are using Composer to manage your site's dependencies,
  then the Chosen plugin will automatically be downloaded to `libraries/chosen`.

-- ACCESSIBILITY CONCERN --

  There are accessibility problems with the main library as identified here:
        https://github.com/harvesthq/chosen/issues/264

-- TROUBLE SHOOTING --

  How to exclude a select field from becoming a chosen select.
    - go to the configuration page and add your field using the jquery "not"
      operator to the textarea with the comma separated values.
      For date fields this could look like:
      select:not([name*='day'],[name*='year'],[name*='month'])

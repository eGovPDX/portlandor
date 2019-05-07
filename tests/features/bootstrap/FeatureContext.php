<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines general application features used by other feature files.
 *
 * @codingStandardsIgnoreStart
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

    /**
   * @Given I am logged in as user :name
   */
  public function iAmLoggedInAsUser($name) {
    $uli = '';
    if(getenv('PANTHEON_ENVIRONMENT') == 'lando') {
      if(strpos($name, '@') === FALSE) {
        $uli = shell_exec("drush uli --name \"$name\"");
      }
      else {
        $uli = shell_exec("drush uli --mail \"$name\"");
      }
      $uli = trim($uli);
      $lando_app_name = getenv('LANDO_APP_NAME');
      $uli = str_replace("//default", "//$lando_app_name.lndo.site", $uli);
    }
    else {
      // Get the suffix for the site based on the environment
      $site_name = (getenv('CIRCLE_BRANCH') == "master") ? "dev" : getenv('CIRCLE_BRANCH');

      // Generate the link to login. Ignore stderr output
      if(strpos($name, '@') === FALSE) {
        $uli = shell_exec("terminus drush portlandor.$site_name -- uli --name \"$name\"");
      }
      else {
        $uli = shell_exec("terminus drush portlandor.$site_name -- uli --mail \"$name\"");
      }

      // Trim EOL characters.
      $uli = trim($uli);
      $repo_name = getenv('CIRCLE_PROJECT_REPONAME');
      $uli = str_replace("//default", "//$site_name-$repo_name.pantheonsite.io", $uli);
    }

    // Log in.
    $this->getSession()->visit($uli);

    $driver = $this->getDriver();
    $manager = $this->getUserManager();

  }
}

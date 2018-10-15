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
    // Get the suffix for the site based on the environment
    $site_name = (getenv('CIRCLE_BRANCH') == "master") ? "dev" : getenv('CIRCLE_BRANCH');

    // Generate the link to login
    $uli = shell_exec("terminus drush portlandor.$site_name uli $name");

    // Trim EOL characters.
    $uli = trim($uli);

    // Log in.
    $this->getSession()->visit($uli);
  }

}

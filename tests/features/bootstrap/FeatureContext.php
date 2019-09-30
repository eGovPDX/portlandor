<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;

// Workaround for https://github.com/Behat/Behat/issues/1076
chdir(__DIR__ . '/../..');

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
      if (getenv('CIRCLE_BRANCH') == "master") {
        $site_name = "dev";
      } elseif ( preg_match("/^dependabot\//", getenv('CIRCLE_BRANCH')) )  {
        preg_match("/\/pull\/([0-9]+)$/", getenv('CIRCLE_PULL_REQUEST'), $matches);
        $site_name = "bot-$matches[1]";
      } else {
        $site_name = getenv('CIRCLE_BRANCH');
      }

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
    $this->getSession()->wait(20000, "jQuery('li.account').length >= 1");
    // $this->getSession()->wait(10000, "document.readyState === 'complete'");

    $driver = $this->getDriver();
    $manager = $this->getUserManager();

  }


  /**
   * @When I fill in wysiwyg on field :locator with :value
   */
  public function iFillInWysiwygOnFieldWith($locator, $value) {
    $el = $this->getSession()->getPage()->findField($locator);

    if (empty($el)) {
      throw new ExpectationException('Could not find WYSIWYG with locator: ' . $locator, $this->getSession());
    }

    $fieldId = $el->getAttribute('id');

    if (empty($fieldId)) {
      throw new Exception('Could not find an id for field with locator: ' . $locator);
    }

    $this->getSession()
      ->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");");
  }

  /**
   * @Then I wait for the page to be loaded
   */
  public function waitForThePageToBeLoaded()
  {
      $this->getSession()->wait(10000, "document.readyState === 'complete'");
  }

  /** @Given I am using a 1440x900 browser window */
  public function iAmUsingA1440x900BrowserWindow()
  {
    $this->getSession()->resizeWindow(1440, 900, 'current');
  }
}

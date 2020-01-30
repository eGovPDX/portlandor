<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\RawMinkContext;

// Workaround for https://github.com/Behat/Behat/issues/1076
chdir(__DIR__ . '/../..');

/**
 * Defines general application features used by other feature files.
 *
 * @codingStandardsIgnoreStart
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  private $rawMinkContext;

  public function __construct() {
    $this->rawMinkContext = new RawMinkContext;
  }

  // fake "extends RawMinkContext" using magic function
  public function __call($method, $args) {
    $this->rawMinkContext->$method($args[0]);
  }

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
    $this->getSession()->wait(30000, "jQuery('li.account').length >= 1");
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
    $this->getSession()->wait(30000, "document.readyState === 'complete'");
  }

  /** @Given I am using a 1440x900 browser window */
  public function iAmUsingA1440x900BrowserWindow()
  {
    $this->getSession()->resizeWindow(1440, 900, 'current');
  }

  /**
   * Click any element.
   *
   * @Given I click the :selector element
   *
   * @see https://stackoverflow.com/a/33672497/1023773
   */
  public function iClickTheElement($selector) {
    $page = $this->getSession()->getPage();
    $element = $page->find('css', $selector);

    if (empty($element)) {
      throw new Exception("No html element found for selector '{$selector}'");
    }

    $element->click();
  }

  /**
   * @AfterStep
   */
  public function printLastResponseOnError(AfterStepScope $event) {
    if (!$event->getTestResult()->isPassed()) {
      $this->saveDebugScreenshot($event);
    }
  }

  /**
   * @Then /^save screenshot$/
   */
  public function saveDebugScreenshot(AfterStepScope $event) {
    $driver = $this->getSession()->getDriver();
    if (!$driver instanceof Selenium2Driver) {
      return;
    }

    // Only save screenshots when running tests on CircleCI
    if (!getenv('BEHAT_SCREENSHOTS')) {
      return;
    }

    $path = "/var/www/html/artifacts/failedtests";
    $testfilename = str_replace('/', '-', $event->getFeature()->getFile());   // Convert 'features/test_name.feature' into 'features-test_name.feature'
    $filename = microtime(true).' '.$event->getFeature()->getTitle().' -- '.$event->getStep()->getText()
        .' ('.$testfilename.' line '.$event->getStep()->getLine().')';

    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    // Save screenshot of failing page
    $this->saveScreenshot($filename.'.png', $path);

    // Get and save failing page's HTML
    $page = $this->getSession()->getPage()->getContent();
    file_put_contents($path.'/'.$filename.'.html', $page);
  }
}

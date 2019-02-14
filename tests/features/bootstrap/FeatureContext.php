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

    // Generate the link to login. Ignore stderr output
    $uli = shell_exec("terminus drush portlandor.$site_name uli $name");

    // Trim EOL characters.
    $uli = trim($uli);

    // Log in.
    $this->getSession()->visit($uli);
  }

  /**
   * @Then I fill in wysiwyg on field :locator with :value
   */
  public function iFillInWysiwygOnFieldWith($locator, $value)
  {
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
}

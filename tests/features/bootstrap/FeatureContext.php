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
   * @BeforeScenario
   */
  public function gatherContexts($scope)
  {
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

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

  /**
   * @When I fill in the autocomplete :autocomplete with :text and click :popup
   */
  public function iFillInTheAutocompleteWithAndClick($autocomplete, $text, $popup)
  {
    $el = $this->getSession()->getPage()->findField($autocomplete);
    $el->focus();

    // Set the autocomplete text then put a space at the end which triggers
    // the JS to go do the autocomplete stuff.
    $el->setValue($text);
    $el->keyUp(' ');

    // Sadly this grace of 1 second is needed here.
    sleep(1);
    $this->iWaitForAjaxToFinish2();

    // Drupal autocompletes have an id of autocomplete which is bad news
    // if there are two on the page.
    $autocomplete = $this->getSession()->getPage()->findById('autocomplete');

    if (empty($autocomplete)) {
      throw new ExpectationException(t('Could not find the autocomplete popup box'), $this->getSession());
    }

    $popup_element = $autocomplete->find('xpath', "//div[text() = '{$popup}']");

    if (empty($popup_element)) {
      throw new ExpectationException(t('Could not find autocomplete popup text @popup', array(
        '@popup' => $popup
      )), $this->getSession());
    }

    $popup_element->click();
  }

  // The following function was copied out of MinkContext.php
  // TODO: figure out how to correctly reference it instead of copy it.
  /**
   * Wait for AJAX to finish.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @Given I wait for AJAX to finish
   */
  public function iWaitForAjaxToFinish2()
  {
    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      var d7_not_ajaxing = true;
      if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
        for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
      }
      var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
        d7_not_ajaxing && d8_not_ajaxing
      );
    }());
JS;
    $result = $this->getSession()->wait(5000, $condition);
    if (!$result) {
      throw new \RuntimeException('Unable to complete AJAX request.');
    }
  }


}

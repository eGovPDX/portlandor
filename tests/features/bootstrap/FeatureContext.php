<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;

use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\GroupContentTypeInterface;
use Drupal\pathauto\Form\PathautoBulkUpdateForm;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\CoreServiceProvider;


/**
 * Defines general application features used by other feature files.
 *
 * @codingStandardsIgnoreStart
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @var \Drupal\DrupalExtension\Context\DrupalContext
   */
  protected $drupalContext;

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $env = $scope->getEnvironment();
    $this->minkContext = $env->getContext( 'Drupal\DrupalExtension\Context\MinkContext');
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

    // Get group alias.
    $group_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/group/38');

  }

  /**
   * @Given I am logged in as user :name and a member of group :group
   */
  public function iAmLoggedInAsUserAndAMemberOfGroup($name, $group)
  {

    $manager = $this->getUserManager();
    $user = $manager->getUser($name);

    // Change internal current user.
    $manager->setCurrentUser($user);

    // Login.
    $this->login($user);

    // check if user is member of group. if not, add them.
    $found_group = \Drupal::entityTypeManager()->getStorage('group')->loadByProperties(['title' => $group]);
    //\Drupal::entityQuery('group')->condition("title", $group)->execute();


    // if (!isset($found_group) || count($found_group) < 1) {
    //   throw new Exception("Group \"$group\" does not exist.");
    // }

    // if (!$found_group->getMember($user)) {
    //   // not a member of group, add user
    //   $found_group->addMember($user);
    //   $found_group->save();
    // }

  }

  /**
   * @Then I fill in wysiwyg on field :locator with :value
   */
  public function iFillInWysiwygOnFieldWith($locator, $value)
  {
    $el = $this->getSession()->getPage()->findField($locator);

    if (empty($el)) {
      throw new Exception('Could not find WYSIWYG with locator: ' . $locator);
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
    $this->minkContext->iWaitForAjaxToFinish();

    // Drupal autocompletes have an id of autocomplete which is bad news
    // if there are two on the page.
    $autocomplete = $this->getSession()->getPage()->findById($autocomplete);

    if (empty($autocomplete)) {
      throw new Exception('Could not find the autocomplete popup box');
    }

    $popup_element = $autocomplete->find('xpath', "//div[text() = '{$popup}']");

    if (empty($popup_element)) {
      throw new Exception('Could not find autocomplete popup text ' . $popup);
    }

    $popup_element->click();
  }

}

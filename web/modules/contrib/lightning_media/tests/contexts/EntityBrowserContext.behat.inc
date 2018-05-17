<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Utility\Html;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with entity browser instances.
 */
class EntityBrowserContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Gets all items in an entity browser.
   *
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   An array of items in the entity browser.
   */
  protected function getItems($browser_id = NULL) {
    if ($browser_id) {
      $selector = 'form#entity-browser-' . Html::cleanCssIdentifier($browser_id) . '-form';
    }
    else {
      $selector = 'form[data-entity-browser-uuid]';
    }

    return $this
      ->assertSession()
      ->elementExists('css', $selector)
      ->findAll('css', '[data-selectable]');
  }

  /**
   * Selects an item in an entity browser view.
   *
   * @param int $n
   *   The one-based index of the item to select.
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if the entity browser
   * contains fewer than $n items.
   *
   * @When I select item :n
   * @When I select item :n from the entity browser
   * @When I select item :n from the :browser_id entity browser
   */
  public function select($n, $browser_id = NULL) {
    $items = $this->getItems($browser_id);

    if ($n > count($items)) {
      throw new ExpectationException("Expected at least $n item(s) in the $browser_id entity browser.", $this->getSession()->getDriver());
    }
    else {
      $items[--$n]->click();
    }
  }

  /**
   * Asserts that a certain number of items are visible in the entity browser.
   *
   * @param int $n
   *   The number of items that should be visible.
   * @param string $browser_id
   *   (optional) The entity browser ID.
   *
   * @throws ExpectationException if the actual number of items in the entity
   * browser does not match the expected number.
   *
   * @Then I should see :n item(s) in the entity browser
   */
  public function assertCount($n, $browser_id = NULL) {
    $count = count($this->getItems($browser_id));

    if ($count !== (int) $n) {
      throw new ExpectationException("Expected $n items in the $browser_id entity browser, but there were $count.", $this->getSession()->getDriver());
    }
  }

  /**
   * Clicks on a tab with the specified text in an active entity browser and
   * waits for it to load.
   *
   * @param string $tab
   *   The text of the tab to switch to.
   *
   * @When I switch to the :tab Entity Browser tab
   */
  public function switchToEBTab($tab) {
    $this
      ->assertSession()
      ->elementExists('css', 'nav.eb-tabs')
      ->clickLink($tab);

    // I don't see any way to assert the tab specifically has loaded. So,
    // instead we just wait a reasonable amount of time.
    sleep(5);
  }

  /**
   * Submits the entity browser.
   *
   * @When I submit the entity browser
   */
  public function submit() {
    $session = $this->getSession();

    // The entity browser frame will be destroyed, so we need to switch into
    // the main window and reach into the frame to submit the form...ugh.
    $frame = $session->evaluateScript('frameElement.name');
    $session->switchToWindow();

    // @TODO: Make this smarter, because we can't be sure that #edit-submit
    // exists at all, or that it's the correct submit button.
    $session->executeScript('frames["' . $frame . '"].document.forms[0].querySelector("#edit-submit").click()');

    sleep(10);
    $this->awaitAjax();
  }

}

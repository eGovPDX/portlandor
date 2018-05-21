<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with Panels IPE.
 */
class PanelsIPEContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Asserts the presence of the Panels IPE tray.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The Panels IPE tray element.
   *
   * @Then I should see the Panels IPE tray
   */
  public function assertTray() {
    return $this->assertSession()->elementExists('css', '#panels-ipe-tray');
  }

  /**
   * Returns the active Panels IPE tab's contents.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The active tab's contents.
   */
  protected function getActiveTab() {
    return $this->assertSession()
      ->elementExists('css', '.ipe-tabs-content', $this->assertTray());
  }

  /**
   * Opens a tab of the Panels IPE tray.
   *
   * @param string $tab
   *   The title of the tab to activate.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The tab contents.
   *
   * @When I open the :tab tab
   */
  public function openTab($tab) {
    $assert = $this->assertSession();

    // Assert that the tab exists...
    $link = $assert->elementExists(
      'named',
      ['link', $tab],
      $assert->elementExists('css', '.ipe-tabs', $this->assertTray())
    );

    // ...but only open it if not already active.
    if ($link->getParent()->hasClass('active') == FALSE) {
      $link->click();
      $this->awaitAjax();
    }

    return $this->getActiveTab();
  }

  /**
   * Opens a particular block category of the "Manage Content" tab.
   *
   * @param string $category
   *   The category to open.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The tab contents.
   *
   * @When I open the :category category
   */
  public function openCategory($category) {
    $tab = $this->openTab('Manage Content');

    // Assert that the category exists...
    $link = $this->assertSession()
      ->elementExists('css',
        '.ipe-category[data-category="' . $category . '"]',
        $tab
      );

    // ...but only open it if not already active.
    if ($link->hasClass('active') == FALSE) {
      $link->click();
      $this->awaitAjax();
    }

    return $tab;
  }

  /**
   * Asserts that a particular block plugin is available.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   * @param string $category
   *   (optional) The category to open.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The link to instantiate the block plugin.
   *
   * @Then I should see the :plugin_id plugin
   * @Then I should see the :plugin_id plugin in the :category category
   */
  public function assertPlugin($plugin_id, $category = NULL) {
    return $this->assertSession()
      ->elementExists(
        'css',
        '.ipe-block-plugin a[data-plugin-id="' . $plugin_id . '"]',
        $category ? $this->openCategory($category) : $this->getActiveTab()
      );
  }

  /**
   * Instantiates a block plugin.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   * @param string $category
   *   (optional) The category in which the block plugin resides.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The block plugin configuration form.
   *
   * @When I instantiate the :plugin_id block
   * @When I instantiate the :plugin_id block from the :category category
   */
  public function instantiate($plugin_id, $category = NULL) {
    $this->assertPlugin($plugin_id, $category)->click();
    $this->awaitAjax();

    return $this->assertSession()
      ->elementExists('css', '.panels-ipe-block-plugin-form', $this->getActiveTab());
  }

  /**
   * Places a block into a Panels IPE layout.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   * @param string $category
   *   (optional) The category in which the block plugin resides.
   *
   * @When I place the :plugin_id block from the :category category
   * @When I place the :plugin_id block
   */
  public function place($plugin_id, $category = NULL) {
    $this->instantiate($plugin_id, $category)->pressButton('Add');
    $this->awaitAjax();
  }

  /**
   * Saves the current IPE layout as a custom layout.
   *
   * @When I save the layout
   */
  public function save() {
    $this->assertSession()
      ->elementExists(
        'named',
        ['link', 'Save'],
        $this->assertTray()
      )
      ->click();

    $this->awaitAjax();
  }

  /**
   * Changes the Panels IPE layout.
   *
   * @param string $category
   *   The layout's category.
   * @param string $layout_id
   *   The layout's data-layout-id value.
   *
   * @When I change the layout to :layout_id from the :category category
   */
  public function changeLayout($category, $layout_id) {
    $assert = $this->assertSession();

    $assert->elementExists('named', ['link', 'Change Layout'])->click();
    $this->awaitAjax();

    $assert->elementExists('css', "a[data-category='$category']")->click();
    $this->awaitAjax();

    $assert->elementExists('css', "a[data-layout-id='$layout_id']")->click();
    $this->awaitAjax();
  }

}

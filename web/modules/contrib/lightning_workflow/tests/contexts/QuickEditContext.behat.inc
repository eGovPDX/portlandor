<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with Quick Edit.
 */
class QuickEditContext extends DrupalSubContextBase {

  /**
   * Asserts that Quick Edit is enabled for at least one entity on the page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if Quick Edit is
   * disabled on the current page.
   *
   * @Then Quick Edit should be enabled
   */
  public function assertEnabled() {
    $session = $this->getSession();

    $victory = $session->wait(10000, 'Drupal.quickedit.collections.entities.length > 0');

    if (empty($victory)) {
      throw new ExpectationException('Expected Quick Edit to be enabled, but it is not.', $session->getDriver());
    }
  }

  /**
   * Asserts that Quick Edit is not enabled for any entities on the page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException if Quick Edit is enabled
   * on the current page.
   *
   * @Then Quick Edit should be disabled
   */
  public function assertDisabled() {
    $session = $this->getSession();

    $victory = $session->wait(10000, 'Drupal.quickedit.collections.entities.length === 0');

    if (empty($victory)) {
      throw new ExpectationException('Expected Quick Edit to be disabled, but it is not.', $session->getDriver());
    }
  }

  /**
   * Asserts that a block exists with a Quick Edit contextual link.
   *
   * @param string $plugin
   *   The block plugin ID.
   *
   * @Then I should see a :plugin block with Quick Edit
   */
  public function assertBlock($plugin) {
    $assert = $this->assertSession();

    $block = $assert->elementExists('css', 'div[data-block-plugin-id="' . $plugin . '"]');
    $links = $assert->elementExists('css', 'ul.contextual-links', $block);

    $assert->elementExists('named', ['link', 'Quick edit'], $links);
  }

}

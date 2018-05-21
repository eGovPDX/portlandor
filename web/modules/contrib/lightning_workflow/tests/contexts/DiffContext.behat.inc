<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Contains step definitions for interacting with the Diff module.
 */
class DiffContext extends DrupalSubContextBase {

  /**
   * Compares two revisions of a node.
   *
   * @param string $a
   *   The one-based index of a revision to compare. 1st is oldest.
   * @param string $b
   *   The one-based index of a revision to compare. 1st is oldest.
   *
   * @When I compare the :a and :b revisions
   */
  public function compareRevisions($a, $b) {
    $assert = $this->assertSession();
    /** @var MinkContext $context */
    $context = $this->getContext(MinkContext::class);

    $re = '/^[0-9]+(st|nd|rd|th)$/i';

    if (preg_match($re, $a)) {
      $a = substr($a, 0, -2);
    }
    if (preg_match($re, $b)) {
      $b = substr($b, 0, -2);
    }
    $a = ((int) $a) - 1;
    $b = ((int) $b) - 1;

    $assert->elementExists('named', ['link', 'Revisions'])->click();

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $this->getSession()
      ->getPage()
      ->findAll('css', '.diff-revisions tbody tr');

    $rows = array_reverse($rows);
    $a = $rows[$a]->findField('radios_left')->getValue();
    $b = $rows[$b]->findField('radios_right')->getValue();

    $context->selectOption('radios_left', $a);
    $context->selectOption('radios_right', $b);

    $assert->elementExists('named', ['button', 'Compare'])->press();
  }

}

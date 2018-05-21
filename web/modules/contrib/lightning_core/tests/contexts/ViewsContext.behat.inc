<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for working with views.
 */
class ViewsContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Submits exposed Views filters.
   *
   * @When I apply the exposed filters
   */
  public function applyExposedFilters() {
    $this->assertSession()
      ->elementExists('css', '.views-exposed-form .form-actions input[type = "submit"]')
      ->press();

    $this->awaitAjax();
  }

}

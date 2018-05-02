<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

class ImageBrowserContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Opens an image browser for a particular field.
   *
   * @param string $field
   *   The field label.
   *
   * @When I open the :field image browser
   */
  public function open($field) {
    /** @var ElementContext $context */
    $context = $this->getContext(ElementContext::class);

    $context->assertDetails($field)->pressButton('Select Image(s)');
    $this->awaitAjax();
    $context->enterFrame('entity_browser_iframe_image_browser');

    // This might be vestigial.
    sleep(10);
  }

}

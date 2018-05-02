<?php

namespace Acquia\LightningExtension\Context;

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\MinkContext;

/**
 * Contains step definitions for interacting with DropzoneJS widgets.
 */
class DropzoneContext extends DrupalSubContextBase {

  /**
   * Attaches a file to a dropzone.
   *
   * @param string $file
   *   The file to attach, relative to the file directory configured for Mink.
   *
   * @When I attach the file :file to the dropzone
   */
  public function attachFileToDropzone($file) {
    $this->getSession()
      ->executeScript('Dropzone.instances[0].hiddenFileInput.name = "file"');

    $this->getContext(MinkContext::class)->attachFileToField('file', $file);

    // @todo: React when the upload actually completes.
    sleep(3);
  }

}

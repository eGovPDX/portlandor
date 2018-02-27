<?php

namespace Drupal\bootstrap_layouts\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BootstrapLayoutsHandler annotation object.
 *
 * @Annotation
 */
class BootstrapLayoutsUpdate extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The schema number to be invoked on.
   *
   * @var int
   */
  public $schema;

}


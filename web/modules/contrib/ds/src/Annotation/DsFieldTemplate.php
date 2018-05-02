<?php

namespace Drupal\ds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DsFieldTemplate annotation object.
 *
 * @Annotation
 */
class DsFieldTemplate extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the DS field layout plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The theme function for this field layout.
   *
   * @var string
   */
  public $theme;

}

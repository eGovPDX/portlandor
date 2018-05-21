<?php

namespace Drupal\ds\Plugin\DsField;

/**
 * The base plugin to create DS theme fields.
 */
abstract class ThemePluginBase extends DsFieldBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $field = $this->getConfiguration();
    $format = $this->formatter();

    return [
      '#markup' => _theme($format, $field),
    ];
  }

  /**
   * Returns the formatter for the theming function.
   */
  protected function formatter() {
    return '';
  }

}

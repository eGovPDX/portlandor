<?php

namespace Drupal\portland_zendesk\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Textarea;

/**
 * Provides a 'custom_markup' element.
 *
 * @WebformElement(
 *   id = "portland_info_block_widget",
 *   label = @Translation("Portland Info Block Widget"),
 *   description = @Translation("Provides an information block for webforms with pre-defined formatting and style options."),
 *   category = @Translation("Markup elements"),
 * )
 */
class PortlandInfoBlockWidget extends Textarea {

  /**
   * {@inheritdoc}
   */
  public function render(array $element, array $value_callback = NULL, $filter = NULL) {
    $element['#markup'] = "<p><strong>XXXXX</strong></p>";// $this->configuration['markup'];
    return parent::render($element, $value_callback, $filter);
  }

}

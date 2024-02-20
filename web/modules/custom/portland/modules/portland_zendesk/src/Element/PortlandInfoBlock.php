<?php

namespace Drupal\portland_zendesk\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for Portland Info Block markup.
 *
 * @FormElement("portland_info_block")
 */
class PortlandInfoBlock extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#pre_render' => [
        [$class, 'preRenderPortlandInfoBlock'],
      ],
    ];
  }

  /**
   * Create Portland Info Block markup for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with Portland Info Block markup.
   */
  public static function preRenderPortlandInfoBlock(array $element) {
    $configuration = $element['#configuration'];
    $alertHeading = $configuration['alert_heading'];
    $alertLevel = $configuration['alert_level'];

    $output = '<div class="portland-info-block ' . $alertLevel . '">';

    if ($alertHeading) {
      $output .= '<h2>' . $alertHeading . '</h2>';
    }

    // Append original content
    $output .= $configuration['markup'];

    $output .= '</div>';

    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

}

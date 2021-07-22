<?php

namespace Drupal\portland_location_picker\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'portland_location_picker'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("portland_location_picker")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_location_picker\Element\PortlandLocationPicker
 */
class PortlandLocationPicker extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'portland_locatiom_picker'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];
    $elements['location_latlon'] = [
      '#type' => 'geofield_latlon',
      '#title' => t('Location lat/lon'),
      '#description' => t('Enter an address or cross streets'),
      // '#attributes' => ['class' => ['visually-hidden']],
    ];
    $elements['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Address or Cross Streets'),
      '#description' => t('Enter an address or cross streets, then click the Locate button to verify the location.'),
    ];
    $elements['location_verify'] = [
      '#type' => 'button',
      '#value' => t('Locate'),
      '#attributes' => ['class' => ['btn', 'location-verify']],
      '#id' => 'location_verify',
    ];
    $elements['location_map'] = [
      '#type' => 'markup',
      '#markup' => '<div id="location_map" class="location-map">Map of Portland</div>',
    ];
    $elements['suggestions_modal'] = [
      '#type' => 'markup',
      '#markup' => '<div id="suggestions_modal" class="visually-hidden"></div>',
    ];

    return $elements;
  }

  // /**
  //  * Performs the after_build callback.
  //  */
  // public static function afterBuild(array $element, FormStateInterface $form_state) {
  //   // Add #states targeting the specific element and table row.
  //   preg_match('/^(.+)\[[^]]+]$/', $element['#name'], $match);
  //   $composite_name = $match[1];
  //   $element['#states']['disabled'] = [
  //     [':input[name="' . $composite_name . '[first_name]"]' => ['empty' => TRUE]],
  //     [':input[name="' . $composite_name . '[last_name]"]' => ['empty' => TRUE]],
  //   ];
  //   // Add .js-form-wrapper to wrapper (ie td) to prevent #states API from
  //   // disabling the entire table row when this element is disabled.
  //   $element['#wrapper_attributes']['class'][] = 'js-form-wrapper';
  //   return $element;
  // }

}

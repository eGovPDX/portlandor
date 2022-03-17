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
   * NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   * in the field config. if not, an error is thrown when trying to add an email handler.
   * 
   * Location types:
   *  street - location_address, location_map
   *  park - location_park, location_map
   *  waterway - location_map
   *  private - location_private_owner
   *  other - location_address, location_map
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'park_facility', 'status' => 1]);

    $elements = [];
    // no other controls should be visible until user has selected a location type; it determines which controls are required.
    $elements['location_type'] = [
      '#id' => 'location_type',
      '#name' => 'location_type',
      '#type' => 'radios',
      '#title' => t('On what type of property is the issue located?'),
      '#title_display' => 'before',
      '#options' => [
        'street' => t('Along ANY street, sidewalk, highway, trail, or other public right-of-way'),
        'private' => t('On private property, such as at a residence or business'),
        'park' => t('Within a public park or natural area'),
        'waterway' => t('Along a river, stream, or other waterway'),
        'other' => t('I\'m not sure')
      ],
      '#options_display' => 'one_column',
      '#default_value' => 'street',
      '#attributes' => ['class' => ['location-type']],
    ];
    $elements['location_park_container'] = [
      '#id' => 'location_park_container',
      '#title' => t('Parks list container'),
      '#type' => 'container',
    ];
    $elements['location_park_container']['location_park'] = [
      '#id' => 'location_park',
      '#title' => t('Which park or natural area?'),
      '#type' => 'webform_entity_select',
      '#description' => t('If you can\'t find the park in the list, choose "Other / I\'m not sure" then find the location by searching for an address or using the map.'),
      "#description_display" => 'before',
      '#target_type' => 'node',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => ['view_name' => 'entity_browser_park_facilities', 'display_name' => 'location_picker_parks_list'],
      ],
      '#empty_option' => t('Select...'),
      '#attributes' => ['class' => ['location-park']],
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            'value' => 'park'
          ],
        ],
      ],
    ];
    $elements['location_park_container']['park_instructions'] = [
      '#type' => 'markup',
      '#title' => 'Park instructions',
      '#title_display' => 'invisible',
      '#markup' => '<p class="webform-element-description description">Please move the marker to the exact spot in the park where the issue was observed.</p>',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_park]"]' => ['filled' => TRUE],
          ':input[name="report_location[location_type]"]' => ['value' => 'park'],
        ],
      ],
    ];
    $elements['waterway_instructions'] = [
      '#type' => 'markup',
      '#title' => 'Waterway instructions',
      '#title_display' => 'invisible',
      '#markup' => '<p class="webform-element-description description">Please use the map to indicate the location of the issue you want to report. Click to set a marker.</p>',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => ['value' => 'waterway'],
        ],
      ],
    ];
    $elements['location_private_owner'] = [
      '#id' => 'location_private_owner',
      '#type' => 'radios',
      '#title' => t('Are you the owner of the property?'),
      '#title_display' => 'before',
      '#options' => 'yes_no',
      '#options_display' => 'side_by_side',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            'value' => 'private'
          ],
        ],
        'required' => [
          ':input[name="report_location[location_type]"]' => [
            'value' => 'private'
          ],
        ],
      ],
    ];
    $elements['location_address'] = [
      '#type' => 'textfield',
      '#id' => 'location_address',
      '#title' => t('Address or Cross Streets'),
      '#attributes' => ['class' => ['location-picker-address']],
      '#description' => t('Enter an address or cross streets of the issue being reported, then click the button to verify the location. Alternately, you may click the map to set the location.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'street']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'other']],
        ],
        'required' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'street']],
        ],
        'optional' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'park']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'waterway']],
           'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'private']],
           'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'other']],
           'or',
          [':input[name="report_location[location_type]"]' => ['checked' => FALSE]],
       ],
      ],
    ];
    $elements['location_map'] = [
      '#type' => 'markup',
      '#id' => 'location_map',
      '#title' => 'Location marker on map',
      '#title_display' => 'invisible',
      '#markup' => '<div id="location_map_container" class="location-map"></div>',
      '#states' => [
        'visible' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'street']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'park']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'waterway']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'other']],
        ],
      ],
    ];
    $elements['suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Suggestions',
      '#title_display' => 'invisible',
      '#markup' => '<div id="suggestions_modal" class="visually-hidden"></div>',
    ];
    $elements['status_modal'] = [
      '#type' => 'markup',
      '#title' => 'Status indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="status_modal" class="visually-hidden"></div>',
    ];
    $elements['place_name'] = [
      '#type' => 'textfield',
      '#id' => 'place_name',
      '#title' => t('Place Name'),
      '#attributes' => ['class' => ['place-name']],
      '#description' => t('If this location has a name, such as a business or public building, please enter it here.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'street']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'park']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'other']],
        ],
      ],
    ];
    $elements['location_details'] = [
      '#type' => 'textarea',
      '#id' => 'location_details',
      '#title' => t('Location Details'),
      '#attributes' => ['class' => ['location-details']],
      '#description' => t('Please provide any other details that might help us locate the site you are reporting.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          [':input[name="report_location[location_type]"]' => ['value' => 'street']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'park']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'waterway']],
          'or',
          [':input[name="report_location[location_type]"]' => ['value' => 'other']],
        ],
      ],
    ];
    $elements['location_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#title_display' => 'invisible',
      '#id' => 'location_lat',
      '#attributes' => ['class' => ['location-lat','visually-hidden']],
    ];
    $elements['location_lon'] = [
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#title_display' => 'invisible',
      '#id' => 'location_lon',
      '#attributes' => ['class' => ['location-lon','visually-hidden']],
    ];

    return $elements;
  }
}

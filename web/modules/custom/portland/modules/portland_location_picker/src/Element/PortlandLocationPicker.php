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
   * street - location_address, location_map
   * park - location_park, location_map
   * waterway - location_map
   * private - location_private_owner
   * other - location_address, location_map
   * 
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'park_facility', 'status' => 1]);

    $elements = [];
    $elements['location_type'] = [
      '#id' => 'location_type',
      '#name' => 'location_type',
      '#type' => 'radios',
      '#title' => t('On what type of property is the issue located?'),
      '#title_display' => 'before',
      '#options' => [
        'street' => t('Along ANY street, sidewalk, highway, trail, or other public right-of-way'),
        'park' => t('Within a public park or natural area'),
        'waterway' => t('On a river, stream, or other waterway'),
        'private' => t('On private property, such as at a residence, school, or business'),
        'other' => t('I\'m not sure')
      ],
      '#options_display' => 'one_column',
      '#default_value' => 'street',
      '#attributes' => ['class' => ['location-type']],
    ];
    // we need to either figure out how to populate the webform_entity_select option value with the park lat/lon, or we need
    // to use a regular select and manually retrieve the values/text we need. ideally the data we need would be stored in the
    // select value, without having to make another trip to the server to look up the parks location.
    $elements['location_park'] = [
      '#id' => 'location_park',
      '#title' => t('Which park or natural area?'),
      '#type' => 'webform_entity_select',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            'value' => 'park'
          ],
        ],
      ],
      '#target_type' => 'node',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => ['view_name' => 'entity_browser_park_facilities', 'display_name' => 'location_picker_parks_list'],
      ],
      '#empty_option' => t('Select...'),
    ];
    // visible if location type != private
    $elements['park_instructions'] = [
      '#type' => 'markup',
      '#title' => 'Park instructions',
      '#title_display' => 'invisible',
      '#markup' => '<p class="webform-element-description description">Please move the marker to the exact spot in the park where the issue was observed.</p>',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_park]"]' => [
            ['filled' => TRUE]
         ],
        ],
      ],
    ];
    // $elements['location_park'] = [
    //   '#id' => 'location_park',
    //   '#title' => t('Which park or natural area?'),
    //   '#type' => 'entity_autocomplete',
    //   '#description' => t('Begin typing to see a list of matching park and natural area names.'),
    //   '#description_display' => 'before',
    //   '#states' => [
    //     'visible' => [
    //       ':input[name="report_location[location_type]"]' => [
    //         'value' => 'park'
    //       ],
    //     ],
    //   ],
    //   '#target_type' => 'node',
    //   '#selection_handler' => 'default:node',
    //   '#selection_settings' => [
    //     'target_bundles' => ['park_facility' => 'park_facility'],
    //     'sort' => ['field' => 'title', 'direction' => 'asc'],
    //   ],
    // ];
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
    // visible if location type = street|other
    $elements['location_address'] = [
      '#type' => 'textfield',
      '#id' => 'location_address',
      '#title' => t('Address or Cross Streets'),
      '#attributes' => ['class' => ['location-picker-address']],
      '#required' => TRUE,
      '#description' => t('Enter an address or cross streets of the issue being reported, then click the button to verify the location. Alternately, you may click the map to set the location.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            ['value' => 'street'],
            'or',
            ['value' => 'other'],
          ],
        ],
      ],
    ];
    // visible if location type != private
    $elements['location_map'] = [
      '#type' => 'markup',
      '#title' => 'Map',
      '#title_display' => 'invisible',
      '#markup' => '<div id="location_map" class="location-map"></div>',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            ['value' => 'street'],
            'or',
            ['value' => 'park'],
             'or',
            ['value' => 'waterway'],
            'or',
            ['value' => 'other'],
         ],
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
      '#title' => t('Place name'),
      '#attributes' => ['class' => ['place-name']],
      '#description' => t('If this location has a name, such as a business or public building, please enter it here.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            ['value' => 'street'],
            'or',
            ['value' => 'park'],
            'or',
            ['value' => 'other'],
          ],
        ],
      ],
    ];
    $elements['location_details'] = [
      '#type' => 'textarea',
      '#id' => 'location_details',
      '#title' => t('Location details'),
      '#attributes' => ['class' => ['location-details']],
      '#description' => t('Please provide any other details that might help us locate the site you are reporting.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [
          ':input[name="report_location[location_type]"]' => [
            ['value' => 'street'],
            'or',
            ['value' => 'park'],
            'or',
            ['value' => 'waterway'],
            'or',
            ['value' => 'other'],
          ],
        ],
      ],
    ];
    $elements['location_lat'] = [
      '#type' => 'hidden',
      '#title' => t('Latitude'),
      '#id' => 'location_lat',
      '#attributes' => ['class' => ['location-lat']],
    ];
    $elements['location_lon'] = [
      '#type' => 'hidden',
      '#title' => t('Longitude'),
      '#id' => 'location_lon',
      '#attributes' => ['class' => ['location-lon']],
    ];

    return $elements;
  }

}

<?php

namespace Drupal\portland_address_verifier\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\WebformOptions;

/**
 * Provides a 'portland_address_verifier'.
 *
 * Portland Address Verifier widget is comprised of a group of sub-elements and
 * client-side scripting to validate addresses in the Portland area using the
 * PortlandMaps API.
 *
 * This widget is ONLY concerned about addresses. It has no zero support for
 * lat/lon coordinates. However, location data returned from the API, such as
 * loacation types, tax lot ID, municipality name, and zipcode, are captured.
 *
 * IMPORTANT:
 * 1. This widget cannot be used for geofencing; the full Location Picker widget must be used
 *    for anything other than simple address verification the areas covered by Portland Maps
 * 2. Webform composite can not contain multiple value elements (i.e. checkboxes)
 *    or composites (i.e. webform_address)
 *
 * @FormElement("portland_address_verifier")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\portland_address_verifier\Element\PortlandAddressVerifier
 */
class PortlandAddressVerifier extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   * //NOTE: custom elements must have a #title attribute. if a value is not set here, it must be set
   * //in the field config. if not, an error is thrown when trying to add an email handler.
   *
   * How to programmatically set field conditions: https://www.drupal.org/docs/drupal-apis/form-api/conditional-form-fields
   */
  public static function getCompositeElements(array $element) {

    $state_codes = WebformOptions::load('state_codes')->getOptions();

    // Generate a unique ID prefix based on the parent element's webform key.
    // This ensures that multiple instances of this composite element on the same form
    // have unique sub-element IDs, which is required for proper label-for associations
    // and JavaScript selectors.
    $parent_key = isset($element['#webform_key']) ? $element['#webform_key'] : 'address-verifier';
    $id_prefix = str_replace('_', '-', $parent_key) . '--';

    $element['location_verification_status'] = [
      '#type' => 'hidden',
      '#title' => t('Address Verification'),
      '#attributes' => [ 'id' => $id_prefix . 'location-verification-status' ],
      '#required_error' => 'The address is not verified.',
      '#element_validate' => [[static::class, 'validateVerificationStatusElement']],
    ];
    $element['location_address'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address'),
      '#id' => $id_prefix . 'location-address',
      '#autocomplete' => 'address-line1',
      '#pre_render' => [[static::class, 'preRenderConditionalRequiredIndicator']],
      '#wrapper_attributes' => [
        'class' => ['mb-0'],
      ],
      '#description' => t('Begin typing to see a list of possible address matches in the Portland metro area, then select one. If there is a unit number, enter it separately in the Unit Number field.'),
      '#description_display' => 'before',
      '#required_error' => 'Please enter an address and verify it.',
    ];
    $element['location_full_address'] = [
      '#type' => 'hidden',
      '#title' => t('Full Address'),
      '#attributes' => ['id' => $id_prefix . 'location-full-address']
    ];
    $element['location_address_street_number'] = [
      '#type' => 'hidden',
      '#title' => t('Street Number'),
      '#attributes' => ['id' => $id_prefix . 'location-address-street-number']
    ];
    $element['location_address_street_quadrant'] = [
      '#type' => 'hidden',
      '#title' => t('Street Quadrant'),
      '#attributes' => ['id' => $id_prefix . 'location-address-street-quadrant']
    ];
    $element['location_address_street_name'] = [
      '#type' => 'hidden',
      '#title' => t('Street Name'),
      '#attributes' => ['id' => $id_prefix . 'location-address-street-name']
    ];
    $element['location_address_street_type'] = [
      '#type' => 'hidden',
      '#title' => t('Street Type'),
      '#attributes' => ['id' => $id_prefix . 'location-address-street-type']
    ];
    $element['unit_number'] = [
      '#type' => 'textfield',
      '#title' => t('Unit Number'),
      '#id' => $id_prefix . 'unit-number',
      '#attributes' => ['autocomplete' => 'off'],
      '#placeholder' => t('e.g. #101, APT 101, or UNIT 101'),
      '#wrapper_attributes' => [
        'class' => ['mb-0'],
      ],
    ];
    $element['location_city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#id' => $id_prefix . 'location-city',
      '#autocomplete' => 'address-level2',
      '#pre_render' => [[static::class, 'preRenderConditionalRequiredIndicator']],
      '#wrapper_attributes' => [
        'class' => ['webform-city', 'mb-0'],
      ],
    ];
    $element['location_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => $state_codes,
      '#default_value' => 'OR',
      '#id' => $id_prefix . 'location-state',
      '#autocomplete' => 'address-level1',
      '#pre_render' => [[static::class, 'preRenderConditionalRequiredIndicator']],
      '#wrapper_attributes' => ['class' => ['webform-state', 'mb-0']],
    ];
    $element['location_zip'] = [
      '#type' => 'textfield',
      '#title' => t('ZIP Code'),
      '#id' => $id_prefix . 'location-zip',
      '#autocomplete' => 'postal-code',
      '#pre_render' => [[static::class, 'preRenderConditionalRequiredIndicator']],
      '#attributes' => ['class' => ['webform-zip']],
      '#wrapper_attributes' => ['class' => ['webform-zip', 'mb-0']],
    ];
    $element['location_jurisdiction'] = [
      '#type' => 'hidden',
      '#title' => t('Jurisdiction'),
      '#attributes' => [ 'id' => $id_prefix . 'location-jurisdiction' ],
    ];
    $element['av_suggestions_modal'] = [
      '#type' => 'markup',
      '#title' => 'Suggestions',
      '#title_display' => 'invisible',
      '#markup' => '<div id="' . $id_prefix . 'av-suggestions-modal" class="visually-hidden"></div>',
    ];
    $element['status_modal'] = [
      '#type' => 'markup',
      '#title' => 'Status Indicator',
      '#title_display' => 'invisible',
      '#markup' => '<div id="' . $id_prefix . 'status-modal" class="visually-hidden"></div>',
    ];
    $element['not_found_modal'] = [
      '#type' => 'markup',
      '#title' => 'Address Not Found',
      '#title_display' => 'invisible',
      '#markup' => '<div id="' . $id_prefix . 'not-found-modal" class="visually-hidden"></div>',
    ];
    $element['location_lat'] = [
      '#type' => 'hidden',
      '#title' => t('Latitude'),
      '#attributes' => [ 'id' => $id_prefix . 'location-lat']
    ];
    $element['location_lon'] = [
      '#type' => 'hidden',
      '#title' => t('Longitude'),
      '#attributes' => [ 'id' => $id_prefix . 'location-lon']
    ];
    $element['location_x'] = [
      '#type' => 'hidden',
      '#title' => t('Coordinates X'),
      '#attributes' => [ 'id' => $id_prefix . 'location-x']
    ];
    $element['location_y'] = [
      '#type' => 'hidden',
      '#title' => t('Coordinates Y'),
      '#attributes' => [ 'id' => $id_prefix . 'location-y']
    ];
    $element['location_taxlot_id'] = [
      '#type' => 'hidden',
      '#title' => t('Taxlot ID'),
      '#attributes' => [ 'id' => $id_prefix . 'location-taxlot-id']
    ];
    $element['location_is_unincorporated'] = [
      '#type' => 'hidden',
      '#title' => t('Is Unincorporated'),
      '#attributes' => [ 'id' => $id_prefix . 'location-is-unincorporated']
    ];
    $element['location_address_label'] = [
      '#type' => 'hidden',
      '#title' => t('Address Label'),
      '#attributes' => [ 'id' => $id_prefix . 'location-address-label']
    ];
    $element['location_capture_field'] = [
      '#type' => 'hidden',
      '#title' => t('Capture Field'),
      '#attributes' => [ 'id' => $id_prefix . 'location-capture-field'],
    ];
    $element['location_data'] = [
      '#type' => 'hidden',
      '#title' => t('Location Data'),
      '#attributes' => [ 'id' => $id_prefix . 'location-data']
    ];

    return $element;
  }

  /**
   * Ensures required indicators render for conditionally required fields.
   */
  public static function preRenderConditionalRequiredIndicator(array $element) {
    if (!empty($element['#_required']) && empty($element['#required'])) {
      $element['#required'] = TRUE;
    }

    return $element;
  }

  /**
   * Custom validation handler for the location_verification_status element.
   */
  public static function validateVerificationStatusElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Skip the validation if a verified address is not required.
    if (empty($element['#required'])) return;

    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    if ($value !== 'Verified') {
      // Set the error on the location address element so it displays properly for the user.
      $form_state->setErrorByName(implode('][', array_slice($element['#parents'], 0, -1)) . '][location_address', $element['#required_error']);
    }
  }
}

<?php

namespace Drupal\portland_location_picker\Validate;

use Drupal\Core\Field\FieldException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form API callback. Validate element value.
 */
class GeolocationValidationConstraint {

  /**
   * Validates given element.
   *
   * @param array              $element      The form element to process.
   * @param FormStateInterface $formState    The form state.
   * @param array              $form The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form) {
    $value = $formState->getValue('report_location');
    // if $value is null, that means the location picker widget wasn't exposed or used in the form.
    // we can ignore validation.
    if (!isset($value)) {
      return false;
    }

    $loctype = $value['location_type'];

    // we need to validate that the user has selected a location and that at minimum
    // either lat/lng or address have been provided. this was changed to not require lat/lng
    // due to a bug in the portlandmaps suggest API that causes lat/lng to be null if only
    // a single suggestion is returned.

    if ($loctype && $loctype == "private") {
      $invalid = false;
    } else {
      $lat = $value['location_lat'];
      $lng = $value['location_lon'];
      $address = $value['location_address'];

      if (($lat && $lat != "0" && $lng && $lng != "0") || $address) {
        $invalid = false;
      } else {
        $invalid = true;
      }
    }

    if ($invalid) {
      // location is required but not provided
      $formState->setError($element, t('You must select a location for this report before submitting.'));
    }
  }
}


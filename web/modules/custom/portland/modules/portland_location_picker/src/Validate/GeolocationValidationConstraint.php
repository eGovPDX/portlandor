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

        // if $value is null, that means the location picker widget wasn't exposed or used.
        // we can ignore validation.
        if (!isset($value)) {
          return false;
        }

        // we need to validate that the user has selected a location and that the
        // lat/lon values have been provided. We don't always need an address, but lat/lon
        // are always required if the report_location field is present.
        $lat = $value['location_lat'];
        $lon = $value['location_lon'];
        $invalid = !$lat || !$lon;

        if ($invalid) {
          // location is required but not provided
          //element['#webform_composite_elements']['location_map']['#parents'] = [];
          //$formState->setError($element['#webform_composite_elements']['location_map'], t('You must select a location for this report before submitting.'));
          $formState->setError($element, t('You must select a location for this report before submitting.'));
        }

        // if ($error) {
        //     if (isset($element['#title'])) {
        //         $tArgs = [
        //             '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
        //             '%value' => $value,
        //         ];
        //         $formState->setError(
        //             $element,
        //             t('The value %value is not allowed for element %name. Please use a different value.', $tArgs)
        //         );
        //     } else {
        //         $formState->setError($element);
        //     }
        // }
    }

}


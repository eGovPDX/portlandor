<?php

namespace Drupal\portland_address_verifier\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Html;

/**
 * Provides a 'portland_address_verifier' element.
 *
 * @WebformElement(
 *   id = "portland_address_verifier",
 *   label = @Translation("Portland Address Verifier"),
 *   description = @Translation("Provides an element for verifying an address in Portland."),
 *   category = @Translation("Portland elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\portland_address_verifier\Element\PortlandAddressVerifier
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PortlandAddressVerifier extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $e = static fn($s) => Html::escape($s);

    // Builds the display string used by [webform_submission:values:location].
    $lines = [];

    $address = '';
    $verified = (!empty($value['location_verification_status']) && $value['location_verification_status'] === 'Verified')
      ? 'Verified '
      : '';

    if (!empty($value['location_address'])) {
      $address = '<strong>' . $verified . 'Address:</strong> ' . $e($value['location_address']);
    }

    if (!empty($value['unit_number'])) {
      $address .= ' ' . $e($value['unit_number']);
    }

    if (!empty($value['location_city'])) {
      $address .= ', ' . $e($value['location_city']);
    }

    if (!empty($value['location_state'])) {
      $address .= ', ' . $e($value['location_state']);
    }

    if (!empty($value['location_zip'])) {
      $address .= ' ' . $e($value['location_zip']);
    }

    if ($address !== '') {
      $lines[] = $address;
    }

    // IMPORTANT for composites: return a LIST of render arrays.
    // Single item containing the full block:
    return [
      [
        '#markup' => Markup::create('<p>' . implode('<br />', $lines) . '</p>'),
      ],
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // this content is used as a display value for the field value, and is what is returned by the parent
    // level token, such as [webform_submission:values:location]. If more granular field sub-field values are
    // needed, such as in a handler that is sending data to an external system, the sub-field needs to be
    // specified in the token, such as [webform_submission:values:location:place_name].
    $lines = [];
    $address = "";

    if ($value['location_verification_status'] == 'Verified') {
      $address = $value['address_label'];
      $address = str_replace("<br>", "\r\n", $address);
    } else {
      $address = $value['location_address'];
    }

    $lines[] = $address;
    return $lines;
  }

    /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Always attach the base verifier library.
    $element['#attached']['library'][] = 'portland_address_verifier/portland_address_verifier';

    $key = isset($element['#webform_key']) ? $element['#webform_key'] : "";

    $machine_name = "edit-" . $key . "--wrapper";
    $machine_name = str_replace("_", "-", $machine_name);

    $defaults = [
      'verification_required' => false,
      'error_test' => 0,
      'address_type' => false,
      'address_suggest' => true,
      'verify_button_text' => 'Verify',
      'lookup_taxlot' => false,
      'show_mailing_label' => false,
      'find_unincorporated' => true,
      'secondary_query_url' => false,
      'secondary_query_capture_property' => false,
      'secondary_query_capture_field' => false,
      'not_verified_heading' => "We're unable to verify this address.",
      'not_verified_reasons' => "This sometimes happens with new addresses, PO boxes, and multi-family buildings with unit numbers.",
      'not_verified_remedy' => "If you're certain the address is correct, you may use it without verification.",
      'not_verified_remedy_required' => "A verified address is required. Please try again.",
      'require_portland_city_limits' => false,
      'out_of_bounds_message' => 'The address you provided is outside of the Portland city limits. Please try a different address.',
      'secondary_queries' => false,
      'use_map' => false,
      'max_zoom' => 18,
    ];

    $map = [
      'verification_required' => function($el) { return !empty($el['#location_verification_status__required']); },
      'error_test' => function($el) { return (array_key_exists('#error_test', $el) && strtolower($el['#error_test']) == '1') ? 1 : 0; },
      'address_type' => function($el) { return (array_key_exists('#address_type', $el) && strtolower($el['#address_type']) == 'any'); },
      'address_suggest' => function($el) { return (array_key_exists('#address_suggest', $el) && strtolower($el['#address_suggest']) == '0') ? false : true; },
      'verify_button_text' => function($el) { return array_key_exists('#verify_button_text', $el) ? $el['#verify_button_text'] : NULL; },
      'lookup_taxlot' => function($el) { return (array_key_exists('#lookup_taxlot', $el) && strtolower($el['#lookup_taxlot']) == 1); },
      'show_mailing_label' => function($el) { return (array_key_exists('#show_mailing_label', $el) && strtolower($el['#show_mailing_label']) == '0'); },
      'find_unincorporated' => function($el) { return (array_key_exists('#find_unincorporated', $el) && strtolower($el['#find_unincorporated']) != '0'); },
      'secondary_query_url' => function($el) { return array_key_exists('#secondary_query_url', $el) ? $el['#secondary_query_url'] : NULL; },
      'secondary_query_capture_property' => function($el) { return array_key_exists('#secondary_query_capture_property', $el) ? $el['#secondary_query_capture_property'] : NULL; },
      'secondary_query_capture_field' => function($el) { return array_key_exists('#secondary_query_capture_field', $el) ? $el['#secondary_query_capture_field'] : NULL; },
      'not_verified_heading' => function($el) { return array_key_exists('#not_verified_heading', $el) ? $el['#not_verified_heading'] : NULL; },
      'not_verified_reasons' => function($el) { return array_key_exists('#not_verified_reasons', $el) ? $el['#not_verified_reasons'] : NULL; },
      'not_verified_remedy' => function($el) { return array_key_exists('#not_verified_remedy', $el) ? $el['#not_verified_remedy'] : NULL; },
      'not_verified_remedy_required' => function($el) { return array_key_exists('#not_verified_remedy_required', $el) ? $el['#not_verified_remedy_required'] : NULL; },
      'require_portland_city_limits' => function($el) { return (array_key_exists('#require_portland_city_limits', $el) && strtolower($el['#require_portland_city_limits']) == '1'); },
      'out_of_bounds_message' => function($el) { return array_key_exists('#out_of_bounds_message', $el) ? $el['#out_of_bounds_message'] : NULL; },
      'secondary_queries' => function($el) { return array_key_exists('#secondary_queries', $el) ? $el['#secondary_queries'] : NULL; },
      'use_map' => function($el) { return (array_key_exists('#use_map', $el) && strtolower($el['#use_map']) == '1'); },
      'max_zoom' => function($el) {
        if (array_key_exists('#max_zoom', $el)) {
          $val = is_numeric($el['#max_zoom']) ? (int) $el['#max_zoom'] : NULL;
          if ($val !== NULL) {
            // Cap at 21 per Leaflet hard limit.
            return ($val > 21) ? 21 : $val;
          }
        }
        return NULL;
      },
    ];

    $settings =& $element['#attached']['drupalSettings']['webform']['portland_address_verifier'][$machine_name];
    foreach ($defaults as $key => $default) {
      $val = NULL;
      if (isset($map[$key]) && is_callable($map[$key])) {
        $val = $map[$key]($element);
      }
      $settings[$key] = ($val !== NULL) ? $val : $default;
    }

    // Conditionally attach map library only when maps are enabled; no new properties added.
    if ((isset($map['use_map']) && is_callable($map['use_map'])) && $map['use_map']($element)) {
      $element['#attached']['library'][] = 'portland_address_verifier/portland_address_verifier.map';
    }
  }
}

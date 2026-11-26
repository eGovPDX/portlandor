<?php

namespace Drupal\portland_webforms\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\webform\Plugin\WebformElement\WebformCustomComposite;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * Provides an extended webform_custom_composite element that supports conditional logic within the sub-elements.
 *
 * @WebformElement(
 *   id = "portland_custom_composite",
 *   label = @Translation("Portland custom composite"),
 *   description = @Translation("Provides an extended webform_custom_composite element that supports conditional logic within the sub-elements. Use {delta} as the index placeholder."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class PortlandCustomComposite extends WebformCustomComposite {
  /**
   * Replace {delta} in the #states selectors with the index of the current item.
   */
  public static function afterBuildModifyStates(array &$element): array {
    foreach ($element['items'] as $item_index => &$item) {
      if (isset($item['_item_'])) {
        foreach ($item['_item_'] as $key => &$value) {
          if (isset($value['#states'])) {
            foreach ($value['#states'] as $state_key => $state_value) {
              foreach ($value['#states'][$state_key] as $selector => &$condition) {
                // Handle multiple conditions within the state_key.
                if (is_numeric($selector) && is_array($condition) && str_contains(key($condition), '{delta}')) {
                  foreach ($condition as $sub_selector => &$sub_condition) {
                    if (str_contains($sub_selector, '{delta}')) {
                      $new_sub_selector = str_replace('{delta}', $item_index, $sub_selector);
                      $value['#states'][$state_key][$selector][$new_sub_selector] = $sub_condition;
                      unset($value['#states'][$state_key][$selector][$sub_selector]);
                      $value['#_webform_states'][$state_key][$selector][$new_sub_selector] = $sub_condition;
                      unset($value['#_webform_states'][$state_key][$selector][$sub_selector]);
                    }
                  }
                } else if (str_contains($selector, '{delta}')) {
                  $new_selector = str_replace('{delta}', $item_index, $selector);
                  $value['#states'][$state_key][$new_selector] = $condition;
                  unset($value['#states'][$state_key][$selector]);
                  $value['#_webform_states'][$state_key][$new_selector] = $condition;
                  unset($value['#_webform_states'][$state_key][$selector]);
                }
              }
            }
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    // Filter out the [_item_] key that is used client-side but unnecessary for server-side validation.
    $composite_key = array_values(array_filter(WebformSubmissionConditionsValidator::getInputNameAsArray($input_name), fn($key) => $key !== '_item_'));
    $value = $this->getRawValue($element, $webform_submission);
    if (is_array($value)) {
      // For e.g. element[items][0][sub_element_key], this returns [0][sub_element_key].
      $key = array_slice($composite_key, 2, 2);
      $nested_value = NestedArray::getValue($value, $key);
      // Handle selectors for checkboxes e.g. element[items][0][sub_element_key][option_key],
      // where the nested value is an array of selected checkboxes.
      $checkbox_key = $composite_key[4] ?? NULL;

      return isset($checkbox_key) && is_array($nested_value) ? in_array($checkbox_key, $nested_value) : $nested_value;
    }

     return null;
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::finalize($element, $webform_submission);
    // Inject our hook as the first one
    $element['#after_build'] = [[get_class($this), 'afterBuildModifyStates'], ...$element['#after_build']];
  }
}

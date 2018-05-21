<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;


/**
 * Trait for shared code in Viewsreference Field Widgets.
 */
trait ViewsReferenceTrait {

  /**
   * Build the field element.
   */
  public function fieldElement($element, $items, $delta) {

    switch ($element['target_id']['#type']) {

      case 'select':
        $test = ['!value' => '_none'];
        $event = 'change';
        break;

      default:
        $test = ['filled' => TRUE];
        $event = 'viewsreference-select';
        break;

    }

    $field_name = $items->getName();
    $name = $field_name . '[' . $delta . '][target_id]';

    $element['target_id']['#target_type'] = 'view';

    $element['target_id']['#ajax'] = [
      'callback' => [$this, 'getDisplayIds'],
      'event' => $event,
      'progress' => [
        'type' => 'throbber',
        'message' => t('Getting display Ids...'),
      ],
    ];

    $default_value = isset($items[$delta]->getValue()['display_id']) ? $items[$delta]->getValue()['display_id'] : '';
    if ($default_value == '') {
      $options = $this->getAllViewsDisplayIds();
    }
    else {
      $options = $this->getViewDisplayIds($items[$delta]->getValue()['target_id']);
    }

    // Build our target_id field name attribute from the parent elements.
    $field_name = $items->getName();
    $field_path = !empty($element['target_id']['#field_parents']) ? $element['target_id']['#field_parents'] : [];
    $original_field_path = $field_path = array_merge($field_path, [$field_name, $delta, 'target_id']);
    $name = array_shift($field_path);
    foreach ($field_path as $field_path_element) {
      $name .= '[' . $field_path_element . ']';
    }
    // We build a unique class name from field elements
    // And any parent elements that might exist
    // Which will be used to render the display id options in our ajax function.
    $class = implode('-', $original_field_path) . '-display';

    $element['display_id'] = [
      '#title' => 'Display Id',
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value,
      '#weight' => 10,
      '#prefix' => '<span id="' . $class . '">',
      '#suffix' => '</span>',
      '#attributes' => [
        'class' => [
          'viewsreference-display-id',
        ],
      ],
      '#states' => [
        'visible' => [
          ':input[name="' . $name . '"]' => $test,
        ],
      ],
    ];

    $element['options'] = [
      '#type' => 'details',
      '#title' => t('Options'),
      '#weight' => 10,
    ];

    // Title and argument are the original options included in this module.
    $element['options']['title'] = [
      '#title' => 'Include View Title',
      '#type' => 'checkbox',
      '#default_value' => isset($items[$delta]->getValue()['title']) ? $items[$delta]->getValue()['title'] : '',
      '#weight' => 20,
      '#states' => [
        'visible' => [
          ':input[name="' . $name . '"]' => $test,
        ],
      ],
    ];

    $element['options']['argument'] = [
      '#title' => 'Argument',
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->getValue()['argument']) ? $items[$delta]->getValue()['argument'] : '',
      '#weight' => 21,
      '#states' => [
        'visible' => [
          ':input[name="' . $name . '"]' => $test,
        ],
      ],
    ];

    $element['#attached']['library'][] = 'viewsreference/viewsreference';

    return $element;
  }

  /**
   * AJAX function to get display IDs for a particular View.
   */
  public function getDisplayIds(array &$form, FormStateInterface $form_state) {

    $trigger = $form_state->getTriggeringElement();
    $field_value = NestedArray::getValue($form_state->getValues(), $trigger['#parents']);

    // Get the value for the target id of the View.
    switch ($trigger['#type']) {
      case 'select':
        $view_id = $field_value[$trigger['#delta']][$trigger['#key_column']];
        break;

      default:
        $view_id = $field_value;
        break;
    }

    // Obtain the display ids for the given View.
    // We recreate the same unique class as in the parent function.
    $element_class_selector = '#' . implode('-', $trigger['#parents']) . '-display';

    // Change the display_id field options.
    $display_id_field_path = array_slice($trigger['#array_parents'], 0, -1);
    array_push($display_id_field_path, 'display_id');
    $display_id_field = NestedArray::getValue($form, $display_id_field_path);
    $display_id_field['#options'] = $this->getViewDisplayIds($view_id);

    // Construct the html.
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand($element_class_selector, render($display_id_field)));
    return $response;
  }

  /**
   * Helper function to get the current entity_id value.
   *
   * The value is taken from the values array based on parent array.
   *
   * @param array $values
   *   Field array.
   * @param array $parents
   *   Element parents.
   *
   * @return array|bool
   *   The entity id.
   */
  protected function getViewId(array $values, array $parents) {
    $key = array_shift($parents);
    $values = $values[$key];
    if ($parents) {
      $values = $this->getViewId($values, $parents);
    }
    return $values;

  }

  /**
   * Helper function to get all display ids.
   */
  protected function getAllViewsDisplayIds() {
    $views = Views::getAllViews();
    $options = [];
    foreach ($views as $view) {
      foreach ($view->get('display') as $display) {
        $options[$display['id']] = $display['display_title'];
      }
    }
    return $options;
  }

  /**
   * Helper to get display ids for a particular View.
   */
  protected function getViewDisplayIds($view_id) {

    $options = [];
    $view_plugins = array_diff($this->getFieldSetting('plugin_types'), ["0"]);
    if ($view = \Drupal::service('entity.manager')->getStorage('view')->load($view_id)) {
      foreach ($view->get('display') as $display) {
        if (in_array($display['display_plugin'], $view_plugins)) {
          $options[$display['id']] = $display['display_title'];
        }
      }
    }
    return $options;
  }

  /**
   * Helper to convert view array of machine names to label names.
   */
  protected function getViewNames($views_array) {
    foreach ($views_array as $key => $value) {
      $view = Views::getView($key);
      $views_list[$view->storage->id()] = $view->storage->label();
    }
    return $views_list;
  }

  /**
   * Helper function to flatten options array.
   */
  public function massageValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (is_array($value['options'])) {
        foreach ($value['options'] as $ind => $option) {
          $values[$key][$ind] = $option;
        }
        unset($value['options']);
      }
    }
    return $values;
  }

}

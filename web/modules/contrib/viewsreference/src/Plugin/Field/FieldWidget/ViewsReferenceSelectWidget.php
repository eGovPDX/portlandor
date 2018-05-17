<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'options_select' widget.
 *
 * @FieldWidget(
 *   id = "viewsreference_select",
 *   label = @Translation("Views Reference Select list"),
 *   description = @Translation("An autocomplete views select list field."),
 *   field_types = {
 *     "viewsreference"
 *   }
 * )
 */
class ViewsReferenceSelectWidget extends OptionsSelectWidget {

  use ViewsReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $select_element['target_id'] = parent::formElement($items, $delta, $element, $form, $form_state);

    $select_element = $this->fieldElement($select_element, $items, $delta);
    $select_element['target_id']['#multiple'] = FALSE;
    if (!$this->isDefaultValueWidget($form_state)) {
      $selected_views = $items->getSetting('preselect_views');
      $selected_views = array_diff($selected_views, ["0"]);
      $selected_views = $this->getViewNames($selected_views);
      if (count($selected_views) >= 1) {
        $first_option = ["- None -"];
        $select_element['target_id']['#options'] = array_merge($first_option, $selected_views);
      }
      else {
        $select_element['target_id']['#empty_option'] = '- None -';
      }
    }

    return $select_element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Options creates an array which we need to flatten.
    $values = $this->massageValues($values, $form, $form_state);
    return $values;
  }

}

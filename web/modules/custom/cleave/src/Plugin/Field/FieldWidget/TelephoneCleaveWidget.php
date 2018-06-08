<?php

namespace Drupal\cleave\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget;

/**
 * Plugin implementation of the 'telephone_cleave' widget.
 *
 * @FieldWidget(
 *   id = "telephone_cleave",
 *   label = @Translation("Telephone number using Cleave.js"),
 *   field_types = {
 *     "telephone"
 *   }
 * )
 */
class TelephoneCleaveWidget extends TelephoneDefaultWidget {
  
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'country' => 'US',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['country'] = [
      '#type' => 'textfield',
      '#size' => 2,
      '#title' => t('Country code'),
      '#default_value' => $this->getSetting('country'),
      '#description' => t('Enter the two character country code to format the telephone number.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $country = $this->getSetting('country');
    if (!empty($country)) {
      $summary[] = t('Country: @country', ['@country' => $country]);
    }
    else {
      $summary[] = t('No country entered');
    }

    return $summary;
  }
  
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $id = Html::getUniqueId('cleave-telephone');

    $element['value'] = $element + [
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#attributes' => [
        'id' => $id,
        'class' => ['cleave-telephone'],
      ],
      '#attached' => [
        'library' => [
          'cleave/telephone',
        ],
        'drupalSettings' => [
          'cleave_telephone' => [
            $id => [
              'phoneRegionCode' => $this->getSetting('country'),
            ],
          ],
        ],
      ],
    ];

    return $element;
  }
}
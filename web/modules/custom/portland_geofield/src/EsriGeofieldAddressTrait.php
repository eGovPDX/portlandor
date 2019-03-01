<?php

namespace Drupal\portland_geofield;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class EsriGeofieldAddressTrait.
 *
 * Provide common settings-related functions for Geofield field UI.
 *
 * @package Drupal\portland_geofield
 */
trait EsriGeofieldAddressTrait {
  function getAddressFieldSettingsFormElement(array &$elements, array $form, FormStateInterface $form_state) {
    $fields_list = $this->entityFieldManager->getFieldMapByFieldType('address');

    if($fields_list) {
      $address_fields_options = [];
      $string_fields_options = [];

      foreach ($fields_list[$form['#entity_type']] as $k => $field) {
        if (in_array(
            $form['#bundle'], $field['bundles']) &&
          !in_array($k, ['title', 'revision_log'])) {
          $string_fields_options[$k] = $k;
        }
      }

      $elements['address_field'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Geoaddressed Field'),
        '#description' => $this->t('Choose an address field to sync and populate with the Search / Reverse Geocoded Address.<br><strong> Note: In case of a multivalue Geofield, this is run just from the first Geofield Map</strong>'),
      );

      $elements['address_field']['field'] = array(
        '#type' => 'select',
        '#title' => $this->t('Choose an existing address field where to store the Searched / Reverse Geocoded Address'),
        '#description' => $this->t('Choose among the address fields of this content type'),
        '#options' => $string_fields_options,
        '#default_value' => $this->getSetting('address_field')['field'],
      );
    }
  }

  function getAddressSettingsSummary() {
    $settings = [
      '#markup' => $this->t('Address Field - @field', [
        '@field' => $this->getSetting('address_field')['field'],
      ]),
    ];

    return $settings;
  }
}

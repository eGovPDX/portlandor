<?php

namespace Drupal\portland_geofield;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common functions for Geofield Map fields.
 *
 * @package Drupal\portland_geofield
 */
trait EsriGeofieldMapTrait {

  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'dimensions' => [
        'width' => '100%',
        'height' => '30vh',
      ],
      'center' => [
        'lat' => '45.523452',
        'lon' => '-122.676207',
      ],
    ];
  }

  /**
   * Generate the Google Map Settings Form.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $settings
   *   Form settings.
   * @param array $default_settings
   *   Default settings.
   *
   * @return array
   *   The GMap Settings Form*/
  public function getSettingsFormElement($form, FormStateInterface $form_state, $settings, $default_settings) {

    // If it is a Field Formatter, then get the field definition.
    /* @var \Drupal\Core\Field\FieldDefinitionInterface|NULL $fieldDefinition */
    $fieldDefinition = property_exists(get_class($this), 'fieldDefinition') ? $this->fieldDefinition : NULL;

    // Get the configurations of possible entity fields.
    $fields_configurations = $this->entityFieldManager->getFieldStorageDefinitions('node');

    $elements = [];

    $elements['dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    ];

    $elements['dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $settings['dimensions']['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
    $elements['dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $settings['dimensions']['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];

    $elements['center'] = [
      '#type' => 'geofield_latlon',
      '#title' => $this->t('Default Center'),
      '#default_value' => $settings['center'],
      '#size' => 25,
      '#description' => $this->t('If there are no entries on the map, where should the map be centered?'),
      '#geolocation' => TRUE,
    ];

    return $elements;

  }
}

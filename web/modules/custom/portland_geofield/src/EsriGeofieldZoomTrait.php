<?php

namespace Drupal\portland_geofield;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common settings-related functions for Geofield field UI.
 *
 * @package Drupal\portland_geofield
 */
trait EsriGeofieldZoomTrait {
  static function getZoomDefaultSettings() {
    return array(
      'zoom' => array(
        'min' => 1,
        'max' => 20,
        'focus' => 12,
        'start' => 8,
      ),
    );
  }

  function getZoomActiveSettings() {

  }

  function getZoomSettingsSummary() {
    $map_zoom_levels = [
      '#markup' => $this->t('Zoom Levels -'),
    ];

    $map_zoom_levels['#markup'] .= ' ' . $this->t('Start: @state;', array('@state' => $this->getSetting('zoom')['start']));
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Focus: @state;', array('@state' => $this->getSetting('zoom')['focus']));
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Min: @state;', array('@state' => $this->getSetting('zoom')['min']));
    $map_zoom_levels['#markup'] .= ' ' . $this->t('Max: @state;', array('@state' => $this->getSetting('zoom')['max']));

    return $map_zoom_levels;
  }

  function getZoomSettingsFormElement(array &$elements) {
    $elements['zoom'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Zoom Settings'),
    );
    $elements['zoom']['start'] = array(
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Start Zoom level'),
      '#description' => $this->t('The initial Zoom level for an empty Geofield.'),
      '#default_value' => $this->getSetting('zoom')['start'],
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    );
    $elements['zoom']['focus'] = array(
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Focus Zoom level'),
      '#description' => $this->t('The Zoom level for an assigned Geofield or for Geocoding operations results.'),
      '#default_value' => $this->getSetting('zoom')['focus'],
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    );
    $elements['zoom']['min'] = array(
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Min Zoom level'),
      '#description' => $this->t('The Minimum Zoom level for the Map.'),
      '#default_value' => $this->getSetting('zoom')['min'],
    );
    $elements['zoom']['max'] = array(
      '#type' => 'number',
      '#min' => $this->getSetting('zoom')['min'],
      '#max' => $this->getSetting('zoom')['max'],
      '#title' => $this->t('Max Zoom level'),
      '#description' => $this->t('The Maximum Zoom level for the Map.'),
      '#default_value' => $this->getSetting('zoom')['max'],
      '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
    );
  }

  /**
   * Form element validation handler for a Map Zoom level.
   *
   * {@inheritdoc}
   */
  public static function zoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the initial map zoom level.
    $zoom = $element['#value'];
    $min_zoom = $values['min'];
    $max_zoom = $values['max'];
    if ($zoom < $min_zoom || $zoom > $max_zoom) {
      $form_state->setError($element, t('The @zoom_field should be between the Minimum and the Maximum Zoom levels.', ['@zoom_field' => $element['#title']]));
    }
  }

  /**
   * Form element validation handler for the Map Max Zoom level.
   *
   * {@inheritdoc}
   */
  public static function maxZoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the max zoom level.
    $min_zoom = $values['min'];
    $max_zoom = $element['#value'];
    if ($max_zoom && $max_zoom <= $min_zoom) {
      $form_state->setError($element, t('The Max Zoom level should be above the Minimum Zoom level.'));
    }
  }
}

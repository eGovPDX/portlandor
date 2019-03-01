<?php

/**
 * @file
 * Definition of Drupal\leaflet\Plugin\Field\FieldFormatter\LeafletDefaultFormatter.
 */

namespace Drupal\portland_geofield\Plugin\Field\FieldFormatter;

use Drupal\portland_geofield\EsriGeofieldMapTrait;
use Drupal\portland_geofield\EsriGeofieldZoomTrait;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @FieldFormatter(
 *   id = "portland_geofield_formatter",
 *   label = @Translation("ESRI map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class EsriGeofieldFormatter extends FormatterBase {

  use EsriGeofieldMapTrait;
  use EsriGeofieldZoomTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = array(
      'center' => [
        'lat' => '45.523452',
        'lon' => '-122.676207',
      ],
      'dimensions' => [
        'width' => '100%',
        'height' => '30em',
      ],
      'basemap' => 'streets-vector',
      'address_field' => [
        'field' => '0',
        'hidden' => FALSE,
        'disabled' => TRUE,
      ],
    );
    $settings += self::getZoomDefaultSettings();
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    // this allows the settings to pass down to the view layer as an object with hierarchy intact
    $elements['#tree'] = TRUE;

    $elements['center'] = [
      'lat' => [
        '#type' => 'value',
        '#value' => $this->getSetting('center')['lat'],
      ],
      'lon' => [
        '#type' => 'value',
        '#value' => $this->getSetting('center')['lon'],
      ],
    ];

    $elements['dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    ];
    $elements['dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $this->getSetting('dimensions')['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
    $elements['dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $this->getSetting('dimensions')['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Google map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];

    $this->getZoomSettingsFormElement($elements);

    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary['dimensions'] = [
      '#markup' => $this->t('Map Dimensions - Width: @width; Height: @height', [
        '@width' => $this->getSetting('dimensions')['width'],
        '@height' => $this->getSetting('dimensions')['height'],
      ]),
    ];

    $summary['zoom'] = $this->getZoomSettingsSummary();

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $values = [];
    foreach ($items as $delta => $item) {
      $values[$delta] = [
        'wkt' => $item->value,
      ];
    }

    $entity = $items->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id();
    $field = $items->getFieldDefinition();

    $mapid = Html::getUniqueId("portland_geofield_map_{$bundle}_{$entity_id}_{$field->getName()}");

    $element = array(
      '#type' => 'portland_geofield_formatter',
      '#mapid' => $mapid,
      '#value' => $values,
      '#basemap' => 'streets-vector',
      '#center' => $this->getSetting('center'),
      '#dimensions' => $this->getSetting('dimensions'),
      '#zoom' => $this->getSetting('zoom'),
    );

    return $element;
  }

  public function validateUrl($element, FormStateInterface $form_state) {
    if (!empty($element['#value']) && !UrlHelper::isValid($element['#value'])) {
      $form_state->setError($element, $this->t("Icon Url is not valid."));
    }
  }

}

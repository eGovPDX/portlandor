<?php

namespace Drupal\portland_geofield\Plugin\Field\FieldWidget;

use Drupal\portland_geofield\EsriGeofieldMapTrait;
use Drupal\portland_geofield\EsriGeofieldZoomTrait;
use Drupal\portland_geofield\EsriGeofieldAddressTrait;
use Drupal\Core\Url;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Plugin implementation of the 'portland_geofield' widget.
 *
 * @FieldWidget(
 *   id = "portland_geofield",
 *   label = @Translation("ESRI Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class EsriGeofieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use EsriGeofieldMapTrait;
  use EsriGeofieldZoomTrait;
  use EsriGeofieldAddressTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The EntityField Manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * GeofieldMapWidget constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The Translation service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The Entity Field Manager.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->config = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('config.factory'),
      $container->get('string_translation'),
      $container->get('entity_field.manager')
    );
  }

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
      'show_wkt' => 0,
    );
    $settings += self::getZoomDefaultSettings();
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $default_settings = self::defaultSettings();

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

    $this->getAddressFieldSettingsFormElement($elements, $form, $form_state);

    $elements['show_wkt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the WKT textarea.'),
      '#default_value' => $this->getSetting('show_wkt'),
    ];

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

    $summary['address'] = $this->getAddressSettingsSummary();

    return $summary;
  }

  /**
   * Implements \Drupal\field\Plugin\Type\Widget\WidgetInterface::formElement().
   *
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // need a center location in this format, not wkt
    $components = ['lat', 'lon'];
    $values = [];

    if(isset($items[$delta]->value)) {
      $values = [
        [
          'wkt' => $items[$delta]->value
        ],
      ];
    }

    foreach ($components as $component) {
      $latlon_value[$component] = isset($items[$delta]->{$component}) ? floatval($items[$delta]->{$component}) : $this->getSetting('center')[$component];
    }

    $element += [
      '#type' => 'portland_geofield_widget',
      '#default_value' => $values,
      '#basemap' => 'streets-vector',
      '#center' => $latlon_value,
      '#dimensions' => $this->getSetting('dimensions'),
      '#zoom' => $this->getSetting('zoom'),
      '#address_field' => $this->getSetting('address_field'),
      '#show_wkt' => $this->getSetting('show_wkt'),
      '#error_label' => !empty($element['#title']) ? $element['#title'] : $this->fieldDefinition->getLabel(),
    ];

    return ['value' => $element];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $geophp = \Drupal::service('geofield.geophp');
    foreach ($values as $delta => $value) {
      if ($geom = $geophp->load($value['value']['wkt'])) {
        $values[$delta]['value'] = $geom->out('wkt');
      }
    }

    return $values;
  }

}

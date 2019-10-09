<?php

namespace Drupal\portland_leaflet_maps\Plugin\Field\FieldFormatter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Leaflet\LeafletService;
use Drupal\leaflet\LeafletSettingsElementsTrait;
use Drupal\Core\Utility\Token;
use Drupal\core\Render\Renderer;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Plugin implementation of the 'leaflet_default' formatter.
 *
 * @FieldFormatter(
 *   id = "leaflet_formatter_default",
 *   label = @Translation("Portland Leaflet Map"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class LeafletDefaultFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  use LeafletSettingsElementsTrait;

  /**
   * The Default Settings.
   *
   * @var array
   */
  protected $defaultSettings;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The token service.
   *
   * @var \Drupal\core\Utility\Token
   */
  protected $token;

  /**
   * The renderer service.
   *
   * @var \Drupal\core\Render\Renderer
   */
  protected $renderer;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * LeafletDefaultFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   * @param \Drupal\core\Utility\Token $token
   *   The token service.
   * @param \Drupal\core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    LeafletService $leaflet_service,
    Token $token,
    Renderer $renderer,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->defaultSettings = self::getDefaultSettings();
    $this->leafletService = $leaflet_service;
    $this->token = $token;
    $this->renderer = $renderer;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('leaflet.service'),
      $container->get('token'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::getDefaultSettings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();

    $form['#tree'] = TRUE;

    // Get the Cardinality set for the Formatter Field.
    $field_cardinality = $this->fieldDefinition->getFieldStorageDefinition()
      ->getCardinality();

    $elements = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();

    if ($field_cardinality !== 1) {
      $elements['multiple_map'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Multiple Maps'),
        '#description' => $this->t('Check this option if you want to render a single Map for every single Geo Point.'),
        '#default_value' => $settings['multiple_map'],
        '#return_value' => 1,
      ];
    }
    else {
      $elements['multiple_map'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }

    $elements['popup'] = [
      '#title' => $this->t('Popup Infowindow'),
      '#description' => $this->t('Show a Popup Infowindow on Marker click, with custom content.'),
      '#type' => 'checkbox',
      '#default_value' => $settings['popup'],
    ];

    $elements['popup_content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Popup content'),
      '#description' => $this->t('Define the custom content for the Pop Infowindow. If empty the Content Title will be output.<br>See "REPLACEMENT PATTERNS" below for available replacements.'),
      '#default_value' => $settings['popup_content'],
      '#states' => [
        'visible' => [
          'input[name="fields[' . $field_name . '][settings_edit_form][settings][popup]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->moduleHandler->moduleExists('token')) {

      $elements['replacement_patterns'] = [
        '#type' => 'details',
        '#title' => 'Replacement patterns',
        '#description' => $this->t('The following replacement tokens are available for the "Popup Content and the Icon Options":'),
        '#states' => [
          'visible' => [
            'input[name="fields[' . $field_name . '][settings_edit_form][settings][popup]"]' => ['checked' => TRUE],
          ],
        ],
      ];

      $elements['replacement_patterns']['token_help'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
      ];
    }
    else {
      $elements['replacement_patterns']['#description'] = $this->t('The @token_link is needed to browse and use @entity_type entity token replacements.', [
        '@token_link' => $this->link->generate(t('Token module'), Url::fromUri('https://www.drupal.org/project/token', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
        '@entity_type' => $this->fieldDefinition->getTargetEntityTypeId(),
      ]);
    }

    // Generate the Leaflet Map General Settings.
    $this->generateMapGeneralSettings($elements, $settings);

    // Generate the Leaflet Map Reset Control.
    $this->setResetMapControl($elements, $settings);

    // Generate the Leaflet Map Position Form Element.
    $map_position_options = $settings['map_position'];
    $elements['map_position'] = $this->generateMapPositionElement($map_position_options);

    // Generate Icon form element.
    $icon = $settings['icon'];
    $elements['icon'] = $this->generateIconFormElement($icon);

    // Set Map Marker Cluster Element.
    $this->setMapMarkerclusterElement($elements, $settings);

    // Set Map Geometries Options Element.
    $this->setMapPathOptionsElement($elements, $settings);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Leaflet Map: @map', ['@map' => $this->getSetting('leaflet_map')]);
    $summary[] = $this->t('Map height: @height px', ['@height' => $this->getSetting('height')]);
    $summary[] = $this->t('Popup Infowindow: @popup', ['@popup' => $this->getSetting('popup') ? $this->t('Yes') : $this->t('No')]);
    if ($this->getSetting('popup') && $this->getSetting('popup_content')) {
      $summary[] = $this->t('Popup content: @popup_content', ['@popup_content' => $this->getSetting('popup_content')]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This function is called from parent::view().
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    // Take the entity translation, if existing.
    /* @var \Drupal\Core\TypedData\TranslatableInterface $entity */
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }

    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_id = $entity->id();
    /* @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    $field = $items->getFieldDefinition();

    // Sets/consider possibly existing previous Zoom settings.
    $this->setExistingZoomSettings();
    $settings = $this->getSettings();

    // Performs some preprocess on the leaflet map settings.
    $this->leafletService->preProcessMapSettings($settings);

    // Always render the map, even if we do not have any data.
    $map = leaflet_map_get_info($settings['leaflet_map']);

    // Add a specific map id.
    $map['id'] = Html::getUniqueId("leaflet_map_{$entity_type}_{$bundle}_{$entity_id}_{$field->getName()}");

    // Get and set the Geofield cardinality.
    // $map['geofield_cardinality'] = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    // Set Map additional map Settings.
    $this->setAdditionalMapOptions($map, $settings);

    // Get token context.
    $token_context = [
      'field' => $items,
      $this->fieldDefinition->getTargetEntityTypeId() => $items->getEntity(),
    ];

    $results = [];
    $features = [];

    foreach ($items as $delta => $item) {

      $points = $this->leafletService->leafletProcessGeofield($item->value);
      // $feature = $points[0];
      $feature['entity_id'] = $entity_id;

      $file_url = file_create_url($item->entity->uri->value);

      if(substr($item->entity->uri->value, -strlen('.zip')) === '.zip') {
        $file_type = 'shapefile';
      }
      else {
        $file_type = 'geojson'; // Default to GeoJSON
      }

      $feature['feature_source'] = 'file';
      $feature['file_url'] = $file_url;
      $feature['file_type'] = $file_type;

      // Eventually set the popup content.
      if ($settings['popup']) {
        // Construct the renderable array for popup title / text. As we later
        // convert that to plain text, losing attachments and cacheability, save
        // them to $results.
        $build = [];
        if ($this->getSetting('popup_content')) {
          $bubbleable_metadata = new BubbleableMetadata();
          $popup_content = $this->token->replace($this->getSetting('popup_content'), $token_context, [], $bubbleable_metadata);
          $build[] = [
            '#markup' => $popup_content,
          ];
          $bubbleable_metadata->applyTo($results);
        }

        // We need a string for using it inside the popup. Save attachments and
        // cacheability to $results.
        $render_context = new RenderContext();
        $rendered = $this->renderer->executeInRenderContext($render_context, function () use (&$build) {
          return $this->renderer->render($build, TRUE);
        });
        $feature['popup'] = !empty($rendered) ? $rendered : $entity->label();
        if (!$render_context->isEmpty()) {
          $render_context->update($results);
        }
      }

      // Add/merge eventual map icon definition from hook_leaflet_map_info.
      if (!empty($map['icon'])) {
        $settings['icon'] = $settings['icon'] ?: [];
        // Remove empty icon options so that they might be replaced by the
        // ones set by the hook_leaflet_map_info.
        foreach ($settings['icon'] as $k => $icon_option) {
          if (empty($icon_option) || (is_array($icon_option) && $this->leafletService->multipleEmpty($icon_option))) {
            unset($settings['icon'][$k]);
          }
        }
        $settings['icon'] = array_replace($map['icon'], $settings['icon']);
      }

      // Eventually set the custom icon.
      if (!empty($settings['icon']['iconUrl'])) {
        $settings['icon']['iconUrl'] = !empty($settings['icon']['iconUrl']) > 0 ? $this->token->replace($settings['icon']['iconUrl'], $token_context) : '';
        $settings['icon']['shadowUrl'] = !empty($settings['icon']['shadowUrl']) > 0 ? $this->token->replace($settings['icon']['shadowUrl'], $token_context) : '';
        $feature['icon'] = $settings['icon'];
      }

      $features[] = $feature;
    }

    $js_settings = [
      'map' => $map,
      'features' => $features,
    ];

    // Allow other modules to add/alter the map js settings.
    $this->moduleHandler->alter('leaflet_default_map_formatter', $js_settings, $items);

    if (!empty($settings['multiple_map'])) {
      foreach ($js_settings['features'] as $k => $feature) {
        $map = $js_settings['map'];
        $map['id'] = $map['id'] . "-{$k}";
        $results[] = $this->leafletService->leafletRenderMap($map, [$feature], $settings['height'] . 'px');
      }
    }
    // Render the map, if we do have data or the hide option is unchecked.
    elseif ( !empty($js_settings['features']) || empty($settings['hide_empty_map'])) {
      $results[] = $this->leafletService->leafletRenderMap($js_settings['map'], $js_settings['features'], $settings['height'] . 'px');
    }

    return $results;
  }

  /**
   * Sets possibly existing previous settings for the Zoom Form Element.
   */
  private function setExistingZoomSettings() {
    $settings = $this->getSettings();
    if (isset($settings['zoom'])) {
      $settings['map_position']['zoom'] = (int) $settings['zoom'];
      $settings['map_position']['minZoom'] = (int) $settings['minZoom'];
      $settings['map_position']['maxZoom'] = (int) $settings['maxZoom'];
      $this->setSettings($settings);
    }
  }

}

<?php

namespace Drupal\leaflet;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url as CoreUrl;
use Drupal\views\Plugin\views\ViewsPluginInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * Class GeofieldMapFieldTrait.
 *
 * Provide common functions for Leaflet Settings Elements.
 *
 * @package Drupal\leaflet
 */
trait LeafletSettingsElementsTrait {

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $gMapTypesOptions = [
    'roadmap' => 'Roadmap',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
    'terrain' => 'Terrain',
  ];

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $infowindowFieldTypesOptions = [
    'string_long',
    'string',
    'text',
    'text_long',
    "text_with_summary",
  ];

  protected $customMapStylePlaceholder = '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0e1626"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#4e6d70"}]}]';

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface $this->link
   */

  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'multiple_map' => 0,
      'leaflet_map' => 'OSM Mapnik',
      'height' => 400,
      'hide_empty_map' => 0,
      'disable_wheel' => 0,
      'fullscreen_control' => 1,
      'reset_map' => [
        'control' => 0,
        'position' => 'topright',
      ],
      'popup' => FALSE,
      'popup_content' => '',
      'map_position' => [
        'force' => 0,
        'center' => [
          'lat' => 0,
          'lon' => 0,
        ],
        'zoom' => 12,
        'minZoom' => 1,
        'maxZoom' => 18,
        'zoomFiner' => 0,
      ],
      'icon' => [
        'iconUrl' => '',
        'iconSize' => ['x' => NULL, 'y' => NULL],
        'iconAnchor' => ['x' => NULL, 'y' => NULL],
        'shadowUrl' => '',
        'shadowSize' => ['x' => NULL, 'y' => NULL],
        'shadowAnchor' => ['x' => NULL, 'y' => NULL],
        'popupAnchor' => ['x' => NULL, 'y' => NULL],
      ],
      'leaflet_markercluster' => [
        'control' => 0,
        'options' => '{"spiderfyOnMaxZoom":true,"showCoverageOnHover":true,"removeOutsideVisibleBounds": false}',
      ],
      'path' => '{"color":"#3388ff","opacity":"1.0","stroke":true,"weight":3,"fill":"depends","fillColor":"*","fillOpacity":"0.2"}',
    ];
  }

  /**
   * Generate the Leaflet Map General Settings.
   *
   * @param array $elements
   *   The form elements.
   * @param array $settings
   *   The settings.
   */
  protected function generateMapGeneralSettings(array &$elements, array $settings) {

    $leaflet_map_options = [];
    foreach (leaflet_map_get_info() as $key => $map) {
      $leaflet_map_options[$key] = $map['label'];
    }

    $leaflet_map = isset($settings['leaflet_map']) ? $settings['leaflet_map'] : $settings['map'];

    $elements['leaflet_map'] = [
      '#title' => $this->t('Leaflet Map'),
      '#type' => 'select',
      '#options' => $leaflet_map_options,
      '#default_value' => $leaflet_map,
      '#required' => TRUE,
    ];

    $elements['height'] = [
      '#title' => $this->t('Map Height'),
      '#type' => 'number',
      '#default_value' => $settings['height'],
      '#field_suffix' => $this->t('px'),
    ];

    $elements['hide_empty_map'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide Map if empty'),
      '#description' => $this->t('Check this option not to render the Map at all, if empty (no output results).'),
      '#default_value' => $settings['hide_empty_map'],
      '#return_value' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="fields[field_geofield][settings_edit_form][settings][multiple_map]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $elements['disable_wheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable zoom using mouse wheel'),
      '#description' => $this->t("If enabled, the mouse wheel won't change the zoom level of the map."),
      '#default_value' => $settings['disable_wheel'],
      '#return_value' => 1,
    ];

    $elements['fullscreen_control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fullscreen Control'),
      '#description' => $this->t('Enable the Fullscreen View of the Map.'),
      '#default_value' => $settings['fullscreen_control'],
      '#return_value' => 1,
    ];

  }

  /**
   * Generate the Leaflet Map Position Form Element.
   *
   * @param array $map_position_options
   *   The map position options array definition.
   *
   * @return array
   *   The Leaflet Map Position Form Element.
   */
  protected function generateMapPositionElement(array $map_position_options) {

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Starting Map State'),
    ];

    $force_checkbox_selector = ':input[name="fields[field_geofield][settings_edit_form][settings][map_position][force]"]';
    if ($this instanceof ViewsPluginInterface) {
      $force_checkbox_selector = ':input[name="style_options[map_position][force]"]';
    }

    $element['description'] = [
      '#type' => 'container',
      'html_tag' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('These settings will be applied in case of single Marker Map (otherwise the Zoom will be set to Fit Markers bounds).'),
      ],
      '#states' => [
        'invisible' => [
          $force_checkbox_selector => ['checked' => TRUE],
        ],
      ],
    ];

    $element['force'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('These settings will be forced anyway as starting Map state.'),
      '#default_value' => $map_position_options['force'],
      '#return_value' => 1,
    ];

    if ($this instanceof ViewsPluginInterface) {
      $element['#title'] = $this->t('Custom Map Center & Zoom');
      $element['description']['#value'] = $this->t('These settings will be applied in case of empty Map.');
      $element['force']['#title'] = $this->t('Force Map Center & Zoom');
    }
    else {
      $element['force']['#title'] = $this->t('Force Map Zoom');
    }

    if ($this instanceof ViewsPluginInterface) {
      $element['center'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Map Center'),
        'lat' => [
          '#title' => $this->t('Latitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#default_value' => $map_position_options['center']['lat'],
          '#required' => FALSE,
        ],
        'lon' => [
          '#title' => $this->t('Longitude'),
          '#type' => 'number',
          '#step' => 'any',
          '#size' => 4,
          '#default_value' => $map_position_options['center']['lon'],
          '#required' => FALSE,
        ],
      ];
    }

    $element['zoom'] = [
      '#title' => $this->t('Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 18,
      '#default_value' => $map_position_options['zoom'],
      '#required' => TRUE,
      '#element_validate' => [[get_class($this), 'zoomLevelValidate']],
    ];

    if ($this instanceof ViewsPluginInterface) {
      $element['zoom']['#description'] = $this->t('These setting will be applied (anyway) to a single Marker Map.');
    }

    $element['minZoom'] = [
      '#title' => $this->t('Min. Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 18,
      '#default_value' => $map_position_options['minZoom'],
      '#required' => TRUE,
    ];

    $element['maxZoom'] = [
      '#title' => $this->t('Max. Zoom'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 22,
      '#default_value' => $map_position_options['maxZoom'],
      '#element_validate' => [[get_class($this), 'maxZoomLevelValidate']],
      '#required' => TRUE,
    ];

    $element['zoomFiner'] = [
      '#title' => $this->t('Zoom Finer'),
      '#type' => 'number',
      '#max' => 5,
      '#min' => -5,
      '#step' => 0,
      '#description' => $this->t('Value that might/will be added to default Fit Markers Bounds Zoom. (-5 / +5)'),
      '#default_value' => $map_position_options['zoomFiner'] ?? $this->defaultSettings['map_position']['zoomFiner'],
      '#states' => [
        'invisible' => [
          $force_checkbox_selector => ['checked' => TRUE],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Generate the Leaflet Icon Form Element.
   *
   * @param array $icon_options
   *   The icon array definition.
   *
   * @return array
   *   The Leaflet Icon Form Element.
   */
  protected function generateIconFormElement(array $icon_options) {

    $icon_url_description = $this->t('Can be an absolute or relative URL.<br><b>Note: </b> Using Tokens it is possible to dynamically define the Marker Icon output, with the composition of Marker Icon paths including entity properties or fields values.');

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Icon'),
      'description' => [
        '#markup' => $this->t('For details on the following setup refer to @leaflet_icon_documentation_link', [
          '@leaflet_icon_documentation_link' => $this->leafletService->leafletIconDocumentationLink(),
        ]),
      ],
    ];

    $element['iconUrl'] = [
      '#title' => $this->t('Icon URL'),
      '#description' => $icon_url_description,
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => isset($icon_options['iconUrl']) ? $icon_options['iconUrl'] : NULL,
    ];

    $element['shadowUrl'] = [
      '#title' => $this->t('Icon Shadow URL'),
      '#description' => $icon_url_description,
      '#type' => 'textarea',
      '#rows' => 3,
      '#default_value' => isset($icon_options['shadowUrl']) ? $icon_options['shadowUrl'] : NULL,
    ];

    if (method_exists($this, 'getProvider') && $this->getProvider() == 'leaflet_views') {
      $twig_link = $this->link->generate('Twig', Url::fromUri('http://twig.sensiolabs.org/documentation', [
        'absolute' => TRUE,
        'attributes' => ['target' => 'blank'],
      ])
      );

      $icon_url_description .= '<br>' . $this->t('You may include @twig_link. You may enter data from this view as per the "Replacement patterns" below.', [
        '@twig_link' => $twig_link,
      ]);

      $element['iconUrl']['#description'] = $icon_url_description;
      $element['shadowUrl']['#description'] = $icon_url_description;

      // Setup the tokens for views fields.
      // Code is snatched from Drupal\views\Plugin\views\field\FieldPluginBase.
      $options = [];
      $optgroup_fields = (string) t('Fields');
      if (isset($this->displayHandler)) {
        foreach ($this->displayHandler->getHandlers('field') as $id => $field) {
          /* @var \Drupal\views\Plugin\views\field\EntityField $field */
          $options[$optgroup_fields]["{{ $id }}"] = substr(strrchr($field->label(), ":"), 2);
        }
      }

      // Default text.
      $output = [];
      // We have some options, so make a list.
      if (!empty($options)) {
        $output[] = [
          '#markup' => '<p>' . $this->t("The following replacement tokens are available. Fields may be marked as <em>Exclude from display</em> if you prefer.") . '</p>',
        ];
        foreach (array_keys($options) as $type) {
          if (!empty($options[$type])) {
            $items = array();
            foreach ($options[$type] as $key => $value) {
              $items[] = $key;
            }
            $item_list = array(
              '#theme' => 'item_list',
              '#items' => $items,
            );
            $output[] = $item_list;
          }
        }
      }

      $element['help'] = array(
        '#type' => 'details',
        '#title' => $this->t('Replacement patterns'),
        '#value' => $output,
      );
    }

    $element['iconSize'] = [
      '#title' => $this->t('Icon Size'),
      '#type' => 'fieldset',
      '#description' => $this->t("Size of the icon image in pixels (if empty the natural icon image size will be used).<br>Note: Both the values shouldn't be null to be valid."),
    ];

    $element['iconSize']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconSize']['x']) ? $icon_options['iconSize']['x'] : NULL,
    ];

    $element['iconSize']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconSize']['y']) ? $icon_options['iconSize']['y'] : NULL,
    ];

    $element['iconAnchor'] = [
      '#title' => $this->t('Icon Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t("The coordinates of the 'tip' of the icon (relative to its top left corner). The icon will be aligned so that this point is at the marker\'s geographical location.<br>Note: Both the values shouldn't be null to be valid."),
    ];

    $element['iconAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconAnchor']) ? $icon_options['iconAnchor']['x'] : NULL,
    ];

    $element['iconAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['iconAnchor']) ? $icon_options['iconAnchor']['y'] : NULL,
    ];

    $element['shadowSize'] = [
      '#title' => $this->t('Shadow Size'),
      '#type' => 'fieldset',
      '#description' => $this->t("Size of the shadow image in pixels (if empty the natural shadow image size will be used). <br>Note: Both the values shouldn't be null to be valid."),
    ];

    $element['shadowSize']['x'] = [
      '#title' => $this->t('Width'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowSize']['x']) ? $icon_options['shadowSize']['x'] : NULL,
    ];

    $element['shadowSize']['y'] = [
      '#title' => $this->t('Height'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowSize']['y']) ? $icon_options['shadowSize']['y'] : NULL,
    ];

    $element['shadowAnchor'] = [
      '#title' => $this->t('Shadow Anchor'),
      '#type' => 'fieldset',
      '#description' => $this->t("The coordinates of the 'tip' of the shadow (relative to its top left corner) (the same as iconAnchor if not specified).<br>Note: Both the values shouldn't be null to be valid."),
    ];

    $element['shadowAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowAnchor']) ? $icon_options['shadowAnchor']['x'] : NULL,
    ];

    $element['shadowAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['shadowAnchor']) ? $icon_options['shadowAnchor']['y'] : NULL,
    ];

    $element['popupAnchor'] = [
      '#title' => $this->t('Popup Anchor'),
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#description' => $this->t("The coordinates of the point from which popups will 'open', relative to the icon anchor.<br>Note: Both the values shouldn't be null to be valid."),
    ];

    $element['popupAnchor']['x'] = [
      '#title' => $this->t('X'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['popupAnchor']) ? $icon_options['popupAnchor']['x'] : NULL,
    ];

    $element['popupAnchor']['y'] = [
      '#title' => $this->t('Y'),
      '#type' => 'number',
      '#default_value' => isset($icon_options['popupAnchor']) ? $icon_options['popupAnchor']['y'] : NULL,
    ];

    return $element;
  }

  /**
   * Set Map Geometries Options Element.
   *
   * @param array $element
   *   The Form element to alter.
   * @param array $settings
   *   The Form Settings.
   */
  protected function setMapPathOptionsElement(array &$element, array $settings) {

    $element['path'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Path Geometries Options'),
      '#rows' => 3,
      '#description' => $this->t('Set here options that will be applied to the rendering of Map Path Geometries (Lines & Polylines, Polygons, Multipolygons, etc.).<br>Refer to the @polygons_documentation.<br>Note: If empty the default Leaflet path style, or the one choosen and defined in leaflet.api/hook_leaflet_map_info, will be used.', [
        '@polygons_documentation' => $this->link->generate($this->t('Leaflet Path Documentation'), Url::fromUri('https://leafletjs.com/reference-1.0.3.html#path', [
          'absolute' => TRUE,
          'attributes' => ['target' => 'blank'],
        ])),
      ]),
      '#default_value' => $settings['path'],
      '#placeholder' => $this::getDefaultSettings()['path'],
      '#element_validate' => [[get_class($this), 'jsonValidate']],
    ];
  }

  /**
   * Set Map additional map Settings.
   *
   * @param array $map
   *   The map object.
   * @param array $options
   *   The options from where to set additional options.
   */
  protected function setAdditionalMapOptions(array &$map, array $options) {
    $default_settings = $this::getDefaultSettings();

    // Add additional settings to the Map, with fallback on the
    // hook_leaflet_map_info ones.
    $map['settings']['map_position_force'] = isset($options['map_position']['force']) ? $options['map_position']['force'] : $default_settings['map_position']['force'];
    $map['settings']['zoom'] = isset($options['map_position']['zoom']) ? (int) $options['map_position']['zoom'] : $default_settings['map_position']['zoom'];
    $map['settings']['zoomFiner'] = isset($options['map_position']['zoomFiner']) ? (int) $options['map_position']['zoomFiner'] : $default_settings['map_position']['zoomFiner'];
    $map['settings']['minZoom'] = isset($options['map_position']['minZoom']) ? (int) $options['map_position']['minZoom'] : (isset($map['settings']['minZoom']) ? $map['settings']['minZoom'] : $default_settings['settings']['minZoom']);
    $map['settings']['maxZoom'] = isset($options['map_position']['maxZoom']) ? (int) $options['map_position']['maxZoom'] : (isset($map['settings']['maxZoom']) ? $map['settings']['maxZoom'] : $default_settings['settings']['maxZoom']);
    $map['settings']['center'] = (isset($options['map_position']['center']['lat']) && isset($options['map_position']['center']['lon'])) ? [
      'lat' => floatval($options['map_position']['center']['lat']),
      'lon' => floatval($options['map_position']['center']['lon']),
    ] : $default_settings['map_position']['center'];
    $map['settings']['scrollWheelZoom'] = $options['disable_wheel'] ? !(bool) $options['disable_wheel'] : (isset($map['settings']['scrollWheelZoom']) ? $map['settings']['scrollWheelZoom'] : TRUE);
    $map['settings']['path'] = isset($options['path']) && !empty($options['path']) ? $options['path'] : (isset($map['path']) ? Json::encode($map['path']) : Json::encode($default_settings['path']));
    $map['settings']['leaflet_markercluster'] = isset($options['leaflet_markercluster']) ? $options['leaflet_markercluster'] : NULL;
    $map['settings']['fullscreen_control'] = isset($options['fullscreen_control']) ? $options['fullscreen_control'] : $default_settings['fullscreen_control'];
    $map['settings']['reset_map'] = isset($options['reset_map']) ? $options['reset_map'] : $default_settings['reset_map'];
  }

  /**
   * Set Map MarkerCluster Element.
   *
   * @param array $element
   *   The Form element to alter.
   * @param array $settings
   *   The Form Settings.
   */
  protected function setMapMarkerclusterElement(array &$element, array $settings) {

    $default_settings = $this::getDefaultSettings();
    $leaflet_markercluster_submodule_warning = $this->t("<u>Note</u>: This functionality and settings are related to the Leaflet Markercluster submodule, present inside the Leaflet module itself.<br><u>(DON'T USE the external self standing Leaflet Markecluster module).</u>");

    $element['leaflet_markercluster'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Marker Clustering'),
    ];

    if ($this->moduleHandler->moduleExists('leaflet_markercluster')) {
      $element['leaflet_markercluster']['control'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable the functionality of the @markeclusterer_api_link.', [
          '@markeclusterer_api_link' => $this->link->generate($this->t('Leaflet Markercluster Js Library'), Url::fromUri('https://github.com/Leaflet/Leaflet.markercluster', [
            'absolute' => TRUE,
            'attributes' => ['target' => 'blank'],
          ])),
        ]),
        '#default_value' => isset($settings['leaflet_markercluster']['control']) ? $settings['leaflet_markercluster']['control'] : $default_settings['leaflet_markercluster']['control'],
        '#description' => $this->t("@leaflet_markercluster_submodule_warning", [
          '@leaflet_markercluster_submodule_warning' => $leaflet_markercluster_submodule_warning,
        ]),
        '#return_value' => 1,
      ];
      $element['leaflet_markercluster']['options'] = [
        '#type' => 'textarea',
        '#rows' => 4,
        '#title' => $this->t('Marker Cluster Additional Options'),
        '#description' => $this->t('An object literal of additional marker cluster options, that comply with the Leaflet Markercluster Js Library.<br>The syntax should respect the javascript object notation (json) format.<br>As suggested in the field placeholder, always use double quotes (") both for the indexes and the string values.'),
        '#default_value' => isset($settings['leaflet_markercluster']['options']) ? $settings['leaflet_markercluster']['options'] : $default_settings['leaflet_markercluster']['options'],
        '#placeholder' => $default_settings['leaflet_markercluster']['options'],
        '#element_validate' => [[get_class($this), 'jsonValidate']],
      ];
      if (isset($this->fieldDefinition)) {
        $element['leaflet_markercluster']['options']['#states'] = [
          'visible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][leaflet_markercluster][control]"]' => ['checked' => TRUE],
          ],
        ];
      }
      else {
        $element['leaflet_markercluster']['options']['#states'] = [
          'visible' => [
            ':input[name="style_options[leaflet_markercluster][control]"]' => ['checked' => TRUE],
          ],
        ];
      }
    }
    else {
      $element['leaflet_markercluster']['markup'] = [
        '#markup' => $this->t("Enable the Leaflet Markecluster submodule to activate this functionality.<br>@leaflet_markercluster_submodule_warning", [
          '@leaflet_markercluster_submodule_warning' => $leaflet_markercluster_submodule_warning,
        ]),
      ];
    }
  }

  /**
   * Set Map MarkerCluster Element.
   *
   * @param array $element
   *   The Form element to alter.
   * @param array $settings
   *   The Form Settings.
   */
  protected function setResetMapControl(array &$element, array $settings) {
    $default_settings = $this::getDefaultSettings();

    $element['reset_map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Reset Map Control'),
    ];

    $element['reset_map']['control'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Map Reset Control'),
      '#description' => $this->t('This will show a "Reset Map" button to reset the Map to its initial center & zoom state<br><b><u>Warning: </u></b>Due to an issue in the Leaflet library (@see https://github.com/Leaflet/Leaflet/issues/6172) the Map Reset control doesn\'t work correctly in Fitting Bounds of coordinates having mixed positive and negative values of latitude &longitudes.<br>In this case the Map will be Reset to the default set Map Center.'),
      '#default_value' => isset($settings['reset_map']['control']) ? $settings['reset_map']['control'] : $default_settings['reset_map']['control'],
    ];

    $element['reset_map']['position'] = [
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => [
        'topleft' => 'Top Left',
        'topright' => 'Top Right',
        'bottomleft' => 'Bottom Left',
        'bottomright' => 'Bottom Right',
      ],
      '#default_value' => isset($settings['reset_map']['position']) ? $settings['reset_map']['position'] : $default_settings['reset_map']['position'],
    ];

    if (isset($this->fieldDefinition)) {
      $element['reset_map']['position']['#states'] = [
        'visible' => [
          ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][reset_map][control]"]' => ['checked' => TRUE],
        ],
      ];
    }
    else {
      $element['reset_map']['position']['#states'] = [
        'visible' => [
          ':input[name="style_options[reset_map][control]"]' => ['checked' => TRUE],
        ],
      ];
    }
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
    $min_zoom = $values['minZoom'];
    $max_zoom = $values['maxZoom'];
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
    $min_zoom = $values['minZoom'];
    $max_zoom = $element['#value'];
    if ($max_zoom && $max_zoom <= $min_zoom) {
      $form_state->setError($element, t('The Max Zoom level should be above the Minimum Zoom level.'));
    }
  }

  /**
   * Form element json format validation handler.
   *
   * {@inheritdoc}
   */
  public static function jsonValidate($element, FormStateInterface &$form_state) {
    $element_values_array = JSON::decode($element['#value']);
    // Check the jsonValue.
    if (!empty($element['#value']) && $element_values_array == NULL) {
      $form_state->setError($element, t('The @field field is not valid Json Format.', ['@field' => $element['#title']]));
    }
    elseif (!empty($element['#value'])) {
      $form_state->setValueForElement($element, JSON::encode($element_values_array));
    }
  }

}

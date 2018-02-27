<?php

namespace Drupal\media_entity_instagram\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\media_entity_instagram\Plugin\media\Source\Instagram;
use Drupal\media_entity_instagram\InstagramEmbedFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'instagram_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "instagram_embed",
 *   label = @Translation("Instagram embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class InstagramEmbedFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The instagram fetcher.
   *
   * @var \Drupal\media_entity_instagram\InstagramEmbedFetcher
   */
  protected $fetcher;

  /**
   * Constructs a InstagramEmbedFormatter instance.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, InstagramEmbedFetcher $fetcher) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->fetcher = $fetcher;
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
      $container->get('media_entity_instagram.instagram_embed_fetcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {
      $matches = [];

      foreach (Instagram::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $this->getEmbedCode($item), $item_matches)) {
          $matches[] = $item_matches;
        }
      }

      if (!empty($matches)) {
        $matches = reset($matches);
      }

      if (!empty($matches['shortcode'])) {

        if ($instagram = $this->fetcher->fetchInstagramEmbed($matches['shortcode'], $settings['hidecaption'], $settings['width'])) {
          $element[$delta] = [
            '#theme' => 'media_entity_instagram_post',
            '#post' => (string) $instagram['html'],
            '#shortcode' => $matches['shortcode'],
          ];
        }
      }
    }

    if (!empty($element)) {
      $element['#attached'] = [
        'library' => [
          'media_entity_instagram/integration',
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => NULL,
      'hidecaption' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['width'] = [
      '#type' => 'number',
      '#title' => $this->t('Width'),
      '#default_value' => $this->getSetting('width'),
      '#min' => 320,
      '#description' => $this->t('Max width of instagram.'),
    ];

    $elements['hidecaption'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Caption hidden'),
      '#default_value' => $this->getSetting('hidecaption'),
      '#description' => $this->t('Enable to hide caption of Instagram posts.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];

    if ($this->getSetting('width')) {
      $summary[] = $this->t('Width: @width px', [
        '@width' => $this->getSetting('width'),
      ]);
    }

    $summary[] = $this->t('Caption: @hidecaption', [
      '@hidecaption' => $settings['hidecaption'] ? $this->t('Hidden') : $this->t('Visible'),
    ]);

    return $summary;
  }

  /**
   * Extracts the raw embed code from input which may or may not be wrapped.
   *
   * @param mixed $value
   *   The input value. Can be a normal string or a value wrapped by the
   *   Typed Data API.
   *
   * @return string|null
   *   The raw embed code.
   */
  protected function getEmbedCode($value) {
    if (is_string($value)) {
      return $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        return $value->$property;
      }
    }
  }

}

<?php

namespace Drupal\media_entity_instagram\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceFieldConstraintsInterface;
use Drupal\media_entity_instagram\InstagramEmbedFetcher;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for Instagram.
 *
 * @MediaSource(
 *   id = "instagram",
 *   label = @Translation("Instagram"),
 *   description = @Translation("Provides business logic and metadata for Instagram."),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "instagram.png"
 * )
 */
class Instagram extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * The instagram fetcher.
   *
   * @var \Drupal\media_entity_instagram\InstagramEmbedFetcher
   */
  protected $fetcher;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\media_entity_instagram\InstagramEmbedFetcher $fetcher
   *   Instagram fetcher service.
   * @param \GuzzleHttp\Client $httpClient
   *   Guzzle client.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, InstagramEmbedFetcher $fetcher, Client $httpClient) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->fetcher = $fetcher;
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('media_entity_instagram.instagram_embed_fetcher'),
      $container->get('http_client')
    );
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '@((http|https):){0,1}//(www\.){0,1}instagram\.com/p/(?<shortcode>[a-z0-9_-]+)@i' => 'shortcode',
    '@((http|https):){0,1}//(www\.){0,1}instagr\.am/p/(?<shortcode>[a-z0-9_-]+)@i' => 'shortcode',
  ];

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'shortcode' => $this->t('Instagram shortcode'),
      'id' => $this->t('Media ID'),
      'type' => $this->t('Media type: image or video'),
      'thumbnail' => $this->t('Link to the thumbnail'),
      'thumbnail_local' => $this->t("Copies thumbnail locally and return it's URI"),
      'thumbnail_local_uri' => $this->t('Returns local URI of the thumbnail'),
      'username' => $this->t('Author of the post'),
      'caption' => $this->t('Caption'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    if ($attribute_name == 'default_name') {
      // Try to get some fields that need the API, if not available, just use
      // the shortcode as default name.
      $username = $this->getMetadata($media, 'username');
      $id = $this->getMetadata($media, 'id');
      if ($username && $id) {
        return $username . ' - ' . $id;
      }
      else {
        $code = $this->getMetadata($media, 'shortcode');
        if (!empty($code)) {
          return $code;
        }
      }
      // Fallback to the parent's default name if everything else failed.
      return parent::getMetadata($media, 'default_name');
    }
    elseif ($attribute_name == 'thumbnail_uri') {
      return $this->getMetadata($media, 'thumbnail_local');
    }

    $matches = $this->matchRegexp($media);

    if (!$matches['shortcode']) {
      return FALSE;
    }

    if ($attribute_name == 'shortcode') {
      return $matches['shortcode'];
    }

    // If we have auth settings return the other fields.
    if ($instagram = $this->fetcher->fetchInstagramEmbed($matches['shortcode'])) {
      switch ($attribute_name) {
        case 'id':
          if (isset($instagram['media_id'])) {
            return $instagram['media_id'];
          }
          return FALSE;

        case 'type':
          if (isset($instagram['type'])) {
            return $instagram['type'];
          }
          return FALSE;

        case 'thumbnail':
          return 'http://instagram.com/p/' . $matches['shortcode'] . '/media/?size=m';

        case 'thumbnail_local':
          $local_uri = $this->getMetadata($media, 'thumbnail_local_uri');

          if ($local_uri) {
            if (file_exists($local_uri)) {
              return $local_uri;
            }
            else {

              $directory = dirname($local_uri);
              file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

              $image_url = $this->getMetadata($media, 'thumbnail');

              $response = $this->httpClient->get($image_url);
              if ($response->getStatusCode() == 200) {
                return file_unmanaged_save_data($response->getBody(), $local_uri, FILE_EXISTS_REPLACE);
              }
            }
          }
          return FALSE;

        case 'thumbnail_local_uri':
          if (isset($instagram['thumbnail_url'])) {
            return $this->configFactory->get('media_entity_instagram.settings')->get('local_images') . '/' . $matches['shortcode'] . '.' . pathinfo(parse_url($instagram['thumbnail_url'], PHP_URL_PATH), PATHINFO_EXTENSION);
          }
          return FALSE;

        case 'username':
          if (isset($instagram['author_name'])) {
            return $instagram['author_name'];
          }
          return FALSE;

        case 'caption':
          if (isset($instagram['title'])) {
            return $instagram['title'];
          }
          return FALSE;

      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return ['InstagramEmbedCode' => []];
  }

  /**
   * Runs preg_match on embed code/URL.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = [];

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();
        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, $media->{$source_field}->{$property_name}, $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

}

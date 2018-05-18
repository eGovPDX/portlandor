<?php

namespace Drupal\media_entity_twitter\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\media_entity_twitter\TweetFetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Twitter entity media source.
 *
 * @MediaSource(
 *   id = "twitter",
 *   label = @Translation("Twitter"),
 *   allowed_field_types = {"string", "string_long", "link"},
 *   default_thumbnail_filename = "twitter.png",
 *   description = @Translation("Provides business logic and metadata for Twitter.")
 * )
 */
class Twitter extends MediaSourceBase implements MediaSourceFieldConstraintsInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The tweet fetcher.
   *
   * @var \Drupal\media_entity_twitter\TweetFetcherInterface
   */
  protected $tweetFetcher;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

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
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('media_entity_twitter.tweet_fetcher'),
      $container->get('logger.factory')->get('media_entity_twitter')
    );
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = [
    '@((http|https):){0,1}//(www\.){0,1}twitter\.com/(?<user>[a-z0-9_-]+)/(status(es){0,1})/(?<id>[\d]+)@i' => 'id',
  ];

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
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   Config field type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\media_entity_twitter\TweetFetcherInterface $tweet_fetcher
   *   The tweet fetcher.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, RendererInterface $renderer, TweetFetcherInterface $tweet_fetcher, LoggerChannelInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->renderer = $renderer;
    $this->tweetFetcher = $tweet_fetcher;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'source_field' => '',
      'use_twitter_api' => FALSE,
      'generate_thumbnails' => FALSE,
      'consumer_key' => '',
      'consumer_secret' => '',
      'oauth_access_token' => '',
      'oauth_access_token_secret' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = [
      'id' => $this->t('Tweet ID'),
      'user' => $this->t('Twitter user information'),
    ];

    if ($this->configuration['use_twitter_api']) {
      $attributes += [
        'image' => $this->t('Link to the twitter image'),
        'image_local' => $this->t('Copies tweet image to the local filesystem and returns the URI.'),
        'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
        'content' => $this->t('This tweet content'),
        'retweet_count' => $this->t('Retweet count for this tweet'),
        'profile_image_url_https' => $this->t('Link to profile image'),
      ];
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $attribute_name) {
    $matches = $this->matchRegexp($media);

    if (!$matches['id']) {
      return NULL;
    }

    // First we return the fields that are available from regex.
    switch ($attribute_name) {
      case 'id':
        return $matches['id'];

      case 'user':
        return $matches['user'] ?: NULL;

      case 'thumbnail_uri':
        // If there's already a local image, use it.
        if ($local_image = $this->getMetadata($media, 'image_local')) {
          return $local_image;
        }

        // If thumbnail generation is disabled, use the default thumbnail.
        if (empty($this->configuration['generate_thumbnails'])) {
          return parent::getMetadata($media, $attribute_name);
        }

        // We might need to generate a thumbnail...
        $id = $this->getMetadata($media, 'id');
        $thumbnail_uri = $this->getLocalImageUri($id, $media);

        // ...unless we already have, in which case, use it.
        if (file_exists($thumbnail_uri)) {
          return $thumbnail_uri;
        }

        // Render the thumbnail SVG using the theme system.
        $thumbnail = [
          '#theme' => 'media_entity_twitter_tweet_thumbnail',
          '#content' => $this->getMetadata($media, 'content'),
          '#author' => $this->getMetadata($media, 'user'),
          '#avatar' => $this->getMetadata($media, 'profile_image_url_https'),
        ];
        $svg = $this->renderer->renderRoot($thumbnail);

        return file_unmanaged_save_data($svg, $thumbnail_uri, FILE_EXISTS_ERROR) ?: parent::getMetadata($media, $attribute_name);
    }

    // If we have auth settings return the other fields.
    if ($this->configuration['use_twitter_api'] && $tweet = $this->fetchTweet($matches['id'])) {
      switch ($attribute_name) {
        case 'image':
          if (isset($tweet['extended_entities']['media'][0]['media_url'])) {
            return $tweet['extended_entities']['media'][0]['media_url'];
          }
          return NULL;

        case 'image_local':
          $local_uri = $this->getMetadata($media, 'image_local_uri');

          if ($local_uri) {
            if (file_exists($local_uri)) {
              return $local_uri;
            }
            else {
              $image_url = $this->getMetadata($media, 'image');
              // @TODO: Use Guzzle, possibly in a service, for this.
              $image_data = file_get_contents($image_url);
              if ($image_data) {
                return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
              }
            }
          }
          return NULL;

        case 'image_local_uri':
          $image_url = $this->getMetadata($media, 'image');
          if ($image_url) {
            return $this->getLocalImageUri($matches['id'], $media, $image_url);
          }
          return NULL;

        case 'content':
          if (isset($tweet['text'])) {
            return $tweet['text'];
          }
          return NULL;

        case 'retweet_count':
          if (isset($tweet['retweet_count'])) {
            return $tweet['retweet_count'];
          }
          return NULL;

        case 'profile_image_url_https':
          if (isset($tweet['user']['profile_image_url_https'])) {
            return $tweet['user']['profile_image_url_https'];
          }
          return NULL;

        case 'default_name':
          $user = $this->getMetadata($media, 'user');
          $id = $this->getMetadata($media, 'id');
          if (!empty($user) && !empty($id)) {
            return $user . ' - ' . $id;
          }
          return NULL;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_twitter_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Whether to use Twitter api to fetch tweets or not.'),
      '#description' => $this->t("In order to use Twitter's api you have to create a developer account and an application. For more information consult the readme file."),
      '#default_value' => empty($this->configuration['use_twitter_api']) ? 0 : $this->configuration['use_twitter_api'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    // @todo: Evaluate if this should be a site-wide configuration.
    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer key'),
      '#default_value' => empty($this->configuration['consumer_key']) ? NULL : $this->configuration['consumer_key'],
      '#states' => [
        'visible' => [
          ':input[name="source_configuration[use_twitter_api]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer secret'),
      '#default_value' => empty($this->configuration['consumer_secret']) ? NULL : $this->configuration['consumer_secret'],
      '#states' => [
        'visible' => [
          ':input[name="source_configuration[use_twitter_api]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['oauth_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Oauth access token'),
      '#default_value' => empty($this->configuration['oauth_access_token']) ? NULL : $this->configuration['oauth_access_token'],
      '#states' => [
        'visible' => [
          ':input[name="source_configuration[use_twitter_api]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['oauth_access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Oauth access token secret'),
      '#default_value' => empty($this->configuration['oauth_access_token_secret']) ? NULL : $this->configuration['oauth_access_token_secret'],
      '#states' => [
        'visible' => [
          ':input[name="source_configuration[use_twitter_api]"]' => ['value' => '1'],
        ],
      ],
    ];

    $form['generate_thumbnails'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate thumbnails'),
      '#default_value' => $this->configuration['generate_thumbnails'],
      '#states' => [
        'visible' => [
          ':input[name="source_configuration[use_twitter_api]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
      '#description' => $this->t('If checked, Drupal will automatically generate thumbnails for tweets that do not reference any external media. In certain circumstances, <strong>this may violate <a href="@policy">Twitter\'s fair use policy</a></strong>. Please <strong>read it and be careful</strong> if you choose to enable this.', [
        '@policy' => 'https://dev.twitter.com/overview/terms/agreement-and-policy',
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'TweetEmbedCode' => [],
      'TweetVisible' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('label', 'Tweet URL');
  }

  /**
   * Computes the destination URI for a tweet image.
   *
   * @param mixed $id
   *   The tweet ID.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   * @param string|null $media_url
   *   The URL of the media (i.e., photo, video, etc.) associated with the
   *   tweet.
   *
   * @return string
   *   The desired local URI.
   */
  protected function getLocalImageUri($id, MediaInterface $media, $media_url = NULL) {
    $directory = $this->configFactory
      ->get('media_entity_twitter.settings')
      ->get('local_images');

    // Ensure that the destination directory is writable. If not, log a warning
    // and return the default thumbnail.
    $ready = file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    if (!$ready) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir', [
        '@dir' => $directory,
      ]);
      return parent::getMetadata($media, 'thumbnail_uri');
    }

    $local_uri = $directory . '/' . $id . '.';
    if ($media_url) {
      $local_uri .= pathinfo($media_url, PATHINFO_EXTENSION);
    }
    else {
      // If there is no media associated with the tweet, we will generate an
      // SVG thumbnail.
      $local_uri .= 'svg';
    }

    return $local_uri;
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

    $source_field = $this->getSourceFieldDefinition($media->bundle->entity)->getName();
    if ($media->hasField($source_field)) {
      $property_name = $media->get($source_field)->first()->mainPropertyName();
      foreach (static::$validationRegexp as $pattern => $key) {
        if (preg_match($pattern, $media->get($source_field)->{$property_name}, $matches)) {
          return $matches;
        }
      }
    }

    return FALSE;
  }

  /**
   * Get a single tweet.
   *
   * @param int $id
   *   The tweet ID.
   *
   * @return array
   *   The tweet information.
   */
  protected function fetchTweet($id) {
    $this->tweetFetcher->setCredentials(
      $this->configuration['consumer_key'],
      $this->configuration['consumer_secret'],
      $this->configuration['oauth_access_token'],
      $this->configuration['oauth_access_token_secret']
    );

    return $this->tweetFetcher->fetchTweet($id);
  }

}

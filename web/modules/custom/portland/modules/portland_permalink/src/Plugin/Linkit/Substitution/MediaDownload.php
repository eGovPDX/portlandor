<?php

namespace Drupal\portland_permalink\Plugin\Linkit\Substitution;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\linkit\SubstitutionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * A substitution plugin for a direct download link to a file.
 *
 * @Substitution(
 *   id = "media_download",
 *   label = @Translation("Direct download URL for media item"),
 * )
 */
class MediaDownload extends PluginBase implements SubstitutionInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Media object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Get the URL associated with a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to get a URL for.
   *
   * @return \Drupal\Core\GeneratedUrl
   *   A url to replace.
   */
  public function getUrl(EntityInterface $entity) {
    $url = new GeneratedUrl();

    /** @var \Drupal\media\Entity\MediaType $media_bundle */
    $media_bundle = $this->entityTypeManager->getStorage('media_type')->load($entity->bundle());
    
    // Default to the canonical URL if the bundle doesn't have a source field.
    if (empty($media_bundle->getSource()->getConfiguration()['source_field'])) {
      return $entity->toUrl('canonical')->toString(TRUE);
    }

    // @todo: Discover if we can support file field deltas at some point via the suggestion matcher.
    $url_object = Url::fromRoute(
      'portland_permalink.download',
      ['media' => $entity->id()]
    );

    $url->setGeneratedUrl($url_object->toString());
    $url->addCacheableDependency($entity);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements('Drupal\media\MediaInterface');
  }

}
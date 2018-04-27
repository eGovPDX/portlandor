<?php

namespace Drupal\shs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shs\Cache\ShsCacheableJsonResponse;
use Drupal\shs\Cache\ShsTermCacheDependency;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for getting taxonomy terms.
 */
class ShsController extends ControllerBase {

  /**
   * Constructs a new ShsController object
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Load term data.
   *
   * @param string $identifier
   *   Name of field to load the data for.
   * @param string $bundle
   *   Bundle (vocabulary) identifier to limit the return list to a specific
   *   bundle.
   * @param integer $entity_id
   *   Id of parent term to load all children for. Defaults to 0.
   *
   * @return CacheableJsonResponse
   *   Cacheable Json response.
   */
  public function getTermData($identifier, $bundle, $entity_id = 0) {
    $context = [
      'identifier' => $identifier,
      'bundle' => $bundle,
      'parent' => $entity_id,
    ];
    $response = new ShsCacheableJsonResponse($context);

    $cache_tags = [];
    $result = [];

    $entity_manager = \Drupal::getContainer()->get('entity.manager');
    $storage = $entity_manager->getStorage('taxonomy_term');
    $terms = $storage->loadTree($bundle, $entity_id, 1, TRUE);

    foreach ($terms as $term) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if ($term->hasTranslation($langcode)) {
        $term = $term->getTranslation($langcode);
      }
      else {
        $langcode = $term->default_langcode;
      }

      $result[] = (object) [
        'tid' => $term->id(),
        'name' => $term->getName(),
        'description__value' => $term->getDescription(),
        'langcode' => $langcode,
        'hasChildren' => shs_term_has_children($term->id()),
      ];
      $cache_tags[] = sprintf('taxonomy_term:%d', $term->id());
    }

    $response->addCacheableDependency(new ShsTermCacheDependency($cache_tags));
    $response->setData($result, TRUE);

    return $response;
  }

}

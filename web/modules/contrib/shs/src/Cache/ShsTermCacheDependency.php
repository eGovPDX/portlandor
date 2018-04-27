<?php

namespace Drupal\shs\Cache;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Cacheable dependency object for term data.
 */
class ShsTermCacheDependency implements CacheableDependencyInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct($tags = []) {
    $this->contexts = ['languages:language_interface'];
    $this->tags = Cache::mergeTags(['taxonomy_term_values'], $tags);
    $this->maxAge = Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags($this->tags, \Drupal::entityManager()->getDefinition('taxonomy_term')->getListCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->maxAge;
  }

}

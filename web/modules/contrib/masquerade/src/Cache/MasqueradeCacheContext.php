<?php

namespace Drupal\masquerade\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the MasqueradeCacheContext service, for "masquerade" caching.
 *
 * Cache context ID: 'session.is_masquerading'.
 */
class MasqueradeCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return new TranslatableMarkup('User is masquerading');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return !empty($_SESSION['masquerading']) ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

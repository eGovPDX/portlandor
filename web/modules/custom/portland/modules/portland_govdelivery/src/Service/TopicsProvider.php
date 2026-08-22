<?php
namespace Drupal\portland_govdelivery\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Provides GovDelivery topics with caching and formatting utilities.
 */
class TopicsProvider {
  protected GovDeliveryClient $client;
  protected CacheBackendInterface $cache;
  protected $logger;

  const CACHE_ID = 'portland_govdelivery:topics';
  const CACHE_TAG = 'portland_govdelivery_topics';
  const CACHE_TTL = 3600; // 1 hour

  public function __construct(GovDeliveryClient $client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $client;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('portland_govdelivery');
  }

  /**
   * Get all topics (cached).
   *
   * @param bool $reset
   *   If TRUE, bypass cache.
   *
   * @return array
   *   Array of topics each with code, name, description.
   */
  public function getAllTopics(bool $reset = FALSE): array {
    if (!$reset) {
      $cached = $this->cache->get(self::CACHE_ID);
      if ($cached !== FALSE) {
        return $cached->data;
      }
    }
    $topics = $this->client->getTopics();
    // Normalize missing keys gracefully.
    $normalized = [];
    foreach ($topics as $t) {
      if (!is_array($t)) { continue; }
      $code = $t['code'] ?? ($t['id'] ?? NULL);
      $name = $t['name'] ?? $t['title'] ?? ($t['label'] ?? NULL);
      $desc = $t['description'] ?? $t['short_description'] ?? '';
      if ($code && $name) {
        $normalized[] = [
          'code' => $code,
          'name' => $name,
          'description' => $desc,
        ];
      }
    }
    $this->cache->set(self::CACHE_ID, $normalized, time() + self::CACHE_TTL, [self::CACHE_TAG]);
    return $normalized;
  }

  /**
   * Get webform select options (code => label) from cached topics.
   */
  public function getWebformOptions(bool $reset = FALSE): array {
    $topics = $this->getAllTopics($reset);
    $options = [];
    foreach ($topics as $topic) {
      $code = $topic['code'] ?? NULL;
      $name = $topic['name'] ?? NULL;
      if ($code && $name) {
        $options[$code] = $name;
      }
    }
    return $options;
  }

  /**
   * Clear the topics cache explicitly.
   */
  public function clearCache(): void {
    \Drupal::service('cache_tags.invalidator')->invalidateTags([self::CACHE_TAG]);
    $this->logger->info('GovDelivery topics cache cleared.');
  }

  /**
   * Last fetched time (UNIX timestamp) or NULL if uncached.
   */
  public function getLastFetched(): ?int {
    $cached = $this->cache->get(self::CACHE_ID);
    if ($cached === FALSE) { return NULL; }
    return isset($cached->created) ? (int) $cached->created : NULL;
  }
}

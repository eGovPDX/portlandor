<?php
namespace Drupal\portland_govdelivery\Service;

/**
 * Formats GovDelivery topics for webforms and other consumers.
 */
class TopicsProvider {
  protected GovDeliveryClient $client;

  public function __construct(GovDeliveryClient $client) {
    $this->client = $client;
  }

  public function getWebformOptions(): array {
    $topics = $this->client->getTopics();
    $options = [];
    foreach ($topics as $topic) {
      if (is_array($topic)) {
        $key = $topic['code'] ?? ($topic['id'] ?? NULL);
        $label = $topic['name'] ?? $topic['title'] ?? ($topic['label'] ?? NULL);
        if ($key && $label) {
          $options[$key] = $label;
        }
      }
    }
    return $options;
  }
}

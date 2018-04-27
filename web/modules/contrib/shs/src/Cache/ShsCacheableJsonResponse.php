<?php

namespace Drupal\shs\Cache;

use Drupal\Core\Cache\CacheableJsonResponse;

/**
 * A cacheable JsonResponse that returns alterable data for Simple hierarchical
 * select.
 */
class ShsCacheableJsonResponse extends CacheableJsonResponse {

  /**
   * Response context used to alter the response data.
   *
   * @var array
   *   Array container the field identifier and the bundle.
   */
  protected $context;

  public function __construct($context, $data = null, $status = 200, $headers = array()) {
    parent::__construct($data, $status, $headers);
    $this->context = $context;
    $this->context['encodingOptions'] = $this->encodingOptions;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $data
   *   Data to send as JsonResponse.
   * @param boolean $alterable
   *   TRUE if the data should be alterable by other modules.
   */
  public function setData($data = [], $alterable = FALSE) {
    if ($alterable === TRUE) {
      $identifier = str_replace('-', '_', $this->context['identifier']);

      $hooks = [
        'shs_term_data',
        "shs__bundle_{$this->context['bundle']}__term_data",
        "shs__field_{$identifier}__term_data",
      ];

      \Drupal::moduleHandler()->alter($hooks, $data, $this->context);
    }
    parent::setData($data);
  }

  /**
   * {@inheritdoc}
   */
  public function sendContent() {
    $identifier = str_replace('-', '_', $this->context['identifier']);

    $hooks = [
      'shs_term_data_response',
      "shs__bundle_{$this->context['bundle']}__term_data_response",
      "shs__field_{$identifier}__term_data_response",
    ];

    // Alter json encoded data.
    \Drupal::moduleHandler()->alter($hooks, $this->content, $this->context);

    echo $this->content;

    return $this;
  }

}

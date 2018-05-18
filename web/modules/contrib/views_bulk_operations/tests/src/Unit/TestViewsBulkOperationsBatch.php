<?php

namespace Drupal\Tests\views_bulk_operations\Unit;

use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;

/**
 * Override some class methods for proper testing.
 */
class TestViewsBulkOperationsBatch extends ViewsBulkOperationsBatch {

  /**
   * Override t method.
   */
  public static function t($string, array $args = [], array $options = []) {
    return strtr($string, $args);
  }

  /**
   * Override message method.
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE) {
    static $storage;
    if (isset($storage)) {
      $output = $storage;
      $storage = NULL;
      return $output;
    }
    else {
      $storage = (string) $message;
    }
  }

}

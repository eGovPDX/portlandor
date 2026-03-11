<?php

namespace Drupal\portland\Logger\Processor;

use Drupal\monolog\Logger\Processor\AbstractRequestProcessor;
/**
 * Class HostnameProcessor.php
 */
class HostnameProcessor extends AbstractRequestProcessor {

  /**
   * Adds the hostname to the "extra" field of the log record.
   * 
   * @param array $record
   *
   * @return array
   */
  public function __invoke($record) {
    if ($request = $this->getRequest()) {
      $record['extra']['hostname'] = $request->getHost();
      $record['extra']['dev'] = 'kevin';
    }

    return $record;
  }

}

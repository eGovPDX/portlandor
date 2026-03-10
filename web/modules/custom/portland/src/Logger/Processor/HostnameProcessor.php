<?php

namespace Drupal\portland\Logger\Processor;

use Drupal\monolog\Logger\Processor\AbstractRequestProcessor;
/**
 * Class HostnameProcessor.php
 */
class HostnameProcessor extends AbstractRequestProcessor {

  /**
   * @param array $record
   *
   * @return array
   */
  public function __invoke($record) {
    // Drop all messages from the "cron" channel.
    if (($record['channel'] ?? '') === 'cron') {
      return false; // Monolog will skip this log entry.
    }
    if ($request = $this->getRequest()) {
      $hostname = $request->getHost();
      // Only log messages from Live sites
      if( ! in_array($hostname, ['www.portland.gov', 'employees.portland.gov'])) {
        return false;
      }
      $record['extra']['hostname'] = $request->getHost();
    }

    return $record;
  }

}

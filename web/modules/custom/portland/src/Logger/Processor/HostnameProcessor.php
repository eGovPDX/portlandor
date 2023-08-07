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
  public function __invoke(array $record) {
    if ($request = $this->getRequest()) {
      $record['extra']['hostname'] = $request->getHost();
    }

    return $record;
  }

}

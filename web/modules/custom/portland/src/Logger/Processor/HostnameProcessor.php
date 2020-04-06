<?php

namespace Drupal\portland\Logger\Processor;

/**
 * Class HostnameProcessor.php
 */
class HostnameProcessor extends \Drupal\monolog\Logger\Processor\AbstractRequestProcessor {

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

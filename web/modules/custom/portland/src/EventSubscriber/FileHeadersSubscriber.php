<?php

namespace Drupal\portland\EventSubscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add custom headers.
 */
class FileHeadersSubscriber implements EventSubscriberInterface {
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();

    if ($response->headers->has('content-disposition')) {
      $header = $response->headers->get('content-disposition');
      $parts = explode(';', $header);
      $header = [];
      foreach ($parts as $key => $value) {
        if($value == 'attachment') {
          $value = 'inline';
        }
        $header[] = $value;
      }
      $response->headers->set('content-disposition', implode(';', $header));
    }
  }

  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
}

<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\serialization\EventSubscriber\DefaultExceptionSubscriber as SerializationDefaultExceptionSubscriber;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Serializes exceptions in compliance with the  JSON API specification.
 *
 * @internal
 */
class DefaultExceptionSubscriber extends SerializationDefaultExceptionSubscriber {

  /**
   * {@inheritdoc}
   */
  protected static function getPriority() {
    return parent::getPriority() + 25;
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['api_json'];
  }

  /**
   * {@inheritdoc}
   */
  public function onException(GetResponseForExceptionEvent $event) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpException $exception */
    $exception = $event->getException();
    if (!$this->isJsonApiFormatted($event->getRequest())) {
      return;
    }
    if (!$exception instanceof HttpException) {
      $exception = new HttpException(500, $exception->getMessage(), $exception);
      $event->setException($exception);
    }

    $this->setEventResponse($event, $exception->getStatusCode());
  }

  /**
   * {@inheritdoc}
   */
  protected function setEventResponse(GetResponseForExceptionEvent $event, $status) {
    /** @var \Symfony\Component\HttpKernel\Exception\HttpException $exception */
    $exception = $event->getException();
    if (!$this->isJsonApiFormatted($event->getRequest())) {
      return;
    }
    $encoded_content = $this->serializer->serialize($exception, 'api_json', ['data_wrapper' => 'errors']);
    $response = new Response($encoded_content, $status);
    $response->headers->set('Content-Type', 'application/vnd.api+json');
    $event->setResponse($response);
  }

  /**
   * Check if the error should be formatted using JSON API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The failed request.
   *
   * @return bool
   *   TRUE if it needs to be formated using JSON API. FALSE otherwise.
   */
  protected function isJsonApiFormatted(Request $request) {
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $format = $request->getRequestFormat();
    // The JSON API format is supported if the format is explicitly set or the
    // request is for a known JSON API route.
    return $format === 'api_json' || ($route && $route->getOption('_is_jsonapi'));
  }

}

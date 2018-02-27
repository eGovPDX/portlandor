<?php

namespace Drupal\jsonapi\EventSubscriber;

use JsonSchema\Validator;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Response subscriber that serializes and removes ResourceResponses' data.
 *
 * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 *
 * This is 99% identical to \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 * but with a few differences:
 * 1. It has the @jsonapi.serializer service injected instead of @serializer
 * 2. It has the @current_route_match service no longer injected
 * 3. It hardcodes the format to 'api_json'
 * 4. In the call to the serializer, it passes in the request and cacheable
 *    metadata as serialization context.
 * 5. It validates the final response according to the JSON API JSON schema
 * 6. It has a different priority, to ensure it runs before the Dynamic Page
 *    Cache event subscriber — but this should also be fixed in the original
 *    class, see issue
 */
class ResourceResponseSubscriber implements EventSubscriberInterface {

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The JSON API logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a ResourceResponseSubscriber object.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   The JSON API logger channel.
   */
  public function __construct(SerializerInterface $serializer, RendererInterface $renderer, LoggerInterface $logger) {
    $this->serializer = $serializer;
    $this->renderer = $renderer;
    $this->logger = $logger;
  }

  /**
   * Serializes ResourceResponse responses' data, and removes that data.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();
    if (!$response instanceof ResourceResponse) {
      return;
    }

    $request = $event->getRequest();
    $format = 'api_json';
    $this->renderResponseBody($request, $response, $this->serializer, $format);
    $event->setResponse($this->flattenResponse($response));

    assert($this->validateResponse($event->getResponse()), 'A JSON API response failed validation (see the logs for details). Please report this in the issue queue on drupal.org');
  }

  /**
   * Renders a resource response body.
   *
   * Serialization can invoke rendering (e.g., generating URLs), but the
   * serialization API does not provide a mechanism to collect the
   * bubbleable metadata associated with that (e.g., language and other
   * contexts), so instead, allow those to "leak" and collect them here in
   * a render context.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\jsonapi\ResourceResponse $response
   *   The response from the JSON API resource.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer to use.
   * @param string|null $format
   *   The response format, or NULL in case the response does not need a format,
   *   for example for the response to a DELETE request.
   *
   * @todo Add test coverage for language negotiation contexts in
   *   https://www.drupal.org/node/2135829.
   */
  protected function renderResponseBody(Request $request, ResourceResponse $response, SerializerInterface $serializer, $format) {
    $data = $response->getResponseData();

    // If there is data to send, serialize and set it as the response body.
    if ($data !== NULL) {
      $context = new RenderContext();
      $output = $this->renderer
        ->executeInRenderContext($context, function () use ($serializer, $data, $format, $request, $response) {
          // The serializer receives the response's cacheability metadata object
          // as serialization context. Normalizers called by the serializer then
          // refine this cacheability metadata, and thus they are effectively
          // updating the response object's cacheability.
          return $serializer->serialize($data, $format, ['request' => $request, 'cacheable_metadata' => $response->getCacheableMetadata()]);
        });

      if ($response instanceof CacheableResponseInterface && !$context->isEmpty()) {
        $response->addCacheableDependency($context->pop());
      }

      $response->setContent($output);
      $response->headers->set('Content-Type', $request->getMimeType($format));
    }
  }

  /**
   * Flattens a fully rendered resource response.
   *
   * Ensures that complex data structures in ResourceResponse::getResponseData()
   * are not serialized. Not doing this means that caching this response object
   * requires unserializing the PHP data when reading this response object from
   * cache, which can be very costly, and is unnecessary.
   *
   * @param \Drupal\jsonapi\ResourceResponse $response
   *   A fully rendered resource response.
   *
   * @return \Drupal\Core\Cache\CacheableResponse|\Symfony\Component\HttpFoundation\Response
   *   The flattened response.
   */
  protected function flattenResponse(ResourceResponse $response) {
    $final_response = ($response instanceof CacheableResponseInterface) ? new CacheableResponse() : new Response();
    $final_response->setContent($response->getContent());
    $final_response->setStatusCode($response->getStatusCode());
    $final_response->setProtocolVersion($response->getProtocolVersion());
    $final_response->setCharset($response->getCharset());
    $final_response->headers = clone $response->headers;
    if ($final_response instanceof CacheableResponseInterface) {
      $final_response->addCacheableDependency($response->getCacheableMetadata());
    }
    return $final_response;
  }

  /**
   * Validates a response against the JSON API specification.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to validate.
   *
   * @return bool
   *   FALSE if the response failed validation, otherwise TRUE.
   */
  protected function validateResponse(Response $response) {
    if (!class_exists("\\JsonSchema\\Validator")) {
      return TRUE;
    }
    // Do not use Json::decode here since it coerces the response into an
    // associative array, which creates validation errors.
    $response_data = json_decode($response->getContent());
    if (empty($response_data)) {
      return TRUE;
    }

    $validator = new Validator();
    $schema_path = dirname(dirname(__DIR__)) . '/schema.json';

    $validator->check($response_data, (object) ['$ref' => 'file://' . $schema_path]);

    if (!$validator->isValid()) {
      $this->logger->debug('Response failed validation: @data', [
        '@data' => Json::encode($response_data),
      ]);
      $this->logger->debug('Validation errors: @errors', [
        '@errors' => Json::encode($validator->getErrors()),
      ]);
    }

    return $validator->isValid();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before \Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 110];
    return $events;
  }

}

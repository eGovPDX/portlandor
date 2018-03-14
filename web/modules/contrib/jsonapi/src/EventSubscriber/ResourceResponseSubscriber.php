<?php

namespace Drupal\jsonapi\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\ResourceResponse;
use Drupal\schemata\SchemaFactory;
use JsonSchema\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Response subscriber that serializes and removes ResourceResponses' data.
 *
 * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 * @internal
 *
 * This is 99% identical to:
 *
 * \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 *
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
   * The schema validator.
   *
   * This property will only be set if the validator library is available.
   *
   * @var \JsonSchema\Validator|null
   */
  protected $validator;

  /**
   * The schemata schema factory.
   *
   * This property will only be set if the schemata module is installed.
   *
   * @var \Drupal\schemata\SchemaFactory|null
   */
  protected $schemaFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The application's root file path.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * Constructs a ResourceResponseSubscriber object.
   *
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The serializer.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Psr\Log\LoggerInterface $logger
   *   The JSON API logger channel.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param string $app_root
   *   The application's root file path.
   */
  public function __construct(SerializerInterface $serializer, RendererInterface $renderer, LoggerInterface $logger, ModuleHandlerInterface $module_handler, $app_root) {
    $this->serializer = $serializer;
    $this->renderer = $renderer;
    $this->logger = $logger;
    $this->moduleHandler = $module_handler;
    $this->appRoot = $app_root;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to be run before the dynamic_page_cache subscriber.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 110];
    return $events;
  }

  /**
   * Sets the validator service if available.
   */
  public function setValidator(Validator $validator = NULL) {
    if ($validator) {
      $this->validator = $validator;
    }
    elseif (class_exists(Validator::class)) {
      $this->validator = new Validator();
    }
  }

  /**
   * Injects the schema factory.
   *
   * @param \Drupal\schemata\SchemaFactory $schema_factory
   *   The schema factory service.
   */
  public function setSchemaFactory(SchemaFactory $schema_factory) {
    $this->schemaFactory = $schema_factory;
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
    $event->setResponse($this->flattenResponse($response, $request));

    $this->doValidateResponse($response, $request);
  }

  /**
   * Wraps validation in an assert to prevent execution in production.
   *
   * @see self::validateResponse
   */
  public function doValidateResponse(Response $response, Request $request) {
    if (PHP_MAJOR_VERSION >= 7 || assert_options(ASSERT_ACTIVE)) {
      assert($this->validateResponse($response, $request), 'A JSON API response failed validation (see the logs for details). Please report this in the issue queue on drupal.org');
    }
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
      $render_function = function () use ($serializer, $data, $format, $request, $response) {
        // The serializer receives the response's cacheability metadata object
        // as serialization context. Normalizers called by the serializer then
        // refine this cacheability metadata, and thus they are effectively
        // updating the response object's cacheability.
        return $serializer->serialize($data, $format, [
          'request' => $request,
          'cacheable_metadata' => $response->getCacheableMetadata(),
        ]);
      };
      $output = $this->renderer->executeInRenderContext($context, $render_function);

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
   * requires deserializing the PHP data when reading this response object from
   * cache, which can be very costly, and is unnecessary.
   *
   * @param \Drupal\jsonapi\ResourceResponse $response
   *   A fully rendered resource response.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request for which this response is generated.
   *
   * @return \Drupal\Core\Cache\CacheableResponse|\Symfony\Component\HttpFoundation\Response
   *   The flattened response.
   */
  protected static function flattenResponse(ResourceResponse $response, Request $request) {
    $final_response = ($response instanceof CacheableResponseInterface && $request->isMethodCacheable()) ? new CacheableResponse() : new Response();
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
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing info about what to validate.
   *
   * @return bool
   *   FALSE if the response failed validation, otherwise TRUE.
   */
  protected function validateResponse(Response $response, Request $request) {
    // If the validator isn't set, then the validation library is not installed.
    if (!$this->validator) {
      return TRUE;
    }

    // Do not use Json::decode here since it coerces the response into an
    // associative array, which creates validation errors.
    $response_data = json_decode($response->getContent());
    if (empty($response_data)) {
      return TRUE;
    }

    $schema_ref = sprintf(
      'file://%s/schema.json',
      implode('/', [
        $this->appRoot,
        $this->moduleHandler->getModule('jsonapi')->getPath(),
      ])
    );
    $generic_jsonapi_schema = (object) ['$ref' => $schema_ref];
    $is_valid = $this->validateSchema($generic_jsonapi_schema, $response_data);
    if (!$is_valid) {
      return FALSE;
    }

    // This will be set if the schemata module is present.
    if (!$this->schemaFactory) {
      // Fall back the valid generic result since schemata is absent.
      return TRUE;
    }

    // Get the schema for the current resource. For that we will need to
    // introspect the request to find the entity type and bundle matched by the
    // router.
    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    $route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME);

    // We shouldn't validate related/relationships.
    $is_related = strpos($route_name, '.related') !== FALSE;
    $is_relationship = strpos($route_name, '.relationship') !== FALSE;
    if ($is_related || $is_relationship) {
      // Fall back the valid generic result since schemata is absent.
      return TRUE;
    }

    $entity_type_id = $route->getRequirement('_entity_type');
    $bundle = $route->getRequirement('_bundle');
    $output_format = 'schema_json';
    $described_format = 'api_json';

    $schema_object = $this->schemaFactory->create($entity_type_id, $bundle);
    $format = $output_format . ':' . $described_format;
    $output = $this->serializer->serialize($schema_object, $format);
    $specific_schema = Json::decode($output);
    if (!$specific_schema) {
      return $is_valid;
    }

    // We need to individually validate each collection resource object.
    $is_collection = strpos($route_name, '.collection') !== FALSE;

    // Iterate over each resource object and check the schema.
    return array_reduce(
      $is_collection ? $response_data->data : [$response_data->data],
      function ($valid, $resource_object) use ($specific_schema) {
        // Validating the schema first ensures that every object is processed.
        return $this->validateSchema($specific_schema, $resource_object) && $valid;
      },
      TRUE
    );
  }

  /**
   * Validates a string against a JSON Schema. It logs any possible errors.
   *
   * @param object $schema
   *   The JSON Schema object.
   * @param string $response_data
   *   The JSON string to validate.
   *
   * @return bool
   *   TRUE if the string is a valid instance of the schema. FALSE otherwise.
   */
  protected function validateSchema($schema, $response_data) {
    $this->validator->check($response_data, $schema);
    $is_valid = $this->validator->isValid();
    if (!$is_valid) {
      $this->logger->debug("Response failed validation.\nResponse:\n@data\n\nErrors:\n@errors", [
        '@data' => Json::encode($response_data),
        '@errors' => Json::encode($this->validator->getErrors()),
      ]);
    }
    return $is_valid;
  }

}

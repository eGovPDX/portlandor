<?php

namespace Drupal\jsonapi\LinkManager;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Query\OffsetPage;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

/**
 * Class to generate links and queries for entities.
 *
 * @deprecated
 */
class LinkManager {

  /**
   * Used to determine the route from a given request.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * Used to generate a link to a jsonapi representation of an entity.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Instantiates a LinkManager object.
   *
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The router.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The Url generator.
   */
  public function __construct(RequestMatcherInterface $router, UrlGeneratorInterface $url_generator) {
    $this->router = $router;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Gets a link for the entity.
   *
   * @param int $entity_id
   *   The entity ID to generate the link for. Note: Depending on the
   *   configuration this might be the UUID as well.
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The JSON API resource type.
   * @param array $route_parameters
   *   Parameters for the route generation.
   * @param string $key
   *   A key to build the route identifier.
   *
   * @return string
   *   The URL string.
   */
  public function getEntityLink($entity_id, ResourceType $resource_type, array $route_parameters, $key) {
    $route_parameters += [
      $resource_type->getEntityTypeId() => $entity_id,
    ];
    $route_key = sprintf('jsonapi.%s.%s', $resource_type->getTypeName(), $key);
    return $this->urlGenerator->generateFromRoute($route_key, $route_parameters, ['absolute' => TRUE]);
  }

  /**
   * Get the full URL for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param array|null $query
   *   The query parameters to use. Leave it empty to get the query from the
   *   request object.
   *
   * @return string
   *   The full URL.
   */
  public function getRequestLink(Request $request, $query = NULL) {
    $query = $query ?: (array) $request->query->getIterator();
    $result = $this->router->matchRequest($request);
    $route_name = $result[RouteObjectInterface::ROUTE_NAME];
    /* @var \Symfony\Component\HttpFoundation\ParameterBag $raw_variables */
    $raw_variables = $result['_raw_variables'];
    $route_parameters = $raw_variables->all();
    $options = [
      'absolute' => TRUE,
      'query' => $query,
    ];
    return $this->urlGenerator->generateFromRoute($route_name, $route_parameters, $options);
  }

  /**
   * Get the pager links for a given request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param array $link_context
   *   An associative array with extra data to build the links.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   *   When the offset and size are invalid.
   *
   * @return string[]
   *   An array of URLs, with:
   *   - a 'next' key if it is not the last page;
   *   - 'prev' and 'first' keys if it's not the first page.
   */
  public function getPagerLinks(Request $request, array $link_context = []) {
    if (!empty($link_context['total_count']) && !$total = (int) $link_context['total_count']) {
      return [];
    }
    $params = $request->get('_json_api_params');
    if ($page_param = $params[OffsetPage::KEY_NAME]) {
      /* @var \Drupal\jsonapi\Query\OffsetPage $page_param */
      $offset = $page_param->getOffset();
      $size = $page_param->getSize();
    }
    else {
      // Apply the defaults.
      $offset = OffsetPage::DEFAULT_OFFSET;
      $size = OffsetPage::SIZE_MAX;
    }
    if ($size <= 0) {
      throw new BadRequestHttpException(sprintf('The page size needs to be a positive integer.'));
    }
    $query = (array) $request->query->getIterator();
    $links = [];
    // Check if this is not the last page.
    if ($link_context['has_next_page']) {
      $links['next'] = $this->getRequestLink($request, $this->getPagerQueries('next', $offset, $size, $query));

      if (!empty($total)) {
        $links['last'] = $this->getRequestLink($request, $this->getPagerQueries('last', $offset, $size, $query, $total));
      }
    }
    // Check if this is not the first page.
    if ($offset > 0) {
      $links['first'] = $this->getRequestLink($request, $this->getPagerQueries('first', $offset, $size, $query));
      $links['prev'] = $this->getRequestLink($request, $this->getPagerQueries('prev', $offset, $size, $query));
    }

    return $links;
  }

  /**
   * Get the query param array.
   *
   * @param string $link_id
   *   The name of the pagination link requested.
   * @param int $offset
   *   The starting index.
   * @param int $size
   *   The pagination page size.
   * @param array $query
   *   The query parameters.
   * @param int $total
   *   The total size of the collection.
   *
   * @return array
   *   The pagination query param array.
   */
  protected function getPagerQueries($link_id, $offset, $size, array $query = [], $total = 0) {
    $extra_query = [];
    switch ($link_id) {
      case 'next':
        $extra_query = [
          'page' => [
            'offset' => $offset + $size,
            'limit' => $size,
          ],
        ];
        break;

      case 'first':
        $extra_query = [
          'page' => [
            'offset' => 0,
            'limit' => $size,
          ],
        ];
        break;

      case 'last':
        if ($total) {
          $extra_query = [
            'page' => [
              'offset' => (ceil($total / $size) - 1) * $size,
              'limit' => $size,
            ],
          ];
        }
        break;

      case 'prev':
        $extra_query = [
          'page' => [
            'offset' => max($offset - $size, 0),
            'limit' => $size,
          ],
        ];
        break;
    }
    return array_merge($query, $extra_query);
  }

}

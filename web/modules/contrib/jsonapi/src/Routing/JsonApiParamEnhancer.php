<?php

namespace Drupal\jsonapi\Routing;

use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\Query\Filter;
use Drupal\jsonapi\Query\Sort;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * @internal
 */
class JsonApiParamEnhancer implements RouteEnhancerInterface {

  /**
   * The filter normalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $filterNormalizer;

  /**
   * The sort normalizer.
   *
   * @var \Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $sortNormalizer;

  /**
   * The page normalizer.
   *
   * @var Symfony\Component\Serializer\Normalizer\DenormalizerInterface
   */
  protected $pageNormalizer;

  /**
   * {@inheritdoc}
   */
  public function __construct(DenormalizerInterface $filter_normalizer, DenormalizerInterface $sort_normalizer, DenormalizerInterface $page_normalizer) {
    $this->filterNormalizer = $filter_normalizer;
    $this->sortNormalizer = $sort_normalizer;
    $this->pageNormalizer = $page_normalizer;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    // This enhancer applies to the JSON API routes.
    return $route->getDefault(RouteObjectInterface::CONTROLLER_NAME) == Routes::FRONT_CONTROLLER;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $options = [];

    $route = $defaults[RouteObjectInterface::ROUTE_OBJECT];
    $context = [
      'entity_type_id' => $route->getRequirement('_entity_type'),
      'bundle' => $route->getRequirement('_bundle'),
    ];

    if ($request->query->has('filter')) {
      $filter = $request->query->get('filter');
      $options['filter'] = $this->filterNormalizer->denormalize($filter, Filter::class, NULL, $context);
    }

    if ($request->query->has('sort')) {
      $sort = $request->query->get('sort');
      $options['sort'] = $this->sortNormalizer->denormalize($sort, Sort::class);
    }

    $page = ($request->query->has('page')) ? $request->query->get('page') : [];
    $options['page'] = $this->pageNormalizer->denormalize($page, OffsetPage::class);

    $defaults['_json_api_params'] = $options;

    return $defaults;
  }

}

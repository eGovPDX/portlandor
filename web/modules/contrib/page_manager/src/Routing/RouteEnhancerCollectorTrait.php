<?php

/**
 * @file
 * Contains \Drupal\page_manager\Routing\RouteEnhancerCollectorTrait.
 */

namespace Drupal\page_manager\Routing;

use Drupal\Core\Routing\EnhancerInterface;

/**
 * Provides a trait to make a service a collector of route enhancers.
 *
 * @todo Move to Symfony CMF in https://github.com/symfony-cmf/Routing/pull/155.
 */
trait RouteEnhancerCollectorTrait {

  /**
   * @var \Drupal\Core\Routing\EnhancerInterface[]
   */
  protected $enhancers = array();

  /**
   * Cached sorted list of enhancers
   *
   * @var \Drupal\Core\Routing\EnhancerInterface[]
   */
  protected $sortedEnhancers = array();

  /**
   * Add route enhancers to the router to let them generate information on
   * matched routes.
   *
   * The order of the enhancers is determined by the priority, the higher the
   * value, the earlier the enhancer is run.
   *
   * @param \Drupal\Core\Routing\EnhancerInterface $enhancer
   * @param int $priority
   *
   * @return $this
   */
  public function addRouteEnhancer(EnhancerInterface $enhancer, $priority = 0) {
    if (empty($this->enhancers[$priority])) {
      $this->enhancers[$priority] = array();
    }

    $this->enhancers[$priority][] = $enhancer;
    $this->sortedEnhancers = array();

    return $this;
  }

  /**
   * Sorts the enhancers and flattens them.
   *
   * @return \Drupal\Core\Routing\EnhancerInterface[]
   *   The enhancers ordered by priority.
   */
  protected function getRouteEnhancers() {
    if (empty($this->sortedEnhancers)) {
      $this->sortedEnhancers = $this->sortRouteEnhancers();
    }

    return $this->sortedEnhancers;
  }

  /**
   * Sort enhancers by priority.
   *
   * The highest priority number is the highest priority (reverse sorting).
   *
   * @return \Drupal\Core\Routing\EnhancerInterface[]
   *   The sorted enhancers.
   */
  protected function sortRouteEnhancers() {
    $sortedEnhancers = array();
    krsort($this->enhancers);

    foreach ($this->enhancers as $enhancers) {
      $sortedEnhancers = array_merge($sortedEnhancers, $enhancers);
    }

    return $sortedEnhancers;
  }

}

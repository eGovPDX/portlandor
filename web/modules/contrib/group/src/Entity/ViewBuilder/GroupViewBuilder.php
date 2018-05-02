<?php

namespace Drupal\group\Entity\ViewBuilder;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for groups.
 *
 * @todo Keep an eye on https://www.drupal.org/node/2791571.
 */
class GroupViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function alterBuild(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    /** @var \Drupal\group\Entity\GroupInterface $entity */
    parent::alterBuild($build, $entity, $display, $view_mode);
    if ($entity->id()) {
      $build['#contextual_links']['group'] = [
        'route_parameters' => ['group' => $entity->id()],
        'metadata' => ['changed' => $entity->getChangedTime()],
      ];
    }
  }

}

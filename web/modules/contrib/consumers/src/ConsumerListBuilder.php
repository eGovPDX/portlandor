<?php

namespace Drupal\consumers;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Access Token entities.
 */
class ConsumerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['uuid'] = $this->t('UUID');
    $header['label'] = $this->t('Label');
    $context = ['type' => 'header'];
    $this->moduleHandler()->alter('consumers_list', $header, $context);
    $header = $header + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\consumers\Entity\Consumer */
    $row['uuid'] = $entity->uuid();
    $row['label'] = $entity->toLink();

    $context = ['type' => 'row', 'entity' => $entity];
    $this->moduleHandler()->alter('consumers_list', $row, $context);
    $row = $row + parent::buildRow($entity);
    return $row;
  }

}

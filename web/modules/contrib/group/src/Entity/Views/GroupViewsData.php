<?php

namespace Drupal\group\Entity\Views;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the group entity type.
 */
class GroupViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['groups_field_data']['id']['argument'] = [
      'id' => 'group_id',
      'name field' => 'label',
      'numeric' => TRUE,
    ];

    $data['groups_field_data']['group_content_id']['relationship'] = [
      'title' => $this->t('Group content'),
      'help' => $this->t('Relate to the group content entities. From there you can relate to the actual grouped entities.'),
      'id' => 'group_to_group_content',
      'base' => 'group_content_field_data',
      'base field' => 'gid',
      'field' => 'id',
      'label' => $this->t('Group content'),
    ];

    return $data;
  }

}

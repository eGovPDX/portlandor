<?php

namespace Drupal\portland\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Access\AccessResult;

/**
 * Views bulk operations action that allows adding users to multiple groups in bulk.
 *
 * @Action(
 *   id = "portland_add_user_to_groups_relationship",
 *   label = @Translation("Add or update members"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
class AddGroupRelationshipMembershipsAction extends AddGroupMembershipsAction implements PluginFormInterface {
  // This class is just a copy of AddGroupMembershipsAction that is applied to group content entities.
}

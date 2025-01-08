<?php

namespace Drupal\portland_relations;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\portland_relations\Entity\RelationType;


/**
 * Provides dynamic permissions for Relation of different types.
 *
 * @ingroup portland_relations
 *
 */
class RelationPermissions{

  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The Relation by bundle permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function generatePermissions() {
    $perms = [];

    foreach (RelationType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\portland_relations\Entity\RelationType $type
   *   The Relation type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(RelationType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id relations" => [
        'title' => $this->t('%type_name: Create new relation entity', $type_params),
      ],
      "edit any $type_id relations" => [
        'title' => $this->t('%type_name: Edit relation entity', $type_params),
      ],
      "delete any $type_id relations" => [
        'title' => $this->t('%type_name: Delete relation entity', $type_params),
      ],
      "view any $type_id relation revisions" => [
        'title' => $this->t('%type_name: View any relation revision pages', $type_params),
      ],
      "revert any $type_id relation revisions" => [
        'title' => $this->t('%type_name: Revert relation revisions', $type_params),
      ],
      "delete any $type_id relation revisions" => [
        'title' => $this->t('%type_name: Delete relation revisions', $type_params),
      ],
    ];
  }

}

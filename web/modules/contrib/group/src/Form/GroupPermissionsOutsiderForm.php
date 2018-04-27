<?php

namespace Drupal\group\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\group\Access\GroupPermissionHandlerInterface;
use Drupal\group\GroupRoleSynchronizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Permission form for the synchronized outsider roles for a group type.
 */
class GroupPermissionsOutsiderForm extends GroupPermissionsTypeSpecificForm {

  /**
   * The group role synchronizer service.
   *
   * @var \Drupal\group\GroupRoleSynchronizerInterface
   */
  protected $groupRoleSynchronizer;

  /**
   * Constructs a new GroupPermissionsOutsiderForm.
   *
   * @param \Drupal\group\GroupRoleSynchronizerInterface $group_role_synchronizer
   *   The group role synchronizer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionHandlerInterface $permission_handler
   *   The group permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(GroupRoleSynchronizerInterface $group_role_synchronizer, EntityTypeManagerInterface $entity_type_manager, GroupPermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager, $permission_handler, $module_handler);
    $this->groupRoleSynchronizer = $group_role_synchronizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_role.synchronizer'),
      $container->get('entity_type.manager'),
      $container->get('group.permissions'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getInfo() {
    $info = [
      'outsider_info' => [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t("If one Outside role to represent all Authenticated users does not cut it, this is the form for you.<br />Here you can assign outsider permissions per configured site role.<br />Please note that any permissions set here will become void once the user joins the group."),
      ],
    ] + parent::getInfo();

    // Unset the info about the group role audiences.
    unset($info['role_info']);

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupRoles() {
    $group_role_ids = $this->groupRoleSynchronizer
      ->getGroupRoleIdsByGroupType($this->groupType->id());
    return $this->entityTypeManager->getStorage('group_role')
      ->loadMultiple($group_role_ids);;
  }

}

<?php

namespace Drupal\group\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Group entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group",
 *   label = @Translation("Group"),
 *   label_singular = @Translation("group"),
 *   label_plural = @Translation("groups"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group",
 *     plural = "@count groups"
 *   ),
 *   bundle_label = @Translation("Group type"),
 *   handlers = {
 *     "view_builder" = "Drupal\group\Entity\ViewBuilder\GroupViewBuilder",
 *     "views_data" = "Drupal\group\Entity\Views\GroupViewsData",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupDeleteForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupAccessControlHandler",
 *   },
 *   admin_permission = "administer group",
 *   base_table = "groups",
 *   data_table = "groups_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/group/add/{group_type}",
 *     "add-page" = "/group/add",
 *     "canonical" = "/group/{group}",
 *     "collection" = "/admin/group",
 *     "edit-form" = "/group/{group}/edit",
 *     "delete-form" = "/group/{group}/delete"
 *   },
 *   bundle_entity_type = "group_type",
 *   field_ui_base_route = "entity.group_type.edit_form",
 *   permission_granularity = "bundle"
 * )
 */
class Group extends ContentEntityBase implements GroupInterface {

  use EntityChangedTrait;

  /**
   * Gets the group membership loader.
   *
   * @return \Drupal\group\GroupMembershipLoaderInterface
   */
  protected function membershipLoader() {
    return \Drupal::service('group.membership_loader');
  }

  /**
   * Gets the group content storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupContentStorageInterface
   */
  protected function groupContentStorage() {
    return $this->entityTypeManager()->getStorage('group_content');
  }

  /**
   * Gets the group role storage.
   *
   * @return \Drupal\group\Entity\Storage\GroupRoleStorageInterface
   */
  protected function groupRoleStorage() {
    return $this->entityTypeManager()->getStorage('group_role');
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function addContent(ContentEntityInterface $entity, $plugin_id, $values = []) {
    $storage = $this->groupContentStorage();
    $group_content = $storage->createForEntityInGroup($entity, $this, $plugin_id, $values);
    $storage->save($group_content);
  }

  /**
   * {@inheritdoc}
   */
  public function getContent($plugin_id = NULL, $filters = []) {
    return $this->groupContentStorage()->loadByGroup($this, $plugin_id, $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentByEntityId($plugin_id, $id) {
    return $this->getContent($plugin_id, ['entity_id' => $id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntities($plugin_id = NULL, $filters = []) {
    $entities = [];

    foreach ($this->getContent($plugin_id, $filters) as $group_content) {
      $entities[] = $group_content->getEntity();
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function addMember(UserInterface $account, $values = []) {
    if (!$this->getMember($account)) {
      $this->addContent($account, 'group_membership', $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeMember(UserInterface $account) {
    if ($member = $this->getMember($account)) {
      $member->getGroupContent()->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getMember(AccountInterface $account) {
    return $this->membershipLoader()->load($this, $account);
  }

  /**
   * {@inheritdoc}
   */
  public function getMembers($roles = NULL) {
    return $this->membershipLoader()->loadByGroup($this, $roles);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission, AccountInterface $account) {
    // If the account can bypass all group access, return immediately.
    if ($account->hasPermission('bypass group access')) {
      return TRUE;
    }

    // Before anything else, check if the user can administer the group.
    if ($permission != 'administer group' && $this->hasPermission('administer group', $account)) {
      return TRUE;
    }

    // Retrieve all of the group roles the user may get for the group.
    $group_roles = $this->groupRoleStorage()->loadByUserAndGroup($account, $this);

    // Check each retrieved role for the requested permission.
    foreach ($group_roles as $group_role) {
      if ($group_role->hasPermission($permission)) {
        return TRUE;
      }
    }

    // If no role had the requested permission, we deny access.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group creator'))
      ->setDescription(t('The username of the group creator.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\group\Entity\Group::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group was created.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group was last edited.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'hidden',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $fields['path'] = BaseFieldDefinition::create('path')
        ->setLabel(t('URL alias'))
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'path',
          'weight' => 30,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setComputed(TRUE);
    }

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If a new group is created and the group type is configured to grant group
    // creators a membership by default, add the creator as a member.
    // @todo Deprecate in 8.x-2.x in favor of a form-only approach. API-created
    //   groups should not get this functionality because it may create
    //   incomplete group memberships.
    $group_type = $this->getGroupType();
    if ($update === FALSE && $group_type->creatorGetsMembership()) {
      $values = ['group_roles' => $group_type->getCreatorRoleIds()];
      $this->addMember($this->getOwner(), $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    // Remove all group content from these groups as well.
    foreach ($entities as $group) {
      foreach ($group->getContent() as $group_content) {
        $group_content->delete();
      }
    }
  }

}

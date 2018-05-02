<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_type",
 *   label = @Translation("Group type"),
 *   label_singular = @Translation("group type"),
 *   label_plural = @Translation("group types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group type",
 *     plural = "@count group types"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\GroupTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupTypeRouteProvider",
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupTypeListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "type",
 *   bundle_of = "group",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/group/types/add",
 *     "collection" = "/admin/group/types",
 *     "content-plugins" = "/admin/group/types/manage/{group_type}/content",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/delete",
 *     "edit-form" = "/admin/group/types/manage/{group_type}",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "creator_membership",
 *     "creator_wizard",
 *     "creator_roles",
 *   }
 * )
 */
class GroupType extends ConfigEntityBundleBase implements GroupTypeInterface {

  /**
   * The machine name of the group type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group type.
   *
   * @var string
   */
  protected $description;

  /**
   * The group creator automatically receives a membership.
   *
   * @var bool
   */
  protected $creator_membership = TRUE;

  /**
   * The group creator must immediately complete their membership.
   *
   * @var bool
   */
  protected $creator_wizard = TRUE;

  /**
   * The IDs of the group roles a group creator should receive.
   *
   * @var string[]
   */
  protected $creator_roles = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles($include_internal = TRUE) {
    $properties = ['group_type' => $this->id()];

    // Exclude internal roles if told to.
    if ($include_internal === FALSE) {
      $properties['internal'] = FALSE;
    }

    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleIds($include_internal = TRUE) {
    $query = $this->entityTypeManager()
      ->getStorage('group_role')
      ->getQuery()
      ->condition('group_type', $this->id());

    // Exclude internal roles if told to.
    if ($include_internal === FALSE) {
      $query->condition('internal', FALSE);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getAnonymousRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousRoleId() {
    return $this->id() . '-anonymous';
  }

  /**
   * {@inheritdoc}
   */
  public function getOutsiderRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getOutsiderRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getOutsiderRoleId() {
    return $this->id() . '-outsider';
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberRole() {
    return $this->entityTypeManager()
      ->getStorage('group_role')
      ->load($this->getMemberRoleId());
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberRoleId() {
    return $this->id() . '-member';
  }

  /**
   * {@inheritdoc}
   */
  public function creatorGetsMembership() {
    return $this->creator_membership;
  }

  /**
   * {@inheritdoc}
   */
  public function creatorMustCompleteMembership() {
    return $this->creator_membership && $this->creator_wizard;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatorRoleIds() {
    return $this->creator_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Throw an exception if the group type ID is longer than the limit.
    if (strlen($this->id()) > GroupTypeInterface::ID_MAX_LENGTH) {
      throw new ConfigEntityIdLengthException("Attempt to create a group type with an ID longer than " . GroupTypeInterface::ID_MAX_LENGTH . " characters: {$this->id()}.");
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // Store the id in a short variable for readability.
      $group_type_id = $this->id();

      // @todo Remove this line when https://www.drupal.org/node/2645202 lands.
      $this->setOriginalId($group_type_id);

      // The code below will create the default group roles and the group
      // content types for enforced plugins. It is extremely important that we
      // only run this code if we are dealing with a new group type that was
      // created through the API or UI; not through config synchronization.
      //
      // We do not create group roles or group content types for a synced group
      // type because those should have been exported along with the group type.
      if (!$this->isSyncing()) {
        // Create the three special roles for the group type.
        GroupRole::create([
          'id' => $this->getAnonymousRoleId(),
          'label' => t('Anonymous'),
          'weight' => -102,
          'internal' => TRUE,
          'audience' => 'anonymous',
          'group_type' => $group_type_id,
        ])->save();
        GroupRole::create([
          'id' => $this->getOutsiderRoleId(),
          'label' => t('Outsider'),
          'weight' => -101,
          'internal' => TRUE,
          'audience' => 'outsider',
          'group_type' => $group_type_id,
        ])->save();
        GroupRole::create([
          'id' => $this->getMemberRoleId(),
          'label' => t('Member'),
          'weight' => -100,
          'internal' => TRUE,
          'group_type' => $group_type_id,
        ])->save();

        // Enable enforced content plugins for new group types.
        $this->getContentEnablerManager()->installEnforced($this);

        // Synchronize outsider roles for new group types.
        $this->getGroupRoleSynchronizer()->createGroupRoles([$group_type_id]);
      }
    }
  }

  /**
   * Returns the group role synchronizer service.
   *
   * @return \Drupal\group\GroupRoleSynchronizerInterface
   *   The group role synchronizer service.
   */
  protected function getGroupRoleSynchronizer() {
    return \Drupal::service('group_role.synchronizer');
  }

  /**
   * Returns the content enabler plugin manager.
   *
   * @return \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   *   The group content plugin manager.
   */
  protected function getContentEnablerManager() {
    return \Drupal::service('plugin.manager.group_content_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledContentPlugins() {
    return $this->getContentEnablerManager()->getInstalled($this);
  }

  /**
   * {@inheritdoc}
   */
  public function hasContentPlugin($plugin_id) {
    $installed = $this->getContentEnablerManager()->getInstalledIds($this);
    return in_array($plugin_id, $installed);
  }

  /**
   * {@inheritdoc}
   */
  public function getContentPlugin($plugin_id) {
    return $this->getInstalledContentPlugins()->get($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function installContentPlugin($plugin_id, array $configuration = []) {
    /** @var \Drupal\group\Entity\Storage\GroupContentTypeStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('group_content_type');
    $storage->createFromPlugin($this, $plugin_id, $configuration)->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateContentPlugin($plugin_id, array $configuration) {
    $plugin = $this->getContentPlugin($plugin_id);
    GroupContentType::load($plugin->getContentTypeConfigId())->updateContentPlugin($configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallContentPlugin($plugin_id) {
    $plugin = $this->getContentPlugin($plugin_id);
    GroupContentType::load($plugin->getContentTypeConfigId())->delete();
    return $this;
  }

}

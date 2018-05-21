<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Group routes.
 */
class GroupController extends ControllerBase {

  /**
   * The private store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the group creation form.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The type of group to create.
   *
   * @return array
   *   A group submission form.
   */
  public function addForm(GroupTypeInterface $group_type) {
    $wizard_id = 'group_creator';
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $group_type->id();

    // See if the group type is configured to ask the creator to fill out their
    // membership details. Also pass this info to the form state.
    $extra['group_wizard'] = $group_type->creatorMustCompleteMembership();
    $extra['group_wizard_id'] = $wizard_id;

    // Pass the group type and store ID to the form state as well.
    $extra['group_type'] = $group_type;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step2 = $extra['group_wizard'] && $store->get("$store_id:step") === 2;

    // Group form, potentially as wizard step 1.
    if (!$step2) {
      $storage = $this->entityTypeManager()->getStorage('group');

      // Only create a new group if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values['type'] = $group_type->id();
        $entity = $storage->create($values);
      }
    }
    // Wizard step 2: Group membership form.
    else {
      // Create an empty group membership that does not yet have a group set.
      $values = [
        'type' => $group_type->getContentPlugin('group_membership')->getContentTypeConfigId(),
        'entity_id' => $this->currentUser()->id(),
      ];
      $entity = $this->entityTypeManager()->getStorage('group_content')->create($values);
    }

    // Return the entity form with the configuration gathered above.
    return $this->entityFormBuilder()->getForm($entity, 'add', $extra);
  }

}

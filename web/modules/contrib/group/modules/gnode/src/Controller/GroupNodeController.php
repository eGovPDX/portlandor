<?php

namespace Drupal\gnode\Controller;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for 'group_node' GroupContent routes.
 */
class GroupNodeController extends GroupContentController {

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupNodeController.
   *
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(GroupContentEnablerManagerInterface $plugin_manager, PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    parent::__construct($temp_store_factory, $entity_type_manager, $entity_form_builder, $renderer);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE) {
    $build = parent::addPage($group, $create_mode);

    // Do not interfere with redirects.
    if (!is_array($build)) {
      return $build;
    }

    // Overwrite the label and description for all of the displayed bundles.
    $storage_handler = $this->entityTypeManager->getStorage('node_type');
    foreach ($this->addPageBundles($group, $create_mode) as $plugin_id => $bundle_name) {
      if (!empty($build['#bundles'][$bundle_name])) {
        $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
        $bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();

        $t_args = ['%node_type' => $bundle_label];
        $description = $create_mode
          ? $this->t('Create a node of type %node_type in the group.', $t_args)
          : $this->t('Add an existing node of type %node_type to the group.', $t_args);

        $build['#bundles'][$bundle_name]['label'] = $bundle_label;
        $build['#bundles'][$bundle_name]['description'] = $description;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageBundles(GroupInterface $group, $create_mode) {
    $bundles = [];

    // Retrieve all group_node plugins for the group's type.
    $plugin_ids = $this->pluginManager->getInstalledIds($group->getGroupType());
    foreach ($plugin_ids as $key => $plugin_id) {
      if (strpos($plugin_id, 'group_node:') !== 0) {
        unset($plugin_ids[$key]);
      }
    }

    // Retrieve all of the responsible group content types, keyed by plugin ID.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $properties = ['group_type' => $group->bundle(), 'content_plugin' => $plugin_ids];
    foreach ($storage->loadByProperties($properties) as $bundle => $group_content_type) {
      /** @var \Drupal\group\Entity\GroupContentTypeInterface $group_content_type */
      $bundles[$group_content_type->getContentPluginId()] = $bundle;
    }

    return $bundles;
  }

}

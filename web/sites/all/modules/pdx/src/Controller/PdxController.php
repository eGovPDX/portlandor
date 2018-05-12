<?php

namespace Drupal\pdx\Controller;

use Drupal\Core\Controller\ControllerBase;
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
class PdxController extends ControllerBase {

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
                ? $this->t('Create a node of type %node_type in the group...', $t_args)
                : $this->t('Add an existing node of type %node_type to the group...', $t_args);

                $build['#bundles'][$bundle_name]['label'] = $bundle_label;
                $build['#bundles'][$bundle_name]['description'] = $description;
            }
        }

        return $build;
    }

}
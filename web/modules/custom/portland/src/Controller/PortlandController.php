<?php

namespace Drupal\portland\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Config\Entity;
use Symfony\Component\HttpKernel;
use Drupal\Core\Plugin;

/**
 * Returns responses for 'group_node' GroupContent routes.
 */
class PortlandController extends GroupContentController {

    /**
     * {@inheritdoc}
     */
    public function addPage(GroupInterface $group, $create_mode = FALSE) {

        $build = GroupContentController::addPage($group, $create_mode);

        // Do not interfere with redirects.
        if (!is_array($build)) {
            return $build;
        }

        // Overwrite the label and description for all of the displayed bundles.
        $storage_handler = $this->entityTypeManager->getStorage('node_type');
        $media_storage_handler = $this->entityTypeManager->getStorage('media_type');
        $page_bundles = $this->addPageBundles($group, $create_mode);
        // NOTE: ksort is working here, but bundle types are still displayed out of order.
        // there must be some other sorting process that occurs after this point.
        ksort($page_bundles);
        foreach ($page_bundles as $plugin_id => $bundle_name) {
            if (!empty($build['#bundles'][$bundle_name])) {
                $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
                if(strpos($plugin_id, 'group_media:') === 0) {
                    $bundle_label = $media_storage_handler->load($plugin->getEntityBundle())->label();
                    $bundle_id = $media_storage_handler->load($plugin->getEntityBundle())->id();
                    $bundle_desc = \Drupal::config('media.type.' . $bundle_id)->get('description');
                }
                else {
                    $bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();
                    $bundle_id = $storage_handler->load($plugin->getEntityBundle())->id();
                    $bundle_desc = \Drupal::config('node.type.' . $bundle_id)->get('description');
                }
                $t_args = ['%node_type' => $bundle_label];
                $description = $create_mode
                ? $this->t('Create a node of type %node_type in the group...<br>' . $bundle_desc, $t_args)
                : $this->t('Add an existing node of type %node_type to the group...<br>' . $bundle_desc, $t_args);

                $build['#bundles'][$bundle_name]['label'] = $bundle_label;
                $build['#bundles'][$bundle_name]['description'] = $description;
            }
        }

        return $build;
    }

}
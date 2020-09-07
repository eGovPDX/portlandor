<?php

namespace Drupal\portland_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\groupmedia\Controller\GroupMediaController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Config\Entity;
use Symfony\Component\HttpKernel;
use Drupal\Core\Plugin;

/**
 * Returns responses for 'group_media' GroupContent routes.
 */
class PortlandGroupMediaController extends GroupMediaController {

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
        $media_storage_handler = $this->entityTypeManager->getStorage('media_type');
        $page_bundles = $this->addPageBundles($group, $create_mode);
        // NOTE: ksort is working here, but bundle types are still displayed out of order.
        // there must be some other sorting process that occurs after this point.
        ksort($page_bundles);
        foreach ($page_bundles as $plugin_id => $bundle_name) {
            // Only handle Media types here. Content are handled by PortlanController next door.
            if(strpos($plugin_id, 'group_media:') !== 0) continue;
            if (!empty($build['#bundles'][$bundle_name])) {
                $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
                $bundle_label = $media_storage_handler->load($plugin->getEntityBundle())->label();
                $bundle_id = $media_storage_handler->load($plugin->getEntityBundle())->id();
                $bundle_desc = \Drupal::config('media.type.' . $bundle_id)->get('description');
                $t_args = ['%node_type' => $bundle_label];

                $new_bundles[$bundle_name] = $build['#bundles'][$bundle_name];
                $new_bundles[$bundle_name]['label'] = $bundle_label;
                $new_bundles[$bundle_name]['description'] = $bundle_desc;
                // build custom link text; this overrides the link text created in the GroupNodeDeriver
                $new_bundles[$bundle_name]['add_link']->setText(t('Add ' . $bundle_label));
            }
        }

        $build['#bundles'] = $new_bundles;
        return $build;
    }

}
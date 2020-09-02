<?php

namespace Drupal\portland_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupContentController;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupContentType;
use Drupal\node\Entity\NodeType;
use Drupal\media\Entity\MediaType;

use Drupal\Core\Config\Entity;
use Symfony\Component\HttpKernel;
use Drupal\Core\Plugin;

/**
 * Returns responses for 'group_node' GroupContent routes.
 */
class PortlandGroupContentController extends GroupContentController {

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
        $page_bundles = $this->addPageBundles($group, $create_mode);
        // NOTE: ksort is working here, but bundle types are still displayed out of order.
        // there must be some other sorting process that occurs after this point.
        ksort($page_bundles);
        foreach ($page_bundles as $plugin_id => $bundle_name) {
            // Don't process Media types. They are handled by PortlanMediaController next door.
            if(strpos($plugin_id, 'group_media:') === 0) {
                unset($build['#bundles'][$bundle_name]);
                continue;
            }
            if (!empty($build['#bundles'][$bundle_name])) {
                $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
                $bundle_label = $storage_handler->load($plugin->getEntityBundle())->label();
                $bundle_id = $storage_handler->load($plugin->getEntityBundle())->id();
                $bundle_desc = \Drupal::config('node.type.' . $bundle_id)->get('description');
                $t_args = ['%node_type' => $bundle_label];

                $build['#bundles'][$bundle_name]['label'] = $bundle_label;
                $build['#bundles'][$bundle_name]['description'] = $bundle_desc;

                // build custom link text; this overrides the link text created in the GroupNodeDeriver
                $text = t('Add ' . $bundle_label);
                $build['#bundles'][$bundle_name]['add_link']->setText($text);
            }
        }

        return $build;
    }

    /**
     * The _title_callback for the entity.group_content.create_form route.
     *
     * @param \Drupal\group\Entity\GroupInterface $group
     *   The group to create the group content in.
     * @param string $plugin_id
     *   The group content enabler to create content with.
     *
     * @return string
     *   The page title.
     */
    public function createFormTitle(GroupInterface $group, $plugin_id) {
        /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
        $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
        $entity_type = $plugin->getEntityTypeId();
        switch ($entity_type) {
        case "media":
            $content_type = MediaType::load($plugin->getEntityBundle());
            break;
        case "node":
            $content_type = NodeType::load($plugin->getEntityBundle());
            break;
        default:
            $content_type = "undefined";
        }
        $return = $this->t('Create @name in @group', ['@name' => $content_type->label(), '@group' => $group->label()]);
        return $return;
    }
}

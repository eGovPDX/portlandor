<?php

namespace Drupal\portland_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\group\Entity\Controller\GroupRelationshipController;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupRelationshipType;
use Drupal\node\Entity\NodeType;
use Drupal\media\Entity\MediaType;

use Drupal\Core\Config\Entity;
use Symfony\Component\HttpKernel;
use Drupal\Core\Plugin;

/**
 * Returns responses for 'group_node' GroupRelationship routes.
 */
class PortlandGroupRelationshipController extends GroupRelationshipController
{

    /**
     * {@inheritdoc}
     */
    public function addPage(GroupInterface $group, $create_mode = FALSE, $base_plugin_id = NULL)
    {

        $build = GroupRelationshipController::addPage($group, $create_mode, $base_plugin_id);

        // Do not interfere with redirects.
        if (!is_array($build)) {
            return $build;
        }

        // Overwrite the label and description for all of the displayed bundles.
        $storage_handler = $this->entityTypeManager->getStorage('node_type');
        $page_bundles = $this->addPageBundles($group, $create_mode, $base_plugin_id);

        // We want the $build['#bundles'] array to be sorted by bundle label, but it's
        // sorted by array index which is the bundle machine name.
        // We need to build a new array using the bundle label as the array index.
        $new_bundles = [];
        foreach ($page_bundles as $plugin_id => $bundle_name) {
            // Don't process Media types. They are handled by PortlanMediaController next door.
            if (strpos($plugin_id, 'group_media:') === 0) {
                unset($build['#bundles'][$bundle_name]);
                continue;
            }
            if (!empty($build['#bundles'][$plugin_id])) {
                $pluginID = $bundle_name->getPlugin()->getPluginId();
                $plugin = $group->getGroupType()->getPlugin($pluginID);
                $plugin_bundle = $plugin->getPluginDefinition()->getEntityBundle();
                $bundle_label = $storage_handler->load($plugin_bundle)->label();
                $bundle_id = $storage_handler->load($plugin_bundle)->id();
                $bundle_desc = \Drupal::config('node.type.' . $bundle_id)->get('description');

                $new_bundles[$bundle_label] = $build['#bundles'][$plugin_id];
                $new_bundles[$bundle_label]['label'] = $bundle_label;
                $new_bundles[$bundle_label]['description'] = $bundle_desc;
                // build custom link text; this overrides the link text created in the GroupNodeDeriver
                $new_bundles[$bundle_label]['add_link']->setText(t('Add ' . $bundle_label));
            }
        }

        // Sort the new array by key (i.e. bundle label)
        ksort($new_bundles);

        $build['#bundles'] = $new_bundles;
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
    public function createFormTitle(GroupInterface $group, $plugin_id)
    {
        /** @var \Drupal\gnode\Plugin\Group\Relation\GroupNode $plugin */
        $plugin = $group->getGroupType()->getPlugin($plugin_id);
        $entity_type = $plugin->getPluginDefinition()->getEntityTypeId();
        $plugin_bundle = $plugin->getPluginDefinition()->getEntityBundle();
        switch ($entity_type) {
            case "media":
                $content_type = MediaType::load($plugin_bundle);
                $name = $content_type->label();
                break;
            case "node":
                $content_type = NodeType::load($plugin_bundle);
                $name = $content_type->label();
                break;
            case "taxonomy_term":
                $name = $plugin_bundle . " term";
                break;
            default:
                $name = "undefined";
        }
        $return = $this->t('Create @name in @group', ['@name' => $name, '@group' => $group->label()]);
        return $return;
    }
}

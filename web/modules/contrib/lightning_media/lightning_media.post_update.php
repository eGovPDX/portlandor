<?php

use Drupal\system\Entity\Action;

/**
 * Change plugin IDs of actions.
 */
function lightning_media_post_update_change_action_plugins() {
  $old_new_action_id_map = [
    'media_publish_action' => 'entity:publish_action:media',
    'media_unpublish_action' => 'entity:unpublish_action:media',
    'media_save_action' => 'entity:save_action:media',
    'media_delete_action' => 'entity:delete_action:media',
  ];

  /** @var Action[] $actions */
  $actions = Action::loadMultiple();
  foreach ($actions as $action) {
    $plugin_id = $action->get('plugin');

    if (isset($old_new_action_id_map[$plugin_id])) {
      $action->setPlugin($old_new_action_id_map[$plugin_id]);
      $action->save();
    }
  }
}

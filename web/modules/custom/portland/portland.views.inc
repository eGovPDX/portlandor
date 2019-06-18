<?php

/**
 * Implements hook_views_data_alter().
 */
function portland_views_data_alter(array &$data) {
    $data['node']['is_featured_in_group'] = array(
        'title' => t('Is featured in parent group'),
        'field' => array(
            'title' => t('Is featured in parent group'),
            'help' => t('TRUE if this node is featured in its field_group group.'),
            'id' => 'is_featured_in_group',
        ),
    );
}
<?php

namespace Drupal\group_test_plugin\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;

/**
 * Provides a content enabler for users.
 *
 * @GroupContentEnabler(
 *   id = "user_as_content",
 *   label = @Translation("Group user"),
 *   description = @Translation("Adds users to groups without making them members."),
 *   entity_type_id = "user",
 *   pretty_path_key = "user",
 *   reference_label = @Translation("Username"),
 *   reference_description = @Translation("The name of the user you want to add to the group")
 * )
 */
class UserAsContent extends GroupContentEnablerBase {
}

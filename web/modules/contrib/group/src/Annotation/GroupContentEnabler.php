<?php

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GroupContentEnabler annotation object.
 *
 * Plugin Namespace: Plugin\GroupContentEnabler
 *
 * For a working example, see
 * \Drupal\group\Plugin\GroupContentEnabler\GroupMembership
 *
 * @see \Drupal\group\Plugin\GroupContentEnablerInterface
 * @see \Drupal\group\Plugin\GroupContentEnablerManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupContentEnabler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the GroupContentEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The ID of the entity type you want to enable as group content.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * (optional) The bundle of the entity type you want to enable as group content.
   *
   * Do not specify if your plugin manages all bundles.
   *
   * @var string|false
   */
  public $entity_bundle = FALSE;

  /**
   * (optional) Whether the plugin defines entity access.
   *
   * This controls whether you can create entities within the group (TRUE) or
   * only add existing ones (FALSE). It also generates the necessary group
   * permissions when enabled.
   *
   * Eventually, this will even generate entity access records for you, but that
   * will only happen after the patch in https://www.drupal.org/node/777578 has
   * been committed to Drupal core.
   *
   * @var bool
   */
  public $entity_access = FALSE;

  /**
   * (optional) The key to use in automatically generated paths.
   *
   * This is exposed through tokens so modules like Pathauto may use it. Only
   * use this if your plugin has something meaningful to show on the actual
   * group content entity; i.e.: the relationship. Otherwise leave blank so it
   * defaults to 'content'.
   *
   * @var string
   */
  public $pretty_path_key = 'content';

  /**
   * (optional) The label for the entity reference field.
   *
   * @var string
   */
  public $reference_label;

  /**
   * (optional) The description for the entity reference field.
   *
   * @var string
   */
  public $reference_description;

  /**
   * (optional) Whether this plugin is always on.
   *
   * @var bool
   */
  public $enforced = FALSE;

}

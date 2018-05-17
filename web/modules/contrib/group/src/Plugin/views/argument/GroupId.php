<?php

namespace Drupal\group\Plugin\views\argument;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Argument handler to accept a group ID.
 *
 * @ViewsArgument("group_id")
 */
class GroupId extends NumericArgument {

  /**
   * The group storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $groupStorage;

  /**
   * Constructs the Gid object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ContentEntityStorageInterface $group_storage
   *   The group entity storage handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContentEntityStorageInterface $group_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->groupStorage = $group_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('group')
    );
  }

  /**
   * Override the behavior of title(). Get the title of the group.
   */
  public function titleQuery() {
    $titles = [];

    $groups = $this->groupStorage->loadMultiple($this->value);
    foreach ($groups as $group) {
      $titles[] = $group->label();
    }

    return $titles;
  }

}

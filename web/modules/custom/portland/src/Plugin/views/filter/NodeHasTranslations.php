<?php

namespace Drupal\portland\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View filter for a boolean of whether node has translations.
 *
 * @ViewsFilter("node_has_translations")
 */
class NodeHasTranslations extends BooleanOperator implements ContainerFactoryPluginInterface {
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LanguageManagerInterface $languageManager;


  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $entity_type->getKeys();
    $entity_id_key = $keys['id'];

    $query_base_table = $this->relationship ?: $this->view->storage->get('base_table');
    $entity_data_table = $entity_type->getDataTable();

    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $subselect = $this->database
      ->select($entity_data_table, 'e')
      ->fields('e', [$entity_id_key, 'langcode'])
      ->where("[e].[$entity_id_key] = [$query_base_table].[$entity_id_key]")
      ->condition('e.langcode', $default_langcode, '<>');

    $condition = $this->database->condition('OR');
    // If filter is (Equal to True OR Not Equal to False): Check that node has translations
    // Else: Check that node does not have translations
    if (
      ($this->operator === '=' && $this->value)
      || ($this->operator === '!=' && !$this->value)) {
      $condition = $condition->exists($subselect);
    } else {
      $condition = $condition->notExists($subselect);
    }
    $this->query->addWhere($this->options['group'], $condition);
  }
}

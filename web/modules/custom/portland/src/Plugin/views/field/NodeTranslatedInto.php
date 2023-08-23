<?php

namespace Drupal\portland\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View field provider for a list of languages that the node has been translated into.
 *
 * @ViewsField("node_translated_into")
 */
class NodeTranslatedInto extends FieldPluginBase {
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LanguageManagerInterface $languageManager;

  protected $languages;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;

    $this->languages = $this->languageManager->getLanguages(LanguageInterface::STATE_ALL);
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
    // do nothing -- to override the parent query.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    $entity = $row->_entity;
    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityType());
    $keys = $entity_type->getKeys();
    $entity_id_key = $keys['id'];

    $entity_data_table = $entity_type->getDataTable();

    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $translated_langcodes = $this->database
      ->select($entity_data_table, 'e')
      ->fields('e', [$entity_id_key, 'langcode'])
      ->where("[e].[$entity_id_key] = {$entity->id()}")
      ->condition('e.langcode', $default_langcode, '<>')
      ->execute()
      ->fetchAllKeyed(1, 1);
    $languages = array_intersect_key($this->languages, $translated_langcodes);

    return [
      '#markup' => join(
        ', ',
        array_map(
          function($lang) use ($entity) {
            $translated_url = "/{$lang->getId()}/node/{$entity->id()}";
            return "<a href=\"{$translated_url}\">{$lang->getName()}</a>";
          },
          $languages,
        ),
      ),
    ];
  }
}

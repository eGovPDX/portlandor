<?php

namespace Drupal\media_embed_field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Plugin to migrate from the Drupal 6 emfield module.
 *
 * @MigrateCckField(
 *   id = "emmedia",
 *   core = {6},
 *   source_module = "emfield",
 *   destination_module = "media_embed_field",
 * )
 */
class EmmediaField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return 'media_embed_field';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'media_embed_field_media',
      'media' => 'media_embed_field_media',
      'thumbnail' => 'media_embed_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'emmedia_textfields' => 'media_embed_field_textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'embed',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}

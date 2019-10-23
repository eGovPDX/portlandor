<?php

namespace Drupal\media_embed_field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Plugin to migrate from the Drupal 7 media_embed_field module.
 *
 * @MigrateCckField(
 *   id = "media_embed_field",
 *   core = {7},
 *   source_module = "media_embed_field",
 *   destination_module = "media_embed_field",
 * )
 */
class MediaEmbedField extends FieldPluginBase {

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
      'media_embed_field' => 'media_embed_field_media',
      'media_embed_field_thumbnail' => 'media_embed_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'media_embed_field_media' => 'media_embed_field_textfield',
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
        'value' => 'media_url',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}

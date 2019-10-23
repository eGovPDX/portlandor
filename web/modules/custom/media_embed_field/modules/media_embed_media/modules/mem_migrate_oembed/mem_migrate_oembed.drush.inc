<?php

/**
 * @file
 * Drush integration for mem_migrate_oembed.
 */

/**
 * Implements hook_drush_command().
 */
function mem_migrate_oembed_drush_command() {

  $items['media-embed-media-migrate-oembed'] = [
    'description' => 'Migrate from VEF to oEmbed in core.',
    'aliases' => ['vemmo'],
    'core' => ['8+'],
    'examples' => [
      "drush vemmo" => "Migrate from VEF to oEmbed in core",
    ],
  ];

  return $items;
}

/**
 * Callback for drush commmand "media-embed-media-migrate-oembed" (vemmo).
 */
function drush_mem_migrate_oembed_media_embed_media_migrate_oembed() {

  /** @var \Drupal\mem_migrate_oembed\MemMigrate $migrate */
  $migrate = \Drupal::service('mem_migrate_oembed.migrate');
  $migrate->migrate();
}

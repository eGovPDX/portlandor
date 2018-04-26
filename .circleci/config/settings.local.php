<?php

// $databases['default']['default'] = array (
//   'database' => 'drupal8',
//   'username' => 'drupal8',
//   'password' => 'drupal8',
//   'prefix' => '',
//   'host' => '127.0.0.1',
//   'port' => '',
//   'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
//   'driver' => 'mysql',
// );
$databases['default']['default'] = array (
    'database' => 'drupal8',
    'username' => 'drupal8',
    'password' => 'drupal8',
    'prefix' => '',
    'host' => '127.0.0.1',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  );

$config['system.file']['path']['temporary'] = '/tmp';
$settings['hash_salt'] = 'CHANGE_THIS';
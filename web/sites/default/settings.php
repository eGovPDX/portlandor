<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';
$settings['container_yamls'][] = __DIR__ . '/monolog.services.yml';

/**
 * Entity update backup.
 *
 * This is used to inform the entity storage handler that the backup tables as
 * well as the original entity type and field storage definitions should be
 * retained after a successful entity update process.
 */
$settings['entity_update_backup'] = FALSE;

/**
 * Include the Pantheon-specific settings file.
 *
 * n.b. The settings.pantheon.php file makes some changes
 *      that affect all envrionments that this site
 *      exists in.  Always include this file, even in
 *      a local development environment, to insure that
 *      the site settings remain consistent.
 */
include __DIR__ . "/settings.pantheon.php";

/**
 * If there is a local settings file, then include it
 */
$local_settings = __DIR__ . "/settings.local.php";
if (file_exists($local_settings)) {
  include $local_settings;
}

// Set environment variable for config_split module
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  // Pantheon environments are 'live', 'test', 'dev', and '[multidev name]'
  $env = $_ENV['PANTHEON_ENVIRONMENT'];
}
else {
  // Default to lando if no Pantheon Environment is set.
  $env = 'lando';
  $_ENV['PANTHEON_ENVIRONMENT'] = 'lando';
}

if (isset($_ENV['PANTHEON_ENVIRONMENT']) && php_sapi_name() != 'cli') {
  // Redirect to https://$primary_domain in the Live environment
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'www.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#dc3545';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Production';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'test.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#ffb81c';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Test';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'lando') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'portlandor.lndo.site';
    $config['environment_indicator.indicator']['bg_color'] = '#046a38';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Local';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'dev') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'dev-portlandor.pantheonsite.io';
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Dev';
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'sandbox') {
    $primary_domain = 'sandbox.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Sandbox';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
    $config['environment_indicator.indicator']['bg_color'] = '#3455eb';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Multidev';
  }

  if ($_ENV['PANTHEON_ENVIRONMENT'] !== 'lando' &&
      ($_SERVER['HTTP_HOST'] != $primary_domain
        || !isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
        || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON' ) ) {

    # Name transaction "redirect" in New Relic for improved reporting (optional)
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Append $primary_domain to Drupal 8 Trusted Host settings array
  if (is_array($settings)) {
    $settings['trusted_host_patterns'][] = '^'. preg_quote($primary_domain) .'$';
  }
}

// Override the SP Entity ID value for each environment
if(empty($primary_domain)) {
  $primary_domain = "portlandor.lndo.site";
}
$config['samlauth.authentication']['sp_entity_id'] = $primary_domain;

// Enable/disable config_split configurations based on the current environment
$config['config_split.config_split.config_multidev']['status'] = FALSE;
$config['config_split.config_split.config_dev']['status'] = FALSE;
$config['config_split.config_split.config_test']['status'] = FALSE;
$config['config_split.config_split.config_prod']['status'] = FALSE;
$config['config_split.config_split.config_local']['status'] = FALSE;
switch ($env) {
  case 'live':
    $config['config_split.config_split.config_prod']['status'] = TRUE;
    break;
  case 'test':
    $config['config_split.config_split.config_test']['status'] = TRUE;
    break;
  case 'dev':
    $config['config_split.config_split.config_dev']['status'] = TRUE;
    break;
  case 'lando':
    $config['config_split.config_split.config_local']['status'] = TRUE;
    break;
  default:  // Everything else (i.e. various multidev environments)
    $config['config_split.config_split.config_multidev']['status'] = TRUE;
    break;
}

// Set the core to "Demo" on the Demo multidev
if($env == 'demo') {
  $config['search_api.server.searchstax']['backend_config']['connector_config']['core'] = 'Demo';
}
else if($env == 'live') {
  $config['search_api.server.searchstax']['backend_config']['connector_config']['core'] = 'Production';
}
else {
  $config['search_api.server.searchstax']['backend_config']['connector_config']['core'] = 'Test';
}


// Overwrite Google Tag Manager environment setting in 'live' production site.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['google_tag.container.portland.gov']['environment_id'] = 'env-1';
    $config['google_tag.container.portland.gov']['environment_token'] = 'gX0sWBBfUHwzWER-y90CyQ';
  }
}

// Pantheon workaround for New Relic segmentation fault issue (https://discuss.newrelic.com/t/segmentation-fault-in-9-7-0-258/94830/20)
// Disables New Relic for CLI context to prevent CircleCI build failures
if (function_exists('newrelic_ignore_transaction') && php_sapi_name() === 'cli') {
  newrelic_ignore_transaction();
}

$config['file.settings']['make_unused_managed_files_temporary'] = TRUE;


// Configure Redis (code borrowed from https://docs.pantheon.io/object-cache/drupal)
if (isset($_ENV['PANTHEON_ENVIRONMENT']) && $_ENV['PANTHEON_ENVIRONMENT'] !== 'lando' 
    && !\Drupal\Core\Installer\InstallerKernel::installationAttempted() && extension_loaded('redis')) {
  
  // Set Redis as the default backend for any cache bin not otherwise specified.
  $settings['cache']['default'] = 'cache.backend.redis';

  //phpredis is built into the Pantheon application container.
  $settings['redis.connection']['interface'] = 'PhpRedis';

  // These are dynamic variables handled by Pantheon.
  $settings['redis.connection']['host'] = $_ENV['CACHE_HOST'];
  $settings['redis.connection']['port'] = $_ENV['CACHE_PORT'];
  $settings['redis.connection']['password'] = $_ENV['CACHE_PASSWORD'];

  $settings['redis_compress_length'] = 100;
  $settings['redis_compress_level'] = 1;
  
  $settings['cache_prefix']['default'] = 'pantheon-redis';

  $settings['cache']['bins']['form'] = 'cache.backend.database'; // Use the database for forms

  // Apply changes to the container configuration to make better use of Redis.
  // This includes using Redis for the lock and flood control systems, as well
  // as the cache tag checksum. Alternatively, copy the contents of that file
  // to your project-specific services.yml file, modify as appropriate, and
  // remove this line.
  $settings['container_yamls'][] = 'modules/contrib/redis/example.services.yml';

  // Manually add the classloader path, this is required for the container
  // cache bin definition below.
  $class_loader->addPsr4('Drupal\\redis\\', 'modules/contrib/redis/src');

  // Use redis for container cache.
  // The container cache is used to load the container definition itself, and
  // thus any configuration stored in the container itself is not available
  // yet. These lines force the container cache to use Redis rather than the
  // default SQL cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize',
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}

// Automatically generated include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

// Set the MySQL transaction isolation level
// See https://www.drupal.org/docs/getting-started/system-requirements/setting-the-mysql-transaction-isolation-level
$databases['default']['default']['init_commands']['isolation_level'] = 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED';

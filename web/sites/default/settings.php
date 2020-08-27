<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';
$settings['container_yamls'][] = __DIR__ . '/monolog.services.yml';

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
$settings['install_profile'] = 'lightning';

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

// Set environment variable for config_split module
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  // Pantheon environments are 'live', 'test', 'dev', and '[multidev name]'
  $env = $_ENV['PANTHEON_ENVIRONMENT'];
}
else {
  // Treat local dev same as Pantheon 'dev'
  $env = 'dev';
}

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


/**
 * Overwrite Google Tag Manager environment setting in 'live' production site.
 *
 * Disable 'include environment' so it uses production settings.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['google_tag.container.portland.gov']['include_environment'] = FALSE;
  }
}

// Pantheon workaround for New Relic segmentation fault issue (https://discuss.newrelic.com/t/segmentation-fault-in-9-7-0-258/94830/20)
// Disables New Relic for CLI context to prevent CircleCI build failures
if (function_exists('newrelic_ignore_transaction') && php_sapi_name() === 'cli') {
  newrelic_ignore_transaction();
}

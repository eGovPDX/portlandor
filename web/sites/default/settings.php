<?php

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = __DIR__ . '/services.yml';

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
    $primary_domain = 'beta.portland.gov';
    $config['environment_indicator.indicator']['bg_color'] = '#dc3545';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Live';
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
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
    $config['environment_indicator.indicator']['bg_color'] = '#046a38';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
    $config['environment_indicator.indicator']['name'] = 'Dev';
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

/**
 * Overwrite Google Analytics tracking code in 'live' production site. 
 * 
 * Set to tracking ID of Google Analytics property for 'production' site.
 */
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] === 'live') {
    $config['google_analytics.settings']['account'] = 'UA-6858269-14';
  }
}

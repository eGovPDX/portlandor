<?php

use Drupal\portland\SecretsReader;

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
  }
  elseif ($_ENV['PANTHEON_ENVIRONMENT'] === 'test') {
    /** Replace www.example.com with your registered domain name */
    $primary_domain = 'test.portland.gov';
  }
  else {
    // Redirect to HTTPS on every Pantheon environment.
    $primary_domain = $_SERVER['HTTP_HOST'];
  }

  if ($_SERVER['HTTP_HOST'] != $primary_domain
      || !isset($_SERVER['HTTP_USER_AGENT_HTTPS'])
      || $_SERVER['HTTP_USER_AGENT_HTTPS'] != 'ON' ) {

    # Name transaction "redirect" in New Relic for improved reporting (optional)
    if (extension_loaded('newrelic')) {
      newrelic_name_transaction("redirect");
    }

    header('HTTP/1.0 301 Moved Permanently');
    header('Location: https://'. $primary_domain . $_SERVER['REQUEST_URI']);
    exit();
  }
  // Drupal 8 Trusted Host Settings
  if (is_array($settings)) {
    $settings['trusted_host_patterns'] = array('^'. preg_quote($primary_domain) .'$');
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
switch ($env) {
  case 'live':
    $config['config_split.config_split.config_dev']['status'] = FALSE;
    $config['config_split.config_split.config_prod']['status'] = TRUE;
    break;
  case 'test':
    $config['config_split.config_split.config_dev']['status'] = FALSE;
    $config['config_split.config_split.config_prod']['status'] = FALSE;
    break;
  case 'dev':
  default:  // Everything else (i.e. various multidev environments)
    $config['config_split.config_split.config_dev']['status'] = TRUE;
    $config['config_split.config_split.config_prod']['status'] = FALSE;
    break;
}

// Set Solr server secrets
//$secrets_reader = new SecretsReader;
$secrets = _get_secrets(array('solr_host'), array());
switch ($env) {
  case 'live':
    $config['search_api.server.searchstax_test']['backend_config']['connector_config']['host'] = '';
    $config['search_api.server.searchstax_test']['backend_config']['connector_config']['core'] = '';
    break;
  case 'test':
    break;
  case 'dev':
  default:  // Everything else (i.e. various multidev environments)
    break;
}

// TODO: Variations of this code also exist in slack_deploy_notification.php and SecretsReader.php
/**
 * Get secrets from secrets file.
 *
 * @param array $requiredKeys  List of keys in secrets file that must exist.
 */
function _get_secrets($requiredKeys, $defaults)
{
  $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
  if (!file_exists($secretsFile)) {
    die('No secrets file found. Aborting!');
  }
  $secretsContents = file_get_contents($secretsFile);
  $secrets = json_decode($secretsContents, 1);
  if ($secrets == FALSE) {
    die('Could not parse json in secrets file. Aborting!');
  }
  $secrets += $defaults;
  $missing = array_diff($requiredKeys, array_keys($secrets));
  if (!empty($missing)) {
    die('Missing required keys in json secrets file: ' . implode(',', $missing) . '. Aborting!');
  }
  return $secrets;
}

<?php

$defaults = [];

$secrets_map = [
  'azure_client_id' => 'client_id',
  'azure_client_secret' => 'client_secret'
];

// Load our hidden credentials.
// See the README.md for instructions on storing secrets.
$secrets = _get_secrets(array_keys($secrets_map), $defaults);

$config_name = 'openid_connect.settings.azure';

foreach ($secrets_map as $secret_key => $config_key) {
  $command = sprintf('drush -y cset %s %s \'%s\'', $config_name, $config_key, $secrets[$secret_key]);
  passthru($command);
}

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

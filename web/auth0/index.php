<?php

  declare(strict_types=1);

  require(__DIR__ . '/../../vendor/autoload.php');

  use Auth0\SDK\Auth0;
  use Auth0\SDK\Configuration\SdkConfiguration;

  $secrets = _get_secrets(array('auth0_client_id','auth0_client_secret'), []);

  $configuration = new SdkConfiguration(
    domain: 'dev-efkownmvxntcobt8.us.auth0.com',
    clientId: $secrets['auth0_client_id'],
    clientSecret: $secrets['auth0_client_secret'],
    redirectUri: 'https://' . $_SERVER['HTTP_HOST'] . '/auth0/callback',
    sessionStorageId: 'STYXKEY_auth0_session',
    transientStorageId: 'STYXKEY_auth0_transient',
    cookieSecret: '4f60eb5de6b5904ad4b8e31d9193e7ea4a3013b476ddb5c259ee9077c05e1457'
  );

  $sdk = new Auth0($configuration);

  require('router.php');


/**
 * Get secrets from secrets file.
 *
 * @param array $requiredKeys  List of keys in secrets file that must exist.
 */
function _get_secrets($requiredKeys, $defaults)
{
  $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
  if (!file_exists($secretsFile)) {
    $secretsFile = '/app/web/sites/default/files/private/secrets.json';
    if (!file_exists($secretsFile)) {
      die('No secrets file found. Aborting!');
    }
  }
  $secretsContents = file_get_contents($secretsFile);
  $secrets = json_decode($secretsContents, true);
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

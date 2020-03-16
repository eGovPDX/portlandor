<?php

// Log deployment information to New Relic


// Use New Relic API to lookup application ID associated with Pantheon Environment
$secrets = _get_secrets(array('new_relic_api_key'), []);
$url = "https://api.newrelic.com/v2/applications.json";
$headers = array(
  'X-Api-Key: '.$secrets['new_relic_api_key'],
);
$pantheon_env = $_ENV['PANTHEON_ENVIRONMENT'];
$pantheon_env = "portlandor ($pantheon_env)";
$payload = "filter[name]=$pantheon_env&exclude_links=true";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// print("\n==== Posting to New Relic API URL $url ====\n");
$result = curl_exec($ch);
// print("RESULT: $result");
// print("\n===== Post Complete! =====\n");
curl_close($ch);
$appdata = json_decode($result);


// Prepare parameters for deployment API
$application_id = $appdata->applications[0]->id;
$url = "https://api.newrelic.com/v2/applications/$application_id/deployments.json";
$headers = array(
  'X-Api-Key: '.$secrets['new_relic_api_key'],
  'Content-Type: application/json',
);
$author = json_encode(trim(`git log -1 --pretty=%an`));
$message = json_encode(trim(`git log -1 --pretty=%B`));
$message_short = json_encode(trim(preg_replace("/CircleCI deployment for:/", "", `git log -1 --pretty=%B`)));
$hash = json_encode(trim(`git log -1 --pretty=%h`));
$payload = <<<PAYLOAD
{
  "deployment": {
    "revision": $hash,
    "description": $message_short,
    "changelog": $message,
    "user": $author
  }
}
PAYLOAD;

// var_dump($url);
// var_dump($headers);
// var_dump($message_short);
// var_dump($payload);
// exit;

// Sample New Relic Deployment API -- https://rpm.newrelic.com/api/explore/applications/list
//
// curl -X POST 'https://api.newrelic.com/v2/applications/{application_id}/deployments.json' \
//      -H 'X-Api-Key:{api_key}' -i \
//      -H 'Content-Type: application/json' \
//      -d \
// '{
//   "deployment": {
//     "revision": "string",
//     "changelog": "string",
//     "description": "string",
//     "user": "string"
//   }
// }' 

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// print("\n==== Posting to New Relic API URL $url ====\n");
$result = curl_exec($ch);
print("RESULT: $result");
// print("\n===== Post Complete! =====\n");
curl_close($ch);




/**
 * Get secrets from secrets file.
 *
 * @param array $requiredKeys  List of keys in secrets file that must exist.
 */
function _get_secrets($requiredKeys, $defaults) {
  $secretsFile = $_SERVER['HOME'] . '/files/private/secrets.json';
  $secretsFile = $_SERVER['HOME'] . '/code/web/sites/default/files/private/secrets.json';
  if (!file_exists($secretsFile)) {
    die('No secrets file found. Aborting!');
  }
  $secretsContents = file_get_contents($secretsFile);
  $secrets = json_decode($secretsContents, 1);
  if ($secrets == FALSE) {
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
          echo ' - No errors';
      break;
      case JSON_ERROR_DEPTH:
          echo ' - Maximum stack depth exceeded';
      break;
      case JSON_ERROR_STATE_MISMATCH:
          echo ' - Underflow or the modes mismatch';
      break;
      case JSON_ERROR_CTRL_CHAR:
          echo ' - Unexpected control character found';
      break;
      case JSON_ERROR_SYNTAX:
          echo ' - Syntax error, malformed JSON';
      break;
      case JSON_ERROR_UTF8:
          echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
      break;
      default:
          echo ' - Unknown error';
      break;
    }    
    die('Could not parse json in secrets file. Aborting!');
  }
  $secrets += $defaults;
  $missing = array_diff($requiredKeys, array_keys($secrets));
  if (!empty($missing)) {
    die('Missing required keys in json secrets file: ' . implode(',', $missing) . '. Aborting!');
  }
  return $secrets;
}

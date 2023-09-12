<?php

// Only run for deployments to Live environment
if( $_ENV['PANTHEON_ENVIRONMENT'] == 'live') {
  $git_repo_name = $_ENV['PANTHEON_SITE_NAME']; // portlandor or employees
  $url = "https://api.github.com/repos/eGovPDX/$git_repo_name/actions/workflows/rebase-sandbox-with-master.yml/dispatches";

  $defaults = [];
  $secrets = _get_secrets(array('github_actions_access_token'), $defaults);
  $payload = '{"ref":"master"}';

  $headers = array(
    'Accept: application/vnd.github+json',
    'Authorization: Bearer ' . $secrets['github_actions_access_token'],
    'User-Agent: egov-pdx',
    'X-GitHub-Api-Version: 2022-11-28',
  );
  
  // var_dump($url);
  // var_dump($headers);
  // var_dump($payload);
  // exit;

  // Shell command:
  // https://docs.github.com/rest/actions/workflows#create-a-workflow-dispatch-event
  //
  // curl -L \
  // -X POST \
  // -H "Accept: application/vnd.github+json" \
  // -H "Authorization: Bearer <YOUR-TOKEN>" \
  // -H "X-GitHub-Api-Version: 2022-11-28" \
  // https://api.github.com/repos/OWNER/REPO/actions/workflows/WORKFLOW_ID/dispatches \
  // -d '{"ref":"topic-branch","inputs":{"name":"Mona the Octocat","home":"San Francisco, CA"}}'

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  print("\n==== Posting to Webhook URL ====\n");
  $result = curl_exec($ch);
  // print("RESULT: $result");
  print("\n===== Post Complete! =====\n");
  curl_close($ch);
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

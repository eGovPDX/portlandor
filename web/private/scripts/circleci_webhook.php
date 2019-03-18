<?php
// Original source from: https://github.com/pantheon-systems/quicksilver-examples/tree/master/webhook

// Define the url the data should be sent to.
// {wf_type} will be replaced with the workflow operation: 
// clone_database, clear_cache, deploy, or sync_code.
//
// Useful to have one webhook handle multiple events.

// DO NOT run tests against Live or Test environment
if( $_ENV['PANTHEON_ENVIRONMENT'] != 'live' && $_ENV['PANTHEON_ENVIRONMENT'] != 'test') {
  $git_repo_name = $_ENV['PANTHEON_SITE_NAME']; // portlandor
  $git_branch_name = ($_ENV['PANTHEON_ENVIRONMENT'] === 'dev') ? 'master' : $_ENV['PANTHEON_ENVIRONMENT']; // master or powr-123
  $url = "https://circleci.com/api/v1.1/project/github/eGovPDX/$git_repo_name/tree/$git_branch_name";

  $defaults = [];
  $secrets = _get_secrets(array('circle_api_user_token'), $defaults);
  $payload = $_POST;

  // Add the site name to the payload in case the receiving app handles
  // multiple sites. You can enhance this payload with more data as
  // needed at this point.

  // Specify the job to run
  $payload['build_parameters[CIRCLE_JOB]'] = 'run_tests';
  $payload['site_name'] = $_ENV['PANTHEON_SITE_NAME'];
  $payload = http_build_query($payload);

  // Shell command:
  // curl -u "API_TOKEN_HERE:" \
  //      -d build_parameters[CIRCLE_JOB]=test \
  //      https://circleci.com/api/v1.1/project/github/eGovPDX/REPO_NAME/tree/BRANCH_NAME

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERNAME, $secrets['circle_api_user_token']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  print("\n==== Posting to Webhook URL ====\n");
  $result = curl_exec($ch);
  print("RESULT: $result");
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
    die('Could not parse json in secrets file. Aborting!');
  }
  $secrets += $defaults;
  $missing = array_diff($requiredKeys, array_keys($secrets));
  if (!empty($missing)) {
    die('Missing required keys in json secrets file: ' . implode(',', $missing) . '. Aborting!');
  }
  return $secrets;
}

<?php

/*
echo "Quicksilver Debuging Output";
echo "\n\n";
echo "\n========= START PAYLOAD ===========\n";
print_r($_POST);
echo "\n========== END PAYLOAD ============\n";

echo "\n------- START ENVIRONMENT ---------\n";
$env = $_ENV;
foreach ($env as $key => $value) {
  if (preg_match('#(PASSWORD|SALT|AUTH|SECURE|NONCE|LOGGED_IN)#', $key)) {
    $env[$key] = '[REDACTED]';
  }
}
print_r($env);
echo "\n-------- END ENVIRONMENT ----------\n";
*/

// Do NOT run for Dependabot branches
if (preg_match('/^bot-\d+/', $_ENV['PANTHEON_ENVIRONMENT'])) {
  die('Dependabot branch detected. Aborting!');
}


// Original source from: https://github.com/pantheon-systems/quicksilver-examples/tree/master/webhook

// Do NOT run tests against Live or Test environment
if ($_ENV['PANTHEON_ENVIRONMENT'] != 'live' && $_ENV['PANTHEON_ENVIRONMENT'] != 'test') {
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
  $payload['build_parameters[CIRCLE_JOB]'] = 'test';
  $payload['site_name'] = $_ENV['PANTHEON_SITE_NAME'];
  $payload = http_build_query($payload);

  // Shell command: the colon at the end of api token is required.
  // curl -u "{CIRCLECI_API_TOKEN}:" \
  //      -d build_parameters[CIRCLE_JOB]=test \
  //      https://circleci.com/api/v1.1/project/github/eGovPDX/REPO_NAME/tree/BRANCH_NAME

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERNAME, $secrets['circle_api_user_token']);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  print("\n==== Posting to Webhook URL $url ====\n");
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
function _get_secrets($requiredKeys, $defaults) {
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

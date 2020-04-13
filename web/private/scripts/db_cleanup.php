<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

	$webform_query = "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;";
	$watchdog_query = "truncate table watchdog;";

	// Delete webform submission data
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $webform_query . '"');
	echo "Webform submission deletion complete.\n";

	// Delete log data
	echo "Deleting log entries...\n";
	passthru('drush sql-query "' . $watchdog_query . '"');
	echo "Log entry deletion complete.\n";

}

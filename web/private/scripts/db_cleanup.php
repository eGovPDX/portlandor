<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

		// Delete webform submission data
		$webform_query = "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;";
		echo "Deleting webform submissions...\n";
		passthru('drush sql-query "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;"');
		echo "Webform submission deletion complete.\n";

}

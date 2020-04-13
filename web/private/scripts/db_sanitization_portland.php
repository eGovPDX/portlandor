<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

	$webform_query = "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;";
	$cache_query = "truncate table cache_bootstrap; truncate table cache_config; truncate table cache_container; truncate table cache_data; truncate table cache_default; truncate table cache_discovery; truncate table cache_entity; truncate table cache_page; truncate table cache_render;";

	// Delete webform submission data
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $webform_query . '"');
	echo "Webform submission deletion complete.\n";

	// Delete cache data
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $cache_query . '"');
	echo "Webform submission deletion complete.\n";

}

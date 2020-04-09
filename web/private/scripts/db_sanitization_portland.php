<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

	$sanitize_query = "update users_field_data left outer join user__roles on uid = entity_id set pass = '', mail = concat('user+', uid, '@localhost.localdomain'), init = concat('user+', uid, '@localhost.localdomain') where roles_target_id IS NULL or roles_target_id <> 'administrator';";
	$webform_query = "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;";
	$cache_query = "truncate table cache_bootstrap; truncate table cache_config; truncate table cache_container; truncate table cache_data; truncate table cache_default; truncate table cache_discovery; truncate table cache_entity; truncate table cache_page; truncate table cache_render;";

	// Run custom query to sanitize user data.
	echo "Sanitizing the user data...\n";
	passthru('drush sql-query "' . $sanitize_query . '"');
	echo "Data sanitization complete.\n";

	// Delete webform submission data
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $webform_query . '"');
	echo "Webform submission deletion complete.\n";

	// Delete cache data
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $cache_query . '"');
	echo "Webform submission deletion complete.\n";

}

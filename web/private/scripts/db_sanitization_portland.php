<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {

	$sanitize_query = "drop temporary table if exists to_update;
	create temporary table to_update
	select distinct uid, name 
	from users_field_data U
	left outer join user__roles R on U.uid = R.entity_id
	where R.roles_target_id is null
		or 'administrator' not in 
		(select roles_target_id from users_field_data U2
		left outer join user__roles R2 on U2.uid = R2.entity_id
		where U2.name = U.name);
	update users_field_data U
	inner join to_update UP on U.uid = UP.uid
	set U.pass = '', U.mail = concat('user+', UP.uid, '@localhost.localdomain'), U.init = concat('user+', UP.uid, '@localhost.localdomain')
	where UP.name <> 'Oliver Outsider' and UP.name <> 'Marty Member' and UP.name <> 'Ally Admin';
	drop temporary table to_update;";
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

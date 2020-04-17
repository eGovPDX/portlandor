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

	// Delete node revisions older than 30 days
	// node revision delete
	$node_revision_query = "		drop temporary table if exists tmp_old_node_revs;
		create temporary table tmp_old_node_revs
		select vid, date_format(from_unixtime(revision_timestamp), '%e %b %Y') AS 'date', revision_timestamp
		from node_revision
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day))
		order by revision_timestamp;

		delete C
		from node_revision__field_body_content as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_summary as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_topics as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_display_groups as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_service_mode as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_audience as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_related_content as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_popular as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		delete C
		from node_revision__field_page_type as C
		inner join tmp_old_node_revs REV on C.revision_id = REV.vid
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day)) and delta > 0;

		drop temporary table tmp_old_node_revs;

		-- now delete the revisions themselves
		delete from node_revision
		where revision_timestamp < unix_timestamp(date_sub(now(), interval 30 day));";

	// Delete log data
	echo "Deleting node revisions older than 30 days...\n";
	passthru('drush sql-query "' . $node_revision_query . '"');
	echo "Node revision deletion complete.\n";

}

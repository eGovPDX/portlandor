<?php
// Don't ever sanitize the database on the live environment. Doing so would
// destroy the canonical version of the data.
if (defined('PANTHEON_ENVIRONMENT') && (PANTHEON_ENVIRONMENT !== 'live')) {


	// Delete webform submission data
	$webform_query = "truncate table webform_submission; truncate table webform_submission_data; truncate table webform_submission_log;";
	echo "Deleting webform submissions...\n";
	passthru('drush sql-query "' . $webform_query . '"');
	echo "Webform submission deletion complete.\n";

	// Delete node revisions older than 30 days
	$revisions_query = "
		DROP TEMPORARY TABLE IF EXISTS expired_revisions;
		CREATE TEMPORARY TABLE expired_revisions
		SELECT t.vid AS vid
		FROM
			(SELECT r.vid AS vid, r.revision_timestamp AS revision_timestamp
			FROM
			node_field_data n
				INNER JOIN node_revision r ON r.nid = n.nid
			WHERE (changed < (CURRENT_DATE - INTERVAL 30 DAY)) AND ((n.vid <> r.vid))
			GROUP BY n.nid, r.vid, r.revision_timestamp
			ORDER BY revision_timestamp DESC
			LIMIT 9223372036854775807 OFFSET 0) t
		WHERE revision_timestamp < (CURRENT_DATE - INTERVAL 30 DAY);

		DELETE C
		FROM node_revision__field_body_content C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_summary C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_topics C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_display_groups C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_service_mode C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_audience C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_related_content C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_popular C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE C
		FROM node_revision__field_page_type C
		INNER JOIN expired_revisions REV ON C.revision_id = REV.vid;

		DELETE R
		FROM node_revision R
		INNER JOIN expired_revisions REV ON R.vid = REV.vid;

		DROP TEMPORARY TABLE IF EXISTS expired_revisions;";
	echo "Deleting revision data older than 30 days...\n";
	passthru('drush sql-query "' . $revisions_query . '"');
	echo "Revision deletion complete.\n";

}

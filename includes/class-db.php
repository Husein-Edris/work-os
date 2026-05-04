<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_DB {

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_memory (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			kind varchar(50) NOT NULL DEFAULT 'work',
			note text NOT NULL,
			tags varchar(255) DEFAULT '',
			archived tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_jobs (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			source varchar(50) DEFAULT '',
			title varchar(255) DEFAULT '',
			company varchar(255) DEFAULT '',
			url text DEFAULT '',
			status varchar(50) NOT NULL DEFAULT 'new',
			notes text DEFAULT '',
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_proposals (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			company varchar(255) DEFAULT '',
			budget varchar(100) DEFAULT '',
			source varchar(50) DEFAULT 'other',
			status varchar(50) NOT NULL DEFAULT 'draft',
			job_url text DEFAULT '',
			notes text DEFAULT '',
			raw_text longtext DEFAULT '',
			research longtext DEFAULT '',
			draft longtext DEFAULT '',
			analysis longtext DEFAULT '',
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_research_log (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			company varchar(255) DEFAULT '',
			job_description text DEFAULT '',
			research_output longtext DEFAULT '',
			analysis_output longtext DEFAULT '',
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_documents (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			category varchar(50) NOT NULL DEFAULT 'other',
			attachment_id bigint(20) NOT NULL DEFAULT 0,
			description text DEFAULT '',
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_portfolio_log (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			analysis longtext DEFAULT '',
			projects_count int(11) NOT NULL DEFAULT 0,
			skills_count int(11) NOT NULL DEFAULT 0,
			experience_count int(11) NOT NULL DEFAULT 0,
			posts_count int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_ea_vendor_mappings (
			id varchar(20) NOT NULL,
			pattern varchar(255) NOT NULL,
			category varchar(100) NOT NULL,
			priority int(11) NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) $charset;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}work_os_suggestions (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			entry_key varchar(100) NOT NULL DEFAULT '',
			entry_label varchar(255) NOT NULL DEFAULT '',
			field varchar(100) NOT NULL DEFAULT '',
			current_value longtext DEFAULT '',
			candidates longtext DEFAULT '',
			rationale text DEFAULT '',
			PRIMARY KEY (id)
		) $charset;" );

		update_option( 'work_os_db_version', WORK_OS_VERSION );
	}
}

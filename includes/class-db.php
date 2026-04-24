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
			PRIMARY KEY (id)
		) $charset;" );

		update_option( 'work_os_db_version', WORK_OS_VERSION );
	}
}

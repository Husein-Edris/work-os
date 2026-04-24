<?php
/**
 * Plugin Name: Work OS
 * Plugin URI:  https://edrishusein.com
 * Description: Personal career intelligence system — CV, memory, research, blog generation.
 * Version:     0.2.0
 * Author:      Edris Husein
 * Author URI:  https://edrishusein.com
 * License:     GPL-2.0-or-later
 * Text Domain: work-os
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WORK_OS_VERSION', '0.2.0' );
define( 'WORK_OS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WORK_OS_URL', plugin_dir_url( __FILE__ ) );

require_once WORK_OS_PATH . 'includes/class-db.php';

register_activation_hook( __FILE__, array( 'WorkOS_DB', 'create_tables' ) );

add_action( 'admin_init', function () {
	if ( get_option( 'work_os_db_version' ) !== WORK_OS_VERSION ) {
		WorkOS_DB::create_tables();
	}
} );

add_action( 'plugins_loaded', function () {
	require_once WORK_OS_PATH . 'includes/api/class-settings.php';
	require_once WORK_OS_PATH . 'includes/api/class-profile.php';
	require_once WORK_OS_PATH . 'includes/api/class-memory.php';
	require_once WORK_OS_PATH . 'includes/api/class-router.php';
	require_once WORK_OS_PATH . 'includes/class-admin.php';

	( new WorkOS_Admin() )->init();
	( new WorkOS_Router() )->init();
} );

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Admin {

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_menu() {
		add_menu_page(
			'Work OS',
			'Work OS',
			'manage_options',
			'work-os',
			array( $this, 'page_today' ),
			'dashicons-portfolio',
			3
		);

		add_submenu_page( 'work-os', 'Today — Work OS',     'Today',     'manage_options', 'work-os',           array( $this, 'page_today' ) );
		add_submenu_page( 'work-os', 'CV — Work OS',        'CV',        'manage_options', 'work-os-cv',        array( $this, 'page_cv' ) );
		add_submenu_page( 'work-os', 'Research — Work OS',  'Research',  'manage_options', 'work-os-research',  array( $this, 'page_research' ) );
		add_submenu_page( 'work-os', 'Proposals — Work OS', 'Proposals', 'manage_options', 'work-os-proposals', array( $this, 'page_proposals' ) );
		add_submenu_page( 'work-os', 'Memory — Work OS',    'Memory',    'manage_options', 'work-os-memory',    array( $this, 'page_memory' ) );
		add_submenu_page( 'work-os', 'Blog — Work OS',      'Blog',      'manage_options', 'work-os-blog',      array( $this, 'page_blog' ) );
		add_submenu_page( 'work-os', 'Settings — Work OS',  'Settings',  'manage_options', 'work-os-settings',  array( $this, 'page_settings' ) );
	}

	public function enqueue_assets( $hook ) {
		$work_os_hooks = array(
			'toplevel_page_work-os',
			'work-os_page_work-os-cv',
			'work-os_page_work-os-research',
			'work-os_page_work-os-proposals',
			'work-os_page_work-os-memory',
			'work-os_page_work-os-blog',
			'work-os_page_work-os-settings',
		);
		if ( ! in_array( $hook, $work_os_hooks, true ) ) {
			return;
		}

		// Inline print CSS for CV export
		wp_add_inline_style( 'wp-admin', '
			@media print {
				#wpadminbar, #adminmenuwrap, #adminmenuback,
				.wo-no-print, .wo-actions, h1.wp-heading-inline,
				.wo-tabs-nav { display: none !important; }
				#wpcontent { margin-left: 0 !important; }
				.wo-cv-preview { max-width: none !important; padding: 0 !important; box-shadow: none !important; border: none !important; }
			}
		' );

		// Pass nonce + REST URL for inline AJAX
		wp_add_inline_script( 'wp-api', sprintf(
			'window.workOsConfig = %s;',
			wp_json_encode( array(
				'apiUrl' => rest_url( 'work-os/v1' ),
				'nonce'  => wp_create_nonce( 'wp_rest' ),
			) )
		), 'before' );

		wp_enqueue_script( 'wp-api' );
	}

	public function page_today() {
		require_once WORK_OS_PATH . 'admin/page-today.php';
	}

	public function page_cv() {
		require_once WORK_OS_PATH . 'admin/page-cv.php';
	}

	public function page_research() {
		require_once WORK_OS_PATH . 'admin/page-research.php';
	}

	public function page_proposals() {
		require_once WORK_OS_PATH . 'admin/page-proposals.php';
	}

	public function page_memory() {
		require_once WORK_OS_PATH . 'admin/page-memory.php';
	}

	public function page_blog() {
		require_once WORK_OS_PATH . 'admin/page-blog.php';
	}

	public function page_settings() {
		require_once WORK_OS_PATH . 'admin/page-settings.php';
	}
}

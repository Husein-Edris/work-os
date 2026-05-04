<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Admin {

	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'edit_form_top', function( $post ) {
			if ( $post->post_type !== 'project' ) return;
			if ( ! get_post_meta( $post->ID, '_work_os_needs_review', true ) ) return;
			$repo = get_post_meta( $post->ID, '_work_os_generated_from_repo', true );
			$when = get_post_meta( $post->ID, '_work_os_generated_at', true );
			echo '<div class="notice notice-warning inline" style="margin:10px 0 20px"><p><strong>Generated from GitHub repo:</strong> ' . esc_html( $repo ) . ' (on ' . esc_html( $when ) . '). Review before publishing — verify all facts against the actual project.</p></div>';
		} );
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
		add_submenu_page( 'work-os', 'Documents — Work OS', 'Documents', 'manage_options', 'work-os-documents', array( $this, 'page_documents' ) );
		add_submenu_page( 'work-os', 'Research — Work OS',  'Research',  'manage_options', 'work-os-research',  array( $this, 'page_research' ) );
		add_submenu_page( 'work-os', 'Proposals — Work OS', 'Proposals', 'manage_options', 'work-os-proposals', array( $this, 'page_proposals' ) );
		add_submenu_page( 'work-os', 'Memory — Work OS',    'Memory',    'manage_options', 'work-os-memory',    array( $this, 'page_memory' ) );
		add_submenu_page( 'work-os', 'Blog — Work OS',      'Blog',      'manage_options', 'work-os-blog',      array( $this, 'page_blog' ) );
		add_submenu_page( 'work-os', 'Settings — Work OS',  'Settings',  'manage_options', 'work-os-settings',  array( $this, 'page_settings' ) );
		add_submenu_page( 'work-os', 'Portfolio — Work OS',   'Portfolio',   'manage_options', 'work-os-portfolio', array( $this, 'page_portfolio' ) );
		add_submenu_page( 'work-os', 'GitHub Sync — Work OS', 'GitHub Sync', 'manage_options', 'work-os-github',    array( $this, 'page_github' ) );
		add_submenu_page( 'work-os', 'E/A Bericht — Work OS', 'E/A Bericht', 'manage_options', 'work-os-ea',      array( $this, 'page_ea' ) );
	}

	public function enqueue_assets( $hook ) {
		$work_os_hooks = array(
			'toplevel_page_work-os',
			'work-os_page_work-os-cv',
			'work-os_page_work-os-documents',
			'work-os_page_work-os-research',
			'work-os_page_work-os-proposals',
			'work-os_page_work-os-memory',
			'work-os_page_work-os-blog',
			'work-os_page_work-os-settings',
			'work-os_page_work-os-portfolio',
			'work-os_page_work-os-github',
			'work-os_page_work-os-ea',
		);

		// Enqueue WP media uploader for Documents page
		if ( $hook === 'work-os_page_work-os-documents' ) {
			wp_enqueue_media();
		}
		if ( ! in_array( $hook, $work_os_hooks, true ) ) {
			return;
		}

		// Shared Work OS admin styles
		wp_add_inline_style( 'wp-admin', '
			/* ── Postbox heading padding ── */
			#wpcontent .postbox-header h2.hndle,
			#wpcontent .postbox-header .hndle { padding-left: 14px; }

			/* ── Shared output areas ── */
			.wo-output {
				font-size: 13px;
				line-height: 1.75;
				color: #1d2327;
				background: #f8f9fa;
				border: 1px solid #dcdcde;
				border-radius: 4px;
				padding: 16px 20px;
				overflow-y: auto;
			}
			.wo-output h2 { font-size: 15px; margin: 16px 0 6px; font-weight: 700; }
			.wo-output h3 { font-size: 14px; margin: 14px 0 4px; font-weight: 700; }
			.wo-output h4 { font-size: 13px; margin: 12px 0 4px; font-weight: 700; }
			.wo-output p  { margin: 4px 0; }
			.wo-output ul { margin: 4px 0 4px 20px; padding: 0; }
			.wo-output li { margin-bottom: 2px; }

			/* ── Status badge ── */
			.wo-badge {
				display: inline-block;
				padding: 2px 9px;
				border-radius: 10px;
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				letter-spacing: 0.04em;
			}

			/* ── Hover rows ── */
			#wo-proposals-table tbody tr { transition: background 0.12s; }
			#wo-proposals-table tbody tr:hover td { background: #f6f7f7; }
			.wo-log-item { transition: background 0.12s; cursor: pointer; }
			.wo-log-item:hover { background: #f6f7f7 !important; }

			/* ── Button micro-interactions ── */
			.button, .button-primary { transition: opacity 0.15s, transform 0.1s; }
			.button:active, .button-primary:active { transform: scale(0.985); }

			/* ── Pulse on draft btn ── */
			#wo-draft-btn.wo-pulse {
				box-shadow: 0 0 0 3px #2271b1, 0 0 0 6px #c7dff7;
				transition: box-shadow 0.2s ease;
			}

			/* ── Step label ── */
			.wo-step-number {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 20px;
				height: 20px;
				border-radius: 50%;
				background: #2271b1;
				color: #fff;
				font-size: 11px;
				font-weight: 700;
				margin-right: 7px;
				flex-shrink: 0;
			}
			.postbox-header .hndle.wo-step-heading {
				display: flex;
				align-items: center;
			}

			/* ── Reduced motion ── */
			@media (prefers-reduced-motion: reduce) {
				.button, .button-primary { transition: none; }
				.button:active, .button-primary:active { transform: none; }
				.wo-log-item { transition: none; }
			}

			/* ── Print CSS for CV ── */
			@media print {
				#wpadminbar, #adminmenuwrap, #adminmenuback,
				.wo-no-print, .wo-actions, h1.wp-heading-inline,
				.wo-tabs-nav { display: none !important; }
				#wpcontent { margin-left: 0 !important; }
				.wo-cv-preview { max-width: none !important; padding: 0 !important; box-shadow: none !important; border: none !important; }
			}
		' );

		// Output config before any page scripts render
		$config = wp_json_encode( array(
			'apiUrl' => rest_url( 'work-os/v1' ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
		) );
		add_action( 'admin_print_scripts', static function() use ( $config ) {
			echo '<script>window.workOsConfig = ' . $config . ';</script>' . "\n";
		} );
	}

	public function page_today() {
		require_once WORK_OS_PATH . 'admin/page-today.php';
	}

	public function page_cv() {
		require_once WORK_OS_PATH . 'admin/page-cv.php';
	}

	public function page_documents() {
		require_once WORK_OS_PATH . 'admin/page-documents.php';
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

	public function page_portfolio() {
		require_once WORK_OS_PATH . 'admin/page-portfolio.php';
	}

	public function page_github() {
		require_once WORK_OS_PATH . 'admin/page-github.php';
	}

	public function page_ea() {
		require_once WORK_OS_PATH . 'admin/page-ea.php';
	}
}

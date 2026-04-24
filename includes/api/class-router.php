<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Router {

	public function init() {
		require_once WORK_OS_PATH . 'includes/api/class-settings.php';
		require_once WORK_OS_PATH . 'includes/api/class-profile.php';
		require_once WORK_OS_PATH . 'includes/api/class-memory.php';
		require_once WORK_OS_PATH . 'includes/api/class-research.php';
		require_once WORK_OS_PATH . 'includes/api/class-proposals.php';
		require_once WORK_OS_PATH . 'includes/api/class-blog.php';
		require_once WORK_OS_PATH . 'includes/api/class-documents.php';

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$auth = fn() => current_user_can( 'manage_options' );

		// Settings
		register_rest_route( 'work-os/v1', '/settings', array(
			array( 'methods' => 'GET',  'callback' => array( 'WorkOS_Settings', 'get' ),  'permission_callback' => $auth ),
			array( 'methods' => 'POST', 'callback' => array( 'WorkOS_Settings', 'save' ), 'permission_callback' => $auth ),
		) );

		// Profile (reads from ACF)
		register_rest_route( 'work-os/v1', '/profile', array(
			'methods'             => 'GET',
			'callback'            => array( 'WorkOS_Profile', 'get' ),
			'permission_callback' => $auth,
		) );

		// Memory
		register_rest_route( 'work-os/v1', '/memory', array(
			array( 'methods' => 'GET',  'callback' => array( 'WorkOS_Memory', 'list_events' ),  'permission_callback' => $auth ),
			array( 'methods' => 'POST', 'callback' => array( 'WorkOS_Memory', 'create_event' ), 'permission_callback' => $auth ),
		) );

		register_rest_route( 'work-os/v1', '/memory/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( 'WorkOS_Memory', 'delete_event' ),
			'permission_callback' => $auth,
			'args'                => array(
				'id' => array( 'validate_callback' => fn( $v ) => is_numeric( $v ) ),
			),
		) );

		// Research log
		register_rest_route( 'work-os/v1', '/research/log', array(
			'methods'             => 'GET',
			'callback'            => array( 'WorkOS_Research', 'list_logs' ),
			'permission_callback' => $auth,
		) );

		// Research + fit analysis
		register_rest_route( 'work-os/v1', '/research', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Research', 'research' ),
			'permission_callback' => $auth,
		) );

		register_rest_route( 'work-os/v1', '/analyse', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Research', 'analyse' ),
			'permission_callback' => $auth,
		) );

		// Proposals
		register_rest_route( 'work-os/v1', '/proposals', array(
			array( 'methods' => 'GET',  'callback' => array( 'WorkOS_Proposals', 'list_proposals' ),  'permission_callback' => $auth ),
			array( 'methods' => 'POST', 'callback' => array( 'WorkOS_Proposals', 'create_proposal' ), 'permission_callback' => $auth ),
		) );

		register_rest_route( 'work-os/v1', '/proposals/extract', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Proposals', 'extract_proposal' ),
			'permission_callback' => $auth,
		) );

		register_rest_route( 'work-os/v1', '/proposals/draft', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Proposals', 'draft_proposal' ),
			'permission_callback' => $auth,
		) );

		register_rest_route( 'work-os/v1', '/proposals/(?P<id>\d+)', array(
			array( 'methods' => 'POST',   'callback' => array( 'WorkOS_Proposals', 'update_proposal' ), 'permission_callback' => $auth ),
			array( 'methods' => 'DELETE', 'callback' => array( 'WorkOS_Proposals', 'delete_proposal' ), 'permission_callback' => $auth ),
		) );

		// Documents
		register_rest_route( 'work-os/v1', '/documents', array(
			array( 'methods' => 'GET',  'callback' => array( 'WorkOS_Documents', 'list_docs' ), 'permission_callback' => $auth ),
			array( 'methods' => 'POST', 'callback' => array( 'WorkOS_Documents', 'add_doc' ),   'permission_callback' => $auth ),
		) );

		register_rest_route( 'work-os/v1', '/documents/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( 'WorkOS_Documents', 'delete_doc' ),
			'permission_callback' => $auth,
			'args'                => array(
				'id' => array( 'validate_callback' => fn( $v ) => is_numeric( $v ) ),
			),
		) );

		// Blog
		register_rest_route( 'work-os/v1', '/blog/generate', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Blog', 'generate' ),
			'permission_callback' => $auth,
		) );

		register_rest_route( 'work-os/v1', '/blog/publish', array(
			'methods'             => 'POST',
			'callback'            => array( 'WorkOS_Blog', 'publish' ),
			'permission_callback' => $auth,
		) );
	}
}

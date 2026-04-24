<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Memory {

	private static $allowed_kinds = array( 'work', 'learning', 'milestone', 'personal', 'client' );

	public static function list_events() {
		global $wpdb;
		$table = $wpdb->prefix . 'work_os_memory';
		$rows  = $wpdb->get_results(
			"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100",
			ARRAY_A
		);
		return rest_ensure_response( $rows ?: array() );
	}

	public static function create_event( WP_REST_Request $request ) {
		global $wpdb;

		$body = $request->get_json_params();
		$note = sanitize_textarea_field( $body['note'] ?? '' );
		$kind = sanitize_text_field( $body['kind'] ?? 'work' );
		$tags = sanitize_text_field( $body['tags'] ?? '' );

		if ( empty( $note ) ) {
			return new WP_Error( 'empty_note', 'Note cannot be empty', array( 'status' => 400 ) );
		}

		if ( ! in_array( $kind, self::$allowed_kinds, true ) ) {
			$kind = 'work';
		}

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'work_os_memory',
			array(
				'note' => $note,
				'kind' => $kind,
				'tags' => $tags,
			),
			array( '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'db_error', 'Could not save event', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'id'         => $wpdb->insert_id,
			'note'       => $note,
			'kind'       => $kind,
			'tags'       => $tags,
			'created_at' => current_time( 'mysql' ),
		) );
	}

	public static function delete_event( WP_REST_Request $request ) {
		global $wpdb;

		$id    = (int) $request->get_param( 'id' );
		$table = $wpdb->prefix . 'work_os_memory';

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id = %d", $id ) );
		if ( ! $exists ) {
			return new WP_Error( 'not_found', 'Event not found', array( 'status' => 404 ) );
		}

		$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		return rest_ensure_response( array( 'ok' => true ) );
	}
}

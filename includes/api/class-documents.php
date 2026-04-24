<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Documents {

	public static function list_docs() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}work_os_documents ORDER BY category ASC, created_at DESC",
			ARRAY_A
		) ?: array();

		foreach ( $rows as &$row ) {
			$att_id = (int) $row['attachment_id'];
			$row['url']       = $att_id ? wp_get_attachment_url( $att_id ) : '';
			$row['mime_type'] = $att_id ? get_post_mime_type( $att_id ) : '';
			$row['filesize']  = $att_id ? self::human_filesize( $att_id ) : '';
			$row['thumb']     = '';
			if ( $att_id && strpos( $row['mime_type'], 'image/' ) === 0 ) {
				$thumb = wp_get_attachment_image_url( $att_id, 'thumbnail' );
				$row['thumb'] = $thumb ?: '';
			}
		}

		return rest_ensure_response( $rows );
	}

	public static function add_doc( WP_REST_Request $request ) {
		$title         = sanitize_text_field( $request->get_param( 'title' ) );
		$category      = sanitize_text_field( $request->get_param( 'category' ) ?? 'other' );
		$attachment_id = absint( $request->get_param( 'attachment_id' ) );
		$description   = sanitize_textarea_field( $request->get_param( 'description' ) ?? '' );

		if ( ! $title || ! $attachment_id ) {
			return new WP_Error( 'missing_param', 'title and attachment_id are required.', array( 'status' => 400 ) );
		}

		$allowed = array( 'cv', 'certificate', 'legal', 'other' );
		if ( ! in_array( $category, $allowed, true ) ) {
			$category = 'other';
		}

		// Verify attachment belongs to this site
		if ( ! get_post( $attachment_id ) ) {
			return new WP_Error( 'invalid_attachment', 'Attachment not found.', array( 'status' => 400 ) );
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'work_os_documents',
			array(
				'title'         => $title,
				'category'      => $category,
				'attachment_id' => $attachment_id,
				'description'   => $description,
			),
			array( '%s', '%s', '%d', '%s' )
		);

		$id  = $wpdb->insert_id;
		$url = wp_get_attachment_url( $attachment_id );

		return rest_ensure_response( array(
			'id'            => $id,
			'title'         => $title,
			'category'      => $category,
			'attachment_id' => $attachment_id,
			'description'   => $description,
			'url'           => $url,
			'mime_type'     => get_post_mime_type( $attachment_id ),
			'filesize'      => self::human_filesize( $attachment_id ),
			'created_at'    => current_time( 'mysql' ),
		) );
	}

	public static function delete_doc( WP_REST_Request $request ) {
		$id = absint( $request->get_param( 'id' ) );
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'work_os_documents', array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	private static function human_filesize( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) return '';
		$bytes = filesize( $path );
		if ( $bytes >= 1048576 ) return round( $bytes / 1048576, 1 ) . ' MB';
		if ( $bytes >= 1024 )    return round( $bytes / 1024 ) . ' KB';
		return $bytes . ' B';
	}
}

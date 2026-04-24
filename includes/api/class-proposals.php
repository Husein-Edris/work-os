<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Proposals {

	private static $allowed_statuses = array( 'draft', 'sent', 'won', 'lost', 'declined' );
	private static $allowed_sources  = array( 'upwork', 'direct', 'linkedin', 'referral', 'other' );

	public static function list_proposals() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}work_os_proposals ORDER BY created_at DESC LIMIT 200",
			ARRAY_A
		) ?: array();

		return rest_ensure_response( $rows );
	}

	public static function create_proposal( WP_REST_Request $request ) {
		global $wpdb;

		$title = sanitize_text_field( $request->get_param( 'title' ) );
		if ( ! $title ) {
			return new WP_Error( 'missing_param', 'title is required.', array( 'status' => 400 ) );
		}

		$source = sanitize_text_field( $request->get_param( 'source' ) ?? 'other' );
		$status = sanitize_text_field( $request->get_param( 'status' ) ?? 'draft' );

		if ( ! in_array( $source, self::$allowed_sources, true ) ) $source = 'other';
		if ( ! in_array( $status, self::$allowed_statuses, true ) ) $status = 'draft';

		$wpdb->insert(
			$wpdb->prefix . 'work_os_proposals',
			array(
				'title'   => $title,
				'company' => sanitize_text_field( $request->get_param( 'company' ) ?? '' ),
				'budget'  => sanitize_text_field( $request->get_param( 'budget' ) ?? '' ),
				'source'  => $source,
				'status'  => $status,
				'job_url' => esc_url_raw( $request->get_param( 'job_url' ) ?? '' ),
				'notes'   => sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$id  = $wpdb->insert_id;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}work_os_proposals WHERE id = %d", $id
		), ARRAY_A );

		return rest_ensure_response( $row );
	}

	public static function update_proposal( WP_REST_Request $request ) {
		global $wpdb;

		$id       = (int) $request->get_param( 'id' );
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}work_os_proposals WHERE id = %d", $id
		) );

		if ( ! $existing ) {
			return new WP_Error( 'not_found', 'Proposal not found.', array( 'status' => 404 ) );
		}

		$data   = array();
		$format = array();

		$status = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		if ( $status && in_array( $status, self::$allowed_statuses, true ) ) {
			$data['status'] = $status;
			$format[]       = '%s';
		}

		$notes = $request->get_param( 'notes' );
		if ( $notes !== null ) {
			$data['notes'] = sanitize_textarea_field( $notes );
			$format[]      = '%s';
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'no_data', 'Nothing to update.', array( 'status' => 400 ) );
		}

		$wpdb->update(
			$wpdb->prefix . 'work_os_proposals',
			$data,
			array( 'id' => $id ),
			$format,
			array( '%d' )
		);

		return rest_ensure_response( array( 'updated' => true ) );
	}

	public static function delete_proposal( WP_REST_Request $request ) {
		global $wpdb;

		$id       = (int) $request->get_param( 'id' );
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}work_os_proposals WHERE id = %d", $id
		) );

		if ( ! $existing ) {
			return new WP_Error( 'not_found', 'Proposal not found.', array( 'status' => 404 ) );
		}

		$wpdb->delete( $wpdb->prefix . 'work_os_proposals', array( 'id' => $id ), array( '%d' ) );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	public static function draft_proposal( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$title   = sanitize_text_field( $request->get_param( 'title' ) ?? '' );
		$company = sanitize_text_field( $request->get_param( 'company' ) ?? '' );
		$budget  = sanitize_text_field( $request->get_param( 'budget' ) ?? '' );
		$notes   = sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' );

		$profile_response = WorkOS_Profile::get();
		$profile          = $profile_response instanceof WP_REST_Response ? $profile_response->get_data() : array(
			'name' => 'Edris Husein', 'headline' => 'WordPress Developer', 'skills' => array(), 'experience' => array(), 'projects' => array(),
		);

		$voice_rate    = get_option( 'work_os_voice_rate', '€38/hr' );
		$voice_niche   = get_option( 'work_os_voice_niche', 'WordPress / WooCommerce' );
		$voice_tagline = get_option( 'work_os_voice_tagline', '' );

		$profile_ctx  = "Name: {$profile['name']}\nNiche: {$voice_niche}\nRate: {$voice_rate}\n";
		if ( $voice_tagline ) $profile_ctx .= "Tagline: {$voice_tagline}\n";
		$profile_ctx .= "Skills: " . implode( ', ', array_slice( $profile['skills'], 0, 15 ) ) . "\n";

		if ( ! empty( $profile['experience'] ) ) {
			$profile_ctx .= "\nExperience:\n";
			foreach ( array_slice( $profile['experience'], 0, 3 ) as $e ) {
				$profile_ctx .= "- {$e['company']}: {$e['role']} ({$e['period']})\n";
				if ( $e['description'] ) $profile_ctx .= "  {$e['description']}\n";
			}
		}

		if ( ! empty( $profile['projects'] ) ) {
			$profile_ctx .= "\nKey projects:\n";
			foreach ( array_slice( $profile['projects'], 0, 2 ) as $p ) {
				$profile_ctx .= "- {$p['title']}: {$p['challenge']}\n";
			}
		}

		$prompt  = "Write a concise freelance proposal for the following opportunity.\n\n";
		$prompt .= "Title: {$title}\n";
		if ( $company ) $prompt .= "Company/Client: {$company}\n";
		if ( $budget )  $prompt .= "Budget: {$budget}\n";
		if ( $notes )   $prompt .= "Context: {$notes}\n";
		$prompt .= "\nCandidate:\n{$profile_ctx}\n\n";
		$prompt .= "Rules:\n- No AI filler (no 'I am thrilled', 'hope this finds you well')\n- Direct and specific\n- Reference at least one concrete past project\n- Under 200 words\n- If it looks like a German-speaking client, write in formal German (Sie)\n- End with a clear next step";

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 45,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $claude_key,
					'anthropic-version' => '2023-06-01',
				),
				'body' => wp_json_encode( array(
					'model'      => 'claude-opus-4-7',
					'max_tokens' => 800,
					'messages'   => array(
						array( 'role' => 'user', 'content' => $prompt ),
					),
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['content'][0]['text'] ?? '';

		if ( ! $text ) {
			$error_msg = $body['error']['message'] ?? 'No response from Claude.';
			return new WP_Error( 'claude_error', $error_msg, array( 'status' => 502 ) );
		}

		return rest_ensure_response( array( 'draft' => $text ) );
	}
}

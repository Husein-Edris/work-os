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

	public static function extract_proposal( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$raw_text = $request->get_param( 'raw_text' );
		$raw_text = str_replace( "\0", '', wp_kses( (string) $raw_text, array() ) );
		if ( ! $raw_text ) {
			return new WP_Error( 'missing_param', 'raw_text is required.', array( 'status' => 400 ) );
		}

		$prompt  = "Extract structured fields from this job posting. Return ONLY valid JSON, no markdown, no code fences, no commentary.\n\n";
		$prompt .= "Rules:\n";
		$prompt .= "- Use empty string for any field you cannot extract with confidence. Do not guess.\n";
		$prompt .= "- 'language' must be exactly 'de' or 'en' based on the actual post text, not assumed from the company name.\n";
		$prompt .= "- 'red_flags' should flag concrete concerns: vague scope, unrealistic budget, NDA before brief, no budget at all, demands for free test work, '24/7 availability' requirements, 'rockstar/ninja' language. Empty string if none.\n";
		$prompt .= "- 'notes' must summarise what they actually need and the mandatory tech, in 2-3 sentences. No hype words.\n";
		$prompt .= "- 'mandatory_tech' is a comma-separated list of technologies the post explicitly requires (not just 'would be nice').\n\n";
		$prompt .= "Text to extract from:\n" . $raw_text . "\n\n";
		$prompt .= "Return exactly this JSON structure:\n";
		$prompt .= "{\"title\":\"\",\"company\":\"\",\"contact_person\":\"\",\"budget\":\"\",\"source\":\"upwork|linkedin|direct|other\",\"job_url\":\"\",\"language\":\"de|en\",\"location\":\"\",\"remote\":\"yes|no|hybrid|unknown\",\"mandatory_tech\":\"\",\"notes\":\"\",\"red_flags\":\"\"}";

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $claude_key,
					'anthropic-version' => '2023-06-01',
				),
				'body' => wp_json_encode( array(
					'model'      => WorkOS_Settings::get_claude_model(),
					'max_tokens' => 600,
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
			return new WP_Error( 'claude_error', $body['error']['message'] ?? 'No response from Claude.', array( 'status' => 502 ) );
		}

		// Strip code fences if Claude wrapped the JSON
		$text = preg_replace( '/^```(?:json)?\s*/m', '', $text );
		$text = preg_replace( '/\s*```\s*$/m', '', $text );

		$extracted = json_decode( trim( $text ), true );
		if ( ! $extracted ) {
			if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
				$extracted = json_decode( $matches[0], true );
			}
		}

		if ( ! $extracted ) {
			return new WP_Error( 'parse_error', 'Could not parse extraction. Try simplifying the pasted text.', array( 'status' => 502 ) );
		}

		return rest_ensure_response( $extracted );
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
				'title'    => $title,
				'company'  => sanitize_text_field( $request->get_param( 'company' ) ?? '' ),
				'budget'   => sanitize_text_field( $request->get_param( 'budget' ) ?? '' ),
				'source'   => $source,
				'status'   => $status,
				'job_url'  => esc_url_raw( $request->get_param( 'job_url' ) ?? '' ),
				'notes'    => sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' ),
				'raw_text' => sanitize_textarea_field( $request->get_param( 'raw_text' ) ?? '' ),
				'research' => sanitize_textarea_field( $request->get_param( 'research' ) ?? '' ),
				'draft'    => sanitize_textarea_field( $request->get_param( 'draft' ) ?? '' ),
				'analysis' => sanitize_textarea_field( $request->get_param( 'analysis' ) ?? '' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
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

		$title            = sanitize_text_field( $request->get_param( 'title' ) ?? '' );
		$company          = sanitize_text_field( $request->get_param( 'company' ) ?? '' );
		$budget           = sanitize_text_field( $request->get_param( 'budget' ) ?? '' );
		$notes            = sanitize_textarea_field( $request->get_param( 'notes' ) ?? '' );
		$research_context = sanitize_textarea_field( $request->get_param( 'research_context' ) ?? '' );
		$fit_analysis     = sanitize_textarea_field( $request->get_param( 'fit_analysis' ) ?? '' );

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

		$draft_rules = WorkOS_Settings::get_draft_prompt_rules();

		$prompt  = "You are drafting a freelance proposal for me. Strict rules apply.\n\n";
		$prompt .= $draft_rules . "\n\n";
		$prompt .= "JOB DETAILS:\n";
		$prompt .= "Title: {$title}\n";
		if ( $company )          $prompt .= "Client: {$company}\n";
		if ( $budget )           $prompt .= "Budget: {$budget}\n";
		if ( $notes )            $prompt .= "Context: {$notes}\n";
		if ( $research_context ) $prompt .= "\nCompany research:\n{$research_context}\n";
		if ( $fit_analysis )     $prompt .= "\nFit analysis:\n{$fit_analysis}\n";
		$prompt .= "\nCANDIDATE PROFILE (only use facts listed here):\n{$profile_ctx}";

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
					'model'      => WorkOS_Settings::get_claude_model(),
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

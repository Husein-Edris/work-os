<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Research {

	public static function research( WP_REST_Request $request ) {
		$gemini_key = WorkOS_Settings::get_gemini_key();
		if ( ! $gemini_key ) {
			return new WP_Error( 'no_key', 'Gemini API key not configured. Add it in Settings.', array( 'status' => 400 ) );
		}

		$company  = sanitize_text_field( $request->get_param( 'company' ) );
		$job_desc = sanitize_textarea_field( $request->get_param( 'job_description' ) ?? '' );

		if ( ! $company ) {
			return new WP_Error( 'missing_param', 'company is required.', array( 'status' => 400 ) );
		}

		$prompt  = "Research the company or client called \"{$company}\".\n\n";
		if ( $job_desc ) {
			$prompt .= "Job/project description:\n{$job_desc}\n\n";
		}
		$prompt .= "Provide a structured overview:\n1. What they do (products, services, market)\n2. Technology stack signals\n3. Current job openings (if any)\n4. Key people (founders, tech leads)\n5. Recent news or activity\n6. Company size and location\n\nBe specific and factual.";

		$response = wp_remote_post(
			'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . rawurlencode( $gemini_key ),
			array(
				'timeout' => 30,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array(
					'contents' => array(
						array(
							'role'  => 'user',
							'parts' => array( array( 'text' => $prompt ) ),
						),
					),
					'tools' => array( array( 'google_search' => new stdClass() ) ),
				) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', $response->get_error_message(), array( 'status' => 502 ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

		if ( ! $text ) {
			$error_msg = $body['error']['message'] ?? 'No response from Gemini.';
			return new WP_Error( 'gemini_error', $error_msg, array( 'status' => 502 ) );
		}

		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'work_os_research_log',
			array(
				'company'         => $company,
				'job_description' => $job_desc,
				'research_output' => $text,
			),
			array( '%s', '%s', '%s' )
		);
		$log_id = $wpdb->insert_id;

		return rest_ensure_response( array( 'output' => $text, 'log_id' => $log_id ) );
	}

	public static function analyse( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured. Add it in Settings.', array( 'status' => 400 ) );
		}

		$company  = sanitize_text_field( $request->get_param( 'company' ) );
		$research = sanitize_textarea_field( $request->get_param( 'research' ) );

		if ( ! $research ) {
			return new WP_Error( 'missing_param', 'research output is required.', array( 'status' => 400 ) );
		}

		$profile      = self::get_profile_context();
		$voice_rate   = get_option( 'work_os_voice_rate', '€38/hr' );
		$voice_niche  = get_option( 'work_os_voice_niche', 'WordPress / WooCommerce' );

		$profile_ctx  = "Name: {$profile['name']}\n";
		$profile_ctx .= "Role: {$profile['headline']}\n";
		$profile_ctx .= "Niche: {$voice_niche}\n";
		$profile_ctx .= "Rate: {$voice_rate}\n";
		$profile_ctx .= "Skills: " . implode( ', ', array_slice( $profile['skills'], 0, 15 ) ) . "\n";

		if ( ! empty( $profile['experience'] ) ) {
			$profile_ctx .= "Experience:\n";
			foreach ( array_slice( $profile['experience'], 0, 3 ) as $e ) {
				$profile_ctx .= "  - {$e['company']} ({$e['role']}, {$e['period']})\n";
			}
		}

		$prompt  = "You are reviewing a potential client or employer. Research:\n\n{$research}\n\n";
		$prompt .= "Candidate profile:\n{$profile_ctx}\n\n";
		$prompt .= "Provide:\n1. Fit score (1-10) with brief reasoning\n2. Skill match (what aligns, what's missing)\n3. Top 5 likely interview / discovery questions they will ask\n4. Recommended opener for the proposal or intro\n5. Red flags (if any)\n\nBe direct. No filler.";

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
					'model'      => 'claude-sonnet-4-6',
					'max_tokens' => 1500,
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

		$log_id = absint( $request->get_param( 'log_id' ) ?? 0 );
		if ( $log_id ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'work_os_research_log',
				array( 'analysis_output' => $text ),
				array( 'id' => $log_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return rest_ensure_response( array( 'output' => $text ) );
	}

	public static function list_logs() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT id, created_at, company, LEFT(research_output,200) as preview, analysis_output != '' as has_analysis
			 FROM {$wpdb->prefix}work_os_research_log
			 ORDER BY created_at DESC LIMIT 20",
			ARRAY_A
		) ?: array();
		return rest_ensure_response( $rows );
	}

	private static function get_profile_context() {
		$response = WorkOS_Profile::get();
		if ( $response instanceof WP_REST_Response ) {
			return $response->get_data();
		}
		return array(
			'name'       => get_bloginfo( 'name' ),
			'headline'   => 'WordPress Developer',
			'skills'     => array(),
			'experience' => array(),
			'projects'   => array(),
			'summary'    => '',
		);
	}
}

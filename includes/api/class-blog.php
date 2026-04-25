<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Blog {

	public static function generate( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$topic     = sanitize_text_field( $request->get_param( 'topic' ) );
		$format    = sanitize_text_field( $request->get_param( 'format' ) ?? 'opinion' );
		$memory_id = absint( $request->get_param( 'memory_id' ) ?? 0 );
		$extra     = sanitize_textarea_field( $request->get_param( 'extra' ) ?? '' );

		if ( ! $topic ) {
			return new WP_Error( 'missing_param', 'topic is required.', array( 'status' => 400 ) );
		}

		$format_labels = array(
			'tutorial'     => 'a step-by-step tutorial',
			'case_study'   => 'a case study',
			'opinion'      => 'an opinion piece',
			'lessons'      => 'a lessons-learned article',
			'announcement' => 'an announcement or update post',
		);
		$format_label = $format_labels[ $format ] ?? 'a blog post';

		$profile_response = WorkOS_Profile::get();
		$profile          = $profile_response instanceof WP_REST_Response ? $profile_response->get_data() : array(
			'name' => 'Edris Husein', 'headline' => 'WordPress Developer', 'skills' => array(), 'projects' => array(), 'summary' => '',
		);

		$voice_niche   = get_option( 'work_os_voice_niche', 'WordPress / WooCommerce' );
		$voice_tagline = get_option( 'work_os_voice_tagline', '' );

		$profile_ctx  = "Author: {$profile['name']}\nNiche: {$voice_niche}\n";
		if ( $voice_tagline ) $profile_ctx .= "Tagline: {$voice_tagline}\n";
		if ( $profile['summary'] ) $profile_ctx .= "Summary: {$profile['summary']}\n";
		$profile_ctx .= "Skills: " . implode( ', ', array_slice( $profile['skills'], 0, 12 ) ) . "\n";

		$memory_ctx = '';
		if ( $memory_id ) {
			global $wpdb;
			$ev = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}work_os_memory WHERE id = %d", $memory_id
			), ARRAY_A );
			if ( $ev ) {
				$memory_ctx = "\nMemory event to ground the post:\n[{$ev['kind']}] {$ev['note']}";
				if ( $ev['tags'] ) $memory_ctx .= "\nTags: {$ev['tags']}";
			}
		}

		$projects_ctx = '';
		if ( ! empty( $profile['projects'] ) ) {
			$projects_ctx = "\nRecent projects for reference:\n";
			foreach ( array_slice( $profile['projects'], 0, 2 ) as $p ) {
				$projects_ctx .= "- {$p['title']}: {$p['challenge']}\n";
				if ( $p['solution'] ) $projects_ctx .= "  Solution: {$p['solution']}\n";
			}
		}

		$blog_rules = WorkOS_Settings::get_blog_prompt_rules();

		$prompt  = "Write {$format_label} for a personal professional blog.\n\n";
		$prompt .= $blog_rules . "\n\n";
		$prompt .= "Topic: {$topic}\n";
		if ( $extra ) $prompt .= "Additional context: {$extra}\n";
		$prompt .= "\nAuthor profile:\n{$profile_ctx}";
		$prompt .= $memory_ctx;
		$prompt .= $projects_ctx;

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $claude_key,
					'anthropic-version' => '2023-06-01',
				),
				'body' => wp_json_encode( array(
					'model'      => 'claude-sonnet-4-6',
					'max_tokens' => 2000,
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

		$title   = $topic;
		$content = $text;
		if ( preg_match( '/^Title:\s*(.+)$/m', $text, $matches ) ) {
			$title   = trim( $matches[1] );
			$content = trim( preg_replace( '/^Title:\s*.+\n?/m', '', $text, 1 ) );
		}

		return rest_ensure_response( array( 'title' => $title, 'content' => $content ) );
	}

	public static function publish( WP_REST_Request $request ) {
		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$content = wp_kses_post( $request->get_param( 'content' ) );

		if ( ! $title || ! $content ) {
			return new WP_Error( 'missing_param', 'title and content are required.', array( 'status' => 400 ) );
		}

		$post_id = wp_insert_post( array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'draft',
			'post_author'  => get_current_user_id(),
			'post_type'    => 'post',
		) );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		return rest_ensure_response( array(
			'post_id'  => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
		) );
	}
}

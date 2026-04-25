<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_GitHub_Sync {

	/**
	 * List repos with a comparison status against the portfolio.
	 * Status values: in_portfolio, missing, blocked, skipped (no README of substance / fork)
	 */
	public static function list_repos() {
		$github_url = get_option( 'work_os_cv_github', '' );
		if ( ! $github_url ) {
			return new WP_Error( 'no_github_url', 'GitHub URL not set in Settings.', array( 'status' => 400 ) );
		}

		$username = trim( parse_url( $github_url, PHP_URL_PATH ), '/' );
		if ( ! $username ) {
			return new WP_Error( 'invalid_github_url', 'Could not parse GitHub URL.', array( 'status' => 400 ) );
		}

		$repos = self::fetch_user_repos( $username );
		if ( is_wp_error( $repos ) ) {
			return $repos;
		}

		$portfolio_slugs = self::get_portfolio_slugs();
		$blocklist       = (array) get_option( 'work_os_repo_blocklist', array() );

		$out = array();
		foreach ( $repos as $repo ) {
			if ( $repo['fork'] ) {
				continue; // Skip forks entirely, never show them
			}

			$repo_key = strtolower( $repo['name'] );

			$status      = 'missing';
			$status_note = '';

			if ( in_array( $repo_key, $blocklist, true ) ) {
				$status      = 'blocked';
				$status_note = 'Skipped permanently';
			} elseif ( self::repo_in_portfolio( $repo['name'], $portfolio_slugs ) ) {
				$status      = 'in_portfolio';
				$status_note = 'Already in portfolio';
			} else {
				// Check README substance (only for missing repos — saves API calls)
				$readme = self::fetch_readme( $username, $repo['name'] );
				if ( $readme === null || self::readme_substance_len( $readme ) < 300 ) {
					$status      = 'thin_readme';
					$status_note = 'README too thin (under 300 chars)';
				}
			}

			$out[] = array(
				'name'         => $repo['name'],
				'description'  => $repo['description'] ?? '',
				'language'     => $repo['language'] ?? '',
				'html_url'     => $repo['html_url'] ?? '',
				'pushed_at'    => $repo['pushed_at'] ?? '',
				'stars'        => $repo['stargazers_count'] ?? 0,
				'topics'       => $repo['topics'] ?? array(),
				'status'       => $status,
				'status_note'  => $status_note,
			);
		}

		// Sort: missing first (action items), then in_portfolio, then thin/blocked at bottom
		$order = array( 'missing' => 0, 'in_portfolio' => 1, 'thin_readme' => 2, 'blocked' => 3 );
		usort( $out, function( $a, $b ) use ( $order ) {
			$ao = $order[ $a['status'] ] ?? 9;
			$bo = $order[ $b['status'] ] ?? 9;
			if ( $ao !== $bo ) return $ao - $bo;
			return strcmp( $a['name'], $b['name'] );
		} );

		return rest_ensure_response( array(
			'username' => $username,
			'repos'    => $out,
		) );
	}

	/**
	 * Generate a project draft from a single repo.
	 */
	public static function generate_project( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$repo_name = sanitize_text_field( $request->get_param( 'repo_name' ) ?? '' );
		if ( ! $repo_name ) {
			return new WP_Error( 'missing_param', 'repo_name is required.', array( 'status' => 400 ) );
		}

		$github_url = get_option( 'work_os_cv_github', '' );
		$username   = trim( parse_url( $github_url, PHP_URL_PATH ), '/' );
		if ( ! $username ) {
			return new WP_Error( 'no_github', 'GitHub URL not set.', array( 'status' => 400 ) );
		}

		// Fetch full repo data
		$repo_data = self::fetch_single_repo( $username, $repo_name );
		if ( is_wp_error( $repo_data ) ) {
			return $repo_data;
		}

		$readme = self::fetch_readme( $username, $repo_name );
		if ( $readme === null || self::readme_substance_len( $readme ) < 300 ) {
			return new WP_Error( 'thin_readme', 'Repo README is too thin to generate a project from.', array( 'status' => 400 ) );
		}

		// Truncate README for prompt budget
		$readme_excerpt = mb_substr( wp_strip_all_tags( $readme ), 0, 4000 );

		// Build prompt with strict anti-fabrication rules
		$prompt  = "You are generating a portfolio project entry from a GitHub repository's actual data. Follow strict rules.\n\n";
		$prompt .= "ANTI-FABRICATION RULES (NON-NEGOTIABLE):\n";
		$prompt .= "- Use ONLY information present in the repo data and README below. Do not invent clients, metrics, scope, or outcomes.\n";
		$prompt .= "- If the README does not state a metric, do not invent one. No 'reduced load time by X%' unless the README says it.\n";
		$prompt .= "- If the README does not name a client, the project is a personal/open-source build. State that.\n";
		$prompt .= "- Stay descriptive, not promotional. The reader will draw their own conclusions.\n";
		$prompt .= "- If a section can't be filled honestly from the data, use 'Not specified in repository.' rather than padding.\n\n";

		$prompt .= "OUTPUT FORMAT — return ONLY valid JSON, no markdown, no code fences:\n";
		$prompt .= "{\n";
		$prompt .= '  "title": "Concise project title (3-6 words, derived from repo name and purpose)",' . "\n";
		$prompt .= '  "excerpt": "One-sentence summary of what this project is and does (max 160 chars)",' . "\n";
		$prompt .= '  "challenge": "What problem does this project solve? 2-3 sentences from README context only.",' . "\n";
		$prompt .= '  "solution": "How does it solve it? Architecture and approach in 2-4 sentences. Stay technical.",' . "\n";
		$prompt .= '  "tech_stack": ["array", "of", "technologies", "explicitly", "mentioned"],' . "\n";
		$prompt .= '  "key_features": [' . "\n";
		$prompt .= '    {"title": "Feature name", "description": "1-2 sentence description"},' . "\n";
		$prompt .= '    ... 2 to 4 features total, only if README describes distinct features' . "\n";
		$prompt .= '  ]' . "\n";
		$prompt .= "}\n\n";

		$prompt .= "REPO DATA:\n";
		$prompt .= "Name: {$repo_data['name']}\n";
		$prompt .= "Description: " . ( $repo_data['description'] ?: '(none)' ) . "\n";
		$prompt .= "Primary language: " . ( $repo_data['language'] ?: 'unknown' ) . "\n";
		if ( ! empty( $repo_data['topics'] ) ) {
			$prompt .= "Topics: " . implode( ', ', $repo_data['topics'] ) . "\n";
		}
		$prompt .= "URL: {$repo_data['html_url']}\n";
		$prompt .= "\nREADME content:\n" . $readme_excerpt . "\n";

		// Call Claude
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
			return new WP_Error( 'claude_error', $body['error']['message'] ?? 'No response from Claude.', array( 'status' => 502 ) );
		}

		// Strip code fences if Claude wrapped the JSON
		$text = preg_replace( '/^```(?:json)?\s*/m', '', $text );
		$text = preg_replace( '/\s*```\s*$/m', '', $text );

		$generated = json_decode( trim( $text ), true );
		if ( ! $generated ) {
			if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
				$generated = json_decode( $matches[0], true );
			}
		}

		if ( ! $generated || empty( $generated['title'] ) ) {
			return new WP_Error( 'parse_error', 'Could not parse generation output.', array( 'status' => 502 ) );
		}

		// Create the project draft
		$post_id = wp_insert_post( array(
			'post_title'   => sanitize_text_field( $generated['title'] ),
			'post_excerpt' => sanitize_text_field( $generated['excerpt'] ?? '' ),
			'post_status'  => 'draft',
			'post_type'    => 'project',
			'post_author'  => get_current_user_id(),
			'meta_input'   => array(
				'_work_os_generated_from_repo' => $repo_name,
				'_work_os_generated_at'        => current_time( 'mysql' ),
			),
		), true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
		}

		// Populate ACF fields if ACF is active.
		// challenge/solution live inside the 'project_content' group; tech_stack inside
		// 'project_overview'; github inside 'project_links' — must update via parent group.
		if ( function_exists( 'update_field' ) ) {
			// project_content group (challenge + solution)
			update_field( 'project_content', array(
				'challenge' => wp_kses_post( $generated['challenge'] ?? '' ),
				'solution'  => wp_kses_post( $generated['solution']  ?? '' ),
			), $post_id );

			// project_links group (github URL)
			update_field( 'project_links', array(
				'github' => esc_url_raw( $repo_data['html_url'] ),
			), $post_id );

			// project_overview group (tech_stack relationship)
			if ( ! empty( $generated['tech_stack'] ) && is_array( $generated['tech_stack'] ) ) {
				$tech_ids = self::resolve_tech_terms( $generated['tech_stack'] );
				if ( ! empty( $tech_ids ) ) {
					update_field( 'project_overview', array(
						'tech_stack' => $tech_ids,
					), $post_id );
				}
			}

			// key_features is a top-level repeater
			if ( ! empty( $generated['key_features'] ) && is_array( $generated['key_features'] ) ) {
				$features = array();
				foreach ( $generated['key_features'] as $f ) {
					$features[] = array(
						'title'       => sanitize_text_field( $f['title'] ?? '' ),
						'description' => sanitize_textarea_field( $f['description'] ?? '' ),
					);
				}
				update_field( 'key_features', $features, $post_id );
			}
		}

		// Add admin notice marker
		update_post_meta( $post_id, '_work_os_needs_review', 1 );

		return rest_ensure_response( array(
			'post_id'  => $post_id,
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'title'    => $generated['title'],
		) );
	}

	/**
	 * Add a repo to the permanent skip list.
	 */
	public static function skip_repo( WP_REST_Request $request ) {
		$repo_name = sanitize_text_field( $request->get_param( 'repo_name' ) ?? '' );
		if ( ! $repo_name ) {
			return new WP_Error( 'missing_param', 'repo_name is required.', array( 'status' => 400 ) );
		}

		$blocklist = (array) get_option( 'work_os_repo_blocklist', array() );
		$key       = strtolower( $repo_name );
		if ( ! in_array( $key, $blocklist, true ) ) {
			$blocklist[] = $key;
			update_option( 'work_os_repo_blocklist', $blocklist );
		}

		return rest_ensure_response( array( 'ok' => true ) );
	}

	/**
	 * Remove a repo from the skip list.
	 */
	public static function unskip_repo( WP_REST_Request $request ) {
		$repo_name = sanitize_text_field( $request->get_param( 'repo_name' ) ?? '' );
		if ( ! $repo_name ) {
			return new WP_Error( 'missing_param', 'repo_name is required.', array( 'status' => 400 ) );
		}

		$blocklist = (array) get_option( 'work_os_repo_blocklist', array() );
		$key       = strtolower( $repo_name );
		$blocklist = array_values( array_diff( $blocklist, array( $key ) ) );
		update_option( 'work_os_repo_blocklist', $blocklist );

		return rest_ensure_response( array( 'ok' => true ) );
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	private static function github_headers() {
		$headers = array(
			'User-Agent' => 'WorkOS-Plugin/1.0',
			'Accept'     => 'application/vnd.github.v3+json',
		);
		$token = WorkOS_Settings::get_github_token();
		if ( $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}
		return $headers;
	}

	private static function fetch_user_repos( $username ) {
		$url = 'https://api.github.com/users/' . rawurlencode( $username ) . '/repos?sort=pushed&per_page=100&type=owner';
		$res = wp_remote_get( $url, array(
			'timeout' => 15,
			'headers' => self::github_headers(),
		) );

		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'github_error', $res->get_error_message(), array( 'status' => 502 ) );
		}
		$code = wp_remote_retrieve_response_code( $res );
		if ( $code !== 200 ) {
			return new WP_Error( 'github_status', "GitHub API returned {$code}", array( 'status' => 502 ) );
		}

		return json_decode( wp_remote_retrieve_body( $res ), true ) ?: array();
	}

	private static function fetch_single_repo( $username, $repo_name ) {
		$url = 'https://api.github.com/repos/' . rawurlencode( $username ) . '/' . rawurlencode( $repo_name );
		$res = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => self::github_headers(),
		) );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'github_error', $res->get_error_message(), array( 'status' => 502 ) );
		}
		if ( wp_remote_retrieve_response_code( $res ) !== 200 ) {
			return new WP_Error( 'github_404', 'Repo not found.', array( 'status' => 404 ) );
		}
		return json_decode( wp_remote_retrieve_body( $res ), true );
	}

	private static function fetch_readme( $username, $repo_name ) {
		$url = 'https://api.github.com/repos/' . rawurlencode( $username ) . '/' . rawurlencode( $repo_name ) . '/readme';
		$res = wp_remote_get( $url, array(
			'timeout' => 10,
			'headers' => array_merge( self::github_headers(), array( 'Accept' => 'application/vnd.github.raw' ) ),
		) );
		if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) !== 200 ) {
			return null;
		}
		return wp_remote_retrieve_body( $res );
	}

	/**
	 * Strip markdown noise (headings, links, code fences) and count remaining substance.
	 */
	private static function readme_substance_len( $readme ) {
		$cleaned = preg_replace( '/```.*?```/s', '', $readme ); // remove code blocks
		$cleaned = preg_replace( '/[#>*_`\[\]\(\)!]+/', '', $cleaned ); // strip markdown chars
		$cleaned = preg_replace( '/\s+/', ' ', $cleaned );
		return strlen( trim( $cleaned ) );
	}

	private static function get_portfolio_slugs() {
		$posts = get_posts( array(
			'post_type'      => 'project',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'pending' ),
		) );
		$slugs = array();
		foreach ( $posts as $p ) {
			$slugs[] = strtolower( $p->post_name );
			$slugs[] = strtolower( str_replace( ' ', '-', $p->post_title ) );
		}
		return array_unique( $slugs );
	}

	private static function repo_in_portfolio( $repo_name, $portfolio_slugs ) {
		$candidates = array(
			strtolower( $repo_name ),
			strtolower( str_replace( '_', '-', $repo_name ) ),
			strtolower( str_replace( '-', '_', $repo_name ) ),
		);
		foreach ( $candidates as $c ) {
			if ( in_array( $c, $portfolio_slugs, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Map tech names to existing 'tech' CPT post IDs. Names that don't exist are dropped.
	 */
	private static function resolve_tech_terms( array $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$name = trim( $name );
			if ( ! $name ) continue;

			$found = get_posts( array(
				'post_type'      => 'tech',
				'title'          => $name,
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			) );

			if ( ! empty( $found ) ) {
				$ids[] = $found[0]->ID;
			}
		}
		return $ids;
	}
}

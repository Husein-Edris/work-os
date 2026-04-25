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
		$voice_tagline = get_option( 'work_os_voice_tagline', '' );

		// ── Identity ──────────────────────────────────────────────────────────
		$profile_ctx  = "## Candidate Profile\n\n";
		$profile_ctx .= "**Name:** {$profile['name']}\n";
		$profile_ctx .= "**Role:** {$profile['headline']}\n";
		$profile_ctx .= "**Location:** " . ( $profile['location'] ?? 'Dornbirn, Österreich' ) . "\n";
		$profile_ctx .= "**Rate:** {$voice_rate}\n";
		$profile_ctx .= "**Niche:** {$voice_niche}\n";
		if ( $voice_tagline ) {
			$profile_ctx .= "**Positioning:** {$voice_tagline}\n";
		}

		// ── Summary ───────────────────────────────────────────────────────────
		if ( ! empty( $profile['summary'] ) ) {
			$profile_ctx .= "\n**About:** {$profile['summary']}\n";
		}

		// ── All experience (not just 3) ───────────────────────────────────────
		if ( ! empty( $profile['experience'] ) ) {
			$profile_ctx .= "\n**Work Experience:**\n";
			foreach ( $profile['experience'] as $e ) {
				$profile_ctx .= "- **{$e['company']}** — {$e['role']} ({$e['period']})\n";
				if ( ! empty( $e['description'] ) ) {
					$profile_ctx .= "  {$e['description']}\n";
				}
				if ( ! empty( $e['tech'] ) ) {
					$profile_ctx .= "  Tech: " . implode( ', ', $e['tech'] ) . "\n";
				}
			}
		}

		// ── All tech skills ───────────────────────────────────────────────────
		$tech_skills = $profile['tech_skills'] ?? array();
		$soft_skills = $profile['skills'] ?? array();
		if ( ! empty( $tech_skills ) ) {
			$profile_ctx .= "\n**Technical Skills (from portfolio):** " . implode( ', ', $tech_skills ) . "\n";
		}
		if ( ! empty( $soft_skills ) ) {
			$profile_ctx .= "**Skills:** " . implode( ', ', $soft_skills ) . "\n";
		}

		// ── Education ─────────────────────────────────────────────────────────
		if ( ! empty( $profile['education'] ) ) {
			$profile_ctx .= "\n**Education:**\n";
			foreach ( $profile['education'] as $edu ) {
				$profile_ctx .= "- {$edu['degree']} — {$edu['institution']} ({$edu['period']})\n";
			}
		}

		// ── Languages ─────────────────────────────────────────────────────────
		if ( ! empty( $profile['languages'] ) ) {
			$profile_ctx .= "\n**Languages:** ";
			$langs = array();
			foreach ( $profile['languages'] as $l ) {
				$langs[] = "{$l['language']} ({$l['level']})";
			}
			$profile_ctx .= implode( ', ', $langs ) . "\n";
		}

		// ── Projects ──────────────────────────────────────────────────────────
		if ( ! empty( $profile['projects'] ) ) {
			$profile_ctx .= "\n**Portfolio Projects:**\n";
			foreach ( array_slice( $profile['projects'], 0, 5 ) as $proj ) {
				$profile_ctx .= "- **{$proj['title']}**";
				if ( ! empty( $proj['tech'] ) ) {
					$profile_ctx .= " [" . implode( ', ', $proj['tech'] ) . "]";
				}
				if ( ! empty( $proj['excerpt'] ) ) {
					$profile_ctx .= " — {$proj['excerpt']}";
				}
				$profile_ctx .= "\n";
			}
		}

		// ── GitHub (live repo data) ────────────────────────────────────────────
		$github_ctx = self::get_github_context();
		if ( $github_ctx ) {
			$profile_ctx .= "\n**GitHub Activity (live data):**\n{$github_ctx}";
		}

		// ── Memory events (recent relevant context) ───────────────────────────
		$memory_ctx = self::get_memory_context();
		if ( $memory_ctx ) {
			$profile_ctx .= "\n**Recent Activity & Notes:**\n{$memory_ctx}";
		}

		$prompt  = "You are a career advisor doing a fit analysis. Use ALL the candidate data provided — do not ignore GitHub, projects, or skills outside their stated niche.\n\n";
		$prompt .= "**Company / Role Research:**\n{$research}\n\n";
		$prompt .= $profile_ctx . "\n\n";
		$prompt .= "Provide:\n";
		$prompt .= "1. **Fit score (1-10)** with reasoning that accounts for ALL demonstrated skills, not just the stated niche\n";
		$prompt .= "2. **Skill match** — what genuinely aligns (cite specific evidence from their profile/GitHub), what's missing\n";
		$prompt .= "3. **Top 5 interview questions** they will likely ask\n";
		$prompt .= "4. **Recommended opener** for the proposal or cover letter\n";
		$prompt .= "5. **Red flags** (if any)\n";
		$prompt .= "6. **Apply or decline** — one clear recommendation with one sentence of reasoning\n\n";
		$prompt .= "Be direct. No filler. If the candidate has relevant skills beyond their main niche, say so explicitly.";

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
			'tech_skills'=> array(),
			'experience' => array(),
			'projects'   => array(),
			'education'  => array(),
			'languages'  => array(),
			'summary'    => '',
		);
	}

	private static function get_github_context() {
		$github_url = get_option( 'work_os_cv_github', '' );
		if ( ! $github_url ) return '';

		// Extract username from URL
		$username = trim( parse_url( $github_url, PHP_URL_PATH ), '/' );
		if ( ! $username ) return '';

		$api_url  = 'https://api.github.com/users/' . rawurlencode( $username ) . '/repos?sort=pushed&per_page=30&type=owner';
		$response = wp_remote_get( $api_url, array(
			'timeout' => 10,
			'headers' => array(
				'User-Agent' => 'WorkOS-Plugin/1.0',
				'Accept'     => 'application/vnd.github.v3+json',
			),
		) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return '';
		}

		$repos = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $repos ) || empty( $repos ) ) return '';

		// Aggregate languages
		$languages = array();
		$repo_lines = array();

		foreach ( $repos as $repo ) {
			if ( ! empty( $repo['language'] ) ) {
				$lang = $repo['language'];
				$languages[ $lang ] = ( $languages[ $lang ] ?? 0 ) + 1;
			}
			if ( ! $repo['fork'] && ! empty( $repo['description'] ) ) {
				$repo_lines[] = "  - **{$repo['name']}**" .
					( $repo['language'] ? " [{$repo['language']}]" : '' ) .
					" — {$repo['description']}" .
					( $repo['stargazers_count'] > 0 ? " ★{$repo['stargazers_count']}" : '' );
			}
		}

		arsort( $languages );
		$ctx  = "GitHub profile: github.com/{$username}\n";
		$ctx .= "Languages used across " . count( $repos ) . " public repos: " . implode( ', ', array_keys( array_slice( $languages, 0, 10, true ) ) ) . "\n";
		if ( ! empty( $repo_lines ) ) {
			$ctx .= "Notable repos:\n" . implode( "\n", array_slice( $repo_lines, 0, 8 ) ) . "\n";
		}

		return $ctx;
	}

	private static function get_memory_context() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT kind, note FROM {$wpdb->prefix}work_os_memory
			 WHERE kind IN ('work','learning','milestone','client')
			   AND archived = 0
			   AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
			 ORDER BY created_at DESC LIMIT 10",
			ARRAY_A
		);
		if ( empty( $rows ) ) return '';

		$lines = array();
		foreach ( $rows as $r ) {
			$lines[] = "- [{$r['kind']}] " . wp_trim_words( $r['note'], 20 );
		}
		return implode( "\n", $lines ) . "\n";
	}
}

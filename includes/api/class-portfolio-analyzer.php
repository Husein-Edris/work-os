<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Portfolio_Analyzer {

	/**
	 * Run a critique pass over the portfolio. Reads profile, projects, blog posts.
	 * Returns a structured analysis and saves it to the log.
	 */
	public static function analyse( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$profile_response = WorkOS_Profile::get();
		if ( ! $profile_response instanceof WP_REST_Response ) {
			return new WP_Error( 'profile_error', 'Could not load portfolio profile.', array( 'status' => 500 ) );
		}
		$profile = $profile_response->get_data();

		$portfolio_text = self::build_portfolio_snapshot( $profile );

		$prompt  = "You are a brutally honest portfolio reviewer for a freelance developer. Read the portfolio snapshot below and critique what's there. Do not be polite. Do not pad with praise.\n\n";

		$prompt .= "REVIEW PHILOSOPHY:\n";
		$prompt .= "- The developer is NOT only WordPress. Their actual range includes PHP backend work, headless builds, performance, security, integrations, and side projects in React/Node. The portfolio should communicate that range, not narrow it.\n";
		$prompt .= "- Critique what's already there. Do NOT suggest new projects to add or new skills to learn. The job is to evaluate the existing surface.\n";
		$prompt .= "- Find weak descriptions, vague claims, outdated framing, missing tech in project metadata, inconsistent voice, junior-sounding language, repetition, and content that doesn't earn its place.\n";
		$prompt .= "- A senior developer reviewing this for ~5 seconds — what would they conclude? Where does it work, where does it leak credibility?\n";
		$prompt .= "- Cite specific entries by name. 'Project X says Y but should be Z' beats 'descriptions could be sharper'.\n";
		$prompt .= "- If something is good, say so briefly. If something is weak, say WHY and WHAT specifically would fix it.\n";
		$prompt .= "- No platitudes. No 'consider adding more case studies'. No filler.\n\n";

		$prompt .= "OUTPUT FORMAT — use this exact structure with markdown:\n\n";
		$prompt .= "## Top-line read\n";
		$prompt .= "Two sentences. What does this portfolio communicate to a recruiter or technical hiring manager? What's the dominant impression?\n\n";

		$prompt .= "## Identity & positioning\n";
		$prompt .= "Critique of name, headline, summary, location framing. Does the positioning match the actual range of work? Does it under-sell or over-sell?\n\n";

		$prompt .= "## Experience entries\n";
		$prompt .= "For each role, flag what's weak. Vague descriptions, missing concrete outcomes, junior-sounding language, dates that don't match other public records (Zeugnis, LinkedIn).\n\n";

		$prompt .= "## Project entries\n";
		$prompt .= "Project by project. For each, name what's missing or weak. Empty challenge/solution fields, generic excerpts, missing tech stack, vague feature descriptions, projects that look identical to each other, projects too old to lead with.\n\n";

		$prompt .= "## Skills & tech list\n";
		$prompt .= "Is the list honest? Does it include things only used once in a tutorial? Does it omit things visible in projects? Junior-flavored entries? Missing categories?\n\n";

		$prompt .= "## Blog posts\n";
		$prompt .= "If posts exist: are they substantive or thin? Do they reinforce the developer's positioning or fight it? If no posts, say so neutrally — don't preach.\n\n";

		$prompt .= "## What's working\n";
		$prompt .= "Three things, max. Specific. No generic compliments.\n\n";

		$prompt .= "## Top three fixes (ranked by leverage)\n";
		$prompt .= "Order by impact. Each fix: name the entry/section, name the change, in one or two lines. If a fix takes ten minutes and shifts the whole impression, that goes first.\n\n";

		$prompt .= "PORTFOLIO SNAPSHOT:\n";
		$prompt .= $portfolio_text;

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 90,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $claude_key,
					'anthropic-version' => '2023-06-01',
				),
				'body' => wp_json_encode( array(
					'model'      => 'claude-sonnet-4-6',
					'max_tokens' => 3000,
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

		// Save to log
		global $wpdb;
		$counts = self::content_counts( $profile );
		$wpdb->insert(
			$wpdb->prefix . 'work_os_portfolio_log',
			array(
				'analysis'        => $text,
				'projects_count'  => $counts['projects'],
				'skills_count'    => $counts['skills'],
				'experience_count'=> $counts['experience'],
				'posts_count'     => $counts['posts'],
			),
			array( '%s', '%d', '%d', '%d', '%d' )
		);
		$log_id = $wpdb->insert_id;

		return rest_ensure_response( array(
			'output'          => $text,
			'log_id'          => $log_id,
			'snapshot_counts' => $counts,
		) );
	}

	/**
	 * List previous analyses (most recent first).
	 */
	public static function list_logs() {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT id, created_at, projects_count, skills_count, experience_count, posts_count, LEFT(analysis, 200) AS preview
			 FROM {$wpdb->prefix}work_os_portfolio_log
			 ORDER BY created_at DESC LIMIT 20",
			ARRAY_A
		) ?: array();
		return rest_ensure_response( $rows );
	}

	/**
	 * Get one full analysis by ID.
	 */
	public static function get_log( WP_REST_Request $request ) {
		global $wpdb;
		$id  = (int) $request->get_param( 'id' );
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}work_os_portfolio_log WHERE id = %d", $id
		), ARRAY_A );

		if ( ! $row ) {
			return new WP_Error( 'not_found', 'Analysis not found.', array( 'status' => 404 ) );
		}
		return rest_ensure_response( $row );
	}

	/**
	 * Delete a log entry.
	 */
	public static function delete_log( WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request->get_param( 'id' );
		$wpdb->delete( $wpdb->prefix . 'work_os_portfolio_log', array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	// ── Fix Suggestions ──────────────────────────────────────────────────────

	/**
	 * List all fixable entries on the portfolio. Used to populate the per-entry
	 * suggestions UI. Each entry has a stable key so we can request fixes for it
	 * and store suggestions tied to it.
	 */
	public static function list_entries() {
		$profile_response = WorkOS_Profile::get();
		if ( ! $profile_response instanceof WP_REST_Response ) {
			return new WP_Error( 'profile_error', 'Could not load portfolio profile.', array( 'status' => 500 ) );
		}
		$profile = $profile_response->get_data();

		$entries = array();

		// Identity / positioning entry — always present
		$entries[] = array(
			'key'    => 'identity',
			'type'   => 'identity',
			'label'  => 'Identity & positioning',
			'fields' => array_filter( array(
				'headline' => $profile['headline'] ?? '',
				'summary'  => $profile['summary']  ?? '',
				'tagline'  => get_option( 'work_os_voice_tagline', '' ),
				'niche'    => get_option( 'work_os_voice_niche', '' ),
			) ),
		);

		// Experience entries
		foreach ( ( $profile['experience'] ?? array() ) as $i => $exp ) {
			$entries[] = array(
				'key'    => 'experience-' . $i,
				'type'   => 'experience',
				'label'  => trim( ( $exp['company'] ?? '' ) . ' — ' . ( $exp['role'] ?? '' ) ),
				'meta'   => $exp['period'] ?? '',
				'fields' => array_filter( array(
					'role'        => $exp['role']        ?? '',
					'description' => $exp['description'] ?? '',
				) ),
			);
		}

		// Projects — these have post IDs we can reference
		foreach ( ( $profile['projects'] ?? array() ) as $proj ) {
			$entries[] = array(
				'key'            => 'project-' . $proj['id'],
				'type'           => 'project',
				'label'          => $proj['title'],
				'meta'           => $proj['date'] ?? '',
				'fields'         => array_filter( array(
					'excerpt'   => $proj['excerpt']   ?? '',
					'challenge' => $proj['challenge'] ?? '',
					'solution'  => $proj['solution']  ?? '',
				) ),
				'features_count' => count( $proj['features'] ?? array() ),
			);
		}

		return rest_ensure_response( $entries );
	}

	/**
	 * Generate rewrite suggestions for a specific entry's field.
	 * Anti-fabrication is enforced — only existing data informs the rewrite.
	 */
	public static function suggest_fixes( WP_REST_Request $request ) {
		$claude_key = WorkOS_Settings::get_claude_key();
		if ( ! $claude_key ) {
			return new WP_Error( 'no_key', 'Claude API key not configured.', array( 'status' => 400 ) );
		}

		$entry_key = sanitize_text_field( $request->get_param( 'entry_key' ) ?? '' );
		$field     = sanitize_text_field( $request->get_param( 'field' ) ?? '' );

		if ( ! $entry_key || ! $field ) {
			return new WP_Error( 'missing_param', 'entry_key and field are required.', array( 'status' => 400 ) );
		}

		// Resolve the entry's full context
		$context = self::resolve_entry_context( $entry_key );
		if ( is_wp_error( $context ) ) return $context;

		// Validate field exists on this entry type
		$allowed_fields = self::allowed_fields_for_type( $context['type'] );
		if ( ! in_array( $field, $allowed_fields, true ) ) {
			return new WP_Error( 'invalid_field', "Field '{$field}' is not editable on this entry type.", array( 'status' => 400 ) );
		}

		$current_value = $context['data'][ $field ] ?? '';

		$prompt = self::build_fix_prompt( $context, $field, $current_value );

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

		$parsed = json_decode( trim( $text ), true );
		if ( ! $parsed ) {
			if ( preg_match( '/\{.*\}/s', $text, $matches ) ) {
				$parsed = json_decode( $matches[0], true );
			}
		}

		if ( ! $parsed || empty( $parsed['candidates'] ) ) {
			return new WP_Error( 'parse_error', 'Could not parse rewrite candidates.', array( 'status' => 502 ) );
		}

		// Save suggestion to history
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'work_os_suggestions',
			array(
				'entry_key'     => $entry_key,
				'entry_label'   => $context['label'],
				'field'         => $field,
				'current_value' => $current_value,
				'candidates'    => wp_json_encode( $parsed['candidates'] ),
				'rationale'     => $parsed['rationale'] ?? '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$id = $wpdb->insert_id;

		return rest_ensure_response( array(
			'id'            => $id,
			'entry_key'     => $entry_key,
			'entry_label'   => $context['label'],
			'field'         => $field,
			'current_value' => $current_value,
			'candidates'    => $parsed['candidates'],
			'rationale'     => $parsed['rationale'] ?? '',
		) );
	}

	/**
	 * List suggestion history with optional entry_key filter.
	 */
	public static function list_suggestions( WP_REST_Request $request ) {
		global $wpdb;
		$entry_key = sanitize_text_field( $request->get_param( 'entry_key' ) ?? '' );

		if ( $entry_key ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}work_os_suggestions WHERE entry_key = %s ORDER BY created_at DESC LIMIT 50",
				$entry_key
			), ARRAY_A );
		} else {
			$rows = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}work_os_suggestions ORDER BY created_at DESC LIMIT 100",
				ARRAY_A
			);
		}

		foreach ( $rows as &$row ) {
			$row['candidates'] = json_decode( $row['candidates'], true ) ?: array();
		}

		return rest_ensure_response( $rows ?: array() );
	}

	public static function delete_suggestion( WP_REST_Request $request ) {
		global $wpdb;
		$id = (int) $request->get_param( 'id' );
		$wpdb->delete( $wpdb->prefix . 'work_os_suggestions', array( 'id' => $id ), array( '%d' ) );
		return rest_ensure_response( array( 'deleted' => true ) );
	}

	// ── Internal helpers ─────────────────────────────────────────────────────

	private static function allowed_fields_for_type( string $type ): array {
		switch ( $type ) {
			case 'identity':
				return array( 'headline', 'summary', 'tagline', 'niche' );
			case 'experience':
				return array( 'description' );
			case 'project':
				return array( 'excerpt', 'challenge', 'solution' );
			default:
				return array();
		}
	}

	private static function resolve_entry_context( string $entry_key ) {
		$profile_response = WorkOS_Profile::get();
		if ( ! $profile_response instanceof WP_REST_Response ) {
			return new WP_Error( 'profile_error', 'Could not load portfolio profile.', array( 'status' => 500 ) );
		}
		$profile = $profile_response->get_data();

		if ( $entry_key === 'identity' ) {
			return array(
				'type'  => 'identity',
				'label' => 'Identity & positioning',
				'data'  => array(
					'name'     => $profile['name']     ?? '',
					'headline' => $profile['headline'] ?? '',
					'summary'  => $profile['summary']  ?? '',
					'location' => $profile['location'] ?? '',
					'tagline'  => get_option( 'work_os_voice_tagline', '' ),
					'niche'    => get_option( 'work_os_voice_niche', '' ),
				),
			);
		}

		if ( strpos( $entry_key, 'experience-' ) === 0 ) {
			$idx = (int) substr( $entry_key, strlen( 'experience-' ) );
			$exp = $profile['experience'][ $idx ] ?? null;
			if ( ! $exp ) return new WP_Error( 'not_found', 'Experience entry not found.', array( 'status' => 404 ) );
			return array(
				'type'  => 'experience',
				'label' => trim( $exp['company'] . ' — ' . $exp['role'] ),
				'data'  => array(
					'company'     => $exp['company']     ?? '',
					'role'        => $exp['role']        ?? '',
					'period'      => $exp['period']      ?? '',
					'description' => $exp['description'] ?? '',
					'tech'        => $exp['tech']        ?? array(),
				),
			);
		}

		if ( strpos( $entry_key, 'project-' ) === 0 ) {
			$post_id = (int) substr( $entry_key, strlen( 'project-' ) );
			$post    = get_post( $post_id );
			if ( ! $post || $post->post_type !== 'project' ) {
				return new WP_Error( 'not_found', 'Project not found.', array( 'status' => 404 ) );
			}
			$proj = null;
			foreach ( $profile['projects'] as $p ) {
				if ( $p['id'] === $post_id ) { $proj = $p; break; }
			}
			if ( ! $proj ) return new WP_Error( 'not_found', 'Project not found in profile.', array( 'status' => 404 ) );

			return array(
				'type'  => 'project',
				'label' => $proj['title'],
				'data'  => array(
					'title'     => $proj['title']     ?? '',
					'excerpt'   => $proj['excerpt']   ?? '',
					'challenge' => $proj['challenge'] ?? '',
					'solution'  => $proj['solution']  ?? '',
					'tech'      => $proj['tech']      ?? array(),
					'features'  => $proj['features']  ?? array(),
					'live_url'  => $proj['live_url']  ?? '',
					'github'    => $proj['github']    ?? '',
				),
			);
		}

		return new WP_Error( 'invalid_key', 'Unknown entry key format.', array( 'status' => 400 ) );
	}

	/**
	 * Build the rewrite prompt for a specific field. Anti-fabrication rules.
	 */
	private static function build_fix_prompt( array $context, string $field, string $current_value ): string {
		$prompt  = "You are rewriting a single field for a freelance developer's portfolio. Strict rules apply.\n\n";

		$prompt .= "ANTI-FABRICATION RULES (NON-NEGOTIABLE):\n";
		$prompt .= "- Use ONLY information present in the entry data below. Do not invent metrics, scope, client names, technologies, or outcomes.\n";
		$prompt .= "- If the data is thin, the rewrite is honest about what's there. Do not pad to look impressive.\n";
		$prompt .= "- If a tech stack is listed, you can reference any of those technologies. If a tech is NOT listed, you cannot mention it.\n";
		$prompt .= "- If the original copy contains a metric (e.g. '40%'), you may keep it but do not invent new metrics.\n";
		$prompt .= "- A senior developer reading the rewrite must be able to defend every claim against the source data.\n\n";

		$prompt .= "WRITING RULES:\n";
		$prompt .= "- Direct, specific, no marketing fluff.\n";
		$prompt .= "- No 'passionate about', 'cutting-edge', 'exceptional digital experiences', 'leveraging', 'synergize'.\n";
		$prompt .= "- No em-dashes inside sentences. No compound hyphens unless the term is genuinely hyphenated.\n";
		$prompt .= "- Match the language of the existing copy (German if German, English if English).\n";
		$prompt .= "- Sound like a senior practitioner, not a junior. No 'mastered', no 'where I really sharpened my skills'.\n\n";

		$prompt .= "CONTEXT — full entry data:\n";
		foreach ( $context['data'] as $k => $v ) {
			if ( is_array( $v ) ) {
				$v = empty( $v ) ? '(none)' : ( is_array( $v[0] ?? null ) ? wp_json_encode( $v ) : implode( ', ', $v ) );
			}
			$prompt .= "  {$k}: " . ( $v === '' ? '(empty)' : $v ) . "\n";
		}
		$prompt .= "\n";

		$field_rules = self::field_specific_rules( $context['type'], $field );
		$prompt .= "FIELD TO REWRITE: {$field}\n";
		$prompt .= "Current value: " . ( $current_value === '' ? '(empty — generate from scratch using only the context above)' : $current_value ) . "\n\n";
		$prompt .= "FIELD-SPECIFIC RULES:\n{$field_rules}\n\n";

		$prompt .= "OUTPUT — return ONLY valid JSON, no markdown, no code fences:\n";
		$prompt .= "{\n";
		$prompt .= '  "candidates": [' . "\n";
		$prompt .= '    {"label": "Short label for the angle (e.g. \"Technical depth\", \"Plain and direct\")", "text": "The rewrite itself."},' . "\n";
		$prompt .= '    ...3 candidates total, each taking a different angle' . "\n";
		$prompt .= "  ],\n";
		$prompt .= '  "rationale": "One sentence on what makes these rewrites stronger than the current value."' . "\n";
		$prompt .= "}\n";

		return $prompt;
	}

	private static function field_specific_rules( string $type, string $field ): string {
		$key = $type . ':' . $field;
		switch ( $key ) {
			case 'identity:headline':
				return "- One line, max 80 chars.\n- States WHAT the developer does, not how they feel about it.\n- Specific over generic. 'WordPress and headless developer in Vorarlberg' beats 'passionate full-stack developer'.";
			case 'identity:summary':
				return "- 2-4 sentences max.\n- Opens with what the developer does, not their feelings about it.\n- Names the actual range (WordPress, PHP, headless, performance, etc.) only if those technologies appear in the entry data.\n- No autobiographical narrative.";
			case 'identity:tagline':
				return "- One line. Under 60 chars.\n- Concrete. If you can't name the work specifically, leave the tagline shorter and sharper.";
			case 'identity:niche':
				return "- Under 60 chars.\n- Honest about range. If the developer does WordPress + headless + PHP backend, say all three. Don't narrow to please any single audience.";
			case 'experience:description':
				return "- 2-4 sentences OR 3-4 bullet points (pick whichever fits the entry).\n- Lead with concrete output, not responsibilities.\n- Past tense for past roles, present tense for current. Pick one and keep it consistent.\n- No 'collaborated with', 'responsible for', 'mastered'. Use verbs that describe what was built or shipped.";
			case 'project:excerpt':
				return "- Under 200 chars.\n- Names what was built, the technical stack (only if listed in context), and the kind of problem solved.\n- Reads as the answer to 'what is this project' for a developer skimming.";
			case 'project:challenge':
				return "- 2-3 sentences.\n- Names the actual problem the project solves. If the only context is a description and tech stack, infer cautiously and don't invent business outcomes.\n- If there's truly nothing in the context to base a challenge on, say so honestly: 'Personal/exploratory project to evaluate X technology' is better than fictional client framing.";
			case 'project:solution':
				return "- 2-4 sentences OR a short list of architectural decisions.\n- Names how the challenge was solved. Reference specific tech only if listed in the entry data.\n- No vague 'modern, scalable solution'. Concrete decisions only.";
			default:
				return "- Keep it concrete and grounded in the source data.";
		}
	}

	// ── Snapshot building ────────────────────────────────────────────────────

	private static function build_portfolio_snapshot( array $profile ): string {
		$out  = "## Identity\n";
		$out .= "Name: {$profile['name']}\n";
		$out .= "Headline: {$profile['headline']}\n";
		$out .= "Location: " . ( $profile['location'] ?? '' ) . "\n";

		$voice_rate    = get_option( 'work_os_voice_rate', '' );
		$voice_niche   = get_option( 'work_os_voice_niche', '' );
		$voice_tagline = get_option( 'work_os_voice_tagline', '' );
		if ( $voice_rate || $voice_niche || $voice_tagline ) {
			$out .= "\n## Stated positioning (from settings)\n";
			if ( $voice_rate )    $out .= "Rate: {$voice_rate}\n";
			if ( $voice_niche )   $out .= "Niche: {$voice_niche}\n";
			if ( $voice_tagline ) $out .= "Tagline: {$voice_tagline}\n";
		}

		if ( ! empty( $profile['summary'] ) ) {
			$out .= "\n## Summary (About page)\n{$profile['summary']}\n";
		}

		if ( ! empty( $profile['experience'] ) ) {
			$out .= "\n## Experience entries\n";
			foreach ( $profile['experience'] as $i => $e ) {
				$out .= "\n### " . ( $i + 1 ) . ". {$e['company']} — {$e['role']} ({$e['period']})\n";
				$out .= "Description: " . ( $e['description'] ?: '(empty)' ) . "\n";
				if ( ! empty( $e['tech'] ) ) {
					$out .= "Tech: " . implode( ', ', $e['tech'] ) . "\n";
				}
			}
		}

		$tech_skills = $profile['tech_skills'] ?? array();
		if ( ! empty( $tech_skills ) ) {
			$out .= "\n## Technical skills (from 'tech' CPT)\n" . implode( ', ', $tech_skills ) . "\n";
		}
		if ( ! empty( $profile['skills'] ) ) {
			$out .= "\n## Highlighted skills (from About page)\n" . implode( ', ', $profile['skills'] ) . "\n";
		}

		if ( ! empty( $profile['languages'] ) ) {
			$out .= "\n## Languages\n";
			foreach ( $profile['languages'] as $l ) {
				$out .= "- {$l['language']}: {$l['level']}\n";
			}
		}

		if ( ! empty( $profile['education'] ) ) {
			$out .= "\n## Education\n";
			foreach ( $profile['education'] as $edu ) {
				$out .= "- {$edu['degree']} — {$edu['institution']} ({$edu['period']})\n";
			}
		}

		if ( ! empty( $profile['projects'] ) ) {
			$out .= "\n## Project entries\n";
			foreach ( $profile['projects'] as $i => $p ) {
				$out .= "\n### " . ( $i + 1 ) . ". {$p['title']}\n";
				$out .= "Excerpt: " . ( $p['excerpt'] ?: '(empty)' ) . "\n";
				$out .= "Challenge: " . ( $p['challenge'] ?: '(empty)' ) . "\n";
				$out .= "Solution: " . ( $p['solution'] ?: '(empty)' ) . "\n";
				if ( ! empty( $p['tech'] ) )     $out .= "Tech: " . implode( ', ', $p['tech'] ) . "\n";
				if ( ! empty( $p['live_url'] ) ) $out .= "Live: {$p['live_url']}\n";
				if ( ! empty( $p['github'] ) )   $out .= "GitHub: {$p['github']}\n";
				if ( ! empty( $p['features'] ) ) {
					$out .= "Features:\n";
					foreach ( $p['features'] as $f ) {
						$out .= "  - {$f['title']}: " . ( $f['description'] ?: '(no description)' ) . "\n";
					}
				}
				$out .= "Posted: {$p['date']}\n";
			}
		} else {
			$out .= "\n## Project entries\n(no projects)\n";
		}

		// Blog posts
		$posts = get_posts( array(
			'post_type'      => 'post',
			'posts_per_page' => 15,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( ! empty( $posts ) ) {
			$out .= "\n## Recent published blog posts\n";
			foreach ( $posts as $post ) {
				$excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 35 );
				$out .= "\n### {$post->post_title} ({$post->post_date_gmt})\n{$excerpt}\n";
			}
		} else {
			$out .= "\n## Recent published blog posts\n(no published posts)\n";
		}

		return $out;
	}

	private static function content_counts( array $profile ): array {
		$post_count = (int) wp_count_posts( 'post' )->publish;
		return array(
			'projects'   => count( $profile['projects'] ?? array() ),
			'experience' => count( $profile['experience'] ?? array() ),
			'skills'     => count( $profile['tech_skills'] ?? array() ) + count( $profile['skills'] ?? array() ),
			'posts'      => $post_count,
		);
	}
}

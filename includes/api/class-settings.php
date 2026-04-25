<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_Settings {

	private static function mask( $key ) {
		if ( empty( $key ) ) {
			return '';
		}
		$len = strlen( $key );
		if ( $len <= 4 ) {
			return str_repeat( '*', $len );
		}
		return str_repeat( '*', $len - 4 ) . substr( $key, - 4 );
	}

	public static function get() {
		return rest_ensure_response( array(
			'claude_key' => self::mask( get_option( 'work_os_claude_key', '' ) ),
			'gemini_key' => self::mask( get_option( 'work_os_gemini_key', '' ) ),
			'claude_set' => ! empty( get_option( 'work_os_claude_key', '' ) ),
			'gemini_set' => ! empty( get_option( 'work_os_gemini_key', '' ) ),
		) );
	}

	public static function save( WP_REST_Request $request ) {
		$body = $request->get_json_params();

		if ( isset( $body['claude_key'] ) && strpos( $body['claude_key'], '*' ) === false ) {
			update_option( 'work_os_claude_key', sanitize_text_field( $body['claude_key'] ) );
		}

		if ( isset( $body['gemini_key'] ) && strpos( $body['gemini_key'], '*' ) === false ) {
			update_option( 'work_os_gemini_key', sanitize_text_field( $body['gemini_key'] ) );
		}

		return rest_ensure_response( array( 'ok' => true ) );
	}

	public static function get_claude_key() {
		return get_option( 'work_os_claude_key', '' );
	}

	public static function get_gemini_key() {
		return get_option( 'work_os_gemini_key', '' );
	}

	public static function get_cv_phone()    { return get_option( 'work_os_cv_phone', '' ); }
	public static function get_cv_email()    { return get_option( 'work_os_cv_email', get_option( 'admin_email' ) ); }
	public static function get_cv_address()  { return get_option( 'work_os_cv_address', '' ); }
	public static function get_cv_linkedin() { return get_option( 'work_os_cv_linkedin', '' ); }
	public static function get_cv_github()   { return get_option( 'work_os_cv_github', '' ); }
	public static function get_linkedin_client_id()     { return get_option( 'work_os_linkedin_client_id', '' ); }
	public static function get_linkedin_client_secret() { return get_option( 'work_os_linkedin_client_secret', '' ); }

	public static function default_draft_prompt_rules(): string {
		return implode( "\n", array(
			"ANTI-FABRICATION RULES (NON-NEGOTIABLE):",
			"- Use ONLY facts present in the candidate profile below. Do not invent client names, project scope, team sizes, metrics, or outcomes.",
			"- If a useful claim isn't supported by the profile, leave it out. Do not approximate.",
			"- Never inflate WordPress plugin work into 'platform engineering' or similar.",
			"- Never claim a percentage improvement, user count, or revenue figure unless it appears explicitly in the profile.",
			"- If the job lists tech the candidate lacks, name the gap honestly in one sentence and offer to bridge it. Do not hide it.",
			"",
			"WRITING RULES:",
			"- No AI filler: no 'I am thrilled', 'hope this finds you well', 'I came across your posting'",
			"- Open with a relevant project or concrete fact — not a compliment or a self-description",
			"- Under 200 words",
			"- If the client appears German-speaking, write in formal German (Sie-form)",
			"- Use the company research to show you understand their specific problem, not generic praise",
			"- End with one concrete, low-friction next step",
		) );
	}

	public static function default_blog_prompt_rules(): string {
		return implode( "\n", array(
			"ANTI-FABRICATION RULES (NON-NEGOTIABLE):",
			"- Only reference real projects, clients, and technologies listed in the author profile below. Do not invent examples.",
			"- Do not claim percentages, revenue figures, user counts, or team sizes unless they appear explicitly in the profile or memory event.",
			"- If grounding the post in a memory event, stay within what the note describes. Do not embellish scope or outcomes.",
			"- No AI filler: no 'In today's fast-paced world', no 'The results were staggering', no vague superlatives.",
			"",
			"WRITING RULES:",
			"- First person, confident and direct",
			"- Specific and concrete — only real examples from the profile",
			"- WordPress / PHP developer perspective",
			"- Target audience: other developers and potential clients",
			"- 500-800 words",
			"- Start with: Title: [your suggested title] on its own line, then the post body",
			"- Markdown formatting (## headings, **bold**, lists)",
		) );
	}

	public static function get_draft_prompt_rules(): string {
		return get_option( 'work_os_prompt_draft_rules', '' ) ?: self::default_draft_prompt_rules();
	}

	public static function get_blog_prompt_rules(): string {
		return get_option( 'work_os_prompt_blog_rules', '' ) ?: self::default_blog_prompt_rules();
	}
}

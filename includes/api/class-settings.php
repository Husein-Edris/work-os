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
}

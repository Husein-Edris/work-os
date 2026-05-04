<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WorkOS_EA_Report {

	private static $category_labels = array(
		'betriebseinnahmen'        => 'Erträge/Betriebseinnahmen',
		'uebrige_ertraege'         => 'übrige Erträge/Betriebseinnahmen',
		'beigestelltes_personal'   => 'Beigestelltes Personal und Fremdleistungen',
		'abschreibungen'           => 'Abschreibungen auf das Anlagevermögen',
		'kfz_kosten'               => 'KFZ-Kosten',
		'miete'                    => 'Miete',
		'rechtsberatung'           => 'Rechtsberatung',
		'werbung'                  => 'Werbung',
		'zinsen'                   => 'Zinsen und ähnliche Ausgaben',
		'eigene_pflichtversicherung' => 'eigene Pflichtversicherungsbeiträge',
		'uebrige_ausgaben'         => 'übrige Ausgaben',
	);

	private static $default_mappings = array(
		array( 'sozialversicherung', 'eigene_pflichtversicherung', 90 ),
		array( 'svs',                'eigene_pflichtversicherung', 80 ),
		array( 'breuss',             'rechtsberatung',             80 ),
		array( 'rt buchhaltung',     'rechtsberatung',             80 ),
		array( 'eni',                'kfz_kosten',                 80 ),
		array( 'shell',              'kfz_kosten',                 80 ),
		array( 'easypark',           'kfz_kosten',                 80 ),
		array( 'erste sparkasse',    'zinsen',                     80 ),
		array( 'sparkasse',          'zinsen',                     70 ),
		array( 'upwork',             'beigestelltes_personal',     80 ),
		array( 'canva',              'werbung',                    80 ),
		array( 'anthropic',          'uebrige_ausgaben',           80 ),
		array( 'cursor',             'uebrige_ausgaben',           80 ),
		array( 'anysphere',          'uebrige_ausgaben',           80 ),
		array( 'google cloud',       'uebrige_ausgaben',           90 ),
		array( 't-mobile',           'uebrige_ausgaben',           80 ),
		array( 'wp umbrella',        'uebrige_ausgaben',           80 ),
		array( 'hostinger',          'uebrige_ausgaben',           80 ),
		array( 'ionos',              'uebrige_ausgaben',           80 ),
		array( 'fastbill',           'uebrige_ausgaben',           80 ),
		array( '1password',          'uebrige_ausgaben',           80 ),
		array( 'amazon',             'uebrige_ausgaben',           70 ),
		array( 'cleverbridge',       'uebrige_ausgaben',           80 ),
	);

	// ── FastBill HTTP helpers ─────────────────────────────────────────────────

	private static function fb_post( string $service, array $filter, int $limit = 100, int $offset = 0 ): array {
		$email   = get_option( 'work_os_fastbill_email', '' );
		$api_key = get_option( 'work_os_fastbill_api_key', '' );

		if ( ! $email || ! $api_key ) {
			return array( 'error' => 'FastBill not configured' );
		}

		$body = wp_json_encode( array(
			'SERVICE' => $service,
			'LIMIT'   => $limit,
			'OFFSET'  => $offset,
			'FILTER'  => $filter,
		) );

		$response = wp_remote_post( 'https://my.fastbill.com/api/1.0/api.php', array(
			'timeout' => 30,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Basic ' . base64_encode( $email . ':' . $api_key ),
			),
			'body' => $body,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'error' => $response->get_error_message() );
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return $data ?: array( 'error' => 'Invalid JSON from FastBill' );
	}

	private static function fetch_invoices( string $start, string $end ): array {
		$results = array();
		$offset  = 0;
		while ( true ) {
			$data  = self::fb_post( 'invoice.get', array( 'STATE' => 'paid', 'START_DATE' => $start, 'END_DATE' => $end ), 100, $offset );
			$batch = $data['RESPONSE']['INVOICES'] ?? array();
			if ( empty( $batch ) ) break;
			if ( is_array( $batch ) && ! isset( $batch[0] ) ) $batch = array( $batch ); // single result
			$results = array_merge( $results, $batch );
			if ( count( $batch ) < 100 ) break;
			$offset += 100;
		}
		return $results;
	}

	private static function fetch_expenses( string $start, string $end ): array {
		$results = array();
		$offset  = 0;
		while ( true ) {
			$data  = self::fb_post( 'expense.get', array( 'START_DATE' => $start, 'END_DATE' => $end ), 100, $offset );
			$batch = $data['RESPONSE']['EXPENSES'] ?? array();
			if ( empty( $batch ) ) break;
			if ( is_array( $batch ) && ! isset( $batch[0] ) ) $batch = array( $batch );
			$results = array_merge( $results, $batch );
			if ( count( $batch ) < 100 ) break;
			$offset += 100;
		}
		return $results;
	}

	private static function get_eur_rate( string $currency, string $date_str ): float {
		if ( $currency === 'EUR' ) return 1.0;

		$cache_key = 'work_os_eur_rate_' . $currency . '_' . $date_str;
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) return (float) $cached;

		$check = \DateTime::createFromFormat( 'Y-m-d', $date_str );
		if ( ! $check ) return 1.0;

		for ( $i = 0; $i < 5; $i++ ) {
			$url      = 'https://api.frankfurter.app/' . $check->format( 'Y-m-d' ) . '?from=' . urlencode( $currency ) . '&to=EUR';
			$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $body['rates']['EUR'] ) ) {
					$rate = (float) $body['rates']['EUR'];
					set_transient( $cache_key, $rate, DAY_IN_SECONDS * 7 );
					return $rate;
				}
			}
			$check->modify( '-1 day' );
		}

		return 1.0;
	}

	private static function invoice_to_eur( array $invoice ): float {
		$total    = (float) ( $invoice['TOTAL'] ?? 0 );
		$currency = $invoice['CURRENCY_CODE'] ?? 'EUR';
		$paid_raw = $invoice['PAID_DATE'] ?? '';
		$date_str = ( $paid_raw && $paid_raw !== '0000-00-00 00:00:00' )
			? substr( $paid_raw, 0, 10 )
			: substr( $invoice['INVOICE_DATE'] ?? '', 0, 10 );
		return round( $total * self::get_eur_rate( $currency, $date_str ), 2 );
	}

	private static function expense_vendor( array $expense ): string {
		return trim( $expense['ORGANIZATION'] ?? $expense['LAST_NAME'] ?? '' );
	}

	private static function categorize_expense( string $vendor, array $mappings ): string {
		$lower = strtolower( $vendor );
		foreach ( $mappings as $m ) {
			if ( strpos( $lower, $m['pattern'] ) !== false ) {
				return $m['category'];
			}
		}
		return 'uebrige_ausgaben';
	}

	// ── REST: POST /ea/report ─────────────────────────────────────────────────

	public static function report( WP_REST_Request $request ): WP_REST_Response {
		$body       = $request->get_json_params();
		$start_date = sanitize_text_field( $body['start_date'] ?? '' );
		$end_date   = sanitize_text_field( $body['end_date'] ?? '' );

		if ( ! $start_date || ! $end_date ) {
			return new WP_REST_Response( array( 'message' => 'start_date and end_date required (YYYY-MM-DD)' ), 400 );
		}
		if ( ! get_option( 'work_os_fastbill_email' ) || ! get_option( 'work_os_fastbill_api_key' ) ) {
			return new WP_REST_Response( array( 'message' => 'FastBill not configured — set email + API key in Settings' ), 503 );
		}

		global $wpdb;
		$table    = esc_sql( $wpdb->prefix . 'work_os_ea_vendor_mappings' );
		$mappings = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY priority DESC", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$raw_invoices = self::fetch_invoices( $start_date, $end_date );
		$raw_expenses = self::fetch_expenses( $start_date, $end_date );

		if ( isset( $raw_invoices['error'] ) || isset( $raw_expenses['error'] ) ) {
			return new WP_REST_Response( array( 'message' => 'FastBill API nicht erreichbar. Bitte Zugangsdaten prüfen.' ), 502 );
		}

		$einnahmen = array();
		foreach ( $raw_invoices as $inv ) {
			try {
				$amount_eur = self::invoice_to_eur( $inv );
			} catch ( \Exception $e ) {
				$amount_eur = (float) ( $inv['TOTAL'] ?? 0 );
			}
			$org         = trim( $inv['ORGANIZATION'] ?? $inv['LAST_NAME'] ?? '' );
			$inv_date    = $inv['INVOICE_DATE'] ?? '';
			$paid_date   = substr( $inv['PAID_DATE'] ?? '', 0, 10 );
			$currency    = $inv['CURRENCY_CODE'] ?? 'EUR';
			$amount_orig = (float) ( $inv['TOTAL'] ?? 0 );
			$einnahmen[] = array(
				'id'            => 'inv-' . ( $inv['INVOICE_ID'] ?? uniqid() ),
				'date'          => $inv_date,
				'paid_date'     => $paid_date,
				'description'   => $org ? $org . ' · ' . $inv_date : $inv_date,
				'currency_orig' => $currency,
				'amount_orig'   => $amount_orig,
				'amount_eur'    => $amount_eur,
				'category'      => 'betriebseinnahmen',
				'source'        => 'fastbill',
			);
		}

		$ausgaben      = array();
		$default_count = 0;
		foreach ( $raw_expenses as $exp ) {
			$vendor   = self::expense_vendor( $exp );
			$category = self::categorize_expense( $vendor, $mappings );
			$amount   = (float) ( $exp['TOTAL'] ?? 0 );
			if ( $amount <= 0 ) continue;
			$exp_date = $exp['INVOICE_DATE'] ?? '';
			if ( $category === 'uebrige_ausgaben' && $vendor ) {
				$matched = false;
				foreach ( $mappings as $m ) {
					if ( strpos( strtolower( $vendor ), $m['pattern'] ) !== false ) { $matched = true; break; }
				}
				if ( ! $matched ) $default_count++;
			}
			$ausgaben[] = array(
				'id'          => 'exp-' . ( $exp['INVOICE_ID'] ?? uniqid() ),
				'date'        => $exp_date,
				'description' => $vendor ?: '—',
				'amount_eur'  => $amount,
				'category'    => $category,
				'source'      => 'fastbill',
			);
		}

		usort( $einnahmen, fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );
		usort( $ausgaben,  fn( $a, $b ) => strcmp( $a['date'], $b['date'] ) );

		$total_e  = round( array_sum( array_column( $einnahmen, 'amount_eur' ) ), 2 );
		$total_a  = round( array_sum( array_column( $ausgaben,  'amount_eur' ) ), 2 );
		$warnings = array();
		if ( $default_count ) {
			$warnings[] = $default_count . ' Ausgaben ohne passende Kategorie-Regel → übrige Ausgaben';
		}

		return rest_ensure_response( array(
			'period'          => array( 'start' => $start_date, 'end' => $end_date ),
			'einnahmen'       => $einnahmen,
			'ausgaben'        => $ausgaben,
			'totals'          => array( 'einnahmen' => $total_e, 'ausgaben' => $total_a, 'gewinn' => round( $total_e - $total_a, 2 ) ),
			'category_labels' => self::$category_labels,
			'warnings'        => $warnings,
		) );
	}

	// ── REST: vendor mapping CRUD ─────────────────────────────────────────────

	public static function list_mappings(): WP_REST_Response {
		global $wpdb;
		$table = esc_sql( $wpdb->prefix . 'work_os_ea_vendor_mappings' );
		$rows  = $wpdb->get_results( "SELECT * FROM `{$table}` ORDER BY priority DESC", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return rest_ensure_response( $rows );
	}

	public static function create_mapping( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$body     = $request->get_json_params();
		$pattern  = strtolower( trim( sanitize_text_field( $body['pattern'] ?? '' ) ) );
		$category = sanitize_text_field( $body['category'] ?? '' );
		$priority = (int) ( $body['priority'] ?? 50 );

		if ( ! $pattern || ! $category ) {
			return new WP_REST_Response( array( 'message' => 'pattern and category required' ), 400 );
		}

		$id    = 'vm-' . substr( wp_generate_uuid4(), 0, 8 );
		$table = $wpdb->prefix . 'work_os_ea_vendor_mappings';
		$wpdb->insert( $table, array( 'id' => $id, 'pattern' => $pattern, 'category' => $category, 'priority' => $priority ) );

		return new WP_REST_Response( array( 'id' => $id, 'pattern' => $pattern, 'category' => $category, 'priority' => $priority ), 201 );
	}

	public static function update_mapping( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$id    = sanitize_text_field( $request->get_param( 'id' ) );
		$body  = $request->get_json_params();
		$table = $wpdb->prefix . 'work_os_ea_vendor_mappings';

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %s", $id ), ARRAY_A );
		if ( ! $row ) return new WP_REST_Response( array( 'message' => 'Not found' ), 404 );

		$data = array();
		if ( isset( $body['pattern'] ) )  $data['pattern']  = strtolower( trim( sanitize_text_field( $body['pattern'] ) ) );
		if ( isset( $body['category'] ) ) $data['category'] = sanitize_text_field( $body['category'] );
		if ( isset( $body['priority'] ) ) $data['priority'] = (int) $body['priority'];

		if ( $data ) $wpdb->update( $table, $data, array( 'id' => $id ) );

		return rest_ensure_response( array_merge( $row, $data ) );
	}

	public static function delete_mapping( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$id    = sanitize_text_field( $request->get_param( 'id' ) );
		$table = $wpdb->prefix . 'work_os_ea_vendor_mappings';
		$wpdb->delete( $table, array( 'id' => $id ) );
		if ( ! $wpdb->rows_affected ) {
			return new WP_REST_Response( array( 'message' => 'Not found' ), 404 );
		}
		return new WP_REST_Response( null, 204 );
	}

	public static function list_categories(): WP_REST_Response {
		$out = array();
		foreach ( self::$category_labels as $key => $label ) {
			$out[] = array( 'key' => $key, 'label' => $label );
		}
		return rest_ensure_response( $out );
	}

	// ── Seeding ───────────────────────────────────────────────────────────────

	public static function seed_mappings(): void {
		global $wpdb;
		$table = $wpdb->prefix . 'work_os_ea_vendor_mappings';
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $count > 0 ) return;

		foreach ( self::$default_mappings as $m ) {
			$wpdb->insert( $table, array(
				'id'       => 'vm-' . substr( wp_generate_uuid4(), 0, 8 ),
				'pattern'  => $m[0],
				'category' => $m[1],
				'priority' => $m[2],
			) );
		}
	}
}

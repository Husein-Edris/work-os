<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_POST['work_os_settings_nonce'] ) && wp_verify_nonce( $_POST['work_os_settings_nonce'], 'work_os_save_settings' ) ) {
	// AI keys
	if ( isset( $_POST['claude_key'] ) && strpos( $_POST['claude_key'], '*' ) === false ) {
		update_option( 'work_os_claude_key', sanitize_text_field( $_POST['claude_key'] ) );
	}
	if ( isset( $_POST['gemini_key'] ) && strpos( $_POST['gemini_key'], '*' ) === false ) {
		update_option( 'work_os_gemini_key', sanitize_text_field( $_POST['gemini_key'] ) );
	}

	// CV & Contact
	if ( isset( $_POST['cv_phone'] ) ) {
		update_option( 'work_os_cv_phone', sanitize_text_field( $_POST['cv_phone'] ) );
	}
	if ( isset( $_POST['cv_email'] ) ) {
		update_option( 'work_os_cv_email', sanitize_email( $_POST['cv_email'] ) );
	}
	if ( isset( $_POST['cv_address'] ) ) {
		update_option( 'work_os_cv_address', sanitize_text_field( $_POST['cv_address'] ) );
	}
	if ( isset( $_POST['cv_linkedin'] ) ) {
		update_option( 'work_os_cv_linkedin', esc_url_raw( $_POST['cv_linkedin'] ) );
	}
	if ( isset( $_POST['cv_github'] ) ) {
		update_option( 'work_os_cv_github', esc_url_raw( $_POST['cv_github'] ) );
	}

	// Upwork
	if ( isset( $_POST['upwork_client_id'] ) ) {
		update_option( 'work_os_upwork_client_id', sanitize_text_field( $_POST['upwork_client_id'] ) );
	}
	if ( isset( $_POST['upwork_client_secret'] ) && strpos( $_POST['upwork_client_secret'], '*' ) === false ) {
		update_option( 'work_os_upwork_client_secret', sanitize_text_field( $_POST['upwork_client_secret'] ) );
	}

	// LinkedIn
	if ( isset( $_POST['linkedin_client_id'] ) ) {
		update_option( 'work_os_linkedin_client_id', sanitize_text_field( $_POST['linkedin_client_id'] ) );
	}
	if ( isset( $_POST['linkedin_client_secret'] ) && strpos( $_POST['linkedin_client_secret'], '*' ) === false ) {
		update_option( 'work_os_linkedin_client_secret', sanitize_text_field( $_POST['linkedin_client_secret'] ) );
	}

	// Voice / rate constants
	if ( isset( $_POST['voice_rate'] ) ) {
		update_option( 'work_os_voice_rate', sanitize_text_field( $_POST['voice_rate'] ) );
	}
	if ( isset( $_POST['voice_niche'] ) ) {
		update_option( 'work_os_voice_niche', sanitize_text_field( $_POST['voice_niche'] ) );
	}
	if ( isset( $_POST['voice_tagline'] ) ) {
		update_option( 'work_os_voice_tagline', sanitize_text_field( $_POST['voice_tagline'] ) );
	}

	echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
}

$claude_key           = get_option( 'work_os_claude_key', '' );
$gemini_key           = get_option( 'work_os_gemini_key', '' );
$cv_phone             = get_option( 'work_os_cv_phone', '' );
$cv_email             = get_option( 'work_os_cv_email', get_option( 'admin_email' ) );
$cv_address           = get_option( 'work_os_cv_address', '' );
$cv_linkedin          = get_option( 'work_os_cv_linkedin', '' );
$cv_github            = get_option( 'work_os_cv_github', '' );
$upwork_client_id     = get_option( 'work_os_upwork_client_id', '' );
$upwork_client_secret = get_option( 'work_os_upwork_client_secret', '' );
$linkedin_client_id     = get_option( 'work_os_linkedin_client_id', '' );
$linkedin_client_secret = get_option( 'work_os_linkedin_client_secret', '' );
$voice_rate           = get_option( 'work_os_voice_rate', '€38/hr' );
$voice_niche          = get_option( 'work_os_voice_niche', 'WordPress / WooCommerce' );
$voice_tagline        = get_option( 'work_os_voice_tagline', '' );

function work_os_mask( $key ) {
	if ( empty( $key ) ) return '';
	$len = strlen( $key );
	return $len <= 4 ? str_repeat( '*', $len ) : str_repeat( '*', $len - 4 ) . substr( $key, -4 );
}
?>
<div class="wrap">
	<h1>Work OS — Settings</h1>

	<form method="post">
		<?php wp_nonce_field( 'work_os_save_settings', 'work_os_settings_nonce' ); ?>

		<h2 class="title">CV &amp; Contact</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="cv_phone">Phone</label></th>
				<td>
					<input type="text" id="cv_phone" name="cv_phone" class="regular-text"
						value="<?php echo esc_attr( $cv_phone ); ?>"
						placeholder="+43 676 391 0128">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cv_email">CV Email</label></th>
				<td>
					<input type="email" id="cv_email" name="cv_email" class="regular-text"
						value="<?php echo esc_attr( $cv_email ); ?>"
						placeholder="kontakt@edrishusein.com">
					<p class="description">Used on CV (separate from WordPress admin email).</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cv_address">Address</label></th>
				<td>
					<input type="text" id="cv_address" name="cv_address" class="regular-text"
						value="<?php echo esc_attr( $cv_address ); ?>"
						placeholder="Mitteldorfgasse 1a, 6850 Dornbirn, Österreich">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cv_linkedin">LinkedIn URL</label></th>
				<td>
					<input type="url" id="cv_linkedin" name="cv_linkedin" class="regular-text"
						value="<?php echo esc_attr( $cv_linkedin ); ?>"
						placeholder="https://linkedin.com/in/edris-husein">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cv_github">GitHub URL</label></th>
				<td>
					<input type="url" id="cv_github" name="cv_github" class="regular-text"
						value="<?php echo esc_attr( $cv_github ); ?>"
						placeholder="https://github.com/Husein-Edris">
				</td>
			</tr>
		</table>

		<h2 class="title">AI Keys</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="claude_key">Claude API Key</label></th>
				<td>
					<input type="password" id="claude_key" name="claude_key" class="regular-text"
						value="<?php echo esc_attr( work_os_mask( $claude_key ) ); ?>"
						autocomplete="new-password">
					<p class="description">
						<?php if ( $claude_key ) : ?>
							<span style="color:#00a32a">&#10003; Key set</span> — enter a new value to replace.
						<?php else : ?>
							Get your key at <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a>.
						<?php endif; ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="gemini_key">Gemini API Key</label></th>
				<td>
					<input type="password" id="gemini_key" name="gemini_key" class="regular-text"
						value="<?php echo esc_attr( work_os_mask( $gemini_key ) ); ?>"
						autocomplete="new-password">
					<p class="description">
						<?php if ( $gemini_key ) : ?>
							<span style="color:#00a32a">&#10003; Key set</span> — enter a new value to replace.
						<?php else : ?>
							Get your key at <a href="https://aistudio.google.com" target="_blank">aistudio.google.com</a>.
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title">Job Sources</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="upwork_client_id">Upwork Client ID</label></th>
				<td>
					<input type="text" id="upwork_client_id" name="upwork_client_id" class="regular-text"
						value="<?php echo esc_attr( $upwork_client_id ); ?>">
					<p class="description">From your Upwork developer app. Required for job feed.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="upwork_client_secret">Upwork Client Secret</label></th>
				<td>
					<input type="password" id="upwork_client_secret" name="upwork_client_secret" class="regular-text"
						value="<?php echo esc_attr( work_os_mask( $upwork_client_secret ) ); ?>"
						autocomplete="new-password">
					<p class="description">
						<?php if ( $upwork_client_secret ) : ?>
							<span style="color:#00a32a">&#10003; Secret set</span>
						<?php else : ?>
							OAuth credentials for Upwork API access.
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title">LinkedIn API</h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="linkedin_client_id">LinkedIn Client ID</label></th>
				<td>
					<input type="text" id="linkedin_client_id" name="linkedin_client_id" class="regular-text"
						value="<?php echo esc_attr( $linkedin_client_id ); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="linkedin_client_secret">LinkedIn Client Secret</label></th>
				<td>
					<input type="password" id="linkedin_client_secret" name="linkedin_client_secret" class="regular-text"
						value="<?php echo esc_attr( work_os_mask( $linkedin_client_secret ) ); ?>"
						autocomplete="new-password">
					<p class="description">
						<?php if ( $linkedin_client_secret ) : ?>
							<span style="color:#00a32a">&#10003; Secret set</span>
						<?php else : ?>
							OAuth credentials for LinkedIn API access.
						<?php endif; ?>
					</p>
				</td>
			</tr>
		</table>

		<h2 class="title">Your Voice</h2>
		<p class="description" style="margin-bottom:12px">Used by Claude when drafting proposals and generating blog posts.</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="voice_rate">Hourly Rate</label></th>
				<td>
					<input type="text" id="voice_rate" name="voice_rate" class="regular-text"
						value="<?php echo esc_attr( $voice_rate ); ?>"
						placeholder="€38/hr">
					<p class="description">Your standard rate used in proposals (e.g. €38/hr).</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="voice_niche">Primary Niche</label></th>
				<td>
					<input type="text" id="voice_niche" name="voice_niche" class="regular-text"
						value="<?php echo esc_attr( $voice_niche ); ?>"
						placeholder="WordPress / WooCommerce">
					<p class="description">Your core specialisation, used as context in AI prompts.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="voice_tagline">Personal Tagline</label></th>
				<td>
					<input type="text" id="voice_tagline" name="voice_tagline" class="large-text"
						value="<?php echo esc_attr( $voice_tagline ); ?>"
						placeholder="WordPress developer focused on WooCommerce and headless builds">
					<p class="description">One-liner that captures your positioning. Injected into proposal drafts.</p>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Save Settings' ); ?>
	</form>
</div>

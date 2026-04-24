<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Fetch profile data from ACF
$profile = WorkOS_Profile::get()->get_data();
$exp     = $profile['experience'] ?? array();
$skills  = $profile['skills']     ?? array();
$projects = $profile['projects']  ?? array();
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Work OS — CV</h1>
	<a href="javascript:window.print()" class="page-title-action wo-no-print">Export PDF</a>
	<hr class="wp-header-end">

	<p class="description wo-no-print" style="margin-bottom:20px;font-size:13px;color:#646970">
		Data is pulled live from your WordPress About page. Edit content there — it reflects here instantly.
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>" style="margin-left:6px">Edit contact info →</a>
	</p>

	<?php if ( empty( $exp ) && empty( $skills ) ) : ?>
		<div class="notice notice-warning">
			<p>No profile data found. Make sure an <strong>About</strong> page exists with ACF experience and skills fields populated.</p>
		</div>
	<?php endif; ?>

	<!-- CV Preview -->
	<div class="wo-cv-preview" style="background:#fff;max-width:800px;padding:32px;box-shadow:0 1px 4px rgba(0,0,0,0.1);border:1px solid #c3c4c7;border-radius:4px">

		<!-- Header -->
		<div style="border-bottom:2px solid #1d2327;padding-bottom:16px;margin-bottom:20px">
			<h2 style="font-size:26px;font-weight:700;margin:0 0 4px;letter-spacing:0.02em;font-family:Georgia,serif">
				<?php echo esc_html( $profile['name'] ); ?>
			</h2>
			<div style="font-size:14px;font-weight:600;color:#2271b1;margin-bottom:6px">
				<?php echo esc_html( $profile['headline'] ); ?>
			</div>
			<?php
			$cv_address  = get_option( 'work_os_cv_address', '' );
			$cv_phone    = get_option( 'work_os_cv_phone', '' );
			$cv_email    = get_option( 'work_os_cv_email', get_option( 'admin_email' ) );
			$cv_linkedin = get_option( 'work_os_cv_linkedin', '' );
			$cv_github   = get_option( 'work_os_cv_github', '' );
			$contact_parts = array_filter( [ $cv_address ?: $profile['location'], $cv_phone, $cv_email ] );
			?>
			<div style="font-size:12px;color:#646970;margin-bottom:4px">
				<?php echo esc_html( implode( '  •  ', $contact_parts ) ); ?>
			</div>
			<?php if ( $cv_linkedin || $cv_github ) : ?>
			<div style="font-size:12px;color:#646970">
				<?php if ( $cv_linkedin ) : ?>
					<a href="<?php echo esc_url( $cv_linkedin ); ?>" style="color:#2271b1;text-decoration:none" target="_blank"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $cv_linkedin ) ); ?></a>
				<?php endif; ?>
				<?php if ( $cv_linkedin && $cv_github ) : ?>&nbsp;&nbsp;•&nbsp;&nbsp;<?php endif; ?>
				<?php if ( $cv_github ) : ?>
					<a href="<?php echo esc_url( $cv_github ); ?>" style="color:#2271b1;text-decoration:none" target="_blank"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', $cv_github ) ); ?></a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>

		<?php if ( $profile['summary'] ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:10px">Profile</div>
				<p style="font-size:13px;color:#333;line-height:1.65;margin:0"><?php echo esc_html( $profile['summary'] ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Experience -->
		<?php if ( ! empty( $exp ) ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:12px">Experience</div>
				<?php foreach ( $exp as $e ) : ?>
					<div style="margin-bottom:14px">
						<div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px">
							<div style="font-size:13px;font-weight:700">
								<?php echo esc_html( $e['company'] ); ?>
								<span style="font-weight:400;color:#555"> — <?php echo esc_html( $e['role'] ); ?></span>
							</div>
							<div style="font-size:11px;color:#888;white-space:nowrap"><?php echo esc_html( $e['period'] ); ?></div>
						</div>
						<?php if ( $e['description'] ) : ?>
							<div style="font-size:12px;color:#333;margin-top:3px;line-height:1.55"><?php echo esc_html( $e['description'] ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $e['tech'] ) ) : ?>
							<div style="font-size:11px;color:#646970;margin-top:3px"><?php echo esc_html( implode( ' · ', $e['tech'] ) ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Technical Skills (from tech CPT) -->
		<?php $tech_skills = $profile['tech_skills'] ?? array(); ?>
		<?php if ( ! empty( $tech_skills ) ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:10px">Technical Skills</div>
				<div style="font-size:12px;color:#333;line-height:1.7"><?php echo esc_html( implode( ' · ', $tech_skills ) ); ?></div>
			</div>
		<?php elseif ( ! empty( $skills ) ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:10px">Skills</div>
				<div style="font-size:12px;color:#333"><?php echo esc_html( implode( ' · ', $skills ) ); ?></div>
			</div>
		<?php endif; ?>

		<!-- Education -->
		<?php $education = $profile['education'] ?? array(); ?>
		<?php if ( ! empty( $education ) ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:12px">Education</div>
				<?php foreach ( $education as $edu ) : ?>
					<div style="margin-bottom:10px">
						<div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px">
							<div style="font-size:13px;font-weight:700"><?php echo esc_html( $edu['degree'] ); ?></div>
							<div style="font-size:11px;color:#888;white-space:nowrap"><?php echo esc_html( $edu['period'] ); ?></div>
						</div>
						<div style="font-size:12px;color:#646970"><?php echo esc_html( $edu['institution'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<!-- Languages -->
		<?php $languages = $profile['languages'] ?? array(); ?>
		<?php if ( ! empty( $languages ) ) : ?>
			<div>
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:10px">Languages</div>
				<div style="font-size:12px;color:#333">
					<?php foreach ( $languages as $lang ) : ?>
						<span style="margin-right:20px"><strong><?php echo esc_html( $lang['language'] ); ?></strong>&nbsp;&nbsp;<?php echo esc_html( $lang['level'] ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

	</div><!-- .wo-cv-preview -->
</div>

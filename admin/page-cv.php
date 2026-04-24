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

	<p class="description wo-no-print" style="margin-bottom:20px">
		Data is pulled live from your WordPress About page and Projects. Edit content there — it reflects here instantly.
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
			<div style="font-size:12px;color:#646970">
				<?php echo esc_html( $profile['location'] ); ?>
				&nbsp;&bull;&nbsp;
				<?php echo esc_html( $profile['email'] ); ?>
			</div>
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

		<!-- Skills -->
		<?php if ( ! empty( $skills ) ) : ?>
			<div style="margin-bottom:20px">
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:10px">Skills</div>
				<div style="font-size:12px;color:#333"><?php echo esc_html( implode( ' · ', $skills ) ); ?></div>
			</div>
		<?php endif; ?>

		<!-- Projects -->
		<?php if ( ! empty( $projects ) ) : ?>
			<div>
				<div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.1em;color:#888;border-bottom:1px solid #ddd;padding-bottom:4px;margin-bottom:12px">Projects</div>
				<?php foreach ( array_slice( $projects, 0, 4 ) as $p ) : ?>
					<div style="margin-bottom:12px">
						<div style="font-size:13px;font-weight:700">
							<?php echo esc_html( $p['title'] ); ?>
							<?php if ( $p['live_url'] ) : ?>
								<a href="<?php echo esc_url( $p['live_url'] ); ?>" style="font-size:11px;font-weight:400;margin-left:6px;color:#2271b1" target="_blank">↗ live</a>
							<?php endif; ?>
						</div>
						<?php if ( $p['challenge'] ) : ?>
							<div style="font-size:12px;color:#333;margin-top:2px;line-height:1.5"><?php echo esc_html( substr( $p['challenge'], 0, 200 ) ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $p['tech'] ) ) : ?>
							<div style="font-size:11px;color:#646970;margin-top:2px"><?php echo esc_html( implode( ' · ', $p['tech'] ) ); ?></div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div><!-- .wo-cv-preview -->
</div>

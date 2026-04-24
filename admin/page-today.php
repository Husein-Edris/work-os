<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$memory = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}work_os_memory ORDER BY created_at DESC LIMIT 5",
	ARRAY_A
) ?: array();

$proposals = $wpdb->get_results(
	"SELECT * FROM {$wpdb->prefix}work_os_proposals WHERE status IN ('draft','sent') ORDER BY created_at DESC LIMIT 10",
	ARRAY_A
) ?: array();

$status_colors = array(
	'draft'    => '#646970',
	'sent'     => '#2271b1',
	'won'      => '#00a32a',
	'lost'     => '#cc1818',
	'declined' => '#8c00d4',
);
?>
<div class="wrap">
	<h1 style="display:flex;align-items:baseline;gap:12px">
		Work OS
		<span style="font-size:13px;font-weight:400;color:#646970"><?php echo esc_html( date_i18n( 'l, j F Y' ) ); ?></span>
	</h1>
	<hr class="wp-header-end">

	<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:980px;margin-top:20px">

		<!-- Active Proposals -->
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">Active Proposals</h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-proposals' ) ); ?>" class="page-title-action" style="margin:10px 12px 0 0">All →</a>
			</div>
			<div class="inside" style="padding:0">
				<?php if ( empty( $proposals ) ) : ?>
					<p style="padding:20px 16px;color:#646970;margin:0;font-size:13px">
						No active proposals yet.
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-proposals' ) ); ?>" style="white-space:nowrap">Log one →</a>
					</p>
				<?php else : ?>
					<table class="widefat" style="border:none;border-radius:0">
						<tbody>
						<?php foreach ( $proposals as $p ) :
							$c = $status_colors[ $p['status'] ] ?? '#646970'; ?>
							<tr>
								<td style="padding:9px 14px">
									<strong style="font-size:13px;line-height:1.4"><?php echo esc_html( $p['title'] ); ?></strong>
									<?php if ( $p['company'] ) : ?>
										<br><span style="font-size:12px;color:#646970"><?php echo esc_html( $p['company'] ); ?></span>
									<?php endif; ?>
								</td>
								<td style="padding:9px 14px;text-align:right;white-space:nowrap;vertical-align:middle">
									<?php if ( $p['budget'] ) : ?>
										<span style="font-size:12px;color:#50575e;margin-right:8px"><?php echo esc_html( $p['budget'] ); ?></span>
									<?php endif; ?>
									<span class="wo-badge" style="background:<?php echo esc_attr( $c ); ?>18;color:<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $p['status'] ); ?></span>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<!-- Recent Memory -->
		<div class="postbox">
			<div class="postbox-header">
				<h2 class="hndle">Recent Memory</h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-memory' ) ); ?>" class="page-title-action" style="margin:10px 12px 0 0">All →</a>
			</div>
			<div class="inside" style="padding:0">
				<?php if ( empty( $memory ) ) : ?>
					<p style="padding:20px 16px;color:#646970;margin:0;font-size:13px">
						No memory events yet.
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-memory' ) ); ?>" style="white-space:nowrap">Add one →</a>
					</p>
				<?php else : ?>
					<table class="widefat" style="border:none;border-radius:0">
						<tbody>
						<?php foreach ( $memory as $ev ) : ?>
							<tr>
								<td style="padding:9px 14px">
									<div style="font-size:13px;color:#1d2327;line-height:1.5"><?php echo esc_html( wp_trim_words( $ev['note'], 14 ) ); ?></div>
									<div style="font-size:11px;color:#8c8f94;margin-top:3px">
										<?php echo esc_html( substr( $ev['created_at'], 0, 10 ) ); ?>
										&nbsp;&middot;&nbsp;<?php echo esc_html( $ev['kind'] ); ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<!-- Quick actions -->
		<div class="postbox" style="grid-column:1/-1">
			<div class="postbox-header"><h2 class="hndle">Quick Actions</h2></div>
			<div class="inside" style="padding:14px 16px 16px;display:flex;gap:10px;flex-wrap:wrap">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-research' ) ); ?>" class="button button-primary">Research a company</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-proposals' ) ); ?>" class="button">Log a proposal</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-memory' ) ); ?>" class="button">Add memory event</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-blog' ) ); ?>" class="button">Generate blog post</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-cv' ) ); ?>" class="button">View CV</a>
			</div>
		</div>

		<!-- Job feed placeholder -->
		<div class="postbox" style="grid-column:1/-1">
			<div class="postbox-header"><h2 class="hndle">Job Feed</h2></div>
			<div class="inside" style="padding:14px 16px 16px">
				<p style="margin:0;font-size:13px;color:#646970">
					<strong style="color:#1d2327">Live job feed coming soon.</strong>
					&nbsp;Connect Upwork credentials in
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Settings</a>
					to enable automatic job fetching from Upwork and LinkedIn.
				</p>
			</div>
		</div>

	</div>
</div>

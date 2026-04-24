<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table  = $wpdb->prefix . 'work_os_memory';
$events = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100", ARRAY_A ) ?: array();

$kinds = array( 'work', 'learning', 'milestone', 'personal', 'client' );

$kind_colors = array(
	'work'      => '#2271b1',
	'learning'  => '#00a32a',
	'milestone' => '#dba617',
	'personal'  => '#8c00d4',
	'client'    => '#cc1818',
);

// Pull profile for the snapshot card
$profile     = array();
$about_parts = array();
try {
	$profile_resp = WorkOS_Profile::get();
	if ( $profile_resp instanceof WP_REST_Response ) {
		$profile = $profile_resp->get_data();
	}
} catch( Exception $e ) {}

$voice_rate  = get_option( 'work_os_voice_rate', '€38/hr' );
$voice_niche = get_option( 'work_os_voice_niche', 'WordPress / WooCommerce' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Work OS — Memory</h1>
	<hr class="wp-header-end">

	<div style="display:grid;grid-template-columns:360px 1fr;gap:20px;margin-top:20px;align-items:start">

		<!-- ── Add event form ── -->
		<div style="position:sticky;top:32px">
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle">Add Memory Event</h2></div>
				<div class="inside" style="padding-bottom:16px">

					<div style="margin-bottom:14px">
						<label for="wo-note" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Note</label>
						<textarea id="wo-note" rows="5" style="width:100%;box-sizing:border-box;font-size:13px;line-height:1.6;resize:vertical" placeholder="What happened, what you learned, what you shipped…"></textarea>
					</div>

					<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
						<div>
							<label for="wo-kind" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Kind</label>
							<select id="wo-kind" style="width:100%">
								<?php foreach ( $kinds as $k ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>"><?php echo ucfirst( $k ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div>
							<label for="wo-tags" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Tags</label>
							<input type="text" id="wo-tags" style="width:100%;box-sizing:border-box" placeholder="wordpress, client…">
						</div>
					</div>

					<button id="wo-add-btn" class="button button-primary" style="width:100%;justify-content:center">Add event</button>
					<div id="wo-add-status" style="margin-top:8px;font-size:13px;min-height:18px;text-align:center"></div>

					<!-- Kind legend -->
					<div style="border-top:1px solid #f0f0f1;padding-top:12px;margin-top:12px;display:flex;flex-wrap:wrap;gap:6px">
						<?php foreach ( $kind_colors as $k => $c ) : ?>
							<span class="wo-badge" style="background:<?php echo esc_attr( $c ); ?>18;color:<?php echo esc_attr( $c ); ?>"><?php echo esc_html( $k ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- ── Feed ── -->
		<div id="wo-memory-feed">

			<!-- Profile snapshot card -->
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;margin-bottom:16px;overflow:hidden">
				<div style="padding:12px 16px 10px;background:#f0f6fc;border-bottom:1px solid #dcdcde;display:flex;justify-content:space-between;align-items:center">
					<div>
						<span style="font-size:13px;font-weight:700;color:#1d2327">What Work OS knows about you</span>
						<span style="font-size:11px;color:#2271b1;background:#2271b118;border-radius:10px;padding:2px 8px;margin-left:8px;font-weight:600">PROFILE SNAPSHOT</span>
					</div>
					<span style="font-size:12px;color:#8c8f94">Pulled from your WordPress site</span>
				</div>
				<div style="padding:16px 18px;display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;font-size:13px">

					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Identity</div>
						<div style="line-height:1.7;color:#1d2327">
							<strong><?php echo esc_html( $profile['name'] ?? 'Edris Husein' ); ?></strong><br>
							<?php echo esc_html( $profile['headline'] ?? 'WordPress & PHP Developer' ); ?><br>
							<span style="color:#646970"><?php echo esc_html( $profile['location'] ?? 'Dornbirn, Österreich' ); ?></span>
						</div>
					</div>

					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Freelance Profile</div>
						<div style="line-height:1.7;color:#1d2327">
							Rate: <strong><?php echo esc_html( $voice_rate ); ?></strong><br>
							Niche: <strong><?php echo esc_html( $voice_niche ); ?></strong><br>
							Email: <span style="color:#646970"><?php echo esc_html( get_option( 'work_os_cv_email', get_option( 'admin_email' ) ) ); ?></span>
						</div>
					</div>

					<?php if ( ! empty( $profile['experience'] ) ) : ?>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Experience</div>
						<div style="line-height:1.8;color:#1d2327">
							<?php foreach ( array_slice( $profile['experience'], 0, 3 ) as $e ) : ?>
								<div>
									<strong><?php echo esc_html( $e['company'] ); ?></strong>
									<span style="color:#646970"> — <?php echo esc_html( $e['role'] ); ?></span>
									<span style="font-size:11px;color:#8c8f94;margin-left:6px"><?php echo esc_html( $e['period'] ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

					<?php
					$tech_skills = $profile['tech_skills'] ?? array();
					$soft_skills = $profile['skills'] ?? array();
					$display_skills = ! empty( $tech_skills ) ? $tech_skills : $soft_skills;
					if ( ! empty( $display_skills ) ) :
					?>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Technical Skills</div>
						<div style="color:#1d2327;line-height:1.8">
							<?php echo esc_html( implode( ' · ', array_slice( $display_skills, 0, 20 ) ) ); ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $profile['education'] ) ) : ?>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Education</div>
						<div style="line-height:1.8;color:#1d2327">
							<?php foreach ( $profile['education'] as $edu ) : ?>
								<div>
									<strong><?php echo esc_html( $edu['degree'] ); ?></strong>
									<span style="color:#646970"> — <?php echo esc_html( $edu['institution'] ); ?></span>
									<span style="font-size:11px;color:#8c8f94;margin-left:6px"><?php echo esc_html( $edu['period'] ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $profile['languages'] ) ) : ?>
					<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Languages</div>
						<div style="line-height:1.8;color:#1d2327">
							<?php foreach ( $profile['languages'] as $lang ) : ?>
								<span style="margin-right:16px"><strong><?php echo esc_html( $lang['language'] ); ?></strong> <span style="color:#646970"><?php echo esc_html( $lang['level'] ); ?></span></span>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $profile['summary'] ) ) : ?>
					<div style="grid-column:1/-1;border-top:1px solid #f0f0f1;padding-top:12px">
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:6px">Profile Summary</div>
						<div style="color:#1d2327;line-height:1.65;font-size:13px"><?php echo esc_html( $profile['summary'] ); ?></div>
					</div>
					<?php endif; ?>

				</div>
			</div>

			<!-- Dynamic memory events -->
			<?php if ( empty( $events ) ) : ?>
				<div style="padding:40px 20px;text-align:center;color:#646970;font-size:13px;background:#f8f9fa;border:1px solid #dcdcde;border-radius:4px">
					No memory events yet. Use the form on the left to log what you learn, ship, or achieve.
				</div>
			<?php else : ?>
				<?php foreach ( $events as $ev ) :
					$kc       = $kind_colors[ $ev['kind'] ] ?? '#646970';
					$date_fmt = date_i18n( 'j M Y', strtotime( $ev['created_at'] ) );
				?>
				<div id="wo-event-<?php echo (int) $ev['id']; ?>" style="background:#fff;border:1px solid #dcdcde;border-radius:4px;margin-bottom:10px;overflow:hidden">
					<div style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:#f8f9fa;border-bottom:1px solid #f0f0f1">
						<div style="display:flex;align-items:center;gap:10px">
							<span class="wo-badge" style="background:<?php echo esc_attr( $kc ); ?>18;color:<?php echo esc_attr( $kc ); ?>"><?php echo esc_html( $ev['kind'] ); ?></span>
							<span style="font-size:12px;color:#8c8f94"><?php echo esc_html( $date_fmt ); ?></span>
							<?php if ( $ev['tags'] ) : ?>
								<span style="font-size:11px;color:#8c8f94">&middot; <?php echo esc_html( $ev['tags'] ); ?></span>
							<?php endif; ?>
						</div>
						<button class="button button-small wo-delete-btn" data-id="<?php echo (int) $ev['id']; ?>" style="color:#cc1818;border-color:#cc181833">×</button>
					</div>
					<div style="padding:13px 16px;font-size:13px;color:#1d2327;line-height:1.7;white-space:pre-wrap"><?php echo esc_html( $ev['note'] ); ?></div>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>

		</div><!-- /#wo-memory-feed -->

	</div>
</div>

<script>
(function() {
	const cfg    = window.workOsConfig;
	const addBtn = document.getElementById('wo-add-btn');
	const status = document.getElementById('wo-add-status');
	const feed   = document.getElementById('wo-memory-feed');

	const colors = { work:'#2271b1', learning:'#00a32a', milestone:'#dba617', personal:'#8c00d4', client:'#cc1818' };

	addBtn.addEventListener('click', async function() {
		const note = document.getElementById('wo-note').value.trim();
		const kind = document.getElementById('wo-kind').value;
		const tags = document.getElementById('wo-tags').value.trim();
		if ( ! note ) { status.style.color = '#cc1818'; status.textContent = 'Note cannot be empty.'; return; }

		addBtn.disabled = true;
		status.style.color = '#646970';
		status.textContent = 'Saving…';

		try {
			const res = await fetch( cfg.apiUrl + '/memory', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ note, kind, tags }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			// Remove empty-state placeholder if present
			const empty = feed.querySelector('div[style*="text-align:center"]');
			if ( empty ) empty.remove();

			const kc      = colors[data.kind] || '#646970';
			const dateStr = new Date(data.created_at).toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
			const card    = document.createElement('div');
			card.id       = 'wo-event-' + data.id;
			card.style.cssText = 'background:#fff;border:1px solid #dcdcde;border-radius:4px;margin-bottom:10px;overflow:hidden';
			card.innerHTML = `
				<div style="display:flex;justify-content:space-between;align-items:center;padding:9px 14px;background:#f8f9fa;border-bottom:1px solid #f0f0f1">
					<div style="display:flex;align-items:center;gap:10px">
						<span class="wo-badge" style="background:${kc}18;color:${kc}">${data.kind}</span>
						<span style="font-size:12px;color:#8c8f94">${dateStr}</span>
						${data.tags ? `<span style="font-size:11px;color:#8c8f94">· ${esc(data.tags)}</span>` : ''}
					</div>
					<button class="button button-small wo-delete-btn" data-id="${data.id}" style="color:#cc1818;border-color:#cc181833">×</button>
				</div>
				<div style="padding:13px 16px;font-size:13px;color:#1d2327;line-height:1.7;white-space:pre-wrap">${esc(data.note)}</div>
			`;

			// Insert after the profile snapshot card
			const snapshot = feed.firstElementChild;
			if ( snapshot ) {
				snapshot.insertAdjacentElement('afterend', card);
			} else {
				feed.insertBefore( card, feed.firstChild );
			}

			document.getElementById('wo-note').value = '';
			document.getElementById('wo-tags').value = '';
			status.style.color = '#00a32a';
			status.textContent = '✓ Saved.';
			setTimeout( () => status.textContent = '', 2500 );
			attachDelete( card.querySelector('.wo-delete-btn') );
		} catch (e) {
			status.style.color = '#cc1818';
			status.textContent = e.message;
		} finally {
			addBtn.disabled = false;
		}
	});

	function attachDelete(btn) {
		btn.addEventListener('click', async function() {
			if ( ! confirm('Delete this memory event?') ) return;
			const id   = this.dataset.id;
			const card = document.getElementById('wo-event-' + id);
			try {
				await fetch( cfg.apiUrl + '/memory/' + id, {
					method: 'DELETE',
					headers: { 'X-WP-Nonce': cfg.nonce },
				});
				if ( card ) card.remove();
				// Show empty state only if no more event cards remain
				const remaining = feed.querySelectorAll('[id^="wo-event-"]');
				if ( remaining.length === 0 ) {
					const empty = document.createElement('div');
					empty.style.cssText = 'padding:40px 20px;text-align:center;color:#646970;font-size:13px;background:#f8f9fa;border:1px solid #dcdcde;border-radius:4px';
					empty.textContent = 'No memory events yet. Use the form on the left to log what you learn, ship, or achieve.';
					feed.appendChild(empty);
				}
			} catch(e) { alert('Could not delete event.'); }
		});
	}

	function esc(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	}

	document.querySelectorAll('.wo-delete-btn').forEach(attachDelete);
})();
</script>

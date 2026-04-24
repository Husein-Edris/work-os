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
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Work OS — Memory</h1>
	<hr class="wp-header-end">

	<div style="max-width:760px">

		<!-- Add event form -->
		<div class="postbox" style="margin-top:20px">
			<div class="postbox-header"><h2 class="hndle">Add memory event</h2></div>
			<div class="inside">
				<table class="form-table" style="margin:0">
					<tr>
						<th style="width:80px"><label for="wo-note">Note</label></th>
						<td><textarea id="wo-note" rows="3" class="large-text" placeholder="What happened, what you learned, what you shipped…"></textarea></td>
					</tr>
					<tr>
						<th><label for="wo-kind">Kind</label></th>
						<td>
							<select id="wo-kind">
								<?php foreach ( $kinds as $k ) : ?>
									<option value="<?php echo esc_attr( $k ); ?>"><?php echo ucfirst( $k ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="wo-tags">Tags</label></th>
						<td><input type="text" id="wo-tags" class="regular-text" placeholder="optional, comma-separated"></td>
					</tr>
				</table>
				<p><button id="wo-add-btn" class="button button-primary">Add event</button>
				<span id="wo-add-status" style="margin-left:10px;font-size:13px"></span></p>
			</div>
		</div>

		<!-- Events list -->
		<table class="widefat striped" id="wo-memory-table">
			<thead>
				<tr>
					<th>Date</th>
					<th>Kind</th>
					<th>Note</th>
					<th>Tags</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $events ) ) : ?>
					<tr><td colspan="5" style="color:#646970;padding:24px 16px;font-size:13px">No events yet. Add one above.</td></tr>
				<?php else : ?>
					<?php foreach ( $events as $ev ) :
						$kc = $kind_colors[ $ev['kind'] ] ?? '#646970'; ?>
						<tr id="wo-event-<?php echo (int) $ev['id']; ?>">
							<td style="white-space:nowrap;color:#8c8f94;font-size:12px;vertical-align:top;padding-top:10px"><?php echo esc_html( substr( $ev['created_at'], 0, 10 ) ); ?></td>
							<td style="vertical-align:top;padding-top:9px">
								<span class="wo-badge" style="background:<?php echo esc_attr( $kc ); ?>18;color:<?php echo esc_attr( $kc ); ?>">
									<?php echo esc_html( $ev['kind'] ); ?>
								</span>
							</td>
							<td style="font-size:13px;line-height:1.55"><?php echo esc_html( $ev['note'] ); ?></td>
							<td style="color:#8c8f94;font-size:12px"><?php echo esc_html( $ev['tags'] ); ?></td>
							<td>
								<button class="button button-small wo-delete-btn" data-id="<?php echo (int) $ev['id']; ?>" style="color:#cc1818;border-color:#cc181833">×</button>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>

<script>
(function() {
	const cfg    = window.workOsConfig;
	const addBtn = document.getElementById('wo-add-btn');
	const status = document.getElementById('wo-add-status');

	addBtn.addEventListener('click', async function() {
		const note = document.getElementById('wo-note').value.trim();
		const kind = document.getElementById('wo-kind').value;
		const tags = document.getElementById('wo-tags').value.trim();
		if ( ! note ) { status.textContent = 'Note cannot be empty.'; return; }

		addBtn.disabled = true;
		status.textContent = 'Saving…';

		try {
			const res = await fetch( cfg.apiUrl + '/memory', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ note, kind, tags }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			const colors = { work:'#2271b1', learning:'#00a32a', milestone:'#dba617', personal:'#8c00d4', client:'#cc1818' };
			const tbody   = document.querySelector('#wo-memory-table tbody');
			const noRow   = tbody.querySelector('td[colspan]');
			if ( noRow ) noRow.closest('tr').remove();

			const tr = document.createElement('tr');
			tr.id = 'wo-event-' + data.id;
			const kc = colors[data.kind] || '#646970';
			tr.innerHTML = `
				<td style="white-space:nowrap;color:#8c8f94;font-size:12px;vertical-align:top;padding-top:10px">${data.created_at.substring(0,10)}</td>
				<td style="vertical-align:top;padding-top:9px"><span class="wo-badge" style="background:${kc}18;color:${kc}">${data.kind}</span></td>
				<td style="font-size:13px;line-height:1.55">${data.note.replace(/</g,'&lt;')}</td>
				<td style="color:#8c8f94;font-size:12px">${(data.tags||'').replace(/</g,'&lt;')}</td>
				<td><button class="button button-small wo-delete-btn" data-id="${data.id}" style="color:#cc1818;border-color:#cc181833">×</button></td>
			`;
			tbody.insertBefore( tr, tbody.firstChild );

			document.getElementById('wo-note').value = '';
			document.getElementById('wo-tags').value = '';
			status.textContent = 'Saved.';
			setTimeout( () => status.textContent = '', 2000 );
			attachDelete( tr.querySelector('.wo-delete-btn') );
		} catch (e) {
			status.textContent = e.message;
		} finally {
			addBtn.disabled = false;
		}
	});

	function attachDelete(btn) {
		btn.addEventListener('click', async function() {
			if ( ! confirm('Delete this event?') ) return;
			const id  = this.dataset.id;
			const row = document.getElementById('wo-event-' + id);
			try {
				await fetch( cfg.apiUrl + '/memory/' + id, {
					method: 'DELETE',
					headers: { 'X-WP-Nonce': cfg.nonce },
				});
				if ( row ) row.remove();
			} catch(e) { alert('Could not delete event.'); }
		});
	}

	document.querySelectorAll('.wo-delete-btn').forEach(attachDelete);
})();
</script>

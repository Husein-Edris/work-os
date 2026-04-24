<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table     = $wpdb->prefix . 'work_os_proposals';
$proposals = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A ) ?: array();

$statuses = array( 'draft', 'sent', 'won', 'lost', 'declined' );
$sources  = array( 'upwork', 'direct', 'linkedin', 'referral', 'other' );

$status_colors = array(
	'draft'    => '#646970',
	'sent'     => '#2271b1',
	'won'      => '#00a32a',
	'lost'     => '#cc1818',
	'declined' => '#8c00d4',
);
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Work OS — Proposals</h1>
	<hr class="wp-header-end">

	<div style="max-width:1040px">

		<div class="postbox" style="margin-top:20px">
			<div class="postbox-header"><h2 class="hndle">Log proposal</h2></div>
			<div class="inside">
				<div style="display:grid;grid-template-columns:1fr 1fr;gap:0 24px">
					<table class="form-table" style="margin:0">
						<tr>
							<th style="width:80px"><label for="wo-p-title">Title</label></th>
							<td><input type="text" id="wo-p-title" class="large-text" placeholder="WooCommerce subscription plugin rebuild"></td>
						</tr>
						<tr>
							<th><label for="wo-p-company">Client</label></th>
							<td><input type="text" id="wo-p-company" class="regular-text" placeholder="Company or client name"></td>
						</tr>
						<tr>
							<th><label for="wo-p-budget">Budget</label></th>
							<td><input type="text" id="wo-p-budget" class="regular-text" placeholder="€1 500 or €38/hr"></td>
						</tr>
					</table>
					<table class="form-table" style="margin:0">
						<tr>
							<th style="width:80px"><label for="wo-p-source">Source</label></th>
							<td>
								<select id="wo-p-source">
									<?php foreach ( $sources as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="wo-p-status">Status</label></th>
							<td>
								<select id="wo-p-status">
									<?php foreach ( $statuses as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="wo-p-url">Job URL</label></th>
							<td><input type="url" id="wo-p-url" class="large-text" placeholder="https://"></td>
						</tr>
					</table>
				</div>
				<table class="form-table" style="margin:0">
					<tr>
						<th style="width:80px"><label for="wo-p-notes">Notes</label></th>
						<td><textarea id="wo-p-notes" rows="2" class="large-text" placeholder="Key context, what they need, red flags…"></textarea></td>
					</tr>
				</table>
				<p>
					<button id="wo-add-proposal-btn" class="button button-primary">Save proposal</button>
					<button id="wo-draft-btn" class="button" style="margin-left:8px">Draft with Claude →</button>
					<span id="wo-proposal-status" style="margin-left:10px;font-size:13px"></span>
				</p>

				<div id="wo-draft-panel" style="display:none;margin-top:12px">
					<div style="background:#f8f9fa;border:1px solid #c3c4c7;border-radius:4px;padding:16px">
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
							<strong style="font-size:13px">Claude Draft</strong>
							<button id="wo-draft-copy-btn" class="button button-small">Copy</button>
						</div>
						<div id="wo-draft-status" style="font-size:13px;color:#646970;margin-bottom:8px"></div>
						<textarea id="wo-draft-output" rows="12" class="large-text" style="font-size:13px;line-height:1.65"></textarea>
					</div>
				</div>
			</div>
		</div>

		<table class="widefat striped" id="wo-proposals-table">
			<thead>
				<tr>
					<th style="width:90px">Date</th>
					<th>Title / Client</th>
					<th style="width:100px">Budget</th>
					<th style="width:80px">Source</th>
					<th style="width:90px">Status</th>
					<th>Notes</th>
					<th style="width:160px"></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $proposals ) ) : ?>
					<tr><td colspan="7" style="color:#646970;padding:20px">No proposals yet. Log one above.</td></tr>
				<?php else : ?>
					<?php foreach ( $proposals as $p ) :
						$c = $status_colors[ $p['status'] ] ?? '#646970'; ?>
						<tr id="wo-proposal-<?php echo (int) $p['id']; ?>">
							<td style="white-space:nowrap;color:#646970;font-size:12px"><?php echo esc_html( substr( $p['created_at'], 0, 10 ) ); ?></td>
							<td>
								<strong style="font-size:13px"><?php echo esc_html( $p['title'] ); ?></strong>
								<?php if ( $p['company'] ) : ?>
									<br><span style="font-size:12px;color:#646970"><?php echo esc_html( $p['company'] ); ?></span>
								<?php endif; ?>
								<?php if ( $p['job_url'] ) : ?>
									<br><a href="<?php echo esc_url( $p['job_url'] ); ?>" target="_blank" style="font-size:11px">↗ Job link</a>
								<?php endif; ?>
							</td>
							<td style="font-size:12px"><?php echo esc_html( $p['budget'] ); ?></td>
							<td style="font-size:12px;color:#646970"><?php echo esc_html( $p['source'] ); ?></td>
							<td>
								<span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;text-transform:uppercase;background:<?php echo esc_attr( $c ); ?>22;color:<?php echo esc_attr( $c ); ?>">
									<?php echo esc_html( $p['status'] ); ?>
								</span>
							</td>
							<td style="font-size:12px;color:#646970;max-width:200px"><?php echo esc_html( wp_trim_words( $p['notes'], 10 ) ); ?></td>
							<td style="white-space:nowrap">
								<select class="wo-status-select" data-id="<?php echo (int) $p['id']; ?>" style="font-size:12px">
									<?php foreach ( $statuses as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $p['status'] ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<button class="button button-small wo-delete-proposal-btn" data-id="<?php echo (int) $p['id']; ?>" style="color:#cc1818;border-color:#cc1818;margin-left:4px">×</button>
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
	const cfg = window.workOsConfig;

	// Pre-fill from research page
	try {
		const prefill = sessionStorage.getItem('wo_prefill_proposal');
		if ( prefill ) {
			const d = JSON.parse(prefill);
			if (d.company) document.getElementById('wo-p-company').value = d.company;
			if (d.notes)   document.getElementById('wo-p-notes').value   = String(d.notes).substring(0, 600);
			sessionStorage.removeItem('wo_prefill_proposal');
		}
	} catch(e) {}

	document.getElementById('wo-add-proposal-btn').addEventListener('click', async function() {
		const payload = getFormData();
		if ( ! payload.title ) { document.getElementById('wo-proposal-status').textContent = 'Title is required.'; return; }
		this.disabled = true;
		document.getElementById('wo-proposal-status').textContent = 'Saving…';
		try {
			const res  = await fetch( cfg.apiUrl + '/proposals', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify(payload),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );
			prependRow(data);
			clearForm();
			document.getElementById('wo-proposal-status').textContent = 'Saved.';
			setTimeout(() => document.getElementById('wo-proposal-status').textContent = '', 2000);
		} catch (e) {
			document.getElementById('wo-proposal-status').textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-draft-btn').addEventListener('click', async function() {
		const payload   = getFormData();
		const panel     = document.getElementById('wo-draft-panel');
		const statusEl  = document.getElementById('wo-draft-status');
		const outputEl  = document.getElementById('wo-draft-output');
		panel.style.display = 'block';
		statusEl.textContent = 'Generating draft with Claude…';
		outputEl.value = '';
		this.disabled = true;
		try {
			const res  = await fetch( cfg.apiUrl + '/proposals/draft', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify(payload),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );
			outputEl.value = data.draft || '';
			statusEl.textContent = '';
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-draft-copy-btn').addEventListener('click', function() {
		const text = document.getElementById('wo-draft-output').value;
		navigator.clipboard.writeText(text).then(() => {
			this.textContent = 'Copied!';
			setTimeout(() => this.textContent = 'Copy', 1500);
		});
	});

	function getFormData() {
		return {
			title:   document.getElementById('wo-p-title').value.trim(),
			company: document.getElementById('wo-p-company').value.trim(),
			budget:  document.getElementById('wo-p-budget').value.trim(),
			source:  document.getElementById('wo-p-source').value,
			status:  document.getElementById('wo-p-status').value,
			job_url: document.getElementById('wo-p-url').value.trim(),
			notes:   document.getElementById('wo-p-notes').value.trim(),
		};
	}

	function clearForm() {
		['wo-p-title','wo-p-company','wo-p-budget','wo-p-url','wo-p-notes'].forEach(id => {
			document.getElementById(id).value = '';
		});
	}

	const statusColors = { draft:'#646970', sent:'#2271b1', won:'#00a32a', lost:'#cc1818', declined:'#8c00d4' };

	function prependRow(p) {
		const tbody = document.querySelector('#wo-proposals-table tbody');
		const noRow = tbody.querySelector('td[colspan]');
		if (noRow) noRow.closest('tr').remove();

		const c  = statusColors[p.status] || '#646970';
		const tr = document.createElement('tr');
		tr.id = 'wo-proposal-' + p.id;
		tr.innerHTML = `
			<td style="white-space:nowrap;color:#646970;font-size:12px">${p.created_at.substring(0,10)}</td>
			<td><strong style="font-size:13px">${esc(p.title)}</strong>${p.company?'<br><span style="font-size:12px;color:#646970">'+esc(p.company)+'</span>':''}</td>
			<td style="font-size:12px">${esc(p.budget||'')}</td>
			<td style="font-size:12px;color:#646970">${esc(p.source||'')}</td>
			<td><span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;text-transform:uppercase;background:${c}22;color:${c}">${esc(p.status)}</span></td>
			<td style="font-size:12px;color:#646970"></td>
			<td style="white-space:nowrap">
				<select class="wo-status-select" data-id="${p.id}" style="font-size:12px">
					${['draft','sent','won','lost','declined'].map(s=>`<option value="${s}"${s===p.status?' selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`).join('')}
				</select>
				<button class="button button-small wo-delete-proposal-btn" data-id="${p.id}" style="color:#cc1818;border-color:#cc1818;margin-left:4px">×</button>
			</td>
		`;
		tbody.insertBefore(tr, tbody.firstChild);
		attachRowEvents(tr);
	}

	function esc(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	}

	function attachRowEvents(tr) {
		const sel = tr.querySelector('.wo-status-select');
		if (sel) sel.addEventListener('change', async function() {
			await fetch( cfg.apiUrl + '/proposals/' + this.dataset.id, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ status: this.value }),
			});
		});

		const btn = tr.querySelector('.wo-delete-proposal-btn');
		if (btn) btn.addEventListener('click', async function() {
			if ( ! confirm('Delete this proposal?') ) return;
			const id  = this.dataset.id;
			await fetch( cfg.apiUrl + '/proposals/' + id, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': cfg.nonce },
			});
			const row = document.getElementById('wo-proposal-' + id);
			if (row) row.remove();
		});
	}

	document.querySelectorAll('#wo-proposals-table tr[id]').forEach(attachRowEvents);
})();
</script>

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

	<div style="margin-top:20px">

		<!-- STEP 1: Paste & Extract -->
		<div class="postbox" id="wo-paste-box">
			<div class="postbox-header" style="display:flex;justify-content:space-between;align-items:center">
				<h2 class="hndle" id="wo-paste-heading">Step 1 — Paste job listing or message</h2>
				<span style="margin:10px 14px 0 0;font-size:12px;color:#646970">
					or <a href="#" id="wo-manual-link">fill manually ↓</a>
				</span>
			</div>
			<div class="inside">
				<textarea id="wo-raw-text" rows="6" class="large-text" style="font-size:13px" placeholder="Paste the full job listing, Upwork brief, LinkedIn DM, or email. Claude extracts title, company, budget, source, and writes a context summary automatically."></textarea>
				<p>
					<button id="wo-extract-btn" class="button button-primary">Extract with Claude →</button>
					<span id="wo-extract-status" style="margin-left:10px;font-size:13px;color:#646970"></span>
				</p>
			</div>
		</div>

		<!-- STEP 2: Job Details -->
		<div class="postbox" id="wo-form-box">
			<div class="postbox-header"><h2 class="hndle">Step 2 — Job Details</h2></div>
			<div class="inside">
				<table class="form-table" style="margin:0">
					<tr>
						<th style="width:90px"><label for="wo-p-title">Title</label></th>
						<td><input type="text" id="wo-p-title" class="large-text" placeholder="WooCommerce subscription plugin rebuild"></td>
					</tr>
					<tr>
						<th><label for="wo-p-company">Company</label></th>
						<td>
							<div style="display:flex;gap:8px;align-items:center">
								<input type="text" id="wo-p-company" class="regular-text" placeholder="Company or client" style="flex:1">
								<button type="button" id="wo-research-btn" class="button button-primary" style="white-space:nowrap;flex-shrink:0">Research with Gemini →</button>
							</div>
						</td>
					</tr>
					<tr>
						<th><label for="wo-p-budget">Budget</label></th>
						<td><input type="text" id="wo-p-budget" class="regular-text" placeholder="€1 500 or €38/hr"></td>
					</tr>
					<tr>
						<th><label for="wo-p-source">Source</label></th>
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
					<tr>
						<th><label for="wo-p-notes">Notes</label></th>
						<td><textarea id="wo-p-notes" rows="10" class="large-text" style="min-height:160px;resize:vertical" placeholder="Requirements, tech stack, context, red flags…"></textarea></td>
					</tr>
				</table>
			</div>
		</div>

		<!-- STEP 3: Research -->
		<div id="wo-research-box" class="postbox">
			<div class="postbox-header"><h2 class="hndle" id="wo-research-heading">Step 3 — Research</h2></div>
			<div class="inside" style="padding:0">
				<div id="wo-research-placeholder" style="padding:16px;font-size:13px;color:#8c8f94">
					Fill in Company above, then click <strong style="color:#1d2327">Research with Gemini →</strong> to research the company and surface tech stack, team size, recent news, and red flags.
				</div>
				<div id="wo-research-status" style="padding:12px 16px;font-size:13px;color:#646970;display:none"></div>
				<div id="wo-research-output" class="wo-output" style="display:none;margin:0 16px 16px;max-height:420px"></div>
				<div style="padding:0 16px 14px">
					<button id="wo-analyse-btn" class="button button-primary button-large" style="display:none;width:100%">Analyse fit with Claude →</button>
				</div>
			</div>
		</div>

		<!-- STEP 3b: Fit Analysis (hidden until triggered) -->
		<div id="wo-analysis-box" style="display:none" class="postbox">
			<div class="postbox-header"><h2 class="hndle">Fit Analysis</h2></div>
			<div class="inside" style="padding:0">
				<div id="wo-analysis-status" style="padding:12px 16px;font-size:13px;color:#646970;display:none"></div>
				<div id="wo-analysis-output" class="wo-output" style="margin:0 16px 16px;max-height:420px"></div>
			</div>
		</div>

		<!-- STEP 4: Draft & Save -->
		<div class="postbox" id="wo-draft-save-box">
			<div class="postbox-header" style="display:flex;justify-content:space-between;align-items:center">
				<h2 class="hndle">Step 4 — Draft &amp; Save</h2>
				<div style="display:flex;gap:8px;align-items:center;margin:10px 12px 0 0">
					<button id="wo-draft-btn" class="button button-primary">Draft with Claude →</button>
					<button id="wo-add-proposal-btn" class="button">Save proposal</button>
					<span id="wo-proposal-status" style="font-size:13px;line-height:28px"></span>
				</div>
			</div>
			<div class="inside" style="padding:12px 16px 16px">
				<div id="wo-draft-status" style="font-size:13px;color:#646970;margin-bottom:8px;display:none"></div>
				<textarea id="wo-draft-output" rows="10" class="large-text" style="font-size:13px;line-height:1.65" placeholder="Your Claude-drafted proposal appears here. Edit it before saving or copying."></textarea>
				<p style="margin:8px 0 0">
					<button id="wo-draft-copy-btn" class="button button-small">Copy draft</button>
					<button id="wo-reset-btn" class="button button-small" style="margin-left:8px;color:#646970">Clear form</button>
				</p>
			</div>
		</div>

		<!-- Proposals table -->
		<table class="widefat striped" id="wo-proposals-table">
			<thead>
				<tr>
					<th style="width:90px">Date</th>
					<th>Title / Client</th>
					<th style="width:100px">Budget</th>
					<th style="width:80px">Source</th>
					<th style="width:90px">Status</th>
					<th style="width:180px">Notes</th>
					<th style="width:180px"></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $proposals ) ) : ?>
					<tr><td colspan="7" style="color:#646970;padding:20px">No proposals yet. Paste a job above to get started.</td></tr>
				<?php else : ?>
					<?php foreach ( $proposals as $p ) :
						$c = $status_colors[ $p['status'] ] ?? '#646970'; ?>
						<tr id="wo-proposal-<?php echo (int) $p['id']; ?>" style="cursor:default">
							<td style="white-space:nowrap;color:#8c8f94;font-size:12px;vertical-align:top;padding-top:10px"><?php echo esc_html( substr( $p['created_at'], 0, 10 ) ); ?></td>
							<td style="vertical-align:top;padding-top:9px">
								<strong style="font-size:13px"><?php echo esc_html( $p['title'] ); ?></strong>
								<?php if ( $p['company'] ) : ?>
									<br><span style="font-size:12px;color:#646970"><?php echo esc_html( $p['company'] ); ?></span>
								<?php endif; ?>
								<?php if ( $p['job_url'] ) : ?>
									<br><a href="<?php echo esc_url( $p['job_url'] ); ?>" target="_blank" style="font-size:11px">↗ Job link</a>
								<?php endif; ?>
							</td>
							<td style="font-size:12px;vertical-align:top;padding-top:10px"><?php echo esc_html( $p['budget'] ); ?></td>
							<td style="font-size:12px;color:#646970;vertical-align:top;padding-top:10px"><?php echo esc_html( $p['source'] ); ?></td>
							<td style="vertical-align:top;padding-top:9px">
								<span class="wo-badge" style="background:<?php echo esc_attr( $c ); ?>18;color:<?php echo esc_attr( $c ); ?>">
									<?php echo esc_html( $p['status'] ); ?>
								</span>
							</td>
							<td style="font-size:12px;color:#646970;max-width:180px;vertical-align:top;padding-top:10px"><?php echo esc_html( wp_trim_words( $p['notes'], 12 ) ); ?></td>
							<td style="white-space:nowrap;vertical-align:top;padding-top:8px">
								<button class="button button-small wo-view-btn" data-id="<?php echo (int) $p['id']; ?>">View ↓</button>
								<select class="wo-status-select" data-id="<?php echo (int) $p['id']; ?>" style="font-size:12px;margin-left:4px">
									<?php foreach ( $statuses as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>" <?php selected( $s, $p['status'] ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<button class="button button-small wo-delete-proposal-btn" data-id="<?php echo (int) $p['id']; ?>" style="color:#cc1818;border-color:#cc181833;margin-left:4px">×</button>
							</td>
						</tr>
						<tr id="wo-detail-<?php echo (int) $p['id']; ?>" style="display:none">
							<td colspan="7" style="padding:0;border-top:none">
								<div style="background:#f8f9fa;border-top:2px solid #2271b1;padding:20px 24px 24px;display:grid;grid-template-columns:1fr 1fr;gap:20px">

									<?php if ( $p['notes'] ) : ?>
									<div>
										<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:8px">Notes</div>
										<div style="font-size:13px;color:#1d2327;line-height:1.65;white-space:pre-wrap"><?php echo esc_html( $p['notes'] ); ?></div>
									</div>
									<?php endif; ?>

									<?php if ( $p['draft'] ) : ?>
									<div>
										<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
											<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94">Draft</span>
											<button class="button button-small wo-copy-draft-btn" data-id="<?php echo (int) $p['id']; ?>">Copy</button>
										</div>
										<div style="font-size:13px;color:#1d2327;line-height:1.65;white-space:pre-wrap;max-height:300px;overflow-y:auto;background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 14px"><?php echo esc_html( $p['draft'] ); ?></div>
									</div>
									<?php endif; ?>

									<?php if ( $p['analysis'] ) : ?>
									<div style="<?php echo ( $p['notes'] && ! $p['draft'] ) ? '' : 'grid-column:1/-1'; ?>">
										<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:8px">Fit Analysis</div>
										<div style="font-size:13px;color:#1d2327;line-height:1.65;white-space:pre-wrap;max-height:200px;overflow-y:auto;background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 14px"><?php echo esc_html( $p['analysis'] ); ?></div>
									</div>
									<?php endif; ?>

									<?php if ( ! $p['notes'] && ! $p['draft'] && ! $p['analysis'] ) : ?>
									<div style="grid-column:1/-1;color:#646970;font-size:13px">No notes, draft, or analysis saved for this proposal.</div>
									<?php endif; ?>

								</div>
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
	let researchText  = '';
	let analysisText  = '';
	let currentLogId  = 0;

	// ── renderMarkdown ────────────────────────────────────────────────────
	function renderMarkdown(text) {
		if (!text) return '';
		// Escape HTML entities first so we don't accidentally execute user content
		// (API output is trusted, but we still escape < > & to be safe before adding our own tags)
		const escaped = text
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;');

		const lines   = escaped.split('\n');
		const output  = [];
		let inList     = false;

		for (let i = 0; i < lines.length; i++) {
			let line = lines[i];

			// Headings
			if (/^### /.test(line)) {
				if (inList) { output.push('</ul>'); inList = false; }
				output.push('<h4 style="margin:12px 0 4px;font-size:13px">' + line.slice(4) + '</h4>');
				continue;
			}
			if (/^## /.test(line)) {
				if (inList) { output.push('</ul>'); inList = false; }
				output.push('<h3 style="margin:14px 0 4px;font-size:14px">' + line.slice(3) + '</h3>');
				continue;
			}
			if (/^# /.test(line)) {
				if (inList) { output.push('</ul>'); inList = false; }
				output.push('<h2 style="margin:16px 0 6px;font-size:15px">' + line.slice(2) + '</h2>');
				continue;
			}

			// List items (- or *)
			if (/^[-*] /.test(line)) {
				if (!inList) { output.push('<ul style="margin:4px 0 4px 18px;padding:0">'); inList = true; }
				output.push('<li>' + applyInline(line.slice(2)) + '</li>');
				continue;
			}

			// Numbered list items
			if (/^\d+\. /.test(line)) {
				if (inList) { output.push('</ul>'); inList = false; }
				// Just treat as paragraph with bold number
				output.push('<p style="margin:4px 0">' + applyInline(line) + '</p>');
				continue;
			}

			// Empty line — close list and add spacing
			if (line.trim() === '') {
				if (inList) { output.push('</ul>'); inList = false; }
				output.push('<div style="height:6px"></div>');
				continue;
			}

			// Normal paragraph line
			if (inList) { output.push('</ul>'); inList = false; }
			output.push('<p style="margin:4px 0">' + applyInline(line) + '</p>');
		}

		if (inList) output.push('</ul>');
		return output.join('');
	}

	function applyInline(text) {
		// Bold: **text**
		text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
		// Italic: *text* (single, not double)
		text = text.replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g, '<em>$1</em>');
		// Inline code: `code`
		text = text.replace(/`([^`]+)`/g, '<code style="background:#f0f0f0;padding:1px 4px;border-radius:3px;font-size:12px">$1</code>');
		return text;
	}

	// ── Pre-fill from research page ───────────────────────────────────────
	try {
		const prefill = sessionStorage.getItem('wo_prefill_proposal');
		if (prefill) {
			const d = JSON.parse(prefill);
			if (d.company) document.getElementById('wo-p-company').value = d.company;
			if (d.notes)   document.getElementById('wo-p-notes').value   = String(d.notes).substring(0, 800);
			sessionStorage.removeItem('wo_prefill_proposal');
			document.getElementById('wo-paste-box').style.display = 'none';
		}
	} catch(e) {}

	// ── Extract ───────────────────────────────────────────────────────────
	document.getElementById('wo-extract-btn').addEventListener('click', async function() {
		const raw = document.getElementById('wo-raw-text').value.trim();
		if (!raw) { setStatus('wo-extract-status', 'Paste something first.', '#cc1818'); return; }

		this.disabled = true;
		setStatus('wo-extract-status', 'Extracting with Claude…', '#646970');

		try {
			const res  = await fetch(cfg.apiUrl + '/proposals/extract', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ raw_text: raw }),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			if (data.title)   document.getElementById('wo-p-title').value   = data.title;
			if (data.company) document.getElementById('wo-p-company').value = data.company;
			if (data.budget)  document.getElementById('wo-p-budget').value  = data.budget;
			if (data.job_url) document.getElementById('wo-p-url').value     = data.job_url;

			if (data.notes || data.red_flags) {
				const notesVal = (data.notes || '') + (data.red_flags ? '\n\nRed flags: ' + data.red_flags : '');
				document.getElementById('wo-p-notes').value = notesVal.trim();
			}

			if (data.source) {
				const sel = document.getElementById('wo-p-source');
				for (const opt of sel.options) {
					if (opt.value === data.source) { sel.value = data.source; break; }
				}
			}

			setStatus('wo-extract-status', '✓ Extracted — review and adjust below.', '#00a32a');
			setTimeout(() => setStatus('wo-extract-status', '', ''), 5000);

			document.getElementById('wo-raw-text').style.display   = 'none';
			this.style.display = 'none';
			document.getElementById('wo-paste-heading').textContent =
				'✓ ' + (data.title ? data.title : 'Job extracted') + ' — details below';

		} catch(e) {
			setStatus('wo-extract-status', 'Error: ' + e.message, '#cc1818');
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-manual-link').addEventListener('click', function(e) {
		e.preventDefault();
		document.getElementById('wo-paste-box').style.display = 'none';
	});

	// ── Research ──────────────────────────────────────────────────────────
	document.getElementById('wo-research-btn').addEventListener('click', async function() {
		const company = document.getElementById('wo-p-company').value.trim();
		const jobDesc = document.getElementById('wo-p-notes').value.trim();
		if (!company) { alert('Enter a company name first.'); return; }

		const placeholderEl = document.getElementById('wo-research-placeholder');
		const statusEl      = document.getElementById('wo-research-status');
		const outputEl      = document.getElementById('wo-research-output');
		const analyseBtn    = document.getElementById('wo-analyse-btn');
		const analysisBox   = document.getElementById('wo-analysis-box');

		placeholderEl.style.display = 'none';
		statusEl.style.display      = 'block';
		statusEl.textContent        = 'Researching with Gemini…';
		outputEl.style.display      = 'none';
		outputEl.innerHTML          = '';
		analyseBtn.style.display    = 'none';
		analysisBox.style.display   = 'none';
		document.getElementById('wo-analysis-output').innerHTML  = '';
		document.getElementById('wo-analysis-status').style.display = 'none';
		document.getElementById('wo-research-heading').textContent  = 'Step 3 — Research: ' + company;
		researchText = '';
		analysisText = '';
		currentLogId = 0;

		this.disabled = true;

		try {
			const res  = await fetch(cfg.apiUrl + '/research', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ company, job_description: jobDesc }),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			researchText             = data.output || '';
			currentLogId             = data.log_id || 0;
			outputEl.innerHTML       = renderMarkdown(researchText);
			outputEl.style.display   = '';
			statusEl.style.display   = 'none';
			analyseBtn.style.display = 'block';

		} catch(e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	// ── Fit Analysis ──────────────────────────────────────────────────────
	document.getElementById('wo-analyse-btn').addEventListener('click', async function() {
		if (!researchText) return;

		const company     = document.getElementById('wo-p-company').value.trim();
		const statusEl    = document.getElementById('wo-analysis-status');
		const outputEl    = document.getElementById('wo-analysis-output');
		const analysisBox = document.getElementById('wo-analysis-box');

		analysisBox.style.display  = 'block';
		statusEl.style.display     = 'block';
		statusEl.textContent       = 'Analysing fit with Claude…';
		outputEl.innerHTML         = '';
		this.disabled              = true;

		try {
			const res  = await fetch(cfg.apiUrl + '/analyse', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ company, research: researchText, log_id: currentLogId }),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			analysisText           = data.output || '';
			outputEl.innerHTML     = renderMarkdown(analysisText);
			statusEl.style.display = 'none';

			// Scroll to Draft section and pulse the Draft button
			const draftBox = document.getElementById('wo-draft-save-box');
			if (draftBox) {
				draftBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
			const draftBtn = document.getElementById('wo-draft-btn');
			if (draftBtn) {
				draftBtn.classList.add('wo-pulse');
				setTimeout(() => draftBtn.classList.remove('wo-pulse'), 2000);
			}

		} catch(e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	// ── Draft ─────────────────────────────────────────────────────────────
	document.getElementById('wo-draft-btn').addEventListener('click', async function() {
		const statusEl = document.getElementById('wo-draft-status');
		const outputEl = document.getElementById('wo-draft-output');

		statusEl.style.display = 'block';
		statusEl.textContent   = 'Drafting with Claude…';
		outputEl.value         = '';
		this.disabled          = true;

		try {
			const res = await fetch(cfg.apiUrl + '/proposals/draft', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({
					title:            document.getElementById('wo-p-title').value.trim(),
					company:          document.getElementById('wo-p-company').value.trim(),
					budget:           document.getElementById('wo-p-budget').value.trim(),
					notes:            document.getElementById('wo-p-notes').value.trim(),
					research_context: researchText,
					fit_analysis:     analysisText,
				}),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			outputEl.value         = data.draft || '';
			statusEl.style.display = 'none';

		} catch(e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-draft-copy-btn').addEventListener('click', function() {
		const text = document.getElementById('wo-draft-output').value;
		if (!text) return;
		navigator.clipboard.writeText(text).then(() => {
			this.textContent = 'Copied!';
			setTimeout(() => this.textContent = 'Copy draft', 1500);
		});
	});

	// ── Save ──────────────────────────────────────────────────────────────
	document.getElementById('wo-add-proposal-btn').addEventListener('click', async function() {
		const title = document.getElementById('wo-p-title').value.trim();
		if (!title) { setStatus('wo-proposal-status', 'Title is required.', '#cc1818'); return; }

		this.disabled = true;
		setStatus('wo-proposal-status', 'Saving…', '#646970');

		try {
			const res = await fetch(cfg.apiUrl + '/proposals', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({
					title,
					company:  document.getElementById('wo-p-company').value.trim(),
					budget:   document.getElementById('wo-p-budget').value.trim(),
					source:   document.getElementById('wo-p-source').value,
					status:   document.getElementById('wo-p-status').value,
					job_url:  document.getElementById('wo-p-url').value.trim(),
					notes:    document.getElementById('wo-p-notes').value.trim(),
					raw_text: document.getElementById('wo-raw-text').value.trim(),
					research: researchText,
					draft:    document.getElementById('wo-draft-output').value.trim(),
					analysis: analysisText,
				}),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			prependRow(data);
			resetForm();
			setStatus('wo-proposal-status', '✓ Saved.', '#00a32a');
			setTimeout(() => setStatus('wo-proposal-status', '', ''), 3000);

		} catch(e) {
			setStatus('wo-proposal-status', 'Error: ' + e.message, '#cc1818');
		} finally {
			this.disabled = false;
		}
	});

	// ── Reset ─────────────────────────────────────────────────────────────
	document.getElementById('wo-reset-btn').addEventListener('click', resetForm);

	function resetForm() {
		['wo-p-title','wo-p-company','wo-p-budget','wo-p-url','wo-p-notes'].forEach(id => {
			document.getElementById(id).value = '';
		});
		document.getElementById('wo-draft-output').value      = '';
		document.getElementById('wo-raw-text').value          = '';
		document.getElementById('wo-raw-text').style.display  = '';
		document.getElementById('wo-extract-btn').style.display = '';
		document.getElementById('wo-paste-box').style.display   = '';
		document.getElementById('wo-paste-heading').textContent = 'Step 1 — Paste job listing or message';
		document.getElementById('wo-research-placeholder').style.display = '';
		document.getElementById('wo-research-output').style.display     = 'none';
		document.getElementById('wo-research-output').innerHTML          = '';
		document.getElementById('wo-analysis-box').style.display         = 'none';
		document.getElementById('wo-analysis-output').innerHTML          = '';
		document.getElementById('wo-analyse-btn').style.display          = 'none';
		document.getElementById('wo-research-status').style.display      = 'none';
		document.getElementById('wo-analysis-status').style.display      = 'none';
		document.getElementById('wo-draft-status').style.display         = 'none';
		document.getElementById('wo-research-heading').textContent       = 'Step 3 — Research';
		researchText = '';
		analysisText = '';
		currentLogId = 0;
	}

	// ── Table helpers ─────────────────────────────────────────────────────
	const statusColors = { draft:'#646970', sent:'#2271b1', won:'#00a32a', lost:'#cc1818', declined:'#8c00d4' };

	function prependRow(p) {
		const tbody = document.querySelector('#wo-proposals-table tbody');
		const noRow = tbody.querySelector('td[colspan]');
		if (noRow) noRow.closest('tr').remove();

		const c  = statusColors[p.status] || '#646970';
		const tr = document.createElement('tr');
		tr.id = 'wo-proposal-' + p.id;
		tr.innerHTML = `
			<td style="white-space:nowrap;color:#8c8f94;font-size:12px;vertical-align:top;padding-top:10px">${p.created_at.substring(0,10)}</td>
			<td style="vertical-align:top;padding-top:9px">
				<strong style="font-size:13px">${esc(p.title)}</strong>
				${p.company ? '<br><span style="font-size:12px;color:#646970">' + esc(p.company) + '</span>' : ''}
				${p.job_url ? '<br><a href="' + esc(p.job_url) + '" target="_blank" style="font-size:11px">↗ Job link</a>' : ''}
			</td>
			<td style="font-size:12px;vertical-align:top;padding-top:10px">${esc(p.budget||'')}</td>
			<td style="font-size:12px;color:#646970;vertical-align:top;padding-top:10px">${esc(p.source||'')}</td>
			<td style="vertical-align:top;padding-top:9px"><span class="wo-badge" style="background:${c}18;color:${c}">${esc(p.status)}</span></td>
			<td style="font-size:12px;color:#646970;max-width:180px;vertical-align:top;padding-top:10px">${esc((p.notes||'').substring(0,80))}</td>
			<td style="white-space:nowrap;vertical-align:top;padding-top:8px">
				<button class="button button-small wo-view-btn" data-id="${p.id}">View ↓</button>
				<select class="wo-status-select" data-id="${p.id}" style="font-size:12px;margin-left:4px">
					${['draft','sent','won','lost','declined'].map(s=>`<option value="${s}"${s===p.status?' selected':''}>${s.charAt(0).toUpperCase()+s.slice(1)}</option>`).join('')}
				</select>
				<button class="button button-small wo-delete-proposal-btn" data-id="${p.id}" style="color:#cc1818;border-color:#cc181833;margin-left:4px">×</button>
			</td>
		`;

		const detail = document.createElement('tr');
		detail.id = 'wo-detail-' + p.id;
		detail.style.display = 'none';
		detail.innerHTML = `
			<td colspan="7" style="padding:0;border-top:none">
				<div style="background:#f8f9fa;border-top:2px solid #2271b1;padding:20px 24px 24px;display:grid;grid-template-columns:1fr 1fr;gap:20px">
					${p.notes ? `<div>
						<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94;margin-bottom:8px">Notes</div>
						<div style="font-size:13px;color:#1d2327;line-height:1.65;white-space:pre-wrap">${esc(p.notes)}</div>
					</div>` : ''}
					${p.draft ? `<div>
						<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
							<span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#8c8f94">Draft</span>
							<button class="button button-small wo-copy-draft-btn" data-id="${p.id}">Copy</button>
						</div>
						<div style="font-size:13px;color:#1d2327;line-height:1.65;white-space:pre-wrap;max-height:300px;overflow-y:auto;background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:12px 14px">${esc(p.draft)}</div>
					</div>` : ''}
					${!p.notes && !p.draft ? '<div style="grid-column:1/-1;color:#646970;font-size:13px">No notes or draft saved yet.</div>' : ''}
				</div>
			</td>
		`;

		tbody.insertBefore(detail, tbody.firstChild);
		tbody.insertBefore(tr, detail);
		attachRowEvents(tr);

		// wire up copy button on the detail row
		const copyBtn = detail.querySelector('.wo-copy-draft-btn');
		if (copyBtn) copyBtn.addEventListener('click', function() {
			const draftEl = this.closest('div').nextElementSibling;
			if (!draftEl) return;
			navigator.clipboard.writeText(draftEl.textContent.trim()).then(() => {
				this.textContent = 'Copied!';
				setTimeout(() => this.textContent = 'Copy', 1500);
			});
		});
	}

	function esc(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}

	function setStatus(id, text, color) {
		const el = document.getElementById(id);
		if (!el) return;
		el.textContent = text;
		el.style.color  = color;
	}

	function attachRowEvents(tr) {
		const sel = tr.querySelector('.wo-status-select');
		if (sel) sel.addEventListener('change', async function() {
			await fetch(cfg.apiUrl + '/proposals/' + this.dataset.id, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ status: this.value }),
			});
		});

		const viewBtn = tr.querySelector('.wo-view-btn');
		if (viewBtn) viewBtn.addEventListener('click', function() {
			const id      = this.dataset.id;
			const detail  = document.getElementById('wo-detail-' + id);
			if (!detail) return;
			const open = detail.style.display !== 'none';
			detail.style.display = open ? 'none' : '';
			this.textContent     = open ? 'View ↓' : 'Hide ↑';
		});

		const copyBtn = tr.nextElementSibling && tr.nextElementSibling.querySelector && tr.nextElementSibling.querySelector('.wo-copy-draft-btn');
		if (copyBtn) copyBtn.addEventListener('click', function() {
			const draftEl = this.closest('div').nextElementSibling;
			if (!draftEl) return;
			navigator.clipboard.writeText(draftEl.textContent.trim()).then(() => {
				this.textContent = 'Copied!';
				setTimeout(() => this.textContent = 'Copy', 1500);
			});
		});

		const btn = tr.querySelector('.wo-delete-proposal-btn');
		if (btn) btn.addEventListener('click', async function() {
			if (!confirm('Delete this proposal?')) return;
			const id = this.dataset.id;
			await fetch(cfg.apiUrl + '/proposals/' + id, {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': cfg.nonce },
			});
			const row    = document.getElementById('wo-proposal-' + id);
			const detail = document.getElementById('wo-detail-' + id);
			if (row)    row.remove();
			if (detail) detail.remove();
		});
	}

	document.querySelectorAll('#wo-proposals-table tr[id^="wo-proposal-"]').forEach(attachRowEvents);

	// Wire copy buttons on PHP-rendered detail rows
	document.querySelectorAll('.wo-copy-draft-btn').forEach(btn => {
		btn.addEventListener('click', function() {
			const draftEl = this.closest('div').nextElementSibling;
			if (!draftEl) return;
			navigator.clipboard.writeText(draftEl.textContent.trim()).then(() => {
				this.textContent = 'Copied!';
				setTimeout(() => this.textContent = 'Copy', 1500);
			});
		});
	});
})();
</script>

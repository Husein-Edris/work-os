<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$gemini_set = ! empty( get_option( 'work_os_gemini_key', '' ) );
$claude_set = ! empty( get_option( 'work_os_claude_key', '' ) );
?>
<div class="wrap">
	<h1>Work OS — Research</h1>
	<hr class="wp-header-end">

	<?php if ( ! $gemini_set ) : ?>
		<div class="notice notice-warning">
			<p>Gemini API key not set. <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Add it in Settings →</a></p>
		</div>
	<?php endif; ?>

	<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;margin-top:20px;max-width:1100px">

		<div>

			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle">Company Research</h2></div>
				<div class="inside">
					<table class="form-table" style="margin:0">
						<tr>
							<th style="width:130px"><label for="wo-company">Company / Client</label></th>
							<td><input type="text" id="wo-company" class="regular-text" placeholder="e.g. Shiftmove, Aleph, Reincarnate GmbH"></td>
						</tr>
						<tr>
							<th><label for="wo-job-desc">Job description</label></th>
							<td><textarea id="wo-job-desc" rows="4" class="large-text" placeholder="Paste the job listing or describe the role (optional)…"></textarea></td>
						</tr>
					</table>
					<p>
						<button id="wo-research-btn" class="button button-primary" <?php echo $gemini_set ? '' : 'disabled'; ?>>Research with Gemini</button>
						<span id="wo-research-status" style="margin-left:10px;font-size:13px;color:#646970"></span>
					</p>
				</div>
			</div>

			<div id="wo-research-results" style="display:none">
				<div class="postbox">
					<div class="postbox-header">
						<h2 class="hndle">Research Results</h2>
						<div style="display:flex;gap:8px;margin:10px 12px 0 0">
							<button id="wo-analyse-btn" class="button button-primary" <?php echo $claude_set ? '' : 'disabled title="Set Claude key in Settings"'; ?>>Analyse fit with Claude →</button>
							<button id="wo-save-proposal-btn" class="button">Save as proposal →</button>
						</div>
					</div>
					<div class="inside">
						<div id="wo-research-output" class="wo-output" style="max-height:500px"></div>
					</div>
				</div>
			</div>

			<div id="wo-analysis-wrap" style="display:none">
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle">Claude Fit Analysis</h2></div>
					<div class="inside">
						<div id="wo-analyse-status" style="margin-bottom:10px;font-size:13px;color:#646970"></div>
						<div id="wo-analysis-output" class="wo-output" style="max-height:600px"></div>
					</div>
				</div>
			</div>

		</div>

		<div>

			<div class="postbox" id="wo-log-box">
				<div class="postbox-header"><h2 class="hndle">Recent Research</h2></div>
				<div class="inside" style="padding:0" id="wo-log-list">
					<p style="padding:16px;color:#646970;font-size:13px;margin:0">Loading…</p>
				</div>
			</div>

		</div>

	</div>
</div>

<script>
(function() {
	const cfg = window.workOsConfig;
	let researchText = '';
	let companyName  = '';

	function renderMarkdown(text) {
		if (!text) return '';
		const escaped = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		const lines = escaped.split('\n');
		const out = [];
		let inList = false;
		for (const line of lines) {
			if (/^### /.test(line)) { if(inList){out.push('</ul>');inList=false;} out.push('<h4>'+line.slice(4)+'</h4>'); continue; }
			if (/^## /.test(line))  { if(inList){out.push('</ul>');inList=false;} out.push('<h3>'+line.slice(3)+'</h3>'); continue; }
			if (/^# /.test(line))   { if(inList){out.push('</ul>');inList=false;} out.push('<h2>'+line.slice(2)+'</h2>'); continue; }
			if (/^[-*] /.test(line)) { if(!inList){out.push('<ul>');inList=true;} out.push('<li>'+inline(line.slice(2))+'</li>'); continue; }
			if (line.trim()==='') { if(inList){out.push('</ul>');inList=false;} out.push('<div style="height:5px"></div>'); continue; }
			if(inList){out.push('</ul>');inList=false;}
			out.push('<p>'+inline(line)+'</p>');
		}
		if(inList) out.push('</ul>');
		return out.join('');
	}
	function inline(t) {
		return t.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
		         .replace(/`([^`]+)`/g,'<code style="background:#f0f0f0;padding:1px 4px;border-radius:3px;font-size:12px">$1</code>');
	}

	document.getElementById('wo-research-btn').addEventListener('click', async function() {
		const company = document.getElementById('wo-company').value.trim();
		const jobDesc = document.getElementById('wo-job-desc').value.trim();
		if ( ! company ) { document.getElementById('wo-research-status').textContent = 'Enter a company name.'; return; }

		companyName = company;
		this.disabled = true;
		const statusEl = document.getElementById('wo-research-status');
		statusEl.textContent = 'Researching with Gemini…';
		document.getElementById('wo-research-results').style.display = 'none';
		document.getElementById('wo-analysis-wrap').style.display    = 'none';

		try {
			const res  = await fetch( cfg.apiUrl + '/research', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ company, job_description: jobDesc }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			researchText = data.output || '';
			document.getElementById('wo-research-output').innerHTML = renderMarkdown(researchText);
			document.getElementById('wo-research-results').style.display = 'block';
			statusEl.textContent = '';
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-analyse-btn').addEventListener('click', async function() {
		if ( ! researchText ) return;
		const statusEl  = document.getElementById('wo-analyse-status');
		const outputEl  = document.getElementById('wo-analysis-output');
		const wrapEl    = document.getElementById('wo-analysis-wrap');

		this.disabled = true;
		wrapEl.style.display = 'block';
		statusEl.textContent = 'Analysing with Claude…';
		outputEl.textContent = '';

		try {
			const res  = await fetch( cfg.apiUrl + '/analyse', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ company: companyName, research: researchText }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			outputEl.innerHTML = renderMarkdown(data.output || '');
			statusEl.textContent = '';
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-save-proposal-btn').addEventListener('click', function() {
		const analysis = document.getElementById('wo-analysis-output').textContent;
		const notes    = analysis || document.getElementById('wo-research-output').textContent.substring(0, 600);
		sessionStorage.setItem('wo_prefill_proposal', JSON.stringify({ company: companyName, notes }));
		window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=work-os-proposals' ) ); ?>';
	});

	// Load research log
	(async function loadLog() {
		const list = document.getElementById('wo-log-list');
		try {
			const res  = await fetch(cfg.apiUrl + '/research/log', { headers: {'X-WP-Nonce': cfg.nonce} });
			const data = await res.json();
			if (!data.length) {
				list.innerHTML = '<p style="padding:16px;color:#646970;font-size:13px;margin:0">No research yet.</p>';
				return;
			}
			list.innerHTML = data.map(r => `
				<div style="padding:10px 14px;border-bottom:1px solid #f0f0f1;cursor:pointer" class="wo-log-item" data-company="${esc(r.company)}">
					<div style="font-size:13px;font-weight:600">${esc(r.company)}</div>
					<div style="display:flex;justify-content:space-between;align-items:center;margin-top:3px">
						<span style="font-size:11px;color:#646970">${r.created_at.substring(0,10)}</span>
						${r.has_analysis == '1'
							? '<span style="font-size:11px;color:#00a32a;font-weight:600">✓ analysed</span>'
							: '<span style="font-size:11px;color:#646970">→ research only</span>'}
					</div>
				</div>
			`).join('');
			// Click to pre-fill company name
			list.querySelectorAll('.wo-log-item').forEach(el => {
				el.addEventListener('click', function() {
					document.getElementById('wo-company').value = this.dataset.company;
					document.getElementById('wo-company').focus();
				});
				el.addEventListener('mouseenter', function() { this.style.background = '#f6f7f7'; });
				el.addEventListener('mouseleave', function() { this.style.background = ''; });
			});
		} catch(e) {
			list.innerHTML = '<p style="padding:16px;color:#cc1818;font-size:13px;margin:0">Could not load log.</p>';
		}
		function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
	})();
})();
</script>

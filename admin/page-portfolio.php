<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$claude_set = ! empty( get_option( 'work_os_claude_key', '' ) );
$tab        = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'critique';
?>
<div class="wrap">
	<h1>Work OS — Portfolio Analyzer</h1>

	<h2 class="nav-tab-wrapper wo-tabs-nav" style="margin-top:14px">
		<a href="?page=work-os-portfolio&tab=critique" class="nav-tab <?php echo $tab === 'critique' ? 'nav-tab-active' : ''; ?>">Critique</a>
		<a href="?page=work-os-portfolio&tab=fixes" class="nav-tab <?php echo $tab === 'fixes' ? 'nav-tab-active' : ''; ?>">Fix Suggestions</a>
	</h2>

	<?php if ( ! $claude_set ) : ?>
		<div class="notice notice-warning" style="margin-top:16px">
			<p>Claude API key not set. <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Add it in Settings →</a></p>
		</div>
	<?php endif; ?>

	<?php if ( $tab === 'critique' ) : ?>

		<p class="description" style="margin-top:16px;font-size:13px;color:#646970;max-width:760px">
			Critiques what's there — weak descriptions, missing tech, junior-sounding language, projects that don't earn their place. Does not auto-fix anything. Reads only.
		</p>

		<div style="margin:20px 0;display:flex;gap:12px;align-items:center">
			<button id="wo-analyze-btn" class="button button-primary" <?php echo $claude_set ? '' : 'disabled'; ?>>Analyze portfolio</button>
			<span id="wo-analyze-status" style="font-size:13px;color:#646970"></span>
		</div>

		<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;max-width:1200px">
			<div>
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle">Latest analysis</h2></div>
					<div class="inside">
						<div id="wo-analysis-output" class="wo-output" style="max-height:none;min-height:200px">
							<p style="color:#8c8f94;margin:0">Click <strong>Analyze portfolio</strong> to run a critique.</p>
						</div>
					</div>
				</div>
			</div>
			<div>
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle">History</h2></div>
					<div class="inside" style="padding:0" id="wo-history-list">
						<p style="padding:14px;color:#646970;font-size:13px;margin:0">Loading…</p>
					</div>
				</div>
			</div>
		</div>

	<?php elseif ( $tab === 'fixes' ) : ?>

		<p class="description" style="margin-top:16px;font-size:13px;color:#646970;max-width:760px">
			Pick an entry below. Claude generates 3 rewrite candidates per field, grounded only in the data already there. Copy what you like, paste into your About page or project. Nothing is written back automatically.
		</p>

		<div style="margin-top:20px;max-width:1100px">
			<div id="wo-entries-loading" style="font-size:13px;color:#646970">Loading entries…</div>
			<div id="wo-entries-wrap" style="display:none">

				<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start">

					<div id="wo-entry-list" style="border:1px solid #c3c4c7;border-radius:4px;background:#fff;max-height:75vh;overflow-y:auto"></div>

					<div id="wo-entry-detail">
						<p style="color:#8c8f94;margin:0;font-size:13px">Pick an entry from the left to see fields and request rewrites.</p>
					</div>

				</div>

			</div>
		</div>

	<?php endif; ?>
</div>

<script>
(function() {
	const cfg = window.workOsConfig;
	const tab = <?php echo wp_json_encode( $tab ); ?>;

	function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

	function renderMarkdown(text) {
		if (!text) return '';
		const escaped = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
		const lines = escaped.split('\n');
		const out = [];
		let inList = false;
		for (const line of lines) {
			if (/^### /.test(line)) { if(inList){out.push('</ul>');inList=false;} out.push('<h4>'+inline(line.slice(4))+'</h4>'); continue; }
			if (/^## /.test(line))  { if(inList){out.push('</ul>');inList=false;} out.push('<h3>'+inline(line.slice(3))+'</h3>'); continue; }
			if (/^# /.test(line))   { if(inList){out.push('</ul>');inList=false;} out.push('<h2>'+inline(line.slice(2))+'</h2>'); continue; }
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

	// ── Critique tab ─────────────────────────────────────────────────────
	if (tab === 'critique') {
		const STEPS = [
			{ at:  0,  msg: 'Reading your About page and profile…' },
			{ at:  4,  msg: 'Loading project entries and ACF fields…' },
			{ at:  10, msg: 'Fetching skills, experience, and blog posts…' },
			{ at:  16, msg: 'Building portfolio snapshot…' },
			{ at:  22, msg: 'Sending to Claude…' },
			{ at:  30, msg: 'Claude is reviewing experience entries…' },
			{ at:  40, msg: 'Claude is reviewing projects and skills…' },
			{ at:  52, msg: 'Writing critique and ranking fixes…' },
		];

		let stepTimers = [];
		let analysisRunning = false;
		let currentStep = '';

		function startSteps(statusEl) {
			clearSteps();
			analysisRunning = true;
			STEPS.forEach(s => {
				const t = setTimeout(() => {
					currentStep = s.msg;
					if (analysisRunning) statusEl.textContent = s.msg;
				}, s.at * 1000);
				stepTimers.push(t);
			});
		}

		function clearSteps() {
			stepTimers.forEach(clearTimeout);
			stepTimers = [];
		}

		document.addEventListener('visibilitychange', () => {
			if (!document.hidden && analysisRunning && currentStep) {
				document.getElementById('wo-analyze-status').textContent = currentStep;
			}
		});

		async function loadHistory() {
			const list = document.getElementById('wo-history-list');
			try {
				const res = await fetch(cfg.apiUrl + '/portfolio/log', { headers: {'X-WP-Nonce': cfg.nonce} });
				const data = await res.json();
				if (!data.length) {
					list.innerHTML = '<p style="padding:14px;color:#646970;font-size:13px;margin:0">No previous analyses yet.</p>';
					return;
				}
				list.innerHTML = data.map(r => `
					<div style="padding:10px 14px;border-bottom:1px solid #f0f0f1" class="wo-log-item">
						<div style="display:flex;justify-content:space-between;align-items:baseline;gap:8px">
							<span style="font-size:12px;font-weight:600">${r.created_at.substring(0,10)} <span style="font-weight:400;color:#8c8f94">${r.created_at.substring(11,16)}</span></span>
							<span style="font-size:11px;color:#8c8f94">${r.projects_count} proj · ${r.skills_count} skills · ${r.posts_count} posts</span>
						</div>
						<div style="font-size:11px;color:#8c8f94;margin-top:4px;line-height:1.4">${esc((r.preview||'').replace(/^#+\s*/gm,'').substring(0,90))}…</div>
						<div style="margin-top:6px"><button class="button-link wo-log-load" data-id="${r.id}" style="font-size:11px;color:#2271b1">Load →</button>
							<button class="button-link wo-log-del" data-id="${r.id}" style="font-size:11px;color:#cc1818;margin-left:10px">Delete</button>
						</div>
					</div>
				`).join('');

				list.querySelectorAll('.wo-log-load').forEach(btn => {
					btn.addEventListener('click', async () => {
						const id = btn.dataset.id;
						const res = await fetch(cfg.apiUrl + '/portfolio/log/' + id, { headers: {'X-WP-Nonce': cfg.nonce} });
						const data = await res.json();
						document.getElementById('wo-analysis-output').innerHTML = renderMarkdown(data.analysis || '');
					});
				});
				list.querySelectorAll('.wo-log-del').forEach(btn => {
					btn.addEventListener('click', async () => {
						if (!confirm('Delete this analysis?')) return;
						await fetch(cfg.apiUrl + '/portfolio/log/' + btn.dataset.id, {
							method: 'DELETE', headers: {'X-WP-Nonce': cfg.nonce}
						});
						loadHistory();
					});
				});
			} catch(e) {
				list.innerHTML = '<p style="padding:14px;color:#cc1818;font-size:13px;margin:0">Could not load history.</p>';
			}
		}

		document.getElementById('wo-analyze-btn').addEventListener('click', async function() {
			const statusEl = document.getElementById('wo-analyze-status');
			const outEl    = document.getElementById('wo-analysis-output');
			this.disabled = true;
			outEl.innerHTML = '<p style="color:#8c8f94;margin:0;font-style:italic">Running…</p>';
			startSteps(statusEl);
			try {
				const res = await fetch(cfg.apiUrl + '/portfolio/analyze', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.message || 'Analysis failed');
				outEl.innerHTML = renderMarkdown(data.output);
				statusEl.textContent = 'Done.';
				loadHistory();
			} catch (e) {
				outEl.innerHTML = '<p style="color:#cc1818;margin:0">Error: ' + esc(e.message) + '</p>';
				statusEl.textContent = '';
			} finally {
				clearSteps();
				analysisRunning = false;
				this.disabled = false;
			}
		});

		loadHistory();
	}

	// ── Fixes tab ────────────────────────────────────────────────────────
	if (tab === 'fixes') {
		const TYPE_LABELS = { identity: 'Identity', experience: 'Experience', project: 'Project' };
		const FIELD_LABELS = {
			headline: 'Headline', summary: 'Summary', tagline: 'Tagline', niche: 'Niche',
			description: 'Description', excerpt: 'Excerpt', challenge: 'Challenge', solution: 'Solution'
		};
		let entries = [];
		let activeKey = null;

		async function loadEntries() {
			try {
				const res = await fetch(cfg.apiUrl + '/portfolio/entries', { headers: {'X-WP-Nonce': cfg.nonce} });
				entries = await res.json();
				if (!Array.isArray(entries)) throw new Error(entries.message || 'Could not load');
				renderEntryList();
				document.getElementById('wo-entries-loading').style.display = 'none';
				document.getElementById('wo-entries-wrap').style.display = 'block';
				if (entries.length) selectEntry(entries[0].key);
			} catch(e) {
				document.getElementById('wo-entries-loading').textContent = 'Error: ' + e.message;
			}
		}

		function renderEntryList() {
			const grouped = { identity: [], experience: [], project: [] };
			entries.forEach(e => grouped[e.type].push(e));
			const html = Object.keys(grouped).map(type => {
				if (!grouped[type].length) return '';
				return `
					<div style="padding:8px 12px;background:#f6f7f7;border-bottom:1px solid #dcdcde;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#646970">${TYPE_LABELS[type]}</div>
					${grouped[type].map(e => `
						<div class="wo-entry-pick" data-key="${esc(e.key)}" style="padding:10px 12px;border-bottom:1px solid #f0f0f1;cursor:pointer;font-size:13px;border-left:3px solid transparent">
							<div style="font-weight:600;line-height:1.3">${esc(e.label || '(no label)')}</div>
							${e.meta ? `<div style="font-size:11px;color:#8c8f94;margin-top:2px">${esc(e.meta)}</div>` : ''}
							<div style="font-size:11px;color:#8c8f94;margin-top:3px">${Object.keys(e.fields || {}).length} field(s) editable</div>
						</div>
					`).join('')}
				`;
			}).join('');
			document.getElementById('wo-entry-list').innerHTML = html;
			document.querySelectorAll('.wo-entry-pick').forEach(el => {
				el.addEventListener('click', () => selectEntry(el.dataset.key));
			});
		}

		function selectEntry(key) {
			activeKey = key;
			document.querySelectorAll('.wo-entry-pick').forEach(el => {
				el.style.background  = el.dataset.key === key ? '#f0f6fc' : '';
				el.style.borderLeft  = el.dataset.key === key ? '3px solid #2271b1' : '3px solid transparent';
			});
			renderEntryDetail();
		}

		function renderEntryDetail() {
			const entry = entries.find(e => e.key === activeKey);
			if (!entry) return;

			const allowed = {
				identity:   ['headline','summary','tagline','niche'],
				experience: ['description'],
				project:    ['excerpt','challenge','solution'],
			}[entry.type] || [];

			const fields = entry.fields || {};

			let html = `
				<div class="postbox">
					<div class="postbox-header"><h2 class="hndle">${esc(entry.label)}</h2></div>
					<div class="inside">
			`;
			allowed.forEach(field => {
				const current = fields[field] || '';
				const isEmpty = !current.trim();
				html += `
					<div class="wo-field-block" data-field="${esc(field)}" style="margin-bottom:24px;padding-bottom:18px;border-bottom:1px solid #f0f0f1">
						<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px">
							<strong style="font-size:13px">${esc(FIELD_LABELS[field] || field)}</strong>
							<button class="button button-secondary wo-suggest-btn" data-field="${esc(field)}">Suggest rewrites</button>
						</div>
						<div style="font-size:13px;line-height:1.6;color:${isEmpty ? '#cc1818' : '#1d2327'};background:#f6f7f7;padding:10px 12px;border-radius:4px;border:1px solid #dcdcde;white-space:pre-wrap">${isEmpty ? '<em>(empty)</em>' : esc(current)}</div>
						<div class="wo-suggest-output" style="margin-top:12px"></div>
					</div>
				`;
			});
			html += `</div></div>`;
			document.getElementById('wo-entry-detail').innerHTML = html;

			document.querySelectorAll('.wo-suggest-btn').forEach(btn => {
				btn.addEventListener('click', () => requestSuggestion(btn));
			});
		}

		async function requestSuggestion(btn) {
			const field = btn.dataset.field;
			const block = btn.closest('.wo-field-block');
			const out   = block.querySelector('.wo-suggest-output');

			btn.disabled = true;
			btn.textContent = 'Generating…';
			out.innerHTML = '<div style="font-size:12px;color:#8c8f94;font-style:italic">Asking Claude for 3 rewrites…</div>';

			try {
				const res = await fetch(cfg.apiUrl + '/portfolio/suggest', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
					body: JSON.stringify({ entry_key: activeKey, field })
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.message || 'Suggestion failed');

				renderCandidates(out, data.candidates, data.rationale);
			} catch (e) {
				out.innerHTML = '<div style="color:#cc1818;font-size:13px">Error: ' + esc(e.message) + '</div>';
			} finally {
				btn.disabled = false;
				btn.textContent = 'Suggest rewrites';
			}
		}

		function renderCandidates(container, candidates, rationale) {
			let html = '';
			if (rationale) {
				html += `<div style="font-size:12px;color:#646970;margin-bottom:10px;padding:8px 10px;background:#f0f6fc;border-left:3px solid #2271b1">${esc(rationale)}</div>`;
			}
			candidates.forEach((c, i) => {
				const text = c.text || '';
				html += `
					<div style="border:1px solid #dcdcde;border-radius:4px;padding:12px;margin-bottom:10px;background:#fff">
						<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px">
							<strong style="font-size:12px;color:#2271b1">${i+1}. ${esc(c.label || 'Option ' + (i+1))}</strong>
							<button class="button button-small wo-copy-btn" data-text="${esc(text)}">Copy</button>
						</div>
						<div style="font-size:13px;line-height:1.6;white-space:pre-wrap">${esc(text)}</div>
					</div>
				`;
			});
			container.innerHTML = html;

			container.querySelectorAll('.wo-copy-btn').forEach(b => {
				b.addEventListener('click', async () => {
					const text = b.dataset.text;
					try {
						if (navigator.clipboard && navigator.clipboard.writeText) {
							await navigator.clipboard.writeText(text);
						} else {
							const el = document.createElement('textarea');
							el.value = text;
							el.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
							document.body.appendChild(el);
							el.select();
							document.execCommand('copy');
							document.body.removeChild(el);
						}
						const orig = b.textContent;
						b.textContent = '✓ Copied';
						setTimeout(() => { b.textContent = orig; }, 1500);
					} catch(e) {
						alert('Could not copy: ' + e.message);
					}
				});
			});
		}

		loadEntries();
	}
})();
</script>

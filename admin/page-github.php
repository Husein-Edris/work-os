<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$github_url  = get_option( 'work_os_cv_github', '' );
$claude_set  = ! empty( get_option( 'work_os_claude_key', '' ) );
$has_token   = ! empty( get_option( 'work_os_github_token', '' ) );
?>
<div class="wrap">
	<h1>Work OS — GitHub Sync</h1>
	<hr class="wp-header-end">

	<?php if ( ! $github_url ) : ?>
		<div class="notice notice-warning">
			<p>GitHub URL not set. <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Add it in Settings →</a></p>
		</div>
	<?php endif; ?>

	<?php if ( ! $claude_set ) : ?>
		<div class="notice notice-warning">
			<p>Claude API key not set. Generation will be disabled. <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Add it in Settings →</a></p>
		</div>
	<?php endif; ?>

	<p class="description" style="margin-top:10px;font-size:13px;color:#646970">
		Sync your public GitHub repos against your portfolio. Repos missing from your portfolio with a substantial README can be turned into project drafts.
		<?php if ( ! $has_token ) : ?>
			<br><strong>Tip:</strong> add a GitHub Token in <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Settings</a> to raise the rate limit from 60 to 5,000 requests/hour.
		<?php endif; ?>
	</p>

	<div style="margin:20px 0">
		<button id="wo-sync-btn" class="button button-primary" <?php echo $github_url ? '' : 'disabled'; ?>>Sync now</button>
		<span id="wo-sync-status" style="margin-left:12px;font-size:13px;color:#646970"></span>
	</div>

	<div id="wo-repo-table-wrap" style="display:none">
		<table class="widefat striped" id="wo-repo-table">
			<thead>
				<tr>
					<th style="width:20%">Repository</th>
					<th>Description</th>
					<th style="width:90px">Language</th>
					<th style="width:140px">Status</th>
					<th style="width:230px">Action</th>
				</tr>
			</thead>
			<tbody id="wo-repo-tbody"></tbody>
		</table>
	</div>
</div>

<script>
(function() {
	const cfg = window.workOsConfig;

	const statusBadge = (status, note) => {
		const colors = {
			missing:      { bg: '#fff8e5', fg: '#996800', label: 'Missing' },
			in_portfolio: { bg: '#edfaef', fg: '#00a32a', label: '✓ In portfolio' },
			thin_readme:  { bg: '#f0f0f1', fg: '#646970', label: 'Thin README' },
			blocked:      { bg: '#f3e8ff', fg: '#8c00d4', label: 'Skipped' },
		};
		const c = colors[status] || colors.missing;
		return `<span style="display:inline-block;padding:3px 8px;border-radius:10px;background:${c.bg};color:${c.fg};font-size:11px;font-weight:600">${c.label}</span>` +
			(note ? `<div style="font-size:11px;color:#8c8f94;margin-top:3px">${note}</div>` : '');
	};

	const actionsFor = (repo) => {
		const generateDisabled = !<?php echo $claude_set ? 'true' : 'false'; ?>;
		switch (repo.status) {
			case 'missing':
				return `
					<button class="button button-primary wo-generate-btn" data-repo="${esc(repo.name)}" ${generateDisabled ? 'disabled' : ''}>Generate draft</button>
					<button class="button button-link-delete wo-skip-btn" data-repo="${esc(repo.name)}" style="margin-left:6px">Skip</button>
				`;
			case 'thin_readme':
				return `<span style="font-size:12px;color:#8c8f94">Add a README first</span>
					<button class="button button-link-delete wo-skip-btn" data-repo="${esc(repo.name)}" style="margin-left:6px">Skip</button>`;
			case 'blocked':
				return `<button class="button wo-unskip-btn" data-repo="${esc(repo.name)}">Unskip</button>`;
			case 'in_portfolio':
				return `<span style="font-size:12px;color:#646970">Nothing to do</span>`;
			default:
				return '';
		}
	};

	const renderRow = (repo) => {
		const desc = repo.description
			? esc(repo.description).slice(0, 140)
			: '<em style="color:#8c8f94">no description</em>';
		const pushedAt = repo.pushed_at ? repo.pushed_at.substring(0, 10) : '';
		return `
			<tr id="repo-${esc(repo.name)}" data-status="${repo.status}">
				<td>
					<strong><a href="${esc(repo.html_url)}" target="_blank" style="text-decoration:none">${esc(repo.name)}</a></strong>
					${pushedAt ? `<div style="font-size:11px;color:#8c8f94">pushed ${pushedAt}</div>` : ''}
				</td>
				<td style="font-size:13px;color:#1d2327;line-height:1.5">${desc}</td>
				<td><code style="font-size:11px;background:#f0f0f1;padding:2px 6px;border-radius:3px">${esc(repo.language || '—')}</code></td>
				<td>${statusBadge(repo.status, repo.status_note)}</td>
				<td class="wo-row-action">${actionsFor(repo)}</td>
			</tr>
		`;
	};

	const sync = async () => {
		const btn      = document.getElementById('wo-sync-btn');
		const statusEl = document.getElementById('wo-sync-status');
		btn.disabled = true;
		statusEl.textContent = 'Fetching repos…';

		try {
			const res = await fetch(cfg.apiUrl + '/github/list', {
				headers: { 'X-WP-Nonce': cfg.nonce }
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Sync failed');

			document.getElementById('wo-repo-tbody').innerHTML = data.repos.map(renderRow).join('');
			document.getElementById('wo-repo-table-wrap').style.display = 'block';

			const missing = data.repos.filter(r => r.status === 'missing').length;
			statusEl.textContent = `${data.repos.length} repos · ${missing} missing from portfolio`;
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			btn.disabled = false;
		}
	};

	document.getElementById('wo-sync-btn').addEventListener('click', sync);

	// Auto-sync on page load so the table is never blank
	<?php if ( $github_url ) : ?>sync();<?php endif; ?>

	// Delegated handlers
	document.getElementById('wo-repo-tbody').addEventListener('click', async (e) => {
		const tgt = e.target;
		if (!tgt.classList.contains('wo-generate-btn') &&
			!tgt.classList.contains('wo-skip-btn') &&
			!tgt.classList.contains('wo-unskip-btn')) return;

		const repoName = tgt.dataset.repo;
		const row      = document.getElementById('repo-' + repoName);
		const cell     = row.querySelector('.wo-row-action');

		if (tgt.classList.contains('wo-generate-btn')) {
			tgt.disabled = true;
			cell.innerHTML = '<span style="font-size:12px;color:#646970">Generating…</span>';
			try {
				const res = await fetch(cfg.apiUrl + '/github/generate', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
					body: JSON.stringify({ repo_name: repoName })
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data.message || 'Generation failed');
				cell.innerHTML = `<a href="${esc(data.edit_url)}" target="_blank" class="button button-primary">Edit draft →</a>`;
				row.dataset.status = 'in_portfolio';
				row.querySelector('td:nth-child(4)').innerHTML = statusBadge('in_portfolio', `Drafted: ${esc(data.title)}`);
			} catch (err) {
				cell.innerHTML = `<span style="color:#cc1818;font-size:12px">${esc(err.message)}</span>`;
			}
			return;
		}

		if (tgt.classList.contains('wo-skip-btn')) {
			if (!confirm('Skip ' + repoName + ' permanently? You can unskip later.')) return;
			try {
				await fetch(cfg.apiUrl + '/github/skip', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
					body: JSON.stringify({ repo_name: repoName })
				});
				row.querySelector('td:nth-child(4)').innerHTML = statusBadge('blocked', 'Skipped permanently');
				row.dataset.status = 'blocked';
				cell.innerHTML = `<button class="button wo-unskip-btn" data-repo="${esc(repoName)}">Unskip</button>`;
			} catch (err) {
				alert('Failed to skip: ' + err.message);
			}
			return;
		}

		if (tgt.classList.contains('wo-unskip-btn')) {
			try {
				await fetch(cfg.apiUrl + '/github/unskip', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
					body: JSON.stringify({ repo_name: repoName })
				});
				// Reload to get fresh status (could be missing or thin_readme)
				sync();
			} catch (err) {
				alert('Failed to unskip: ' + err.message);
			}
		}
	});

	function esc(s) {
		return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}
})();
</script>

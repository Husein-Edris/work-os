<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$categories = array(
	'cv'          => array( 'label' => 'CV',           'color' => '#2271b1' ),
	'certificate' => array( 'label' => 'Certificates', 'color' => '#00a32a' ),
	'legal'       => array( 'label' => 'Legal',        'color' => '#dba617' ),
	'other'       => array( 'label' => 'Other',        'color' => '#646970' ),
);

global $wpdb;
$docs = $wpdb->get_results(
	"SELECT d.*, p.guid as att_url FROM {$wpdb->prefix}work_os_documents d
	 LEFT JOIN {$wpdb->posts} p ON p.ID = d.attachment_id
	 ORDER BY d.category ASC, d.created_at DESC",
	ARRAY_A
) ?: array();

// Enrich with real attachment URLs and metadata
foreach ( $docs as &$doc ) {
	$att_id = (int) $doc['attachment_id'];
	$doc['url']       = $att_id ? wp_get_attachment_url( $att_id ) : '';
	$doc['mime_type'] = $att_id ? get_post_mime_type( $att_id ) : '';
	$path = $att_id ? get_attached_file( $att_id ) : '';
	if ( $path && file_exists( $path ) ) {
		$bytes = filesize( $path );
		$doc['filesize'] = $bytes >= 1048576 ? round( $bytes/1048576, 1 ) . ' MB'
			: ( $bytes >= 1024 ? round( $bytes/1024 ) . ' KB' : $bytes . ' B' );
	} else {
		$doc['filesize'] = '';
	}
}
unset( $doc );

// Group by category
$grouped = array_fill_keys( array_keys( $categories ), array() );
foreach ( $docs as $doc ) {
	$cat = isset( $grouped[ $doc['category'] ] ) ? $doc['category'] : 'other';
	$grouped[ $cat ][] = $doc;
}

// CV shareable link (first CV document)
$cv_doc     = ! empty( $grouped['cv'] ) ? $grouped['cv'][0] : null;
$cv_url     = $cv_doc ? $cv_doc['url'] : '';
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Work OS — Documents</h1>
	<button id="wo-add-doc-btn" class="page-title-action" style="margin-left:8px">+ Add Document</button>
	<hr class="wp-header-end">

	<!-- ── CV Spotlight ── -->
	<div class="postbox" style="margin-top:20px;border-top:3px solid #2271b1">
		<div class="postbox-header"><h2 class="hndle">CV — Shareable Link</h2></div>
		<div class="inside">
			<?php if ( $cv_url ) : ?>
				<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
					<a href="<?php echo esc_url( $cv_url ); ?>" target="_blank" class="button button-primary">
						Open CV →
					</a>
					<div style="display:flex;align-items:center;gap:8px;flex:1;min-width:280px">
						<input type="text" id="wo-cv-link" value="<?php echo esc_attr( $cv_url ); ?>"
							readonly style="flex:1;font-size:12px;color:#50575e;background:#f8f9fa;cursor:text">
						<button class="button" id="wo-copy-cv-link">Copy link</button>
					</div>
					<span style="font-size:12px;color:#8c8f94">
						<?php echo esc_html( $cv_doc['title'] ); ?>
						<?php if ( $cv_doc['filesize'] ) : ?>
							&middot; <?php echo esc_html( $cv_doc['filesize'] ); ?>
						<?php endif; ?>
					</span>
				</div>
				<p style="margin:10px 0 0;font-size:12px;color:#646970">
					This is a direct link to your CV file in the WordPress Media Library — permanent and shareable. To update, delete the current CV entry and add the new file.
				</p>
			<?php else : ?>
				<p style="margin:0;color:#646970;font-size:13px">
					No CV uploaded yet.
					<a href="#" id="wo-add-cv-shortcut">Upload your CV →</a>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<!-- ── Document sections ── -->
	<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:0">

		<?php foreach ( $categories as $cat_key => $cat ) :
			$cat_docs = $grouped[ $cat_key ] ?? array();
		?>
		<div class="postbox" id="wo-section-<?php echo esc_attr( $cat_key ); ?>">
			<div class="postbox-header" style="display:flex;justify-content:space-between;align-items:center">
				<h2 class="hndle" style="display:flex;align-items:center;gap:8px">
					<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $cat['color'] ); ?>"></span>
					<?php echo esc_html( $cat['label'] ); ?>
					<span style="font-size:11px;color:#8c8f94;font-weight:400">(<?php echo count( $cat_docs ); ?>)</span>
				</h2>
				<button class="page-title-action wo-add-in-cat" data-cat="<?php echo esc_attr( $cat_key ); ?>"
					style="margin:8px 12px 0 0;font-size:11px">+ Add</button>
			</div>
			<div class="inside" style="padding:0">
				<?php if ( empty( $cat_docs ) ) : ?>
					<p style="padding:16px;color:#8c8f94;font-size:13px;margin:0">No <?php echo esc_html( strtolower( $cat['label'] ) ); ?> documents yet.</p>
				<?php else : ?>
					<?php foreach ( $cat_docs as $doc ) : ?>
						<div id="wo-doc-<?php echo (int) $doc['id']; ?>"
							style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-bottom:1px solid #f0f0f1">

							<!-- File icon -->
							<div style="width:36px;height:36px;border-radius:4px;background:<?php echo esc_attr( $cat['color'] ); ?>12;border:1px solid <?php echo esc_attr( $cat['color'] ); ?>30;display:flex;align-items:center;justify-content:center;flex-shrink:0">
								<?php
								$mime = $doc['mime_type'];
								if ( strpos( $mime, 'pdf' ) !== false ) echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $cat['color'] ) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
								elseif ( strpos( $mime, 'image/' ) === 0 ) echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $cat['color'] ) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
								else echo '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr( $cat['color'] ) . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
								?>
							</div>

							<!-- Info -->
							<div style="flex:1;min-width:0">
								<div style="font-size:13px;font-weight:600;color:#1d2327;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
									<?php echo esc_html( $doc['title'] ); ?>
								</div>
								<div style="font-size:11px;color:#8c8f94;margin-top:2px">
									<?php echo esc_html( substr( $doc['created_at'], 0, 10 ) ); ?>
									<?php if ( $doc['filesize'] ) echo '&nbsp;&middot;&nbsp;' . esc_html( $doc['filesize'] ); ?>
									<?php if ( $doc['description'] ) echo '&nbsp;&middot;&nbsp;' . esc_html( wp_trim_words( $doc['description'], 6 ) ); ?>
								</div>
							</div>

							<!-- Actions -->
							<div style="display:flex;gap:6px;flex-shrink:0">
								<?php if ( $doc['url'] ) : ?>
									<a href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" class="button button-small" title="Open">↗</a>
									<button class="button button-small wo-copy-doc-link" data-url="<?php echo esc_attr( $doc['url'] ); ?>" title="Copy link">⎘</button>
								<?php endif; ?>
								<button class="button button-small wo-delete-doc" data-id="<?php echo (int) $doc['id']; ?>"
									style="color:#cc1818;border-color:#cc181833" title="Delete">×</button>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php endforeach; ?>

	</div>
</div>

<!-- ── Add Document Modal ── -->
<div id="wo-doc-modal" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,0.5);align-items:center;justify-content:center">
	<div style="background:#fff;border-radius:6px;width:480px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,0.2)">
		<div style="padding:16px 20px;border-bottom:1px solid #dcdcde;display:flex;justify-content:space-between;align-items:center">
			<h3 style="margin:0;font-size:15px">Add Document</h3>
			<button id="wo-modal-close" style="background:none;border:none;cursor:pointer;font-size:20px;color:#646970;padding:0 4px">&times;</button>
		</div>
		<div style="padding:20px">
			<div style="margin-bottom:14px">
				<label style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Title</label>
				<input type="text" id="wo-doc-title" class="large-text" placeholder="e.g. CV – April 2026, AWS Certificate, Freelance Contract">
			</div>
			<div style="margin-bottom:14px">
				<label style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Category</label>
				<select id="wo-doc-category" style="width:100%">
					<?php foreach ( $categories as $key => $cat ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $cat['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div style="margin-bottom:14px">
				<label style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">File</label>
				<div style="display:flex;gap:8px;align-items:center">
					<input type="text" id="wo-doc-filename" readonly placeholder="No file selected"
						style="flex:1;background:#f8f9fa;color:#50575e;font-size:12px;cursor:default">
					<button class="button" id="wo-select-file-btn">Select / Upload</button>
				</div>
				<input type="hidden" id="wo-doc-attachment-id">
			</div>
			<div style="margin-bottom:4px">
				<label style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Description <span style="font-weight:400;color:#8c8f94">(optional)</span></label>
				<input type="text" id="wo-doc-description" class="large-text" placeholder="e.g. Updated April 2026">
			</div>
		</div>
		<div style="padding:14px 20px;border-top:1px solid #dcdcde;display:flex;justify-content:flex-end;gap:8px;align-items:center">
			<span id="wo-doc-save-status" style="font-size:13px;color:#646970;margin-right:auto"></span>
			<button id="wo-modal-cancel" class="button">Cancel</button>
			<button id="wo-doc-save-btn" class="button button-primary">Save Document</button>
		</div>
	</div>
</div>

<script>
(function() {
	const cfg = window.workOsConfig;

	// ── Copy CV link ──────────────────────────────────────────────────────
	const copyCvBtn = document.getElementById('wo-copy-cv-link');
	if (copyCvBtn) {
		copyCvBtn.addEventListener('click', function() {
			const val = document.getElementById('wo-cv-link').value;
			navigator.clipboard.writeText(val).then(() => {
				this.textContent = 'Copied!';
				setTimeout(() => this.textContent = 'Copy link', 1500);
			});
		});
	}

	const addCvShortcut = document.getElementById('wo-add-cv-shortcut');
	if (addCvShortcut) {
		addCvShortcut.addEventListener('click', function(e) {
			e.preventDefault();
			document.getElementById('wo-doc-category').value = 'cv';
			openModal();
		});
	}

	// ── Copy doc link buttons ─────────────────────────────────────────────
	document.querySelectorAll('.wo-copy-doc-link').forEach(btn => {
		btn.addEventListener('click', function() {
			navigator.clipboard.writeText(this.dataset.url).then(() => {
				this.textContent = '✓';
				setTimeout(() => this.textContent = '⎘', 1500);
			});
		});
	});

	// ── Delete ────────────────────────────────────────────────────────────
	document.querySelectorAll('.wo-delete-doc').forEach(attachDelete);

	function attachDelete(btn) {
		btn.addEventListener('click', async function() {
			if (!confirm('Remove this document from Work OS? (The file in Media Library is kept.)')) return;
			const id = this.dataset.id;
			try {
				const res = await fetch(cfg.apiUrl + '/documents/' + id, {
					method: 'DELETE',
					headers: { 'X-WP-Nonce': cfg.nonce },
				});
				if (!res.ok) throw new Error('Delete failed');
				const el = document.getElementById('wo-doc-' + id);
				if (el) el.remove();
			} catch(e) { alert('Could not delete: ' + e.message); }
		});
	}

	// ── "Add in category" buttons ─────────────────────────────────────────
	document.querySelectorAll('.wo-add-in-cat').forEach(btn => {
		btn.addEventListener('click', function() {
			document.getElementById('wo-doc-category').value = this.dataset.cat;
			openModal();
		});
	});

	document.getElementById('wo-add-doc-btn').addEventListener('click', openModal);

	// ── Modal ─────────────────────────────────────────────────────────────
	function openModal() {
		document.getElementById('wo-doc-modal').style.display = 'flex';
	}
	function closeModal() {
		document.getElementById('wo-doc-modal').style.display = 'none';
		document.getElementById('wo-doc-title').value = '';
		document.getElementById('wo-doc-description').value = '';
		document.getElementById('wo-doc-filename').value = '';
		document.getElementById('wo-doc-attachment-id').value = '';
		document.getElementById('wo-doc-save-status').textContent = '';
	}

	document.getElementById('wo-modal-close').addEventListener('click', closeModal);
	document.getElementById('wo-modal-cancel').addEventListener('click', closeModal);
	document.getElementById('wo-doc-modal').addEventListener('click', function(e) {
		if (e.target === this) closeModal();
	});

	// ── WP Media picker ───────────────────────────────────────────────────
	let mediaFrame;
	document.getElementById('wo-select-file-btn').addEventListener('click', function(e) {
		e.preventDefault();
		if (mediaFrame) { mediaFrame.open(); return; }
		mediaFrame = wp.media({
			title:    'Select or Upload Document',
			button:   { text: 'Use this file' },
			multiple: false,
		});
		mediaFrame.on('select', function() {
			const attachment = mediaFrame.state().get('selection').first().toJSON();
			document.getElementById('wo-doc-attachment-id').value = attachment.id;
			document.getElementById('wo-doc-filename').value = attachment.filename || attachment.url.split('/').pop();
			if (!document.getElementById('wo-doc-title').value) {
				document.getElementById('wo-doc-title').value = attachment.title || '';
			}
		});
		mediaFrame.open();
	});

	// ── Save ──────────────────────────────────────────────────────────────
	document.getElementById('wo-doc-save-btn').addEventListener('click', async function() {
		const title         = document.getElementById('wo-doc-title').value.trim();
		const category      = document.getElementById('wo-doc-category').value;
		const attachment_id = document.getElementById('wo-doc-attachment-id').value;
		const description   = document.getElementById('wo-doc-description').value.trim();
		const statusEl      = document.getElementById('wo-doc-save-status');

		if (!title)         { statusEl.style.color='#cc1818'; statusEl.textContent='Title is required.'; return; }
		if (!attachment_id) { statusEl.style.color='#cc1818'; statusEl.textContent='Please select a file.'; return; }

		this.disabled = true;
		statusEl.style.color = '#646970';
		statusEl.textContent = 'Saving…';

		try {
			const res  = await fetch(cfg.apiUrl + '/documents', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ title, category, attachment_id: parseInt(attachment_id), description }),
			});
			const data = await res.json();
			if (!res.ok) throw new Error(data.message || 'Error');

			closeModal();
			// Reload to reflect new document in correct section
			window.location.reload();
		} catch(e) {
			statusEl.style.color = '#cc1818';
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});
})();
</script>

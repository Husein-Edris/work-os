<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$claude_set = ! empty( get_option( 'work_os_claude_key', '' ) );

$recent_posts = get_posts( array(
	'post_type'      => 'post',
	'posts_per_page' => 6,
	'post_status'    => array( 'publish', 'draft' ),
	'orderby'        => 'date',
	'order'          => 'DESC',
) );

global $wpdb;
$memory_events = $wpdb->get_results(
	"SELECT id, note, kind, created_at FROM {$wpdb->prefix}work_os_memory ORDER BY created_at DESC LIMIT 20",
	ARRAY_A
) ?: array();

$formats = array(
	'tutorial'     => 'Tutorial / How-to',
	'case_study'   => 'Case study',
	'opinion'      => 'Opinion / Thought piece',
	'lessons'      => 'Lessons learned',
	'announcement' => 'Announcement / Update',
);
?>
<div class="wrap">
	<h1>Work OS — Blog Generator</h1>
	<hr class="wp-header-end">

	<?php if ( ! $claude_set ) : ?>
		<div class="notice notice-warning">
			<p>Claude API key not set. <a href="<?php echo esc_url( admin_url( 'admin.php?page=work-os-settings' ) ); ?>">Add it in Settings →</a></p>
		</div>
	<?php endif; ?>

	<div style="margin-top:20px;display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start">

		<div class="postbox">
			<div class="postbox-header"><h2 class="hndle">Generate post</h2></div>
			<div class="inside">

				<div style="margin-bottom:14px">
					<label for="wo-blog-topic" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Topic</label>
					<input type="text" id="wo-blog-topic" class="large-text" placeholder="e.g. How I migrated a WooCommerce shop to headless WordPress">
				</div>

				<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
					<div>
						<label for="wo-blog-format" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Format</label>
						<select id="wo-blog-format" style="width:100%">
							<?php foreach ( $formats as $val => $label ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php if ( ! empty( $memory_events ) ) : ?>
					<div>
						<label for="wo-blog-memory" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Ground in memory</label>
						<select id="wo-blog-memory" style="width:100%">
							<option value="">— none —</option>
							<?php foreach ( $memory_events as $ev ) : ?>
								<option value="<?php echo (int) $ev['id']; ?>">
									<?php echo esc_html( substr( $ev['created_at'], 0, 10 ) . ' — ' . wp_trim_words( $ev['note'], 8 ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
				</div>

				<div style="margin-bottom:16px">
					<label for="wo-blog-extra" style="display:block;font-size:12px;font-weight:600;color:#1d2327;margin-bottom:5px">Extra context <span style="font-weight:400;color:#8c8f94">(optional)</span></label>
					<textarea id="wo-blog-extra" rows="3" class="large-text" placeholder="Target audience, specific angle, key points to cover…"></textarea>
				</div>

				<div style="display:flex;align-items:center;gap:10px">
					<button id="wo-generate-btn" class="button button-primary" <?php echo $claude_set ? '' : 'disabled'; ?>>Generate with Claude</button>
					<span id="wo-generate-status" style="font-size:13px;color:#646970"></span>
				</div>

			</div>
		</div>

		<div class="postbox">
			<div class="postbox-header"><h2 class="hndle">Recent posts</h2></div>
			<div class="inside" style="padding:0">
				<?php if ( empty( $recent_posts ) ) : ?>
					<p style="padding:16px;color:#646970;margin:0">No posts yet.</p>
				<?php else : ?>
					<table class="widefat" style="border:none;border-radius:0">
						<tbody>
						<?php foreach ( $recent_posts as $post ) : ?>
							<tr>
								<td style="padding:8px 12px">
									<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" style="font-size:13px;font-weight:600;text-decoration:none;color:#2271b1">
										<?php echo esc_html( $post->post_title ?: '(no title)' ); ?>
									</a>
									<br>
									<span style="font-size:11px;color:#646970">
										<?php echo esc_html( get_the_date( 'j M Y', $post ) ); ?>
										&bull; <?php echo esc_html( $post->post_status ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<div id="wo-post-wrap" style="display:none;grid-column:1/-1">
			<div class="postbox">
				<div class="postbox-header"><h2 class="hndle">Generated Post</h2></div>
				<div class="inside">
					<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap">
						<input type="text" id="wo-post-title" class="regular-text" placeholder="Post title…"
							style="font-size:13px;flex:1;min-width:240px">
						<button id="wo-publish-btn" class="button button-primary">Publish as draft →</button>
						<span id="wo-publish-status" style="font-size:13px;color:#646970"></span>
					</div>
					<textarea id="wo-post-content" rows="26" class="large-text"
						style="font-size:13px;line-height:1.7;font-family:monospace;background:#f8f9fa;border-color:#dcdcde;resize:vertical"></textarea>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
(function() {
	const cfg = window.workOsConfig;

	document.getElementById('wo-generate-btn').addEventListener('click', async function() {
		const topic     = document.getElementById('wo-blog-topic').value.trim();
		const format    = document.getElementById('wo-blog-format').value;
		const memoryEl  = document.getElementById('wo-blog-memory');
		const memoryId  = memoryEl ? memoryEl.value : '';
		const extra     = document.getElementById('wo-blog-extra').value.trim();
		const statusEl  = document.getElementById('wo-generate-status');

		if ( ! topic ) { statusEl.textContent = 'Enter a topic.'; return; }

		this.disabled = true;
		statusEl.textContent = 'Generating… (this may take 20-30 seconds)';

		try {
			const res  = await fetch( cfg.apiUrl + '/blog/generate', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ topic, format, memory_id: memoryId || null, extra }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			document.getElementById('wo-post-title').value   = data.title || topic;
			document.getElementById('wo-post-content').value = data.content || '';
			document.getElementById('wo-post-wrap').style.display = 'block';
			statusEl.textContent = '';
			document.getElementById('wo-post-wrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});

	document.getElementById('wo-publish-btn').addEventListener('click', async function() {
		const title    = document.getElementById('wo-post-title').value.trim();
		const content  = document.getElementById('wo-post-content').value.trim();
		const statusEl = document.getElementById('wo-publish-status');

		if ( ! title || ! content ) { statusEl.textContent = 'Title and content required.'; return; }

		this.disabled = true;
		statusEl.textContent = 'Publishing…';

		try {
			const res  = await fetch( cfg.apiUrl + '/blog/publish', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
				body: JSON.stringify({ title, content }),
			});
			const data = await res.json();
			if ( ! res.ok ) throw new Error( data.message || 'Error' );

			statusEl.innerHTML = '&#10003; Saved as draft. <a href="' + data.edit_url + '" target="_blank">Edit in WP →</a>';
		} catch (e) {
			statusEl.textContent = 'Error: ' + e.message;
		} finally {
			this.disabled = false;
		}
	});
})();
</script>

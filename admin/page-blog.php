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

	<div style="max-width:960px;margin-top:20px;display:grid;grid-template-columns:3fr 2fr;gap:20px;align-items:start">

		<div class="postbox">
			<div class="postbox-header"><h2 class="hndle">Generate post</h2></div>
			<div class="inside">
				<table class="form-table" style="margin:0">
					<tr>
						<th style="width:80px"><label for="wo-blog-topic">Topic</label></th>
						<td><input type="text" id="wo-blog-topic" class="large-text" placeholder="e.g. How I migrated a WooCommerce shop to headless WordPress"></td>
					</tr>
					<tr>
						<th><label for="wo-blog-format">Format</label></th>
						<td>
							<select id="wo-blog-format" class="regular-text">
								<?php foreach ( $formats as $val => $label ) : ?>
									<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<?php if ( ! empty( $memory_events ) ) : ?>
					<tr>
						<th><label for="wo-blog-memory">Memory</label></th>
						<td>
							<select id="wo-blog-memory" class="regular-text">
								<option value="">— none —</option>
								<?php foreach ( $memory_events as $ev ) : ?>
									<option value="<?php echo (int) $ev['id']; ?>">
										<?php echo esc_html( substr( $ev['created_at'], 0, 10 ) . ' — ' . wp_trim_words( $ev['note'], 8 ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">Ground the post in a specific memory event.</p>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th><label for="wo-blog-extra">Extra context</label></th>
						<td><textarea id="wo-blog-extra" rows="3" class="large-text" placeholder="Target audience, specific angle, key points to cover…"></textarea></td>
					</tr>
				</table>
				<p>
					<button id="wo-generate-btn" class="button button-primary" <?php echo $claude_set ? '' : 'disabled'; ?>>Generate with Claude</button>
					<span id="wo-generate-status" style="margin-left:10px;font-size:13px;color:#646970"></span>
				</p>
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
				<div class="postbox-header">
					<h2 class="hndle">Generated Post</h2>
					<div style="display:flex;align-items:center;gap:8px;margin:10px 12px 0 0">
						<input type="text" id="wo-post-title" class="regular-text" placeholder="Post title…" style="font-size:13px;min-width:260px">
						<button id="wo-publish-btn" class="button button-primary">Publish as draft →</button>
					</div>
				</div>
				<div class="inside">
					<div id="wo-publish-status" style="margin-bottom:10px;font-size:13px;color:#646970"></div>
					<textarea id="wo-post-content" rows="22" class="large-text" style="font-size:13px;line-height:1.65;font-family:Georgia,serif"></textarea>
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

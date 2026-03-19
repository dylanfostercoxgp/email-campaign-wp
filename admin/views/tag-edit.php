<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! $tag ) { echo '<div class="wrap"><p>Tag not found.</p></div>'; return; }
?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Edit Tag: <?php echo esc_html( $tag->name ); ?></h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-tags' ); ?>" class="ecwp-btn ecwp-btn-secondary">&larr; Back to Tags</a>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Tag updated successfully.</div>
	<?php endif; ?>

	<div style="max-width:480px;">
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-tag"></span> Tag Details</div>
			<div class="ecwp-card-body">
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<input type="hidden" name="action" value="ecwp_edit_tag">
					<input type="hidden" name="tag_id" value="<?php echo $tag->id; ?>">
					<?php wp_nonce_field( 'ecwp_edit_tag' ); ?>

					<div class="ecwp-field">
						<label for="tag_name">Tag Name <span class="required">*</span></label>
						<input type="text" id="tag_name" name="name" class="ecwp-input"
						       value="<?php echo esc_attr( $tag->name ); ?>" required>
					</div>
					<div class="ecwp-field">
						<label for="tag_color">Tag Color</label>
						<div style="display:flex;gap:10px;align-items:center;">
							<input type="color" id="tag_color" name="color"
							       value="<?php echo esc_attr( $tag->color ); ?>"
							       style="width:48px;height:38px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;cursor:pointer;">
							<span id="ecwp-color-preview"
							      style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:99px;font-size:13px;font-weight:600;background:<?php echo esc_attr( $tag->color ); ?>22;color:<?php echo esc_attr( $tag->color ); ?>;border:1px solid <?php echo esc_attr( $tag->color ); ?>44;">
								<span id="ecwp-color-dot" style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;display:inline-block;"></span>
								<span id="ecwp-color-name"><?php echo esc_html( $tag->name ); ?></span>
							</span>
						</div>
					</div>
					<div class="ecwp-form-actions" style="margin-top:16px;display:flex;gap:10px;">
						<button type="submit" class="ecwp-btn ecwp-btn-primary">Save Changes</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-tags&action=view&tag_id=' . $tag->id ) ); ?>"
						   class="ecwp-btn ecwp-btn-secondary">View Subscribers</a>
					</div>
				</form>
			</div>
		</div>
	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>
<script>
(function() {
	var colorInput = document.getElementById('tag_color');
	var dot        = document.getElementById('ecwp-color-dot');
	var preview    = document.getElementById('ecwp-color-preview');
	var nameSpan   = document.getElementById('ecwp-color-name');
	var nameInput  = document.getElementById('tag_name');

	function updatePreview() {
		var c = colorInput.value;
		dot.style.background  = c;
		preview.style.color   = c;
		preview.style.background = c + '22';
		preview.style.borderColor = c + '44';
		nameSpan.textContent  = nameInput.value || 'Preview';
	}
	colorInput.addEventListener('input', updatePreview);
	nameInput.addEventListener('input', updatePreview);
})();
</script>

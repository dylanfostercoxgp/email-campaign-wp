<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! $tag ) { echo '<div class="wrap"><p>Tag not found.</p></div>'; return; }
?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			<span style="display:inline-flex;align-items:center;gap:8px;">
				<span style="width:14px;height:14px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;display:inline-block;"></span>
				<?php echo esc_html( $tag->name ); ?>
			</span>
		</h1>
		<div style="display:flex;gap:8px;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-tags&action=edit&tag_id=' . $tag->id ) ); ?>"
			   class="ecwp-btn ecwp-btn-secondary">Edit Tag</a>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-tags' ); ?>"
			   class="ecwp-btn ecwp-btn-secondary">&larr; Back to Tags</a>
		</div>
	</div>

	<?php if ( isset( $_GET['removed'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Subscriber removed from tag.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Subscriber deleted.</div>
	<?php endif; ?>

	<!-- Tag stats bar -->
	<div class="ecwp-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));margin-bottom:20px;">
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:<?php echo esc_attr( $tag->color ); ?>22;color:<?php echo esc_attr( $tag->color ); ?>;">
				<span class="dashicons dashicons-tag"></span>
			</div>
			<div>
				<div class="ecwp-stat-value"><?php echo number_format( count( $tag_subscribers ) ); ?></div>
				<div class="ecwp-stat-label">Subscribers</div>
			</div>
		</div>
	</div>

	<!-- Subscribers table -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-groups"></span>
			Subscribers with tag &ldquo;<?php echo esc_html( $tag->name ); ?>&rdquo;
			(<?php echo number_format( count( $tag_subscribers ) ); ?>)
			<div style="margin-left:auto;">
				<input type="text" class="ecwp-input ecwp-input-sm" placeholder="Search&hellip;"
				       oninput="filterTable(this,'ecwp-tag-sub-table')" style="width:180px;">
			</div>
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $tag_subscribers ) ) : ?>
				<div class="ecwp-empty" style="padding:32px;">No subscribers have this tag yet.</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover" id="ecwp-tag-sub-table">
					<thead>
						<tr>
							<th>Email</th>
							<th>Name</th>
							<th>Status</th>
							<th>Subscribed</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $tag_subscribers as $sub ) : ?>
						<tr>
							<td><?php echo esc_html( $sub->email ); ?></td>
							<td><?php echo esc_html( trim( $sub->first_name . ' ' . $sub->last_name ) ?: '—' ); ?></td>
							<td>
								<?php if ( $sub->status === 'active' ) : ?>
									<span class="ecwp-badge ecwp-badge-green">Active</span>
								<?php else : ?>
									<span class="ecwp-badge ecwp-badge-grey">Unsubscribed</span>
								<?php endif; ?>
							</td>
							<td style="white-space:nowrap;"><?php echo esc_html( date( 'M j, Y', strtotime( $sub->subscribed_at ) ) ); ?></td>
							<td class="ecwp-actions">
								<!-- Edit subscriber -->
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-subscribers&action=edit&subscriber_id=' . $sub->id ) ); ?>"
								   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">Edit</a>

								<!-- Remove from this tag (standalone form) -->
								<button type="button"
								        class="ecwp-btn ecwp-btn-warning ecwp-btn-sm"
								        onclick="ecwpRemoveFromTag(<?php echo (int) $sub->id; ?>)">Remove Tag</button>

								<!-- Delete subscriber entirely -->
								<button type="button"
								        class="ecwp-btn ecwp-btn-danger ecwp-btn-sm"
								        onclick="ecwpDeleteSub(<?php echo (int) $sub->id; ?>)">Delete</button>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Remove-from-tag form (standalone, outside any other form) -->
	<form id="ecwp-remove-tag-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
		<input type="hidden" name="action"        value="ecwp_remove_subscriber_from_tag">
		<input type="hidden" name="tag_id"        value="<?php echo $tag->id; ?>">
		<input type="hidden" name="subscriber_id" id="ecwp-rtag-sub-id" value="">
		<?php wp_nonce_field( 'ecwp_remove_subscriber_from_tag' ); ?>
	</form>

	<!-- Delete subscriber form (standalone) -->
	<form id="ecwp-delete-sub-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
		<input type="hidden" name="action"           value="ecwp_delete_subscriber">
		<input type="hidden" name="subscriber_id"    id="ecwp-del-sub-id" value="">
		<input type="hidden" name="ecwp_redirect_to" value="<?php echo esc_attr( admin_url( 'admin.php?page=ecwp-tags&action=view&tag_id=' . $tag->id . '&deleted=1' ) ); ?>">
		<?php wp_nonce_field( 'ecwp_delete_subscriber' ); ?>
	</form>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>
<script>
function ecwpRemoveFromTag(id) {
	if ( ! confirm( 'Remove this subscriber from the "<?php echo esc_js( $tag->name ); ?>" tag?' ) ) { return; }
	document.getElementById('ecwp-rtag-sub-id').value = id;
	document.getElementById('ecwp-remove-tag-form').submit();
}
function ecwpDeleteSub(id) {
	if ( ! confirm( 'Permanently delete this subscriber? This cannot be undone.' ) ) { return; }
	document.getElementById('ecwp-del-sub-id').value = id;
	document.getElementById('ecwp-delete-sub-form').submit();
}
</script>

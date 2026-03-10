<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Tags</h1>
	</div>

	<?php if ( isset( $_GET['created'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Tag created successfully.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Tag deleted.</div>
	<?php endif; ?>

	<div class="ecwp-two-col">

		<!-- Create tag -->
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-tag"></span> Create New Tag</div>
			<div class="ecwp-card-body">
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<input type="hidden" name="action" value="ecwp_create_tag">
					<?php wp_nonce_field( 'ecwp_create_tag' ); ?>
					<div class="ecwp-field">
						<label for="tag_name">Tag Name <span class="required">*</span></label>
						<input type="text" id="tag_name" name="name" class="ecwp-input" placeholder="e.g. Newsletter, VIP, Lead" required>
					</div>
					<div class="ecwp-field">
						<label for="tag_color">Tag Color</label>
						<div style="display:flex;gap:10px;align-items:center;">
							<input type="color" id="tag_color" name="color" value="#3b82f6" style="width:48px;height:38px;border:1px solid #e5e7eb;border-radius:6px;padding:2px;cursor:pointer;">
							<span class="ecwp-hint" style="margin:0;">Choose a color to visually identify this tag</span>
						</div>
					</div>
					<div class="ecwp-form-actions" style="margin-top:16px;">
						<button type="submit" class="ecwp-btn ecwp-btn-primary">Create Tag</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Tags list -->
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-list-view"></span> All Tags (<?php echo count( $all_tags ); ?>)</div>
			<div class="ecwp-card-body ecwp-no-pad">
				<?php if ( empty( $all_tags ) ) : ?>
					<div class="ecwp-empty" style="padding:32px;">No tags yet. Create your first tag to get started.</div>
				<?php else : ?>
					<table class="ecwp-table ecwp-table-hover">
						<thead>
							<tr>
								<th>Tag</th>
								<th>Subscribers</th>
								<th>Created</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $all_tags as $tag ) : ?>
							<tr>
								<td>
									<span style="display:inline-flex;align-items:center;gap:6px;">
										<span style="width:12px;height:12px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;display:inline-block;"></span>
										<strong><?php echo esc_html( $tag->name ); ?></strong>
									</span>
								</td>
								<td><?php echo number_format( $tag->subscriber_count ); ?></td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $tag->created_at ) ) ); ?></td>
								<td class="ecwp-actions">
									<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
									      class="ecwp-confirm-form"
									      data-confirm="Delete tag &quot;<?php echo esc_attr( $tag->name ); ?>&quot;? This removes it from all subscribers.">
										<input type="hidden" name="action"   value="ecwp_delete_tag">
										<input type="hidden" name="tag_id"   value="<?php echo $tag->id; ?>">
										<?php wp_nonce_field( 'ecwp_delete_tag' ); ?>
										<button type="submit" class="ecwp-btn ecwp-btn-danger ecwp-btn-sm">Delete</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

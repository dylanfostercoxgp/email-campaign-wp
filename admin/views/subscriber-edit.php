<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			<?php echo $subscriber ? 'Edit Subscriber' : 'Add Subscriber'; ?>
		</h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-subscribers' ); ?>" class="ecwp-btn ecwp-btn-secondary">
			← Back to Subscribers
		</a>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Subscriber updated successfully.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['edit_error'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-error"><?php echo esc_html( urldecode( $_GET['edit_error'] ) ); ?></div>
	<?php endif; ?>

	<?php if ( ! $subscriber ) : ?>
		<div class="ecwp-notice ecwp-notice-error">Subscriber not found.</div>
	<?php else : ?>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
		<input type="hidden" name="action"        value="ecwp_edit_subscriber">
		<input type="hidden" name="subscriber_id" value="<?php echo $subscriber->id; ?>">
		<?php wp_nonce_field( 'ecwp_edit_subscriber' ); ?>

		<div class="ecwp-two-col">

			<!-- Contact details -->
			<div class="ecwp-card">
				<div class="ecwp-card-header"><span class="dashicons dashicons-admin-users"></span> Contact Details</div>
				<div class="ecwp-card-body">
					<div class="ecwp-field">
						<label for="sub_email">Email Address <span class="required">*</span></label>
						<input type="email" id="sub_email" name="email" value="<?php echo esc_attr( $subscriber->email ); ?>"
						       class="ecwp-input" required>
					</div>
					<div class="ecwp-field-row">
						<div class="ecwp-field">
							<label for="sub_fn">First Name</label>
							<input type="text" id="sub_fn" name="first_name" value="<?php echo esc_attr( $subscriber->first_name ); ?>"
							       class="ecwp-input" placeholder="Optional">
						</div>
						<div class="ecwp-field">
							<label for="sub_ln">Last Name</label>
							<input type="text" id="sub_ln" name="last_name" value="<?php echo esc_attr( $subscriber->last_name ); ?>"
							       class="ecwp-input" placeholder="Optional">
						</div>
					</div>
					<div class="ecwp-field">
						<label for="sub_phone">Phone</label>
						<input type="text" id="sub_phone" name="phone"
						       value="<?php echo esc_attr( $subscriber->phone ?? '' ); ?>"
						       class="ecwp-input" placeholder="Optional">
					</div>
					<div class="ecwp-field">
						<label for="sub_address">Address</label>
						<input type="text" id="sub_address" name="address"
						       value="<?php echo esc_attr( $subscriber->address ?? '' ); ?>"
						       class="ecwp-input" placeholder="Optional">
					</div>
					<div class="ecwp-field">
						<label for="sub_website">Website URL</label>
						<input type="url" id="sub_website" name="website"
						       value="<?php echo esc_attr( $subscriber->website ?? '' ); ?>"
						       class="ecwp-input" placeholder="https://example.com (Optional)">
					</div>
					<div class="ecwp-field">
						<label for="sub_notes">Notes</label>
						<textarea id="sub_notes" name="notes" class="ecwp-input" rows="3"
						          placeholder="Optional notes about this contact"><?php echo esc_textarea( $subscriber->notes ?? '' ); ?></textarea>
					</div>
					<div class="ecwp-field">
						<label for="sub_status">Status</label>
						<select id="sub_status" name="status" class="ecwp-input ecwp-input-sm">
							<option value="active"       <?php selected( $subscriber->status, 'active' ); ?>>Active</option>
							<option value="unsubscribed" <?php selected( $subscriber->status, 'unsubscribed' ); ?>>Unsubscribed</option>
						</select>
					</div>
				</div>
			</div>

			<!-- Tags -->
			<div class="ecwp-card">
				<div class="ecwp-card-header"><span class="dashicons dashicons-tag"></span> Tags</div>
				<div class="ecwp-card-body">
					<?php if ( empty( $all_tags ) ) : ?>
						<p class="ecwp-hint">No tags yet. <a href="<?php echo admin_url( 'admin.php?page=ecwp-tags' ); ?>">Create tags</a> first.</p>
					<?php else : ?>
						<div class="ecwp-sub-list" style="max-height:300px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;">
							<?php foreach ( $all_tags as $tag ) : ?>
								<label class="ecwp-sub-item">
									<input type="checkbox" name="tag_ids[]" value="<?php echo $tag->id; ?>"
									       <?php checked( in_array( (int) $tag->id, $subscriber_tag_ids, true ) ); ?>>
									<span style="display:inline-flex;align-items:center;gap:6px;">
										<span style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;flex-shrink:0;"></span>
										<?php echo esc_html( $tag->name ); ?>
									</span>
									<small><?php echo number_format( $tag->subscriber_count ); ?> subscribers</small>
								</label>
							<?php endforeach; ?>
						</div>
						<span class="ecwp-hint" style="margin-top:8px;display:block;">Check all tags that apply to this subscriber.</span>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<!-- Subscriber info -->
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-info"></span> Subscriber Info</div>
			<div class="ecwp-card-body">
				<table class="ecwp-info-table">
					<tr><th>Subscriber ID</th><td>#<?php echo $subscriber->id; ?></td></tr>
					<tr><th>Subscribed</th><td><?php echo esc_html( date( 'M j, Y \a\t g:i a', strtotime( $subscriber->subscribed_at ) ) ); ?></td></tr>
					<?php if ( $subscriber->unsubscribed_at ) : ?>
						<tr><th>Unsubscribed</th><td><?php echo esc_html( date( 'M j, Y \a\t g:i a', strtotime( $subscriber->unsubscribed_at ) ) ); ?></td></tr>
					<?php endif; ?>
				</table>
			</div>
		</div>

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Save Changes</button>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-subscribers' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-lg">Cancel</a>
		</div>
	</form>

	<?php endif; ?>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">New Campaign</h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary">← Back</a>
	</div>

	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-error"><?php echo esc_html( urldecode( $_GET['error'] ) ); ?></div>
	<?php endif; ?>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action" value="ecwp_create_campaign">
		<?php wp_nonce_field( 'ecwp_create_campaign' ); ?>

		<div class="ecwp-form-grid">

			<!-- Left: Campaign details -->
			<div>

				<!-- Details -->
				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-email-alt"></span> Campaign Details</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label for="camp_name">Campaign Name <span class="required">*</span></label>
							<input type="text" id="camp_name" name="name" class="ecwp-input" placeholder="March Newsletter" required>
						</div>
						<div class="ecwp-field">
							<label for="camp_subject">Email Subject Line <span class="required">*</span></label>
							<input type="text" id="camp_subject" name="subject" class="ecwp-input" placeholder="Hey {{first_name}}, here's what's new…" required>
						</div>
						<div class="ecwp-field">
							<label for="camp_preview">Preview Text</label>
							<input type="text" id="camp_preview" name="preview_text" class="ecwp-input" placeholder="The one-line teaser shown in the inbox beneath your subject line…" maxlength="150">
							<span class="ecwp-hint">Shown in the inbox below the subject line. Keep it under 90 characters. Leave blank to let the email client use the first visible text in the email.</span>
						</div>
					</div>
				</div>

				<!-- HTML Content -->
				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-editor-code"></span> Email HTML</div>
					<div class="ecwp-card-body">

						<!-- Template picker -->
						<?php if ( ! empty( $all_templates ) ) : ?>
						<div class="ecwp-field">
							<label>Start from Template</label>
							<div class="ecwp-template-mini-grid">
								<?php foreach ( $all_templates as $tpl ) :
									$tpl_id   = is_array( $tpl ) ? $tpl['id']                  : $tpl->id;
									$tpl_name = is_array( $tpl ) ? $tpl['name']                : $tpl->name;
									$tpl_acc  = is_array( $tpl ) ? ( $tpl['preview'] ?? '#2563eb' ) : '#2563eb';
									$tpl_icon = is_array( $tpl ) ? ( $tpl['icon']   ?? '📄' )       : '📄';
								?>
									<label class="ecwp-template-mini-card">
										<input type="radio" name="template_id" value="<?php echo $tpl_id; ?>" style="display:none;">
										<div class="ecwp-template-mini-icon" style="background:<?php echo esc_attr( $tpl_acc ); ?>22;color:<?php echo esc_attr( $tpl_acc ); ?>;">
											<?php echo $tpl_icon; ?>
										</div>
										<span><?php echo esc_html( $tpl_name ); ?></span>
									</label>
								<?php endforeach; ?>
								<label class="ecwp-template-mini-card">
									<input type="radio" name="template_id" value="" checked style="display:none;">
									<div class="ecwp-template-mini-icon" style="background:#f3f4f6;color:#6b7280;">📝</div>
									<span>Blank / Upload</span>
								</label>
							</div>
							<span class="ecwp-hint">Selecting a template pre-fills the email HTML. You can still upload or edit it after.</span>
						</div>
						<hr class="ecwp-divider">
						<?php endif; ?>

						<div class="ecwp-field">
							<label for="html_file">Upload HTML File</label>
							<input type="file" id="html_file" name="html_file" accept=".html,.htm">
							<span class="ecwp-file-name"></span>
							<span class="ecwp-hint">Overrides the template selection above if a file is chosen.</span>
						</div>
						<div class="ecwp-field">
							<label>Available Placeholders</label>
							<div class="ecwp-code" style="font-size:12px;">
								<code>{{first_name}}</code> &nbsp; <code>{{last_name}}</code> &nbsp;
								<code>{{email}}</code> &nbsp; <code>{{unsubscribe_url}}</code> &nbsp;
								<code>{{unsubscribe_link}}</code>
							</div>
						</div>
					</div>
				</div>

				<!-- Audience -->
				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-groups"></span> Audience</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label>Who receives this campaign?</label>
							<div style="display:flex;flex-direction:column;gap:10px;">
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="all" checked onchange="ecwpTargetChange(this)">
									<div>
										<strong>All Active Subscribers</strong>
										<span class="ecwp-hint" style="display:block;">Every active subscriber receives this campaign.</span>
									</div>
								</label>
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="tags" onchange="ecwpTargetChange(this)">
									<div>
										<strong>By Tags</strong>
										<span class="ecwp-hint" style="display:block;">Send only to subscribers who have any of the selected tags.</span>
									</div>
								</label>
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="selected" onchange="ecwpTargetChange(this)">
									<div>
										<strong>Specific Subscribers</strong>
										<span class="ecwp-hint" style="display:block;">Hand-pick individual subscribers from the list below.</span>
									</div>
								</label>
							</div>
						</div>

						<!-- Tag selector (shown when target = tags) -->
						<div id="ecwp-tag-target" style="display:none;">
							<hr class="ecwp-divider">
							<label style="font-weight:600;font-size:13px;display:block;margin-bottom:8px;">Select Tags <span class="required">*</span></label>
							<?php if ( empty( $all_tags ) ) : ?>
								<p class="ecwp-hint">No tags yet. <a href="<?php echo admin_url( 'admin.php?page=ecwp-tags' ); ?>">Create tags first.</a></p>
							<?php else : ?>
								<div style="display:flex;flex-wrap:wrap;gap:8px;">
									<?php foreach ( $all_tags as $tag ) : ?>
										<label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;background:<?php echo esc_attr( $tag->color ); ?>11;border:1px solid <?php echo esc_attr( $tag->color ); ?>44;border-radius:99px;padding:4px 12px;">
											<input type="checkbox" name="target_tag_ids[]" value="<?php echo $tag->id; ?>">
											<span style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;"></span>
											<?php echo esc_html( $tag->name ); ?>
											<small style="color:<?php echo esc_attr( $tag->color ); ?>;">(<?php echo number_format( $tag->subscriber_count ); ?>)</small>
										</label>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>

						<!-- Subscriber picker (shown when target = selected) -->
						<div id="ecwp-subscriber-target" style="display:none;">
							<hr class="ecwp-divider">
							<div id="subscriber_list_wrap">
								<input type="text" class="ecwp-input" style="margin-bottom:8px;" placeholder="Filter subscribers…" oninput="filterSubscribers(this)">
								<div class="ecwp-sub-list" style="max-height:280px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;">
									<?php foreach ( $all_subscribers as $sub ) : ?>
										<label class="ecwp-sub-item">
											<input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>">
											<span><?php echo esc_html( $sub->email ); ?></span>
											<small><?php echo esc_html( trim( $sub->first_name . ' ' . $sub->last_name ) ); ?></small>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>

					</div>
				</div>

			</div><!-- /left -->

			<!-- Right: Scheduling & batch -->
			<div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-clock"></span> Scheduling</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="schedule_enabled" value="1" class="ecwp-toggle-input">
								<span class="ecwp-toggle"></span>
								<strong>Enable auto-schedule</strong>
							</label>
							<span class="ecwp-hint" style="margin-top:6px;display:block;">When on, fires automatically at the daily trigger time.</span>
						</div>
						<div class="ecwp-field">
							<label for="send_time">Send Time</label>
							<input type="time" id="send_time" name="send_time" value="<?php echo esc_attr( get_option( 'ecwp_send_time', '10:00' ) ); ?>" class="ecwp-input ecwp-input-sm">
						</div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-filter"></span> Batch Settings</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field-row">
							<div class="ecwp-field">
								<label for="batch_size">Batch Size</label>
								<input type="number" id="batch_size" name="batch_size" value="<?php echo esc_attr( get_option( 'ecwp_batch_size', 10 ) ); ?>" min="1" max="500" class="ecwp-input ecwp-input-sm">
								<span class="ecwp-hint">Emails per batch</span>
							</div>
							<div class="ecwp-field">
								<label for="batch_interval">Interval (min)</label>
								<input type="number" id="batch_interval" name="batch_interval" value="<?php echo esc_attr( get_option( 'ecwp_batch_interval', 30 ) ); ?>" min="1" class="ecwp-input ecwp-input-sm">
								<span class="ecwp-hint">Gap between batches</span>
							</div>
						</div>
						<div id="ecwp-batch-preview" class="ecwp-notice ecwp-notice-info" style="margin-top:8px;font-size:12px;"></div>
					</div>
				</div>

			</div><!-- /right -->

		</div><!-- /form-grid -->

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Create Campaign</button>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-lg">Cancel</a>
		</div>
	</form>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

<script>
function ecwpTargetChange(radio) {
	document.getElementById('ecwp-tag-target').style.display        = radio.value === 'tags'     ? '' : 'none';
	document.getElementById('ecwp-subscriber-target').style.display = radio.value === 'selected' ? '' : 'none';
}
// Template mini-card visual selection
document.querySelectorAll('.ecwp-template-mini-card input').forEach(function(r) {
	r.addEventListener('change', function() {
		document.querySelectorAll('.ecwp-template-mini-card').forEach(function(c){ c.classList.remove('selected'); });
		r.closest('.ecwp-template-mini-card').classList.add('selected');
	});
});
</script>

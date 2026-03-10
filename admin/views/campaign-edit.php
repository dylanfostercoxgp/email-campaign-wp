<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! $campaign ) { echo '<div class="wrap"><p>Campaign not found.</p></div>'; return; }
$assigned_ids = array_column( (array) $campaign_subscribers, 'id' );
?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Edit: <?php echo esc_html( $campaign->name ); ?></h1>
		<div style="display:flex;gap:8px;">
			<a href="<?php echo admin_url( "admin.php?page=ecwp-html-editor&campaign_id={$campaign->id}" ); ?>" class="ecwp-btn ecwp-btn-success">
				<span class="dashicons dashicons-editor-code"></span> HTML Editor
			</a>
			<a href="<?php echo admin_url( "admin.php?page=ecwp-analytics&campaign_id={$campaign->id}" ); ?>" class="ecwp-btn ecwp-btn-secondary">Analytics</a>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary">← Back</a>
		</div>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Campaign updated successfully.</div>
	<?php endif; ?>

	<!-- Quick actions -->
	<div class="ecwp-two-col" style="margin-bottom:0;">
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
			<input type="hidden" name="action"      value="ecwp_trigger_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_trigger_campaign' ); ?>
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg" style="width:100%;">
				<span class="dashicons dashicons-controls-play"></span> Send Now
			</button>
		</form>
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
		      class="ecwp-confirm-form" data-confirm="Pause this campaign?">
			<input type="hidden" name="action"      value="ecwp_pause_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_pause_campaign' ); ?>
			<button type="submit" class="ecwp-btn ecwp-btn-warning ecwp-btn-lg" style="width:100%;">
				<span class="dashicons dashicons-controls-pause"></span> Pause
			</button>
		</form>
	</div>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data" style="margin-top:20px;">
		<input type="hidden" name="action"      value="ecwp_update_campaign">
		<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
		<?php wp_nonce_field( 'ecwp_update_campaign' ); ?>

		<div class="ecwp-form-grid">

			<!-- Left -->
			<div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-email-alt"></span> Campaign Details</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label>Campaign Name <span class="required">*</span></label>
							<input type="text" name="name" value="<?php echo esc_attr( $campaign->name ); ?>" class="ecwp-input" required>
						</div>
						<div class="ecwp-field">
							<label>Subject Line <span class="required">*</span></label>
							<input type="text" name="subject" value="<?php echo esc_attr( $campaign->subject ); ?>" class="ecwp-input" required>
						</div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-editor-code"></span> Email HTML</div>
					<div class="ecwp-card-body">
						<?php if ( $campaign->html_content ) : ?>
							<div class="ecwp-notice ecwp-notice-info" style="margin-bottom:12px;">
								✅ This campaign has HTML content.
								<a href="<?php echo admin_url( "admin.php?page=ecwp-html-editor&campaign_id={$campaign->id}" ); ?>" style="font-weight:600;">
									Open in HTML Editor →
								</a>
							</div>
							<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:6px;overflow:hidden;">
								<iframe srcdoc="<?php echo esc_attr( $campaign->html_content ); ?>"
								        style="width:100%;height:200px;border:none;pointer-events:none;"
								        title="Email preview"></iframe>
							</div>
							<p class="ecwp-hint" style="margin-top:8px;">Upload a new file below to replace the current HTML.</p>
						<?php endif; ?>
						<div class="ecwp-field" style="margin-top:12px;">
							<label>Replace HTML File</label>
							<input type="file" name="html_file" accept=".html,.htm">
							<span class="ecwp-file-name"></span>
						</div>
						<!-- Template picker -->
						<?php if ( ! empty( $all_templates ) ) : ?>
						<div class="ecwp-field">
							<label>Or Load a Template</label>
							<div class="ecwp-template-mini-grid">
								<?php foreach ( $all_templates as $tpl ) :
									$tpl_id   = is_array( $tpl ) ? $tpl['id']                      : $tpl->id;
									$tpl_name = is_array( $tpl ) ? $tpl['name']                    : $tpl->name;
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
							</div>
							<span class="ecwp-hint">Selecting a template replaces the current HTML on save.</span>
						</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Audience -->
				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-groups"></span> Audience</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<div style="display:flex;flex-direction:column;gap:10px;">
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="all"
									       <?php checked( $campaign->target_type, 'all' ); ?>
									       onchange="ecwpTargetChange(this)">
									<div><strong>All Active Subscribers</strong></div>
								</label>
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="tags"
									       <?php checked( $campaign->target_type, 'tags' ); ?>
									       onchange="ecwpTargetChange(this)">
									<div><strong>By Tags</strong></div>
								</label>
								<label style="cursor:pointer;display:flex;align-items:flex-start;gap:8px;">
									<input type="radio" name="target_type" value="selected"
									       <?php checked( $campaign->target_type, 'selected' ); ?>
									       onchange="ecwpTargetChange(this)">
									<div><strong>Specific Subscribers</strong></div>
								</label>
							</div>
						</div>

						<div id="ecwp-tag-target" style="display:<?php echo $campaign->target_type === 'tags' ? '' : 'none'; ?>;">
							<hr class="ecwp-divider">
							<label style="font-weight:600;font-size:13px;display:block;margin-bottom:8px;">Select Tags</label>
							<div style="display:flex;flex-wrap:wrap;gap:8px;">
								<?php foreach ( $all_tags as $tag ) : ?>
									<label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;background:<?php echo esc_attr( $tag->color ); ?>11;border:1px solid <?php echo esc_attr( $tag->color ); ?>44;border-radius:99px;padding:4px 12px;">
										<input type="checkbox" name="target_tag_ids[]" value="<?php echo $tag->id; ?>"
										       <?php checked( in_array( (int) $tag->id, $selected_tag_ids, true ) ); ?>>
										<span style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;"></span>
										<?php echo esc_html( $tag->name ); ?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div id="ecwp-subscriber-target" style="display:<?php echo $campaign->target_type === 'selected' ? '' : 'none'; ?>;">
							<hr class="ecwp-divider">
							<input type="text" class="ecwp-input" style="margin-bottom:8px;" placeholder="Filter subscribers…" oninput="filterSubscribers(this)">
							<div id="subscriber_list_wrap">
								<div class="ecwp-sub-list" style="max-height:280px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;">
									<?php foreach ( $all_subscribers as $sub ) : ?>
										<label class="ecwp-sub-item">
											<input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>"
											       <?php checked( in_array( (int) $sub->id, $assigned_ids, true ) ); ?>>
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

			<!-- Right: Scheduling + batch -->
			<div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-info"></span> Campaign Status</div>
					<div class="ecwp-card-body">
						<table class="ecwp-info-table">
							<tr><th>Status</th>
								<td>
								<?php
								$status_map = ['draft'=>['grey','Draft'],'scheduled'=>['blue','Scheduled'],'sending'=>['yellow','Sending'],'sent'=>['green','Sent'],'paused'=>['orange','Paused']];
								[$sc,$sl] = $status_map[$campaign->status] ?? ['grey',ucfirst($campaign->status)];
								echo "<span class='ecwp-badge ecwp-badge-{$sc}'>{$sl}</span>";
								?>
								</td>
							</tr>
							<tr><th>Sent</th><td><?php echo number_format( $campaign->total_sent ); ?></td></tr>
							<tr><th>Subscribers</th><td><?php echo number_format( count( $assigned_ids ) ); ?></td></tr>
							<tr><th>Created</th><td><?php echo esc_html( date( 'M j, Y', strtotime( $campaign->created_at ) ) ); ?></td></tr>
						</table>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-clock"></span> Scheduling</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="schedule_enabled" value="1" class="ecwp-toggle-input"
								       <?php checked( $campaign->schedule_enabled, 1 ); ?>>
								<span class="ecwp-toggle"></span>
								<strong>Enable auto-schedule</strong>
							</label>
						</div>
						<div class="ecwp-field">
							<label>Send Time</label>
							<input type="time" name="send_time" value="<?php echo esc_attr( $campaign->send_time ); ?>" class="ecwp-input ecwp-input-sm">
						</div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-filter"></span> Batch Settings</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field-row">
							<div class="ecwp-field">
								<label>Batch Size</label>
								<input type="number" id="ecwp_batch_size" name="batch_size" value="<?php echo esc_attr( $campaign->batch_size ); ?>" min="1" max="500" class="ecwp-input ecwp-input-sm">
							</div>
							<div class="ecwp-field">
								<label>Interval (min)</label>
								<input type="number" id="ecwp_batch_interval" name="batch_interval" value="<?php echo esc_attr( $campaign->batch_interval ); ?>" min="1" class="ecwp-input ecwp-input-sm">
							</div>
						</div>
						<div id="ecwp-batch-preview" class="ecwp-notice ecwp-notice-info" style="margin-top:8px;font-size:12px;"></div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-trash"></span> Danger Zone</div>
					<div class="ecwp-card-body">
						<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
						      class="ecwp-confirm-form" data-confirm="Permanently delete this campaign?">
							<input type="hidden" name="action"      value="ecwp_delete_campaign">
							<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
							<?php wp_nonce_field( 'ecwp_delete_campaign' ); ?>
							<button type="submit" class="ecwp-btn ecwp-btn-danger" style="width:100%;">Delete Campaign</button>
						</form>
					</div>
				</div>

			</div><!-- /right -->

		</div><!-- /form-grid -->

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Save Changes</button>
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
document.querySelectorAll('.ecwp-template-mini-card input').forEach(function(r) {
	r.addEventListener('change', function() {
		document.querySelectorAll('.ecwp-template-mini-card').forEach(function(c){ c.classList.remove('selected'); });
		r.closest('.ecwp-template-mini-card').classList.add('selected');
	});
});
</script>

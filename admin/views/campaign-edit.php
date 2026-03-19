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

	<?php if ( isset( $_GET['updated'] ) && ! isset( $_GET['sent'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Campaign updated successfully.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['sent'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">✅ Campaign saved and sending has started!</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['scheduled'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">🕐 Campaign scheduled! It will send automatically at the configured time.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['unscheduled'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-info">Campaign unscheduled and set back to draft.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['schedule_error'] ) && $_GET['schedule_error'] === 'past' ) : ?>
		<div class="ecwp-notice ecwp-notice-error">&#9888; That date and time is already in the past. Please choose a future date and time.</div>
	<?php endif; ?>

	<!-- Quick actions — buttons vary by campaign status -->
	<?php
		$s          = $campaign->status;
		$scheduler  = new ECWP_Scheduler();
		$next_run   = $scheduler->get_next_run( $campaign->id );
		$is_draft   = in_array( $s, [ 'draft', 'paused' ], true );
		$is_sched   = ( $s === 'scheduled' );
		$is_sending = ( $s === 'sending' );
	?>
	<div style="display:flex;gap:10px;margin-bottom:0;flex-wrap:wrap;">

		<!-- Send Now — always available for draft / scheduled / paused -->
		<?php if ( $is_draft || $is_sched ) : ?>
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="flex:1;min-width:160px;">
			<input type="hidden" name="action"      value="ecwp_trigger_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_trigger_campaign' ); ?>
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg" style="width:100%;">
				<span class="dashicons dashicons-controls-play"></span> Send Now
			</button>
		</form>
		<?php endif; ?>

		<!-- Schedule Send — shown for draft / paused: includes date + time pickers -->
		<?php if ( $is_draft ) : ?>
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="flex:1;">
			<input type="hidden" name="action"      value="ecwp_schedule_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_schedule_campaign' ); ?>
			<div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
				<input type="date" name="send_date"
				       class="ecwp-input ecwp-input-sm"
				       value="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
				       min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>"
				       required
				       style="width:145px;">
				<input type="time" name="send_time"
				       class="ecwp-input ecwp-input-sm"
				       value="<?php echo esc_attr( $campaign->send_time ?: '10:00' ); ?>"
				       required
				       style="width:100px;">
				<button type="submit" class="ecwp-btn ecwp-btn-success ecwp-btn-lg">
					<span class="dashicons dashicons-calendar-alt"></span> Schedule
				</button>
			</div>
		</form>
		<?php endif; ?>

		<!-- Unschedule — shown when scheduled -->
		<?php if ( $is_sched ) : ?>
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="flex:1;min-width:160px;">
			<input type="hidden" name="action"      value="ecwp_unschedule_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_unschedule_campaign' ); ?>
			<button type="submit" class="ecwp-btn ecwp-btn-warning ecwp-btn-lg" style="width:100%;">
				<span class="dashicons dashicons-no-alt"></span> Unschedule
			</button>
		</form>
		<?php endif; ?>

		<!-- Pause — shown while sending -->
		<?php if ( $is_sending ) : ?>
		<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="flex:1;min-width:160px;"
		      class="ecwp-confirm-form" data-confirm="Pause this campaign?">
			<input type="hidden" name="action"      value="ecwp_pause_campaign">
			<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
			<?php wp_nonce_field( 'ecwp_pause_campaign' ); ?>
			<button type="submit" class="ecwp-btn ecwp-btn-warning ecwp-btn-lg" style="width:100%;">
				<span class="dashicons dashicons-controls-pause"></span> Pause
			</button>
		</form>
		<?php endif; ?>

	</div>
	<?php if ( $is_sched && $next_run ) : ?>
		<div class="ecwp-notice ecwp-notice-info" style="margin-top:10px;">
			🕐 <strong>One-Time Send Scheduled:</strong> will fire on <strong><?php echo esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next_run ), 'M j, Y \a\t g:i a' ) ); ?></strong>
			<span class="ecwp-hint" style="margin-left:8px;">(<?php echo esc_html( wp_timezone_string() ); ?> timezone)</span>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data" style="margin-top:20px;" id="ecwp-edit-form">
		<input type="hidden" name="action"           value="ecwp_update_campaign">
		<input type="hidden" name="campaign_id"      value="<?php echo $campaign->id; ?>">
		<input type="hidden" name="send_immediately" value="0" id="ecwp-send-immediately">
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
						<div class="ecwp-field">
							<label>Preview Text</label>
							<input type="text" name="preview_text" value="<?php echo esc_attr( $campaign->preview_text ?? '' ); ?>" class="ecwp-input" placeholder="The one-line teaser shown in the inbox beneath your subject line…" maxlength="150">
							<span class="ecwp-hint">Shown in the inbox below the subject line. Keep it under 90 characters. Leave blank to let the email client use the first visible text in the email.</span>
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
						<!-- Delete button submits the standalone form placed OUTSIDE the edit form -->
						<button type="button"
						        class="ecwp-btn ecwp-btn-danger"
						        style="width:100%;"
						        onclick="if(confirm('Permanently delete this campaign?')){document.getElementById('ecwp-delete-form').submit();}">
							Delete Campaign
						</button>
					</div>
				</div>

			</div><!-- /right -->

		</div><!-- /form-grid -->

		<div class="ecwp-form-actions" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg" id="ecwp-btn-save">Save Changes</button>
			<button type="button" class="ecwp-btn ecwp-btn-success ecwp-btn-lg" id="ecwp-btn-send-now"
			        style="display:none;" onclick="ecwpSendNow()">
				<span class="dashicons dashicons-controls-play" style="vertical-align:middle;margin-top:-2px;"></span> Save &amp; Send Now
			</button>
			<span id="ecwp-schedule-hint" class="ecwp-hint" style="display:none;">
				Schedule is ON — campaign will send automatically at the scheduled time.
			</span>
		</div>
	</form>

	<!-- ── Standalone delete form — OUTSIDE the main edit form ─────────────
	     Keeps it separate so the main form's action/nonce are never polluted. -->
	<form id="ecwp-delete-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
		<input type="hidden" name="action"      value="ecwp_delete_campaign">
		<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
		<?php wp_nonce_field( 'ecwp_delete_campaign' ); ?>
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

// ── Schedule-aware save button ─────────────────────────────────────────────
// "Save Changes" is always visible so the user can save without sending.
// "Save & Send Now" appears as an additional option when schedule is OFF.
// The schedule hint appears when schedule is ON.
function ecwpUpdateSaveButton() {
	var schedEnabled = document.querySelector('input[name="schedule_enabled"]').checked;
	document.getElementById('ecwp-btn-send-now').style.display  = schedEnabled ? 'none' : '';
	document.getElementById('ecwp-schedule-hint').style.display = schedEnabled ? '' : 'none';
}
function ecwpSendNow() {
	document.getElementById('ecwp-send-immediately').value = '1';
	document.getElementById('ecwp-edit-form').submit();
}
// Initialise on page load.
document.addEventListener('DOMContentLoaded', function() {
	ecwpUpdateSaveButton();
	document.querySelector('input[name="schedule_enabled"]').addEventListener('change', ecwpUpdateSaveButton);
});
</script>

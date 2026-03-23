<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<?php
	/* ── Notices ─────────────────────────────────────────────────────── */
	if ( isset( $_GET['created'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Automation created successfully.</div>';
	endif;
	if ( isset( $_GET['deleted'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Automation deleted.</div>';
	endif;
	if ( isset( $_GET['toggled'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Automation status updated.</div>';
	endif;
	if ( isset( $_GET['ran'] ) ) :
		$sent = intval( $_GET['ran'] );
		echo '<div class="ecwp-notice ecwp-notice-success">Manual run complete — ' . number_format( $sent ) . ' follow-up email(s) dispatched.</div>';
	endif;
	if ( isset( $_GET['run_error'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-error">' . esc_html( urldecode( $_GET['run_error'] ) ) . '</div>';
	endif;
	if ( isset( $_GET['create_error'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-error">' . esc_html( urldecode( $_GET['create_error'] ) ) . '</div>';
	endif;
	?>

	<?php /* ═══════════════════════════════════════════════════════════
	        DETAIL / LOG VIEW  (?action=view&automation_id=X)
	       ═══════════════════════════════════════════════════════════ */
	if ( $action === 'view' && $automation ) :
		$condition_labels = [
			'not_clicked'        => 'Did not click',
			'not_opened'         => 'Did not open',
			'opened_not_clicked' => 'Opened but did not click',
		];
	?>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations' ); ?>" style="text-decoration:none;color:inherit;">Automations</a>
			<span style="opacity:.4;margin:0 6px;">›</span>
			<?php echo esc_html( $automation->name ); ?>
		</h1>
		<div style="margin-left:auto;display:flex;gap:8px;">
			<!-- Toggle pause/activate -->
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<input type="hidden" name="action"        value="ecwp_toggle_automation">
				<input type="hidden" name="automation_id" value="<?php echo (int) $automation->id; ?>">
				<?php wp_nonce_field( 'ecwp_toggle_automation' ); ?>
				<?php if ( $automation->status === 'active' ) : ?>
					<button type="submit" class="ecwp-btn ecwp-btn-warning ecwp-btn-sm">
						<span class="dashicons dashicons-controls-pause"></span> Pause
					</button>
				<?php else : ?>
					<button type="submit" class="ecwp-btn ecwp-btn-success ecwp-btn-sm">
						<span class="dashicons dashicons-controls-play"></span> Activate
					</button>
				<?php endif; ?>
			</form>
			<!-- Manual run -->
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<input type="hidden" name="action"        value="ecwp_run_automation">
				<input type="hidden" name="automation_id" value="<?php echo (int) $automation->id; ?>">
				<?php wp_nonce_field( 'ecwp_run_automation' ); ?>
				<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-sm"
				        onclick="return confirm('Run this automation now? Eligible subscribers will receive the follow-up immediately.');">
					<span class="dashicons dashicons-update"></span> Run Now
				</button>
			</form>
			<!-- Delete -->
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<input type="hidden" name="action"        value="ecwp_delete_automation">
				<input type="hidden" name="automation_id" value="<?php echo (int) $automation->id; ?>">
				<?php wp_nonce_field( 'ecwp_delete_automation' ); ?>
				<button type="submit" class="ecwp-btn ecwp-btn-danger ecwp-btn-sm"
				        onclick="return confirm('Delete this automation and all its log data? This cannot be undone.');">
					<span class="dashicons dashicons-trash"></span> Delete
				</button>
			</form>
		</div>
	</div>

	<!-- Automation summary cards -->
	<div class="ecwp-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));margin-bottom:20px;">
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dbeafe;color:#2563eb;"><span class="dashicons dashicons-megaphone"></span></div>
			<div>
				<div class="ecwp-stat-value" style="font-size:14px;word-break:break-word;"><?php echo esc_html( $automation->trigger_subject ?: '—' ); ?></div>
				<div class="ecwp-stat-label">Trigger Campaign</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fef9c3;color:#ca8a04;"><span class="dashicons dashicons-email-alt"></span></div>
			<div>
				<div class="ecwp-stat-value" style="font-size:14px;word-break:break-word;"><?php echo esc_html( $automation->followup_subject ?: '—' ); ?></div>
				<div class="ecwp-stat-label">Follow-up Campaign</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#f3e8ff;color:#9333ea;"><span class="dashicons dashicons-filter"></span></div>
			<div>
				<div class="ecwp-stat-value" style="font-size:13px;"><?php echo esc_html( $condition_labels[ $automation->condition ] ?? $automation->condition ); ?></div>
				<div class="ecwp-stat-label">Condition</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dcfce7;color:#16a34a;"><span class="dashicons dashicons-clock"></span></div>
			<div>
				<div class="ecwp-stat-value"><?php echo esc_html( ECWP_Automations::delay_label( $automation->delay_days, $automation->delay_unit ?? 'days' ) ); ?></div>
				<div class="ecwp-stat-label">Wait Period</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fee2e2;color:#dc2626;"><span class="dashicons dashicons-chart-bar"></span></div>
			<div>
				<div class="ecwp-stat-value"><?php echo number_format( $automation->total_sent ); ?></div>
				<div class="ecwp-stat-label">Total Sent</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:<?php echo $automation->status === 'active' ? '#dcfce7;color:#16a34a' : '#f1f5f9;color:#64748b'; ?>;"><span class="dashicons dashicons-controls-play"></span></div>
			<div>
				<div class="ecwp-stat-value"><?php echo $automation->status === 'active' ? 'Active' : 'Paused'; ?></div>
				<div class="ecwp-stat-label">Status</div>
			</div>
		</div>
	</div>

	<!-- Log table -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-list-view"></span>
			Send Log (<?php echo number_format( $log_count ); ?> total)
			<?php if ( $automation->last_run_at ) : ?>
				<span class="ecwp-hint" style="margin-left:12px;">Last evaluated: <?php echo esc_html( date( 'M j, Y g:i a', strtotime( $automation->last_run_at ) ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $auto_log ) ) : ?>
				<div class="ecwp-empty" style="padding:32px;">
					<?php if ( $automation->status === 'active' ) : ?>
						No follow-up emails have been sent yet. The automation will evaluate automatically once the wait period has passed, or you can use <strong>Run Now</strong> above to trigger it manually.
					<?php else : ?>
						This automation is paused. Activate it to allow follow-up sends.
					<?php endif; ?>
				</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover">
					<thead>
						<tr>
							<th>Email</th>
							<th>Name</th>
							<th>Subscriber Status</th>
							<th>Sent At</th>
							<th>Message ID</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $auto_log as $entry ) : ?>
						<tr>
							<td><?php echo esc_html( $entry->email ); ?></td>
							<td><?php echo esc_html( trim( ( $entry->first_name ?? '' ) . ' ' . ( $entry->last_name ?? '' ) ) ?: '—' ); ?></td>
							<td>
								<?php if ( ( $entry->subscriber_status ?? '' ) === 'active' ) : ?>
									<span class="ecwp-badge ecwp-badge-green">Active</span>
								<?php elseif ( $entry->subscriber_status ) : ?>
									<span class="ecwp-badge ecwp-badge-grey">Unsubscribed</span>
								<?php else : ?>
									<span class="ecwp-hint">—</span>
								<?php endif; ?>
							</td>
							<td style="white-space:nowrap;"><?php echo esc_html( $entry->sent_at ? date( 'M j, Y g:i a', strtotime( $entry->sent_at ) ) : '—' ); ?></td>
							<td style="font-size:11px;color:#6b7280;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html( $entry->message_id ?: '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<?php /* ═══════════════════════════════════════════════════════════
	        CREATE FORM  (?action=create)
	       ═══════════════════════════════════════════════════════════ */
	elseif ( $action === 'create' ) : ?>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations' ); ?>" style="text-decoration:none;color:inherit;">Automations</a>
			<span style="opacity:.4;margin:0 6px;">›</span> New Automation
		</h1>
	</div>

	<div class="ecwp-card" style="max-width:680px;">
		<div class="ecwp-card-header"><span class="dashicons dashicons-plus-alt"></span> Create Drip Automation</div>
		<div class="ecwp-card-body">
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<input type="hidden" name="action" value="ecwp_create_automation">
				<?php wp_nonce_field( 'ecwp_create_automation' ); ?>

				<div class="ecwp-field">
					<label for="auto_name">Automation Name <span class="required">*</span></label>
					<input type="text" id="auto_name" name="name" class="ecwp-input" placeholder="e.g. Re-engagement follow-up" required>
					<span class="ecwp-hint">An internal label for your reference.</span>
				</div>

				<div class="ecwp-field">
					<label for="trigger_campaign_id">Trigger Campaign <span class="required">*</span></label>
					<select id="trigger_campaign_id" name="trigger_campaign_id" class="ecwp-input" required onchange="ecwpCheckSameCampaign()">
						<option value="">— Select a campaign —</option>
						<?php foreach ( $all_campaigns as $c ) :
							$status_label = match( $c->status ) {
								'sent'      => ' ✓ sent',
								'sending'   => ' ⏳ sending',
								'scheduled' => ' 📅 scheduled',
								'draft'     => ' ✏️ draft',
								default     => ' [' . esc_html( $c->status ) . ']',
							};
						?>
							<option value="<?php echo (int) $c->id; ?>"><?php echo esc_html( $c->subject ) . $status_label; ?></option>
						<?php endforeach; ?>
					</select>
					<span class="ecwp-hint">
						Pick any campaign — including drafts or scheduled ones — and set up the automation in advance.
						The follow-up will only be evaluated <strong>after</strong> the trigger campaign has fully sent.
					</span>
				</div>

				<div class="ecwp-field">
					<label for="auto_condition">Follow-up Condition <span class="required">*</span></label>
					<select id="auto_condition" name="condition" class="ecwp-input" required>
						<option value="not_clicked" selected>Did not click any link (recommended — catches non-openers too)</option>
						<option value="not_opened">Did not open the email</option>
						<option value="opened_not_clicked">Opened but did not click (re-engagement nudge)</option>
					</select>
					<span class="ecwp-hint">Who should receive the follow-up?</span>
				</div>

				<div class="ecwp-field">
					<label for="delay_days">Wait Period <span class="required">*</span></label>
					<div style="display:flex;gap:8px;align-items:center;">
						<input type="number" id="delay_days" name="delay_days" class="ecwp-input ecwp-input-sm" value="5" min="1" max="9999" required style="width:90px;">
						<select name="delay_unit" id="delay_unit" class="ecwp-input ecwp-input-sm" style="width:130px;">
							<option value="minutes">minutes</option>
							<option value="hours">hours</option>
							<option value="days" selected>days</option>
							<option value="weeks">weeks</option>
						</select>
					</div>
					<span class="ecwp-hint">How long after the trigger campaign's last send to wait before evaluating. Use minutes for testing.</span>
				</div>

				<div class="ecwp-field">
					<label for="followup_campaign_id">Follow-up Campaign <span class="required">*</span></label>
					<select id="followup_campaign_id" name="followup_campaign_id" class="ecwp-input" required onchange="ecwpCheckSameCampaign()">
						<option value="">— Select follow-up campaign —</option>
						<?php foreach ( $all_campaigns as $c ) : ?>
							<option value="<?php echo (int) $c->id; ?>"><?php echo esc_html( $c->subject ); ?>
								<?php if ( $c->status !== 'draft' ) echo ' [' . esc_html( $c->status ) . ']'; ?>
							</option>
						<?php endforeach; ?>
					</select>
					<span class="ecwp-hint">The email to send to eligible subscribers. Can be any campaign (including drafts).</span>
					<div id="ecwp-same-campaign-warn" class="ecwp-notice ecwp-notice-warning" style="display:none;margin-top:8px;">
						⚠️ Trigger and follow-up campaigns are the same — this would re-send the original email. Is that intentional?
					</div>
				</div>

				<div style="display:flex;gap:10px;margin-top:16px;">
					<button type="submit" class="ecwp-btn ecwp-btn-success ecwp-btn-lg">
						<span class="dashicons dashicons-yes"></span> Create Automation
					</button>
					<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-lg">Cancel</a>
				</div>
			</form>
		</div>
	</div>

	<div class="ecwp-card" style="max-width:680px;margin-top:0;">
		<div class="ecwp-card-header"><span class="dashicons dashicons-info"></span> How Drip Automations Work</div>
		<div class="ecwp-card-body" style="font-size:13px;line-height:1.7;color:#374151;">
			<p>Once active, the automation is evaluated <strong>once per day</strong> (via WordPress Cron). Here's what happens:</p>
			<ol style="margin:8px 0 0 16px;">
				<li><strong>Set it up any time</strong> — you can create the automation before the trigger campaign has been sent. It won't fire until the trigger campaign's status becomes "sent".</li>
				<li>After the trigger campaign sends and your wait period elapses, the system identifies who received it.</li>
				<li>Subscribers who <em>met your condition</em> (e.g., didn't click) are eligible for the follow-up.</li>
				<li>Anyone who has already received this follow-up is skipped — each subscriber only ever gets it once.</li>
				<li>Unsubscribed contacts are always excluded.</li>
				<li>The follow-up is sent via Mailgun and appears in your Analytics like any campaign send.</li>
			</ol>
			<p style="margin-top:10px;">💡 <strong>Tip:</strong> Use <em>minutes</em> for the wait period while testing so you can see results right away. You can also trigger a <strong>manual run</strong> from the automation's detail page.</p>
		</div>
	</div>

	<?php /* ═══════════════════════════════════════════════════════════
	        DEFAULT: LIST VIEW
	       ═══════════════════════════════════════════════════════════ */
	else : ?>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Automations</h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations&action=create' ); ?>" class="ecwp-btn ecwp-btn-success">
			<span class="dashicons dashicons-plus-alt"></span> New Automation
		</a>
	</div>

	<!-- Summary stats -->
	<div class="ecwp-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dcfce7;color:#16a34a;"><span class="dashicons dashicons-controls-play"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $active_count ); ?></div><div class="ecwp-stat-label">Active</div></div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#f1f5f9;color:#64748b;"><span class="dashicons dashicons-controls-pause"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $paused_count ); ?></div><div class="ecwp-stat-label">Paused</div></div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dbeafe;color:#2563eb;"><span class="dashicons dashicons-email-alt"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $total_sent_all ); ?></div><div class="ecwp-stat-label">Total Sent</div></div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fef9c3;color:#ca8a04;"><span class="dashicons dashicons-clock"></span></div>
			<div>
				<div class="ecwp-stat-value" style="font-size:13px;">
					<?php
					$next_cron = wp_next_scheduled( 'ecwp_evaluate_automations' );
					echo $next_cron
						? esc_html( date( 'M j, g:i a', $next_cron ) )
						: 'Not scheduled';
					?>
				</div>
				<div class="ecwp-stat-label">Next Auto-Run</div>
			</div>
		</div>
	</div>

	<!-- Automations table -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-list-view"></span> All Automations (<?php echo count( $automations ); ?>)
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $automations ) ) : ?>
				<div class="ecwp-empty" style="padding:48px;text-align:center;">
					<div style="font-size:40px;margin-bottom:12px;">🤖</div>
					<div style="font-size:16px;font-weight:600;margin-bottom:6px;">No automations yet</div>
					<div class="ecwp-hint" style="margin-bottom:16px;">Create your first drip automation to automatically follow up with subscribers who didn't engage.</div>
					<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations&action=create' ); ?>" class="ecwp-btn ecwp-btn-success">
						<span class="dashicons dashicons-plus-alt"></span> Create Your First Automation
					</a>
				</div>
			<?php else : ?>
				<?php
				$condition_labels = [
					'not_clicked'        => 'No click',
					'not_opened'         => 'No open',
					'opened_not_clicked' => 'Opened, no click',
				];
				?>
				<table class="ecwp-table ecwp-table-hover">
					<thead>
						<tr>
							<th>Name</th>
							<th>Trigger Campaign</th>
							<th>Follow-up Campaign</th>
							<th>Condition</th>
							<th>Wait</th>
							<th>Sent</th>
							<th>Last Run</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $automations as $auto ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $auto->name ); ?></strong></td>
							<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr( $auto->trigger_subject ); ?>">
								<?php echo esc_html( $auto->trigger_subject ?: '—' ); ?>
							</td>
							<td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr( $auto->followup_subject ); ?>">
								<?php echo esc_html( $auto->followup_subject ?: '—' ); ?>
							</td>
							<td><span class="ecwp-hint"><?php echo esc_html( $condition_labels[ $auto->condition ] ?? $auto->condition ); ?></span></td>
							<td style="white-space:nowrap;"><?php echo esc_html( ECWP_Automations::delay_label( $auto->delay_days, $auto->delay_unit ?? 'days' ) ); ?></td>
							<td><?php echo number_format( $auto->total_sent ); ?></td>
							<td style="white-space:nowrap;" class="ecwp-hint">
								<?php echo $auto->last_run_at ? esc_html( date( 'M j, Y', strtotime( $auto->last_run_at ) ) ) : 'Never'; ?>
							</td>
							<td>
								<?php if ( $auto->status === 'active' ) : ?>
									<span class="ecwp-badge ecwp-badge-green">Active</span>
								<?php else : ?>
									<span class="ecwp-badge ecwp-badge-grey">Paused</span>
								<?php endif; ?>
							</td>
							<td class="ecwp-actions">
								<a href="<?php echo esc_url( admin_url( "admin.php?page=ecwp-automations&action=view&automation_id={$auto->id}" ) ); ?>"
								   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">View</a>

								<!-- Toggle pause/activate -->
								<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;">
									<input type="hidden" name="action"        value="ecwp_toggle_automation">
									<input type="hidden" name="automation_id" value="<?php echo (int) $auto->id; ?>">
									<?php wp_nonce_field( 'ecwp_toggle_automation' ); ?>
									<button type="submit" class="ecwp-btn ecwp-btn-sm <?php echo $auto->status === 'active' ? 'ecwp-btn-warning' : 'ecwp-btn-success'; ?>">
										<?php echo $auto->status === 'active' ? 'Pause' : 'Activate'; ?>
									</button>
								</form>

								<!-- Delete -->
								<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;">
									<input type="hidden" name="action"        value="ecwp_delete_automation">
									<input type="hidden" name="automation_id" value="<?php echo (int) $auto->id; ?>">
									<?php wp_nonce_field( 'ecwp_delete_automation' ); ?>
									<button type="submit" class="ecwp-btn ecwp-btn-danger ecwp-btn-sm"
									        onclick="return confirm('Delete this automation and its entire send log?');">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<?php endif; ?>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>
<script>
function ecwpCheckSameCampaign() {
	var trigger  = document.getElementById('trigger_campaign_id');
	var followup = document.getElementById('followup_campaign_id');
	var warn     = document.getElementById('ecwp-same-campaign-warn');
	if ( ! trigger || ! followup || ! warn ) { return; }
	warn.style.display = ( trigger.value && followup.value && trigger.value === followup.value ) ? 'block' : 'none';
}
</script>

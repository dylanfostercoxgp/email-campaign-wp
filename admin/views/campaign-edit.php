<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! $campaign ) { echo '<div class="wrap"><p>Campaign not found.</p></div>'; return; }
$assigned_ids = array_column( (array) $campaign_subscribers, 'id' );
?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Edit Campaign: <?php echo esc_html( $campaign->name ); ?></h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary">← Back</a>
	</div>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Campaign updated.</div>
	<?php endif; ?>

	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
		<input type="hidden" name="action"      value="ecwp_update_campaign">
		<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
		<?php wp_nonce_field( 'ecwp_update_campaign' ); ?>

		<div class="ecwp-form-grid">

			<div class="ecwp-form-main">
				<div class="ecwp-card">
					<div class="ecwp-card-header">Campaign Details</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label for="name">Campaign Name <span class="required">*</span></label>
							<input type="text" id="name" name="name" required value="<?php echo esc_attr( $campaign->name ); ?>" class="ecwp-input">
						</div>
						<div class="ecwp-field">
							<label for="subject">Subject Line <span class="required">*</span></label>
							<input type="text" id="subject" name="subject" required value="<?php echo esc_attr( $campaign->subject ); ?>" class="ecwp-input">
						</div>
						<div class="ecwp-field">
							<label for="html_file">
								Replace HTML Email File
								<span class="ecwp-hint">Leave blank to keep the existing file. Placeholders: <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{email}}</code>, <code>{{unsubscribe_link}}</code></span>
							</label>
							<input type="file" id="html_file" name="html_file" accept=".html,.htm" class="ecwp-input">
						</div>
						<?php if ( ! empty( $campaign->html_content ) ) : ?>
							<div class="ecwp-field">
								<label>Current Email Preview</label>
								<div class="ecwp-html-preview">
									<iframe srcdoc="<?php echo esc_attr( $campaign->html_content ); ?>" style="width:100%;height:320px;border:1px solid #e5e7eb;border-radius:6px;" sandbox="allow-same-origin"></iframe>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header">Batch & Schedule Settings</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field-row">
							<div class="ecwp-field">
								<label>Batch Size</label>
								<input type="number" name="batch_size" value="<?php echo esc_attr( $campaign->batch_size ); ?>" min="1" max="500" class="ecwp-input ecwp-input-sm">
							</div>
							<div class="ecwp-field">
								<label>Interval (minutes)</label>
								<input type="number" name="batch_interval" value="<?php echo esc_attr( $campaign->batch_interval ); ?>" min="1" class="ecwp-input ecwp-input-sm">
							</div>
							<div class="ecwp-field">
								<label>Daily Send Time</label>
								<input type="time" name="send_time" value="<?php echo esc_attr( $campaign->send_time ); ?>" class="ecwp-input ecwp-input-sm">
							</div>
						</div>
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="schedule_enabled" value="1" class="ecwp-toggle-input" <?php checked( $campaign->schedule_enabled, 1 ); ?>>
								<span class="ecwp-toggle"></span>
								Enable automatic daily schedule for this campaign
							</label>
						</div>
						<div class="ecwp-field">
							<label>Current Status</label>
							<?php
							if ( ! function_exists( 'ecwp_status_badge' ) ) {
								function ecwp_status_badge( $s ) {
									$m = [ 'draft'=>['grey','Draft'],'scheduled'=>['blue','Scheduled'],'sending'=>['yellow','Sending'],'sent'=>['green','Sent'],'paused'=>['orange','Paused'] ];
									[$c,$l] = $m[$s] ?? ['grey', ucfirst($s)];
									return "<span class='ecwp-badge ecwp-badge-{$c}'>{$l}</span>";
								}
							}
							echo ecwp_status_badge( $campaign->status );
							?>
							&nbsp; <strong>Total Sent:</strong> <?php echo number_format( $campaign->total_sent ); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="ecwp-form-side">
				<div class="ecwp-card">
					<div class="ecwp-card-header">Subscribers</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="assign_all" value="1" id="assign_all" class="ecwp-toggle-input" onchange="toggleSubscriberList(this)">
								<span class="ecwp-toggle"></span>
								Re-assign all active subscribers
							</label>
						</div>
						<div id="subscriber_list_wrap">
							<?php if ( empty( $all_subscribers ) ) : ?>
								<p class="ecwp-hint">No active subscribers found.</p>
							<?php else : ?>
								<div class="ecwp-field">
									<input type="text" placeholder="Search..." class="ecwp-input ecwp-sub-search" onkeyup="filterSubscribers(this)">
								</div>
								<div class="ecwp-sub-list" style="max-height:320px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;">
									<?php foreach ( $all_subscribers as $sub ) : ?>
										<label class="ecwp-sub-item">
											<input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>" <?php checked( in_array( $sub->id, $assigned_ids ) ); ?>>
											<span><?php echo esc_html( $sub->email ); ?></span>
											<?php if ( $sub->first_name ) : ?><small class="ecwp-hint"><?php echo esc_html( trim( $sub->first_name . ' ' . $sub->last_name ) ); ?></small><?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="ecwp-hint"><?php echo count( $assigned_ids ); ?> assigned of <?php echo count( $all_subscribers ); ?> active</p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header">Actions</div>
					<div class="ecwp-card-body">
						<?php if ( in_array( $campaign->status, [ 'draft', 'scheduled', 'paused' ], true ) ) : ?>
							<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="ecwp-confirm-form" data-confirm="Send this campaign now in batches?">
								<input type="hidden" name="action"      value="ecwp_trigger_campaign">
								<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
								<?php wp_nonce_field( 'ecwp_trigger_campaign' ); ?>
								<button type="submit" class="ecwp-btn ecwp-btn-success" style="width:100%;margin-bottom:8px;">
									<span class="dashicons dashicons-controls-play"></span> Send Now
								</button>
							</form>
						<?php endif; ?>
						<?php if ( $campaign->status === 'sending' ) : ?>
							<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" class="ecwp-confirm-form" data-confirm="Pause sending?">
								<input type="hidden" name="action"      value="ecwp_pause_campaign">
								<input type="hidden" name="campaign_id" value="<?php echo $campaign->id; ?>">
								<?php wp_nonce_field( 'ecwp_pause_campaign' ); ?>
								<button type="submit" class="ecwp-btn ecwp-btn-warning" style="width:100%;margin-bottom:8px;">
									<span class="dashicons dashicons-controls-pause"></span> Pause
								</button>
							</form>
						<?php endif; ?>
						<a href="<?php echo admin_url( "admin.php?page=ecwp-analytics&campaign_id={$campaign->id}" ); ?>" class="ecwp-btn ecwp-btn-secondary" style="width:100%;text-align:center;margin-bottom:8px;display:block;">
							<span class="dashicons dashicons-chart-bar"></span> View Analytics
						</a>
					</div>
				</div>
			</div>

		</div>

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Save Changes</button>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-lg">Cancel</a>
		</div>
	</form>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

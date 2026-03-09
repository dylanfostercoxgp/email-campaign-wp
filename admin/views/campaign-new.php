<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
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

			<!-- Left column -->
			<div class="ecwp-form-main">
				<div class="ecwp-card">
					<div class="ecwp-card-header">Campaign Details</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label for="name">Campaign Name <span class="required">*</span></label>
							<input type="text" id="name" name="name" required placeholder="e.g. April Newsletter" class="ecwp-input">
						</div>
						<div class="ecwp-field">
							<label for="subject">Email Subject Line <span class="required">*</span></label>
							<input type="text" id="subject" name="subject" required placeholder="e.g. Here's what's new this month..." class="ecwp-input">
						</div>
						<div class="ecwp-field">
							<label for="html_file">
								HTML Email File <span class="required">*</span>
								<span class="ecwp-hint">Upload your .html email file. Use <code>{{first_name}}</code>, <code>{{last_name}}</code>, <code>{{email}}</code>, <code>{{unsubscribe_link}}</code> as placeholders.</span>
							</label>
							<input type="file" id="html_file" name="html_file" accept=".html,.htm" class="ecwp-input" required>
						</div>
					</div>
				</div>

				<div class="ecwp-card">
					<div class="ecwp-card-header">Batch & Schedule Settings</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field-row">
							<div class="ecwp-field">
								<label for="batch_size">Batch Size</label>
								<input type="number" id="batch_size" name="batch_size" value="10" min="1" max="500" class="ecwp-input ecwp-input-sm">
								<span class="ecwp-hint">Emails per batch</span>
							</div>
							<div class="ecwp-field">
								<label for="batch_interval">Interval (minutes)</label>
								<input type="number" id="batch_interval" name="batch_interval" value="30" min="1" class="ecwp-input ecwp-input-sm">
								<span class="ecwp-hint">Gap between batches</span>
							</div>
							<div class="ecwp-field">
								<label for="send_time">Daily Send Time</label>
								<input type="time" id="send_time" name="send_time" value="10:00" class="ecwp-input ecwp-input-sm">
								<span class="ecwp-hint">Local server time</span>
							</div>
						</div>
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="schedule_enabled" value="1" class="ecwp-toggle-input" id="schedule_enabled">
								<span class="ecwp-toggle"></span>
								Enable automatic daily schedule for this campaign
							</label>
						</div>
					</div>
				</div>
			</div>

			<!-- Right column: subscriber assignment -->
			<div class="ecwp-form-side">
				<div class="ecwp-card">
					<div class="ecwp-card-header">Assign Subscribers</div>
					<div class="ecwp-card-body">
						<div class="ecwp-field">
							<label class="ecwp-toggle-label">
								<input type="checkbox" name="assign_all" value="1" id="assign_all" class="ecwp-toggle-input" onchange="toggleSubscriberList(this)">
								<span class="ecwp-toggle"></span>
								Assign all active subscribers
							</label>
						</div>
						<div id="subscriber_list_wrap">
							<?php if ( empty( $all_subscribers ) ) : ?>
								<p class="ecwp-hint">No active subscribers yet. <a href="<?php echo admin_url('admin.php?page=ecwp-subscribers'); ?>">Import some.</a></p>
							<?php else : ?>
								<div class="ecwp-field">
									<input type="text" placeholder="Search subscribers..." class="ecwp-input ecwp-sub-search" onkeyup="filterSubscribers(this)">
								</div>
								<div class="ecwp-sub-list" style="max-height:320px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:6px;">
									<?php foreach ( $all_subscribers as $sub ) : ?>
										<label class="ecwp-sub-item">
											<input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>">
											<span><?php echo esc_html( $sub->email ); ?></span>
											<?php if ( $sub->first_name ) : ?>
												<small class="ecwp-hint"><?php echo esc_html( trim( $sub->first_name . ' ' . $sub->last_name ) ); ?></small>
											<?php endif; ?>
										</label>
									<?php endforeach; ?>
								</div>
								<p class="ecwp-hint" style="margin-top:8px;"><?php echo count( $all_subscribers ); ?> active subscriber(s)</p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>

		</div><!-- /ecwp-form-grid -->

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Create Campaign</button>
			<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-lg">Cancel</a>
		</div>
	</form>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

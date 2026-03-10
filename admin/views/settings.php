<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Settings</h1>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Settings saved successfully.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['test_sent'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Test email sent! Check your inbox.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['test_error'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-error">Test email failed: <?php echo esc_html( urldecode( $_GET['test_error'] ) ); ?></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['conn_ok'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">✅ Mailgun connection successful!</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['conn_error'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-error">❌ Mailgun connection failed: <?php echo esc_html( urldecode( $_GET['conn_error'] ) ); ?></div>
	<?php endif; ?>

	<!-- ═══════════════════════════════════════════════════════════════════
	     Main settings form — wraps ALL setting fields.
	     Test Connection and Send Test Email use SEPARATE forms placed
	     below this closing </form> tag to avoid the HTML nested-form
	     violation that silently breaks all form submissions on this page.
	     ═══════════════════════════════════════════════════════════════════ -->
	<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" id="ecwp-settings-form">
		<input type="hidden" name="action" value="ecwp_save_settings">
		<?php wp_nonce_field( 'ecwp_save_settings' ); ?>

		<div class="ecwp-settings-layout">

			<!-- Mailgun -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-cloud"></span> Mailgun Configuration
				</div>
				<div class="ecwp-card-body">
					<div class="ecwp-field">
						<label for="ecwp_mailgun_api_key">API Key <span class="required">*</span></label>
						<input type="password"
						       id="ecwp_mailgun_api_key"
						       name="ecwp_mailgun_api_key"
						       value="<?php echo esc_attr( get_option( 'ecwp_mailgun_api_key', '' ) ); ?>"
						       class="ecwp-input"
						       placeholder="key-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
						       autocomplete="off">
						<span class="ecwp-hint">Found in Mailgun → Account → API Keys</span>
					</div>
					<div class="ecwp-field">
						<label for="ecwp_mailgun_domain">Sending Domain <span class="required">*</span></label>
						<input type="text"
						       id="ecwp_mailgun_domain"
						       name="ecwp_mailgun_domain"
						       value="<?php echo esc_attr( get_option( 'ecwp_mailgun_domain', '' ) ); ?>"
						       class="ecwp-input"
						       placeholder="mg.ideaboss.io">
					</div>
					<div class="ecwp-field">
						<label for="ecwp_mailgun_region">Mailgun Region</label>
						<select id="ecwp_mailgun_region" name="ecwp_mailgun_region" class="ecwp-input ecwp-input-sm">
							<option value="us" <?php selected( get_option( 'ecwp_mailgun_region', 'us' ), 'us' ); ?>>US (api.mailgun.net)</option>
							<option value="eu" <?php selected( get_option( 'ecwp_mailgun_region', 'us' ), 'eu' ); ?>>EU (api.eu.mailgun.net)</option>
						</select>
					</div>
					<!-- Test Connection — button only; the actual form is outside this form below -->
					<div style="display:flex;gap:8px;margin-top:8px;">
						<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm"
						        onclick="ecwpTestConnection()">Test Connection</button>
					</div>
				</div>
			</div>

			<!-- Sender Identity -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-id"></span> Sender Identity
				</div>
				<div class="ecwp-card-body">
					<div class="ecwp-field">
						<label for="ecwp_from_name">From Name</label>
						<input type="text"
						       id="ecwp_from_name"
						       name="ecwp_from_name"
						       value="<?php echo esc_attr( get_option( 'ecwp_from_name', 'Rodrick Cox' ) ); ?>"
						       class="ecwp-input"
						       placeholder="Rodrick Cox">
					</div>
					<div class="ecwp-field">
						<label for="ecwp_from_email">From Email Address</label>
						<input type="email"
						       id="ecwp_from_email"
						       name="ecwp_from_email"
						       value="<?php echo esc_attr( get_option( 'ecwp_from_email', 'info@ideaboss.io' ) ); ?>"
						       class="ecwp-input"
						       placeholder="info@ideaboss.io">
						<span class="ecwp-hint">Must be on your Mailgun verified domain.</span>
					</div>
				</div>
			</div>

			<!-- Global Schedule -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-clock"></span> Global Schedule
				</div>
				<div class="ecwp-card-body">
					<div class="ecwp-field">
						<label class="ecwp-toggle-label">
							<input type="checkbox"
							       name="ecwp_schedule_enabled"
							       value="1"
							       class="ecwp-toggle-input"
							       <?php checked( get_option( 'ecwp_schedule_enabled', '0' ), '1' ); ?>>
							<span class="ecwp-toggle"></span>
							<strong>Enable daily schedule</strong>
						</label>
						<p class="ecwp-hint" style="margin-top:6px;">When enabled, campaigns with scheduling turned on will fire automatically every day at the time below.</p>
					</div>
					<div class="ecwp-field">
						<label for="ecwp_send_time">Daily Trigger Time</label>
						<input type="time"
						       id="ecwp_send_time"
						       name="ecwp_send_time"
						       value="<?php echo esc_attr( get_option( 'ecwp_send_time', '10:00' ) ); ?>"
						       class="ecwp-input ecwp-input-sm">
						<span class="ecwp-hint">Uses WordPress site timezone (<?php echo esc_html( wp_timezone_string() ); ?>)</span>
					</div>
					<?php
					$next = wp_next_scheduled( 'ecwp_daily_trigger' );
					if ( $next ) : ?>
						<div class="ecwp-notice ecwp-notice-info" style="margin-top:12px;">
							Next trigger: <strong><?php echo esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next ), 'M j, Y \a\t g:i a' ) ); ?></strong>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Default Batch Settings -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-filter"></span> Default Batch Settings
				</div>
				<div class="ecwp-card-body">
					<div class="ecwp-field-row">
						<div class="ecwp-field">
							<label for="ecwp_batch_size">Default Batch Size</label>
							<input type="number"
							       id="ecwp_batch_size"
							       name="ecwp_batch_size"
							       value="<?php echo esc_attr( get_option( 'ecwp_batch_size', '10' ) ); ?>"
							       min="1" max="500"
							       class="ecwp-input ecwp-input-sm">
							<span class="ecwp-hint">Emails per batch</span>
						</div>
						<div class="ecwp-field">
							<label for="ecwp_batch_interval">Default Interval (minutes)</label>
							<input type="number"
							       id="ecwp_batch_interval"
							       name="ecwp_batch_interval"
							       value="<?php echo esc_attr( get_option( 'ecwp_batch_interval', '30' ) ); ?>"
							       min="1"
							       class="ecwp-input ecwp-input-sm">
							<span class="ecwp-hint">Gap between batches</span>
						</div>
					</div>
					<div class="ecwp-notice ecwp-notice-info" style="margin-top:12px;">
						Example: 50 subscribers ÷ <?php echo get_option('ecwp_batch_size', 10); ?> = <strong><?php echo ceil(50 / max(1,(int)get_option('ecwp_batch_size', 10))); ?> batches</strong>,
						completing in ~<?php echo ceil(50 / max(1,(int)get_option('ecwp_batch_size', 10)) - 1) * (int)get_option('ecwp_batch_interval', 30); ?> minutes.
					</div>
				</div>
			</div>

			<!-- Send Test Email — button triggers a hidden form outside this form -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-email-alt"></span> Send Test Email
				</div>
				<div class="ecwp-card-body">
					<div style="display:flex;gap:8px;align-items:flex-end;">
						<div class="ecwp-field" style="flex:1;margin:0;">
							<label for="test_email">Recipient Email</label>
							<input type="email" id="test_email" class="ecwp-input"
							       placeholder="you@example.com"
							       value="<?php echo esc_attr( get_option( 'ecwp_from_email', '' ) ); ?>" required>
						</div>
						<button type="button" class="ecwp-btn ecwp-btn-secondary"
						        onclick="ecwpSendTest()">Send Test</button>
					</div>
				</div>
			</div>

			<!-- System Info -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-info"></span> System Info
				</div>
				<div class="ecwp-card-body">
					<table class="ecwp-info-table">
						<tr><th>Plugin Version</th><td><?php echo ECWP_VERSION; ?></td></tr>
						<tr><th>WordPress Version</th><td><?php echo get_bloginfo( 'version' ); ?></td></tr>
						<tr><th>PHP Version</th><td><?php echo PHP_VERSION; ?></td></tr>
						<tr><th>Site Timezone</th><td><?php echo esc_html( wp_timezone_string() ); ?></td></tr>
						<tr><th>WP Cron Enabled</th><td><?php echo defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? '<span class="ecwp-badge ecwp-badge-red">Disabled</span>' : '<span class="ecwp-badge ecwp-badge-green">Enabled</span>'; ?></td></tr>
						<tr><th>Webhook URL</th><td><code><?php echo esc_html( rest_url( 'ecwp/v1/webhook' ) ); ?></code></td></tr>
						<tr><th>Unsubscribe URL</th><td><code><?php echo esc_html( home_url( '/ecwp-unsubscribe/' ) ); ?></code></td></tr>
					</table>
					<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
						<div class="ecwp-notice ecwp-notice-warning" style="margin-top:12px;">
							⚠️ WP Cron is disabled. Add this to your server's cron:<br>
							<code>* * * * * wget -q -O /dev/null "<?php echo esc_html( site_url( '/wp-cron.php?doing_wp_cron' ) ); ?>"</code>
						</div>
					<?php endif; ?>
				</div>
			</div>

		</div><!-- /ecwp-settings-layout -->

		<div class="ecwp-form-actions">
			<button type="submit" class="ecwp-btn ecwp-btn-primary ecwp-btn-lg">Save Settings</button>
		</div>
	</form><!-- end #ecwp-settings-form -->

	<!-- ═══════════════════════════════════════════════════════════════════
	     Standalone forms — OUTSIDE the main settings form so they never
	     collide with it. JS onclick handlers copy current field values
	     into hidden inputs and submit the correct form.
	     ═══════════════════════════════════════════════════════════════════ -->

	<!-- Test Connection form -->
	<form id="ecwp-test-conn-form" method="post"
	      action="<?php echo admin_url( 'admin-post.php' ); ?>"
	      style="display:none;">
		<input type="hidden" name="action"               value="ecwp_test_mailgun">
		<input type="hidden" name="ecwp_mailgun_api_key" id="ecwp-tc-api-key">
		<input type="hidden" name="ecwp_mailgun_domain"  id="ecwp-tc-domain">
		<input type="hidden" name="ecwp_mailgun_region"  id="ecwp-tc-region">
		<?php wp_nonce_field( 'ecwp_test_mailgun' ); ?>
	</form>

	<!-- Send Test Email form -->
	<form id="ecwp-send-test-form" method="post"
	      action="<?php echo admin_url( 'admin-post.php' ); ?>"
	      style="display:none;">
		<input type="hidden" name="action"      value="ecwp_send_test">
		<input type="hidden" name="test_email"  id="ecwp-st-email">
		<?php wp_nonce_field( 'ecwp_send_test' ); ?>
	</form>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash;
		by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

<script>
/**
 * Test Connection — copies the current (possibly unsaved) API key, domain
 * and region into the standalone test form and submits it.
 * The handler saves these values first, then tests, so this also acts as
 * a save for the Mailgun credentials section.
 */
function ecwpTestConnection() {
	var apiKey = document.getElementById('ecwp_mailgun_api_key');
	var domain = document.getElementById('ecwp_mailgun_domain');
	var region = document.getElementById('ecwp_mailgun_region');

	if ( ! apiKey.value.trim() || ! domain.value.trim() ) {
		alert('Please enter your Mailgun API key and sending domain before testing.');
		return;
	}

	document.getElementById('ecwp-tc-api-key').value = apiKey.value;
	document.getElementById('ecwp-tc-domain').value  = domain.value;
	document.getElementById('ecwp-tc-region').value  = region.value;
	document.getElementById('ecwp-test-conn-form').submit();
}

/**
 * Send Test Email — copies the recipient address into the standalone
 * send-test form and submits it.
 */
function ecwpSendTest() {
	var email = document.getElementById('test_email');
	if ( ! email.value.trim() ) {
		alert('Please enter a recipient email address.');
		return;
	}
	document.getElementById('ecwp-st-email').value = email.value;
	document.getElementById('ecwp-send-test-form').submit();
}
</script>

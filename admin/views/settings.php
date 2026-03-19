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
					<div class="ecwp-field">
						<label class="ecwp-toggle-label">
							<input type="checkbox"
							       name="ecwp_click_tracking"
							       value="1"
							       class="ecwp-toggle-input"
							       <?php checked( get_option( 'ecwp_click_tracking', '0' ), '1' ); ?>>
							<span class="ecwp-toggle"></span>
							<strong>Enable Mailgun click tracking</strong>
						</label>
						<p class="ecwp-hint" style="margin-top:6px;">
							⚠️ <strong>Leave this OFF unless you have configured a custom tracking domain with a valid SSL cert in Mailgun.</strong>
							When enabled, Mailgun rewrites every link in outgoing emails to redirect through your Mailgun sending domain (e.g. <code>email.mg.yourdomain.com</code>).
							If that subdomain does not have a valid SSL certificate, every link will show a browser security warning for recipients.
							Open tracking (email open pixel) is always active and is unaffected by this setting.
						</p>
					</div>
					<div class="ecwp-field">
						<label class="ecwp-toggle-label">
							<input type="checkbox"
							       name="ecwp_custom_link_tracking"
							       value="1"
							       class="ecwp-toggle-input"
							       <?php checked( get_option( 'ecwp_custom_link_tracking', '0' ), '1' ); ?>>
							<span class="ecwp-toggle"></span>
							<strong>Enable custom (first-party) link tracking</strong>
						</label>
						<p class="ecwp-hint" style="margin-top:6px;">
							✅ <strong>Recommended.</strong> Replaces every link in outgoing emails with a redirect through <em>your own site</em> before reaching the destination.
							Tracks which specific link was clicked, how many times, and by whom — stored in your own database. No SSL domain setup required.
							When this is on, Mailgun's click tracking is automatically disabled to avoid a double-redirect chain.
							See results in <a href="<?php echo admin_url('admin.php?page=ecwp-analytics'); ?>">Analytics → Link Performance</a>.
						</p>
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

			<!-- Plugin Updates -->
		<div class="ecwp-card">
			<div class="ecwp-card-header">
				<span class="dashicons dashicons-update"></span> Plugin Updates
			</div>
			<div class="ecwp-card-body">
				<div class="ecwp-field">
					<label for="ecwp_github_token">GitHub Personal Access Token</label>
					<input type="password"
					       id="ecwp_github_token"
					       name="ecwp_github_token"
					       value="<?php echo esc_attr( get_option( 'ecwp_github_token', '' ) ); ?>"
					       class="ecwp-input"
					       placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
					       autocomplete="off">
					<span class="ecwp-hint">
						Required because this plugin is hosted in a <strong>private</strong> GitHub repository.
						Generate a token at <a href="https://github.com/settings/tokens" target="_blank">GitHub → Settings → Developer settings → Tokens</a>
						with the <code>repo</code> scope.  Once saved, WordPress will detect new releases automatically
						and show an <strong>Update</strong> button in <a href="<?php echo admin_url('plugins.php'); ?>">Plugins</a>
						whenever a new version is pushed.
					</span>
				</div>
				<?php
				// Show current update status
				$release_cache = get_transient( 'ecwp_github_release' );
				if ( $release_cache ) {
					$remote_ver = ltrim( $release_cache->tag_name ?? '', 'v' );
					if ( version_compare( ECWP_VERSION, $remote_ver, '<' ) ) {
						echo '<div class="ecwp-notice ecwp-notice-warning" style="margin-top:8px;">⬆️ Update available: <strong>v' . esc_html( $remote_ver ) . '</strong> — go to <a href="' . admin_url('plugins.php') . '">Plugins</a> to update.</div>';
					} else {
						echo '<div class="ecwp-notice ecwp-notice-success" style="margin-top:8px;">✅ Plugin is up to date (v' . esc_html( ECWP_VERSION ) . ').</div>';
					}
				}
				?>
				<div style="margin-top:12px;">
					<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm"
					        onclick="ecwpCheckUpdates()">Check for Updates Now</button>
					<span class="ecwp-hint" style="margin-left:8px;">Updates are also checked automatically every 12 hours.</span>
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

	<!-- ═══════════════════════════════════════════════════════════════════
	     Automations status
	     ═══════════════════════════════════════════════════════════════════ -->
	<div class="ecwp-card">
		<div class="ecwp-card-header"><span class="dashicons dashicons-controls-play"></span> Drip Automations</div>
		<div class="ecwp-card-body">
			<?php
			global $wpdb;
			$auto_table   = $wpdb->prefix . 'ecwp_automations';
			$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$auto_table}'" ) === $auto_table;

			if ( $table_exists ) {
				$active_autos  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$auto_table} WHERE status = 'active'" );
				$paused_autos  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$auto_table} WHERE status = 'paused'" );
				$total_auto_sent = (int) $wpdb->get_var( "SELECT SUM(total_sent) FROM {$auto_table}" );
				$next_cron     = wp_next_scheduled( 'ecwp_evaluate_automations' );
			} else {
				$active_autos = $paused_autos = $total_auto_sent = 0;
				$next_cron = false;
			}
			?>
			<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">
				<div style="display:flex;gap:16px;flex-wrap:wrap;">
					<div style="text-align:center;min-width:80px;">
						<div style="font-size:22px;font-weight:700;color:#16a34a;"><?php echo $active_autos; ?></div>
						<div class="ecwp-hint">Active</div>
					</div>
					<div style="text-align:center;min-width:80px;">
						<div style="font-size:22px;font-weight:700;color:#64748b;"><?php echo $paused_autos; ?></div>
						<div class="ecwp-hint">Paused</div>
					</div>
					<div style="text-align:center;min-width:80px;">
						<div style="font-size:22px;font-weight:700;color:#2563eb;"><?php echo number_format( $total_auto_sent ); ?></div>
						<div class="ecwp-hint">Total Sent</div>
					</div>
				</div>
				<div style="flex:1;min-width:200px;">
					<div class="ecwp-field" style="margin:0;">
						<label style="margin-bottom:4px;">Next Automatic Evaluation</label>
						<div class="ecwp-hint" style="font-size:13px;">
							<?php
							if ( $next_cron ) {
								echo '<strong>' . esc_html( date( 'M j, Y \a\t g:i a', $next_cron ) ) . '</strong>';
								echo ' (' . esc_html( human_time_diff( time(), $next_cron ) ) . ' from now)';
							} else {
								echo '<span style="color:#dc2626;">Not scheduled</span> — <a href="' . admin_url( 'admin.php?page=ecwp-automations' ) . '">create an automation</a> to activate.';
							}
							?>
						</div>
					</div>
					<div style="margin-top:12px;">
						<a href="<?php echo admin_url( 'admin.php?page=ecwp-automations' ); ?>" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">
							<span class="dashicons dashicons-controls-play"></span> Manage Automations
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>

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

/**
 * Check for plugin updates — deletes the cached release transient via AJAX
 * then reloads so WordPress re-fetches from GitHub on next update check.
 */
function ecwpCheckUpdates() {
	var btn = event.target;
	btn.disabled = true;
	btn.textContent = 'Checking…';
	fetch( ajaxurl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: 'action=ecwp_clear_update_cache&_ajax_nonce=<?php echo wp_create_nonce("ecwp_clear_update_cache"); ?>',
	} ).then( function() {
		window.location.href = '<?php echo admin_url("update-core.php?force-check=1"); ?>';
	} );
}
</script>

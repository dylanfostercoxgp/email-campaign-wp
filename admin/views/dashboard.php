<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header">
		<div class="ecwp-header-inner">
			<div class="ecwp-logo">
				<span class="dashicons dashicons-email-alt2"></span>
				<span>Email Campaign WP</span>
			</div>
			<div class="ecwp-header-meta">
				<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
			</div>
		</div>
	</div>

	<h1 class="ecwp-page-title">Dashboard</h1>

	<!-- Stats Grid -->
	<div class="ecwp-stats-grid">
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#e0f2fe;color:#0284c7;">
				<span class="dashicons dashicons-groups"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['active_subs'] ); ?></div>
				<div class="ecwp-stat-label">Active Subscribers</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dcfce7;color:#16a34a;">
				<span class="dashicons dashicons-email-alt"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['total_sent'] ); ?></div>
				<div class="ecwp-stat-label">Total Emails Sent</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fef9c3;color:#ca8a04;">
				<span class="dashicons dashicons-visibility"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['total_opens'] ); ?></div>
				<div class="ecwp-stat-label">Unique Opens</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fce7f3;color:#db2777;">
				<span class="dashicons dashicons-admin-links"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['total_clicks'] ); ?></div>
				<div class="ecwp-stat-label">Unique Clicks</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fee2e2;color:#dc2626;">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['total_bounces'] ); ?></div>
				<div class="ecwp-stat-label">Bounces</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#f3e8ff;color:#9333ea;">
				<span class="dashicons dashicons-megaphone"></span>
			</div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['total_campaigns'] ); ?></div>
				<div class="ecwp-stat-label">Campaigns</div>
			</div>
		</div>
	</div>

	<!-- Quick Actions + Recent Campaigns -->
	<div class="ecwp-two-col">
		<div class="ecwp-card">
			<div class="ecwp-card-header">Quick Actions</div>
			<div class="ecwp-card-body">
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns&action=new' ); ?>" class="ecwp-btn ecwp-btn-primary" style="display:block;margin-bottom:10px;">
					<span class="dashicons dashicons-plus-alt"></span> Create New Campaign
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-subscribers' ); ?>" class="ecwp-btn ecwp-btn-secondary" style="display:block;margin-bottom:10px;">
					<span class="dashicons dashicons-upload"></span> Import Subscribers
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-analytics' ); ?>" class="ecwp-btn ecwp-btn-secondary" style="display:block;margin-bottom:10px;">
					<span class="dashicons dashicons-chart-bar"></span> View Analytics
				</a>
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-settings' ); ?>" class="ecwp-btn ecwp-btn-secondary" style="display:block;">
					<span class="dashicons dashicons-admin-settings"></span> Plugin Settings
				</a>
			</div>
		</div>

		<div class="ecwp-card">
			<div class="ecwp-card-header">Recent Campaigns</div>
			<div class="ecwp-card-body ecwp-no-pad">
				<?php if ( empty( $recent_campaigns ) ) : ?>
					<div class="ecwp-empty">No campaigns yet. <a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns&action=new' ); ?>">Create one.</a></div>
				<?php else : ?>
					<table class="ecwp-table">
						<thead><tr><th>Campaign</th><th>Status</th><th>Sent</th></tr></thead>
						<tbody>
						<?php foreach ( $recent_campaigns as $c ) : ?>
							<tr>
								<td><a href="<?php echo admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$c->id}" ); ?>"><?php echo esc_html( $c->name ); ?></a></td>
								<td><?php echo ecwp_status_badge( $c->status ); ?></td>
								<td><?php echo number_format( $c->total_sent ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php
	$schedule_enabled = get_option( 'ecwp_schedule_enabled', '0' );
	$send_time        = get_option( 'ecwp_send_time', '10:00' );
	$next_cron        = wp_next_scheduled( 'ecwp_daily_trigger' );
	?>

	<div class="ecwp-card ecwp-info-bar">
		<div class="ecwp-card-body" style="display:flex;gap:32px;flex-wrap:wrap;align-items:center;">
			<div>
				<strong>Schedule Status:</strong>
				<?php echo $schedule_enabled === '1'
					? '<span class="ecwp-badge ecwp-badge-green">Active</span>'
					: '<span class="ecwp-badge ecwp-badge-grey">Inactive</span>'; ?>
			</div>
			<div><strong>Daily Send Time:</strong> <?php echo esc_html( $send_time ); ?></div>
			<?php if ( $next_cron ) : ?>
				<div><strong>Next Trigger:</strong> <?php echo esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $next_cron ), 'M j, Y g:i a' ) ); ?></div>
			<?php endif; ?>
			<div>
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-settings' ); ?>" class="ecwp-btn ecwp-btn-sm">Edit Settings</a>
			</div>
		</div>
	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash;
		by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
		&nbsp;|&nbsp;
		Webhook URL: <code><?php echo esc_html( rest_url( 'ecwp/v1/webhook' ) ); ?></code>
	</div>
</div>
<?php
function ecwp_status_badge( $status ) {
	$map = [
		'draft'     => [ 'grey',   'Draft' ],
		'scheduled' => [ 'blue',   'Scheduled' ],
		'sending'   => [ 'yellow', 'Sending' ],
		'sent'      => [ 'green',  'Sent' ],
		'paused'    => [ 'orange', 'Paused' ],
	];
	[$colour, $label] = $map[ $status ] ?? [ 'grey', ucfirst( $status ) ];
	return "<span class='ecwp-badge ecwp-badge-{$colour}'>{$label}</span>";
}

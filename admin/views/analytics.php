<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Analytics</h1>
		<form method="get" style="display:flex;gap:8px;align-items:center;">
			<input type="hidden" name="page" value="ecwp-analytics">
			<select name="campaign_id" class="ecwp-input ecwp-input-sm" onchange="this.form.submit()">
				<option value="0">All Campaigns</option>
				<?php foreach ( $all_campaigns as $c ) : ?>
					<option value="<?php echo $c->id; ?>" <?php selected( $selected_id, $c->id ); ?>>
						<?php echo esc_html( $c->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</form>
	</div>

	<!-- Key stats -->
	<div class="ecwp-stats-grid">
		<?php
		$total_for_rate = max( 1, $stats['sent'] );
		$delivered_rate  = $stats['sent']      ? round( $stats['delivered']    / $total_for_rate * 100, 1 ) : 0;
		$open_rate       = $stats['delivered'] ? round( $stats['opened']       / max(1,$stats['delivered']) * 100, 1 ) : 0;
		$click_rate      = $stats['delivered'] ? round( $stats['clicked']      / max(1,$stats['delivered']) * 100, 1 ) : 0;
		$bounce_rate     = $stats['sent']      ? round( $stats['bounced']      / $total_for_rate * 100, 1 ) : 0;
		$unsub_rate      = $stats['sent']      ? round( $stats['unsubscribed'] / $total_for_rate * 100, 1 ) : 0;
		?>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#e0f2fe;color:#0284c7;"><span class="dashicons dashicons-email-alt"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['sent'] ); ?></div>
				<div class="ecwp-stat-label">Total Sent</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dcfce7;color:#16a34a;"><span class="dashicons dashicons-yes-alt"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['delivered'] ); ?> <span class="ecwp-rate"><?php echo $delivered_rate; ?>%</span></div>
				<div class="ecwp-stat-label">Delivered</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fef9c3;color:#ca8a04;"><span class="dashicons dashicons-visibility"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['opened'] ); ?> <span class="ecwp-rate"><?php echo $open_rate; ?>%</span></div>
				<div class="ecwp-stat-label">Opens</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fce7f3;color:#db2777;"><span class="dashicons dashicons-admin-links"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['clicked'] ); ?> <span class="ecwp-rate"><?php echo $click_rate; ?>%</span></div>
				<div class="ecwp-stat-label">Clicks</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fee2e2;color:#dc2626;"><span class="dashicons dashicons-warning"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['bounced'] ); ?> <span class="ecwp-rate"><?php echo $bounce_rate; ?>%</span></div>
				<div class="ecwp-stat-label">Bounces</div>
			</div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#f3e8ff;color:#9333ea;"><span class="dashicons dashicons-no-alt"></span></div>
			<div class="ecwp-stat-body">
				<div class="ecwp-stat-value"><?php echo number_format( $stats['unsubscribed'] ); ?> <span class="ecwp-rate"><?php echo $unsub_rate; ?>%</span></div>
				<div class="ecwp-stat-label">Unsubscribed</div>
			</div>
		</div>
	</div>

	<!-- Per-campaign breakdown -->
	<?php if ( ! $selected_id && ! empty( $campaign_stats ) ) : ?>
	<div class="ecwp-card">
		<div class="ecwp-card-header">Campaign Breakdown</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<table class="ecwp-table ecwp-table-hover">
				<thead>
					<tr>
						<th>Campaign</th><th>Status</th><th>Sent</th><th>Delivered</th>
						<th>Opened</th><th>Clicked</th><th>Bounced</th><th>Unsubscribed</th>
					</tr>
				</thead>
				<tbody>
				<?php
				if ( ! function_exists( 'ecwp_status_badge' ) ) {
					function ecwp_status_badge( $s ) {
						$m = ['draft'=>['grey','Draft'],'scheduled'=>['blue','Scheduled'],'sending'=>['yellow','Sending'],'sent'=>['green','Sent'],'paused'=>['orange','Paused']];
						[$c,$l] = $m[$s] ?? ['grey', ucfirst($s)];
						return "<span class='ecwp-badge ecwp-badge-{$c}'>{$l}</span>";
					}
				}
				?>
				<?php foreach ( $campaign_stats as $cs ) : ?>
					<tr>
						<td><a href="<?php echo admin_url( "admin.php?page=ecwp-analytics&campaign_id={$cs->id}" ); ?>"><?php echo esc_html( $cs->name ); ?></a></td>
						<td><?php echo ecwp_status_badge( $cs->status ); ?></td>
						<td><?php echo number_format( $cs->total_sent ); ?></td>
						<td><?php echo number_format( $cs->delivered ); ?></td>
						<td><?php echo number_format( $cs->opened ); ?></td>
						<td><?php echo number_format( $cs->clicked ); ?></td>
						<td><?php echo number_format( $cs->bounced ); ?></td>
						<td><?php echo number_format( $cs->unsubscribed ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<!-- Recent events -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">Recent Events <span class="ecwp-hint" style="font-weight:normal;">(last 100)</span></div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $recent_events ) ) : ?>
				<div class="ecwp-empty" style="padding:32px;">No events recorded yet. Make sure your Mailgun webhooks are configured.</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover">
					<thead>
						<tr><th>Time</th><th>Event</th><th>Recipient</th><th>Message ID</th></tr>
					</thead>
					<tbody>
					<?php foreach ( $recent_events as $ev ) : ?>
						<?php
						$event_colours = [
							'delivered'    => 'green',
							'opened'       => 'blue',
							'clicked'      => 'purple',
							'bounced'      => 'red',
							'failed'       => 'red',
							'complained'   => 'orange',
							'unsubscribed' => 'grey',
						];
						$colour = $event_colours[ $ev->event_type ] ?? 'grey';
						?>
						<tr>
							<td style="white-space:nowrap;"><?php echo esc_html( date( 'M j, g:i a', strtotime( $ev->created_at ) ) ); ?></td>
							<td><span class="ecwp-badge ecwp-badge-<?php echo $colour; ?>"><?php echo esc_html( ucfirst( $ev->event_type ) ); ?></span></td>
							<td><?php echo esc_html( $ev->recipient ); ?></td>
							<td><code style="font-size:11px;"><?php echo esc_html( substr( $ev->message_id, 0, 32 ) . ( strlen( $ev->message_id ) > 32 ? '…' : '' ) ); ?></code></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<div class="ecwp-card ecwp-info-bar">
		<div class="ecwp-card-body">
			<strong>📡 Webhook URL (add to Mailgun):</strong>
			<code><?php echo esc_html( rest_url( 'ecwp/v1/webhook' ) ); ?></code>
			&nbsp; Configure this in your Mailgun dashboard under <em>Sending → Webhooks</em> for all event types.
		</div>
	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

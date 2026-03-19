<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			Analytics
			<?php if ( $event_filter ) : ?>
				&mdash; <span style="font-size:16px;font-weight:500;color:#6b7280;">
					<?php echo esc_html( ucfirst( $event_filter ) ); ?> events
				</span>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-analytics' . ( $selected_id ? '&campaign_id=' . $selected_id : '' ) ) ); ?>"
				   class="ecwp-btn ecwp-btn-sm ecwp-btn-secondary" style="font-size:12px;margin-left:8px;">&times; Clear filter</a>
			<?php endif; ?>
		</h1>
		<form method="get" style="display:flex;gap:8px;align-items:center;">
			<input type="hidden" name="page" value="ecwp-analytics">
			<?php if ( $event_filter ) : ?>
				<input type="hidden" name="event_filter" value="<?php echo esc_attr( $event_filter ); ?>">
			<?php endif; ?>
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

	<!-- Webhook setup notice -->
	<?php if ( empty( $recent_events ) ) : ?>
	<div class="ecwp-notice ecwp-notice-warning" style="display:flex;align-items:flex-start;gap:12px;padding:16px 20px;margin-bottom:20px;">
		<span class="dashicons dashicons-warning" style="font-size:20px;margin-top:2px;flex-shrink:0;"></span>
		<div style="flex:1;">
			<strong>Webhooks not yet receiving events.</strong>
			Add the URL below in your <a href="https://app.mailgun.com/mg/sending/domains" target="_blank">Mailgun dashboard</a>
			under <em>Sending → Webhooks</em> for all event types (delivered, opened, clicked, failed, bounced, complained, unsubscribed)
			— then Delivered, Opens, and Clicks will start populating automatically.
			<div style="margin-top:10px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
				<code id="ecwp-webhook-url" style="background:#f1f5f9;padding:6px 10px;border-radius:4px;font-size:13px;word-break:break-all;">
					<?php echo esc_html( rest_url( 'ecwp/v1/webhook' ) ); ?>
				</code>
				<button type="button" class="ecwp-btn ecwp-btn-sm ecwp-btn-secondary"
				        onclick="navigator.clipboard.writeText(document.getElementById('ecwp-webhook-url').textContent.trim()).then(function(){this.textContent='Copied!';var b=this;setTimeout(function(){b.textContent='Copy URL';},2000);}.bind(this));">
					Copy URL
				</button>
			</div>
		</div>
	</div>
	<?php else : ?>
	<div class="ecwp-notice ecwp-notice-info" style="padding:10px 16px;margin-bottom:16px;font-size:13px;">
		<strong>📡 Webhook URL:</strong>
		<code id="ecwp-webhook-url" style="margin:0 8px;"><?php echo esc_html( rest_url( 'ecwp/v1/webhook' ) ); ?></code>
		<button type="button" class="ecwp-btn ecwp-btn-sm ecwp-btn-secondary"
		        onclick="navigator.clipboard.writeText(document.getElementById('ecwp-webhook-url').textContent.trim()).then(function(){this.textContent='Copied!';var b=this;setTimeout(function(){b.textContent='Copy';},2000);}.bind(this));">
			Copy
		</button>
	</div>
	<?php endif; ?>

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

	<!-- ── Event drill-down: who performed this event? ──────────── -->
	<?php if ( $event_filter && ! empty( $event_subscribers ) ) : ?>
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<?php
			$ev_icons = ['opened'=>'dashicons-visibility','clicked'=>'dashicons-admin-links','bounced'=>'dashicons-warning','complained'=>'dashicons-flag','failed'=>'dashicons-dismiss','unsubscribed'=>'dashicons-no-alt'];
			$ev_icon  = $ev_icons[$event_filter] ?? 'dashicons-list-view';
			?>
			<span class="dashicons <?php echo $ev_icon; ?>"></span>
			Subscribers who <strong><?php echo esc_html( $event_filter ); ?></strong>
			<span class="ecwp-hint" style="font-weight:normal;">(<?php echo count( $event_subscribers ); ?> unique)</span>
			<div style="margin-left:auto;">
				<input type="text" class="ecwp-input ecwp-input-sm" placeholder="Search&hellip;"
				       oninput="filterTable(this,'ecwp-event-sub-table')" style="width:180px;">
			</div>
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<table class="ecwp-table ecwp-table-hover" id="ecwp-event-sub-table">
				<thead>
					<tr>
						<th>Email</th>
						<th>Name</th>
						<th>Subscriber Status</th>
						<th>Times</th>
						<th>Last Event</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $event_subscribers as $es ) : ?>
					<tr>
						<td><?php echo esc_html( $es->recipient ); ?></td>
						<td><?php echo esc_html( trim( ( $es->first_name ?? '' ) . ' ' . ( $es->last_name ?? '' ) ) ?: '—' ); ?></td>
						<td>
							<?php if ( $es->subscriber_id ) : ?>
								<?php $s_status = $es->status ?? 'active'; ?>
								<span class="ecwp-badge ecwp-badge-<?php echo $s_status === 'active' ? 'green' : 'grey'; ?>">
									<?php echo $s_status === 'active' ? 'Active' : 'Unsubscribed'; ?>
								</span>
							<?php else : ?>
								<span class="ecwp-hint">&mdash;</span>
							<?php endif; ?>
						</td>
						<td><?php echo (int) $es->event_count; ?></td>
						<td style="white-space:nowrap;"><?php echo esc_html( date( 'M j, Y g:i a', strtotime( $es->last_event ) ) ); ?></td>
						<td>
							<?php if ( $es->subscriber_id ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-subscribers&action=edit&subscriber_id=' . $es->subscriber_id ) ); ?>"
								   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">View</a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php elseif ( $event_filter && empty( $event_subscribers ) ) : ?>
	<div class="ecwp-notice ecwp-notice-info">
		No <?php echo esc_html( $event_filter ); ?> events recorded yet.
		<?php if ( empty( $recent_events ) ) : ?>
			Make sure your <a href="<?php echo admin_url('admin.php?page=ecwp-analytics'); ?>">Mailgun webhook</a> is configured.
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- ── Link Performance (custom first-party tracking) ──────────── -->
	<?php if ( $custom_tracking_on && ( ! empty( $link_stats ) || ! empty( $link_clickers ) ) ) : ?>
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-admin-links"></span> Link Performance
			<span class="ecwp-hint" style="font-weight:normal;margin-left:10px;">
				<?php echo number_format( $link_click_totals['total_clicks'] ); ?> total clicks
				&mdash; <?php echo number_format( $link_click_totals['unique_clickers'] ); ?> unique clickers
				<?php if ( $selected_id ) : ?>
					(this campaign)
				<?php else : ?>
					(all campaigns)
				<?php endif; ?>
			</span>
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $link_stats ) ) : ?>
				<div class="ecwp-empty" style="padding:24px;">No link clicks recorded yet for this campaign.</div>
			<?php else : ?>
			<table class="ecwp-table ecwp-table-hover">
				<thead>
					<tr>
						<th>Link URL</th>
						<th style="text-align:right;width:120px;">Total Clicks</th>
						<th style="text-align:right;width:140px;">Unique Clickers</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $link_stats as $ls ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( $ls->link_url ); ?>" target="_blank" rel="noopener"
							   style="max-width:420px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:bottom;"
							   title="<?php echo esc_attr( $ls->link_url ); ?>">
								<?php echo esc_html( $ls->link_url ); ?>
							</a>
						</td>
						<td style="text-align:right;font-weight:600;"><?php echo number_format( $ls->total_clicks ); ?></td>
						<td style="text-align:right;"><?php echo number_format( $ls->unique_clickers ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Per-subscriber click detail -->
	<?php if ( ! empty( $link_clickers ) ) : ?>
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-admin-users"></span> Who Clicked &amp; What
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<table class="ecwp-table ecwp-table-hover">
				<thead>
					<tr>
						<th>Subscriber</th>
						<th>Link Clicked</th>
						<th style="text-align:right;width:90px;">Clicks</th>
						<th style="width:140px;">First Click</th>
						<th style="width:140px;">Last Click</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $link_clickers as $lc ) : ?>
					<tr>
						<td>
							<?php if ( $lc->subscriber_id ) : ?>
								<a href="<?php echo esc_url( admin_url( "admin.php?page=ecwp-subscribers&action=edit&subscriber_id={$lc->subscriber_id}" ) ); ?>">
									<?php echo esc_html( trim( ( $lc->first_name ?? '' ) . ' ' . ( $lc->last_name ?? '' ) ) ?: $lc->email ); ?>
								</a>
								<br><span class="ecwp-hint" style="font-size:11px;"><?php echo esc_html( $lc->email ); ?></span>
							<?php else : ?>
								<?php echo esc_html( $lc->email ); ?>
							<?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( $lc->link_url ); ?>" target="_blank" rel="noopener"
							   style="max-width:300px;display:inline-block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;vertical-align:bottom;font-size:12px;"
							   title="<?php echo esc_attr( $lc->link_url ); ?>">
								<?php echo esc_html( $lc->link_url ); ?>
							</a>
						</td>
						<td style="text-align:right;font-weight:600;"><?php echo number_format( $lc->click_count ); ?></td>
						<td style="white-space:nowrap;" class="ecwp-hint"><?php echo esc_html( $lc->first_click ? date( 'M j, g:i a', strtotime( $lc->first_click ) ) : '—' ); ?></td>
						<td style="white-space:nowrap;" class="ecwp-hint"><?php echo esc_html( $lc->last_click  ? date( 'M j, g:i a', strtotime( $lc->last_click  ) ) : '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>
	<?php elseif ( $custom_tracking_on ) : ?>
	<div class="ecwp-card">
		<div class="ecwp-card-header"><span class="dashicons dashicons-admin-links"></span> Link Performance</div>
		<div class="ecwp-card-body">
			<div class="ecwp-empty">No link clicks recorded yet. Clicks will appear here once subscribers start clicking links in your campaigns.</div>
		</div>
	</div>
	<?php elseif ( ! $custom_tracking_on ) : ?>
	<div class="ecwp-card" style="border:1px dashed #d1d5db;">
		<div class="ecwp-card-header" style="color:#6b7280;"><span class="dashicons dashicons-admin-links"></span> Link Performance <span class="ecwp-hint">(disabled)</span></div>
		<div class="ecwp-card-body">
			<p class="ecwp-hint">Enable <strong>Custom Link Tracking</strong> in
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-settings' ); ?>">Settings</a>
				to track which specific links subscribers click, with full per-subscriber detail stored in your own database.</p>
		</div>
	</div>
	<?php endif; ?>

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

<?php if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! function_exists( 'ecwp_status_badge' ) ) {
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
}
?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Campaigns</h1>
		<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns&action=new' ); ?>" class="ecwp-btn ecwp-btn-primary">
			<span class="dashicons dashicons-plus-alt"></span> New Campaign
		</a>
	</div>

	<?php if ( isset( $_GET['created'] ) )  : ?><div class="ecwp-notice ecwp-notice-success">Campaign created successfully.</div><?php endif; ?>
	<?php if ( isset( $_GET['updated'] ) )  : ?><div class="ecwp-notice ecwp-notice-success">Campaign updated successfully.</div><?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) )  : ?><div class="ecwp-notice ecwp-notice-success">Campaign deleted.</div><?php endif; ?>
	<?php if ( isset( $_GET['triggered'] ) ): ?><div class="ecwp-notice ecwp-notice-success">Campaign sending has started! Batches will fire at your configured interval.</div><?php endif; ?>
	<?php if ( isset( $_GET['paused'] ) )   : ?><div class="ecwp-notice ecwp-notice-warning">Campaign paused. Pending batches will be skipped.</div><?php endif; ?>

	<div class="ecwp-card">
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $all_campaigns ) ) : ?>
				<div class="ecwp-empty" style="padding:48px;">
					No campaigns yet. <a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns&action=new' ); ?>">Create your first campaign.</a>
				</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover">
					<thead>
						<tr>
							<th>Name</th>
							<th>Subject</th>
							<th>Subscribers</th>
							<th>Sent</th>
							<th>Send Time</th>
							<th>Schedule</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $all_campaigns as $c ) : ?>
						<tr>
							<td><strong><a href="<?php echo admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$c->id}" ); ?>"><?php echo esc_html( $c->name ); ?></a></strong></td>
							<td><?php echo esc_html( wp_trim_words( $c->subject, 8, '...' ) ); ?></td>
							<td><?php echo number_format( $c->sub_count ); ?></td>
							<td><?php echo number_format( $c->sent_count ); ?></td>
							<td><?php echo esc_html( $c->send_time ); ?></td>
							<td>
								<?php echo $c->schedule_enabled
									? '<span class="ecwp-badge ecwp-badge-green">On</span>'
									: '<span class="ecwp-badge ecwp-badge-grey">Off</span>'; ?>
							</td>
							<td><?php echo ecwp_status_badge( $c->status ); ?></td>
							<td class="ecwp-actions">
								<a href="<?php echo admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$c->id}" ); ?>" class="ecwp-btn ecwp-btn-sm">Edit</a>

								<?php if ( in_array( $c->status, [ 'draft', 'scheduled', 'paused' ], true ) ) : ?>
									<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;" class="ecwp-confirm-form" data-confirm="Send this campaign now?">
										<input type="hidden" name="action"      value="ecwp_trigger_campaign">
										<input type="hidden" name="campaign_id" value="<?php echo $c->id; ?>">
										<?php wp_nonce_field( 'ecwp_trigger_campaign' ); ?>
										<button type="submit" class="ecwp-btn ecwp-btn-sm ecwp-btn-success">Send Now</button>
									</form>
								<?php endif; ?>

								<?php if ( $c->status === 'sending' ) : ?>
									<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;" class="ecwp-confirm-form" data-confirm="Pause this campaign?">
										<input type="hidden" name="action"      value="ecwp_pause_campaign">
										<input type="hidden" name="campaign_id" value="<?php echo $c->id; ?>">
										<?php wp_nonce_field( 'ecwp_pause_campaign' ); ?>
										<button type="submit" class="ecwp-btn ecwp-btn-sm ecwp-btn-warning">Pause</button>
									</form>
								<?php endif; ?>

								<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;" class="ecwp-confirm-form" data-confirm="Delete this campaign? This cannot be undone.">
									<input type="hidden" name="action"      value="ecwp_delete_campaign">
									<input type="hidden" name="campaign_id" value="<?php echo $c->id; ?>">
									<?php wp_nonce_field( 'ecwp_delete_campaign' ); ?>
									<button type="submit" class="ecwp-btn ecwp-btn-sm ecwp-btn-danger">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

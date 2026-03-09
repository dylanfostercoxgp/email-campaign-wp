<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Subscribers</h1>
	</div>

	<?php if ( isset( $_GET['imported'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">
			Imported <strong><?php echo intval( $_GET['imported'] ); ?></strong> subscriber(s).
			<?php if ( isset( $_GET['skipped'] ) && $_GET['skipped'] > 0 ) : ?>
				<strong><?php echo intval( $_GET['skipped'] ); ?></strong> duplicate(s) skipped.
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Subscriber deleted.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['import_error'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-error">Import error: <?php echo esc_html( urldecode( $_GET['import_error'] ) ); ?></div>
	<?php endif; ?>

	<div class="ecwp-two-col">

		<!-- Import panel -->
		<div>
			<div class="ecwp-card">
				<div class="ecwp-card-header">Import Subscribers (CSV)</div>
				<div class="ecwp-card-body">
					<p class="ecwp-hint">Upload a CSV file with an <strong>email</strong> column. Optional columns: <strong>first_name</strong>, <strong>last_name</strong>.</p>
					<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
						<input type="hidden" name="action" value="ecwp_import_subscribers">
						<?php wp_nonce_field( 'ecwp_import_subscribers' ); ?>
						<div class="ecwp-field">
							<label for="subscriber_csv">Select CSV File</label>
							<input type="file" id="subscriber_csv" name="subscriber_csv" accept=".csv" required class="ecwp-input" style="margin-bottom:12px;">
						</div>
						<button type="submit" class="ecwp-btn ecwp-btn-primary">
							<span class="dashicons dashicons-upload"></span> Import CSV
						</button>
					</form>
					<div class="ecwp-divider"></div>
					<h4 style="margin:0 0 6px;">Example CSV format</h4>
					<pre class="ecwp-code">email,first_name,last_name
john@example.com,John,Smith
jane@example.com,Jane,
bob@example.com,,</pre>
				</div>
			</div>
		</div>

		<!-- Stats panel -->
		<div>
			<div class="ecwp-card">
				<div class="ecwp-card-header">Subscriber Summary</div>
				<div class="ecwp-card-body">
					<div class="ecwp-mini-stats">
						<div class="ecwp-mini-stat">
							<div class="ecwp-mini-stat-value"><?php echo number_format( $active_count ); ?></div>
							<div class="ecwp-mini-stat-label">Active</div>
						</div>
						<div class="ecwp-mini-stat">
							<div class="ecwp-mini-stat-value"><?php echo number_format( $unsub_count ); ?></div>
							<div class="ecwp-mini-stat-label">Unsubscribed</div>
						</div>
						<div class="ecwp-mini-stat">
							<div class="ecwp-mini-stat-value"><?php echo number_format( $active_count + $unsub_count ); ?></div>
							<div class="ecwp-mini-stat-label">Total</div>
						</div>
					</div>
					<p class="ecwp-hint" style="margin-top:12px;">
						Unsubscribe page URL:<br>
						<code><?php echo esc_html( home_url( '/ecwp-unsubscribe/' ) ); ?></code>
					</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Subscriber table -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			All Subscribers
			<input type="text" placeholder="Filter by email or name..." class="ecwp-input ecwp-sub-search ecwp-input-sm" style="float:right;width:240px;margin-top:-4px;" onkeyup="filterTable(this,'ecwp-sub-table')">
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $all_subscribers ) ) : ?>
				<div class="ecwp-empty" style="padding:40px;">No subscribers yet.</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover" id="ecwp-sub-table">
					<thead>
						<tr>
							<th>Email</th>
							<th>First Name</th>
							<th>Last Name</th>
							<th>Status</th>
							<th>Subscribed</th>
							<th>Unsubscribed</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $all_subscribers as $sub ) : ?>
						<tr>
							<td><?php echo esc_html( $sub->email ); ?></td>
							<td><?php echo esc_html( $sub->first_name ); ?></td>
							<td><?php echo esc_html( $sub->last_name ); ?></td>
							<td>
								<?php echo $sub->status === 'active'
									? '<span class="ecwp-badge ecwp-badge-green">Active</span>'
									: '<span class="ecwp-badge ecwp-badge-grey">Unsubscribed</span>'; ?>
							</td>
							<td><?php echo esc_html( $sub->subscribed_at ? date( 'M j, Y', strtotime( $sub->subscribed_at ) ) : '' ); ?></td>
							<td><?php echo esc_html( $sub->unsubscribed_at ? date( 'M j, Y', strtotime( $sub->unsubscribed_at ) ) : '—' ); ?></td>
							<td>
								<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:inline;" class="ecwp-confirm-form" data-confirm="Delete this subscriber permanently?">
									<input type="hidden" name="action"        value="ecwp_delete_subscriber">
									<input type="hidden" name="subscriber_id" value="<?php echo $sub->id; ?>">
									<?php wp_nonce_field( 'ecwp_delete_subscriber' ); ?>
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

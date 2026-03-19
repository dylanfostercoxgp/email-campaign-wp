<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Subscribers</h1>
	</div>

	<?php
	if ( isset( $_GET['imported'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Imported ' . intval( $_GET['imported'] ) . ' subscribers. ' . intval( $_GET['skipped'] ?? 0 ) . ' skipped (duplicates).</div>';
	endif;
	if ( isset( $_GET['deleted'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Subscriber removed.</div>';
	endif;
	if ( isset( $_GET['add_success'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Subscriber added successfully.</div>';
	endif;
	if ( isset( $_GET['bulk_tagged'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">' . intval( $_GET['bulk_tagged'] ) . ' subscriber(s) tagged.</div>';
	endif;
	if ( isset( $_GET['bulk_untagged'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">' . intval( $_GET['bulk_untagged'] ) . ' subscriber(s) untagged.</div>';
	endif;
	if ( isset( $_GET['import_error'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-error">Import error: ' . esc_html( urldecode( $_GET['import_error'] ) ) . '</div>';
	endif;
	if ( isset( $_GET['add_error'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-error">' . esc_html( urldecode( $_GET['add_error'] ) ) . '</div>';
	endif;
	if ( isset( $_GET['bulk_error'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-warning">Please select at least one subscriber and a tag for bulk tagging.</div>';
	endif;
	if ( isset( $_GET['unsubscribed'] ) ) :
		echo '<div class="ecwp-notice ecwp-notice-success">Subscriber has been unsubscribed.</div>';
	endif;
	?>

	<!-- Stats -->
	<div class="ecwp-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dcfce7;color:#16a34a;"><span class="dashicons dashicons-groups"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $active_count ); ?></div><div class="ecwp-stat-label">Active</div></div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#fee2e2;color:#dc2626;"><span class="dashicons dashicons-no-alt"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $unsub_count ); ?></div><div class="ecwp-stat-label">Unsubscribed</div></div>
		</div>
		<div class="ecwp-stat-card">
			<div class="ecwp-stat-icon" style="background:#dbeafe;color:#2563eb;"><span class="dashicons dashicons-admin-users"></span></div>
			<div><div class="ecwp-stat-value"><?php echo number_format( $active_count + $unsub_count ); ?></div><div class="ecwp-stat-label">Total</div></div>
		</div>
	</div>

	<div class="ecwp-two-col">

		<!-- Import CSV -->
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-upload"></span> Import Subscribers (CSV)</div>
			<div class="ecwp-card-body">
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="ecwp_import_subscribers">
					<?php wp_nonce_field( 'ecwp_import_subscribers' ); ?>
					<div class="ecwp-field">
						<label>CSV File <span class="required">*</span></label>
						<input type="file" name="subscriber_csv" accept=".csv" required>
						<span class="ecwp-hint">Columns: <code>email</code> (required), <code>first_name</code>, <code>last_name</code> (optional)</span>
					</div>
					<?php if ( ! empty( $all_tags ) ) : ?>
					<div class="ecwp-field">
						<label>Auto-tag imported subscribers</label>
						<select name="import_tag_id" class="ecwp-input ecwp-input-sm">
							<option value="">— No tag —</option>
							<?php foreach ( $all_tags as $tag ) : ?>
								<option value="<?php echo $tag->id; ?>"><?php echo esc_html( $tag->name ); ?></option>
							<?php endforeach; ?>
						</select>
						<span class="ecwp-hint">Optionally apply a tag to all newly imported subscribers</span>
					</div>
					<?php endif; ?>
					<button type="submit" class="ecwp-btn ecwp-btn-primary" style="margin-top:4px;">Import CSV</button>
				</form>
			</div>
		</div>

		<!-- Add Single Subscriber -->
		<div class="ecwp-card">
			<div class="ecwp-card-header"><span class="dashicons dashicons-plus-alt"></span> Add Single Subscriber</div>
			<div class="ecwp-card-body">
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
					<input type="hidden" name="action" value="ecwp_add_subscriber">
					<?php wp_nonce_field( 'ecwp_add_subscriber' ); ?>
					<div class="ecwp-field">
						<label for="add_email">Email Address <span class="required">*</span></label>
						<input type="email" id="add_email" name="email" class="ecwp-input" placeholder="contact@example.com" required>
					</div>
					<div class="ecwp-field-row">
						<div class="ecwp-field">
							<label>First Name</label>
							<input type="text" name="first_name" class="ecwp-input" placeholder="Optional">
						</div>
						<div class="ecwp-field">
							<label>Last Name</label>
							<input type="text" name="last_name" class="ecwp-input" placeholder="Optional">
						</div>
					</div>
					<div class="ecwp-field">
						<label>Phone</label>
						<input type="text" name="phone" class="ecwp-input" placeholder="Optional">
					</div>
					<div class="ecwp-field">
						<label>Address</label>
						<input type="text" name="address" class="ecwp-input" placeholder="Optional">
					</div>
					<div class="ecwp-field">
						<label>Website URL</label>
						<input type="url" name="website" class="ecwp-input" placeholder="https://example.com (Optional)">
					</div>
					<div class="ecwp-field">
						<label>Notes</label>
						<textarea name="notes" class="ecwp-input" rows="2" placeholder="Optional notes about this contact"></textarea>
					</div>
					<?php if ( ! empty( $all_tags ) ) : ?>
					<div class="ecwp-field">
						<label>Tags</label>
						<div style="display:flex;flex-wrap:wrap;gap:8px;">
							<?php foreach ( $all_tags as $tag ) : ?>
								<label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;">
									<input type="checkbox" name="tag_ids[]" value="<?php echo $tag->id; ?>">
									<span style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr( $tag->color ); ?>;display:inline-block;"></span>
									<?php echo esc_html( $tag->name ); ?>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>
					<button type="submit" class="ecwp-btn ecwp-btn-success" style="margin-top:4px;">Add Subscriber</button>
				</form>
			</div>
		</div>

	</div>

	<!-- Bulk tag + subscriber table -->
	<div class="ecwp-card">
		<div class="ecwp-card-header">
			<span class="dashicons dashicons-list-view"></span> All Subscribers (<?php echo number_format( $total_count ); ?>)
			<div style="margin-left:auto;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
				<!-- Filter form (GET, auto-submits on change) -->
				<form method="get" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
					<input type="hidden" name="page" value="ecwp-subscribers">
					<?php if ( $orderby !== 'subscribed_at' || $order !== 'DESC' ) : ?>
						<input type="hidden" name="orderby" value="<?php echo esc_attr( $orderby ); ?>">
						<input type="hidden" name="order"   value="<?php echo esc_attr( $order ); ?>">
					<?php endif; ?>
					<select name="filter_status" class="ecwp-input ecwp-input-sm" onchange="this.form.submit()" style="width:auto;">
						<option value="" <?php selected( $filter_status === 'all' || $filter_status === '' ); ?>>All Statuses</option>
						<option value="active"       <?php selected( $filter_status, 'active' ); ?>>Active</option>
						<option value="unsubscribed" <?php selected( $filter_status, 'unsubscribed' ); ?>>Unsubscribed</option>
					</select>
					<?php if ( ! empty( $all_tags ) ) : ?>
					<select name="filter_tag" class="ecwp-input ecwp-input-sm" onchange="this.form.submit()" style="width:auto;">
						<option value="0" <?php selected( $filter_tag, 0 ); ?>>All Tags</option>
						<?php foreach ( $all_tags as $t ) : ?>
							<option value="<?php echo (int) $t->id; ?>" <?php selected( $filter_tag, (int) $t->id ); ?>>
								<?php echo esc_html( $t->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
					<?php if ( in_array( $filter_status, ['active','unsubscribed'], true ) || $filter_tag ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=ecwp-subscribers' ) ); ?>"
						   class="ecwp-btn ecwp-btn-sm ecwp-btn-secondary" style="font-size:11px;">&times; Clear</a>
					<?php endif; ?>
				</form>
				<input type="text" class="ecwp-input ecwp-input-sm" placeholder="Search…" oninput="filterTable(this,'ecwp-sub-table')" style="width:160px;">
			</div>
		</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $all_subscribers ) ) : ?>
				<div class="ecwp-empty" style="padding:32px;">No subscribers yet. Import a CSV or add one above.</div>
			<?php else : ?>
				<!-- Bulk tag bar — this form only handles bulk tagging; delete is handled by a separate form below -->
				<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" id="ecwp-bulk-tag-form">
					<input type="hidden" name="action" value="ecwp_bulk_tag">
					<?php wp_nonce_field( 'ecwp_bulk_tag' ); ?>

					<!-- Hidden field set by JS before submit so we know tag vs untag -->
					<input type="hidden" name="bulk_action" id="ecwp-bulk-action" value="tag">
					<div style="padding:10px 14px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
						<?php if ( ! empty( $all_tags ) ) : ?>
							<strong style="font-size:13px;">Bulk actions:</strong>
							<select name="bulk_tag_id" id="ecwp-bulk-tag-id" class="ecwp-input ecwp-input-sm" style="width:auto;">
								<option value="">— Select tag —</option>
								<?php foreach ( $all_tags as $tag ) : ?>
									<option value="<?php echo $tag->id; ?>"><?php echo esc_html( $tag->name ); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm"
							        onclick="ecwpBulkSubmit('tag')">Add Tag to Selected</button>
							<button type="button" class="ecwp-btn ecwp-btn-danger ecwp-btn-sm"
							        onclick="ecwpBulkSubmit('untag')">Remove Tag from Selected</button>
						<?php else : ?>
							<span class="ecwp-hint">No tags. <a href="<?php echo admin_url( 'admin.php?page=ecwp-tags' ); ?>">Create tags</a> first.</span>
						<?php endif; ?>
						<label style="margin-left:auto;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:5px;">
							<input type="checkbox" id="ecwp-select-all" onchange="ecwpToggleAll(this)"> Select All
						</label>
					</div>

					<?php
					// Build base URL for sort links — preserves active filters, resets to page 1
					$_sort_args = [ 'page' => 'ecwp-subscribers' ];
					if ( in_array( $filter_status, ['active','unsubscribed'], true ) ) { $_sort_args['filter_status'] = $filter_status; }
					if ( $filter_tag )    { $_sort_args['filter_tag']    = $filter_tag; }
					$_sort_base = admin_url( 'admin.php?' . http_build_query( $_sort_args ) );
					$_sort_link = function( $label, $col ) use ( $orderby, $order, $_sort_base ) {
						$new_order = ( $orderby === $col && $order === 'ASC' ) ? 'DESC' : 'ASC';
						$url       = add_query_arg( [ 'orderby' => $col, 'order' => $new_order ], $_sort_base );
						$arrow     = $orderby === $col
							? ' <span style="font-size:10px;opacity:.7;">' . ( $order === 'ASC' ? '↑' : '↓' ) . '</span>'
							: ' <span style="font-size:10px;opacity:.3;">↕</span>';
						return '<a href="' . esc_url( $url ) . '" style="color:inherit;text-decoration:none;white-space:nowrap;">' . esc_html( $label ) . $arrow . '</a>';
					};
					?>
					<table class="ecwp-table ecwp-table-hover" id="ecwp-sub-table">
						<thead>
							<tr>
								<th style="width:36px;"></th>
								<th><?php echo $_sort_link( 'Email', 'email' ); ?></th>
								<th><?php echo $_sort_link( 'Name', 'first_name' ); ?></th>
								<th>Tags</th>
								<th><?php echo $_sort_link( 'Status', 'status' ); ?></th>
								<th><?php echo $_sort_link( 'Subscribed', 'subscribed_at' ); ?></th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
						<?php
						$tags_obj = new ECWP_Tags();
						foreach ( $all_subscribers as $sub ) :
							$sub_tags = $tags_obj->get_subscriber_tags( $sub->id );
						?>
							<tr>
								<td><input type="checkbox" name="subscriber_ids[]" value="<?php echo $sub->id; ?>" class="ecwp-sub-check"></td>
								<td><?php echo esc_html( $sub->email ); ?></td>
								<td><?php echo esc_html( trim( $sub->first_name . ' ' . $sub->last_name ) ?: '—' ); ?></td>
								<td>
									<?php if ( ! empty( $sub_tags ) ) : ?>
										<div style="display:flex;flex-wrap:wrap;gap:3px;">
											<?php foreach ( $sub_tags as $t ) : ?>
												<span style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:99px;font-size:11px;font-weight:600;background:<?php echo esc_attr( $t->color ); ?>22;color:<?php echo esc_attr( $t->color ); ?>;border:1px solid <?php echo esc_attr( $t->color ); ?>44;">
													<?php echo esc_html( $t->name ); ?>
												</span>
											<?php endforeach; ?>
										</div>
									<?php else : ?>
										<span class="ecwp-hint">—</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( $sub->status === 'active' ) : ?>
										<span class="ecwp-badge ecwp-badge-green">Active</span>
									<?php else : ?>
										<span class="ecwp-badge ecwp-badge-grey">Unsubscribed</span>
									<?php endif; ?>
								</td>
								<td style="white-space:nowrap;"><?php echo esc_html( date( 'M j, Y', strtotime( $sub->subscribed_at ) ) ); ?></td>
								<td class="ecwp-actions">
									<a href="<?php echo admin_url( "admin.php?page=ecwp-subscribers&action=edit&subscriber_id={$sub->id}" ); ?>"
									   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">Edit</a>
									<?php if ( $sub->status === 'active' ) : ?>
									<button type="button"
									        class="ecwp-btn ecwp-btn-warning ecwp-btn-sm"
									        onclick="ecwpUnsubSub(<?php echo (int) $sub->id; ?>)">Unsub</button>
									<?php endif; ?>
									<!-- Delete uses a shared form below — no nested form here -->
									<button type="button"
									        class="ecwp-btn ecwp-btn-danger ecwp-btn-sm"
									        onclick="ecwpDeleteSub(<?php echo (int) $sub->id; ?>)">Delete</button>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</form><!-- end #ecwp-bulk-tag-form -->

				<!-- Pagination -->
				<?php if ( $total_pages > 1 ) : ?>
				<div class="ecwp-pagination" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;padding:12px 14px;border-top:1px solid #e5e7eb;background:#f9fafb;">
					<span class="ecwp-hint" style="margin-right:4px;">
						Page <?php echo $paged; ?> of <?php echo $total_pages; ?> &mdash; <?php echo number_format($total_count); ?> total
					</span>
					<?php
					$_pag_args = [ 'page' => 'ecwp-subscribers' ];
				if ( $orderby !== 'subscribed_at' || $order !== 'DESC' ) {
					$_pag_args['orderby'] = $orderby;
					$_pag_args['order']   = $order;
				}
				if ( in_array( $filter_status, ['active','unsubscribed'], true ) ) { $_pag_args['filter_status'] = $filter_status; }
				if ( $filter_tag )    { $_pag_args['filter_tag']    = $filter_tag; }
				$base_url = admin_url( 'admin.php?' . http_build_query( $_pag_args ) );
				// Show at most 10 page links around current page
					$start = max( 1, $paged - 4 );
					$end   = min( $total_pages, $start + 9 );
					$start = max( 1, $end - 9 );
					if ( $paged > 1 ) :
					?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) ); ?>"
						   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">&laquo; Prev</a>
					<?php endif; ?>
					<?php for ( $p = $start; $p <= $end; $p++ ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $p, $base_url ) ); ?>"
						   class="ecwp-btn <?php echo $p === $paged ? 'ecwp-btn-primary' : 'ecwp-btn-secondary'; ?> ecwp-btn-sm">
							<?php echo $p; ?>
						</a>
					<?php endfor; ?>
					<?php if ( $paged < $total_pages ) : ?>
						<a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) ); ?>"
						   class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm">Next &raquo;</a>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<!-- Shared delete form — sits OUTSIDE the bulk-tag form to prevent HTML nesting violation -->
				<form id="ecwp-delete-sub-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
					<input type="hidden" name="action"        value="ecwp_delete_subscriber">
					<input type="hidden" name="subscriber_id" id="ecwp-delete-sub-id"    value="">
					<input type="hidden" name="_wpnonce"      id="ecwp-delete-sub-nonce" value="<?php echo wp_create_nonce( 'ecwp_delete_subscriber' ); ?>">
				</form>

				<!-- Quick-unsubscribe form — standalone, outside bulk-tag form -->
				<form id="ecwp-unsub-sub-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
					<input type="hidden" name="action"           value="ecwp_quick_unsubscribe">
					<input type="hidden" name="subscriber_id"    id="ecwp-unsub-sub-id" value="">
					<input type="hidden" name="ecwp_redirect_to" value="<?php echo esc_attr( admin_url( 'admin.php?page=ecwp-subscribers&unsubscribed=1' . ( in_array( $filter_status, ['active','unsubscribed'], true ) ? '&filter_status=' . rawurlencode( $filter_status ) : '' ) . ( $filter_tag ? '&filter_tag=' . intval( $filter_tag ) : '' ) ) ); ?>">
					<input type="hidden" name="_wpnonce"         id="ecwp-unsub-sub-nonce" value="<?php echo wp_create_nonce( 'ecwp_quick_unsubscribe' ); ?>">
				</form>

			<?php endif; ?>
		</div>
	</div>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>
<script>
function ecwpToggleAll(cb) {
	document.querySelectorAll('.ecwp-sub-check').forEach(function(c){ c.checked = cb.checked; });
}

function ecwpBulkSubmit(action) {
	var tagSel = document.getElementById('ecwp-bulk-tag-id');
	var checked = document.querySelectorAll('.ecwp-sub-check:checked');
	if ( ! tagSel || ! tagSel.value ) {
		alert('Please select a tag first.');
		return;
	}
	if ( checked.length === 0 ) {
		alert('Please select at least one subscriber.');
		return;
	}
	document.getElementById('ecwp-bulk-action').value = action;
	document.getElementById('ecwp-bulk-tag-form').submit();
}

function ecwpDeleteSub(id) {
	if ( ! confirm( 'Remove this subscriber? This cannot be undone.' ) ) { return; }
	document.getElementById('ecwp-delete-sub-id').value = id;
	document.getElementById('ecwp-delete-sub-form').submit();
}

function ecwpUnsubSub(id) {
	if ( ! confirm( 'Unsubscribe this person? They will no longer receive campaigns.' ) ) { return; }
	document.getElementById('ecwp-unsub-sub-id').value = id;
	document.getElementById('ecwp-unsub-sub-form').submit();
}
</script>

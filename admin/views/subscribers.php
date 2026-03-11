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
			<span class="dashicons dashicons-list-view"></span> All Subscribers (<?php echo count( $all_subscribers ); ?>)
			<div style="margin-left:auto;">
				<input type="text" class="ecwp-input ecwp-input-sm" placeholder="Search…" oninput="filterTable(this,'ecwp-sub-table')" style="width:180px;">
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

					<table class="ecwp-table ecwp-table-hover" id="ecwp-sub-table">
						<thead>
							<tr>
								<th style="width:36px;"></th>
								<th>Email</th>
								<th>Name</th>
								<th>Tags</th>
								<th>Status</th>
								<th>Subscribed</th>
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

				<!-- Shared delete form — sits OUTSIDE the bulk-tag form to prevent HTML nesting violation -->
				<form id="ecwp-delete-sub-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>" style="display:none;">
					<input type="hidden" name="action"        value="ecwp_delete_subscriber">
					<input type="hidden" name="subscriber_id" id="ecwp-delete-sub-id"    value="">
					<input type="hidden" name="_wpnonce"      id="ecwp-delete-sub-nonce" value="<?php echo wp_create_nonce( 'ecwp_delete_subscriber' ); ?>">
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
</script>

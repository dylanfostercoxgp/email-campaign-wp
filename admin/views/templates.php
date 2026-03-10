<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">Template Library</h1>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Template saved successfully.</div>
	<?php endif; ?>
	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="ecwp-notice ecwp-notice-success">Template deleted.</div>
	<?php endif; ?>

	<!-- Built-in templates -->
	<div class="ecwp-card">
		<div class="ecwp-card-header"><span class="dashicons dashicons-layout"></span> Built-in Templates</div>
		<div class="ecwp-card-body">
			<p class="ecwp-hint" style="margin-bottom:16px;">These system templates are ready to use. Select one when creating a campaign, or copy its HTML to start a custom template.</p>
			<div class="ecwp-template-grid">
				<?php foreach ( $system_templates as $tpl ) : ?>
					<div class="ecwp-template-card">
						<div class="ecwp-template-thumb" style="background:<?php echo esc_attr( $tpl['accent'] ); ?>22;border:2px solid <?php echo esc_attr( $tpl['accent'] ); ?>33;">
							<div style="color:<?php echo esc_attr( $tpl['accent'] ); ?>;font-size:28px;text-align:center;padding:20px 0;">
								<?php echo $tpl['icon']; ?>
							</div>
						</div>
						<div class="ecwp-template-info">
							<strong><?php echo esc_html( $tpl['name'] ); ?></strong>
							<span class="ecwp-hint"><?php echo esc_html( $tpl['description'] ); ?></span>
						</div>
						<div class="ecwp-template-actions">
							<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm"
							        onclick="ecwpPreviewTemplate(<?php echo esc_attr( $tpl['id'] ); ?>)">
								Preview
							</button>
							<button type="button" class="ecwp-btn ecwp-btn-primary ecwp-btn-sm"
							        onclick="ecwpCopyTemplate('<?php echo esc_js( $tpl['id'] ); ?>', '<?php echo esc_js( $tpl['name'] ); ?>')">
								Use as Custom
							</button>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<!-- User templates -->
	<div class="ecwp-card">
		<div class="ecwp-card-header"><span class="dashicons dashicons-edit"></span> My Templates (<?php echo count( $user_templates ); ?>)</div>
		<div class="ecwp-card-body ecwp-no-pad">
			<?php if ( empty( $user_templates ) ) : ?>
				<div class="ecwp-empty" style="padding:32px;">No custom templates yet. Save a template from the HTML editor or copy a built-in template above.</div>
			<?php else : ?>
				<table class="ecwp-table ecwp-table-hover">
					<thead>
						<tr><th>Name</th><th>Subject</th><th>Created</th><th>Actions</th></tr>
					</thead>
					<tbody>
					<?php foreach ( $user_templates as $tpl ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $tpl->name ); ?></strong></td>
							<td><?php echo esc_html( $tpl->subject ?: '—' ); ?></td>
							<td><?php echo esc_html( date( 'M j, Y', strtotime( $tpl->created_at ) ) ); ?></td>
							<td class="ecwp-actions">
								<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm"
								        onclick="ecwpPreviewUserTemplate(<?php echo $tpl->id; ?>)">
									Preview
								</button>
								<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>"
								      class="ecwp-confirm-form" data-confirm="Delete this template?">
									<input type="hidden" name="action"      value="ecwp_delete_template">
									<input type="hidden" name="template_id" value="<?php echo $tpl->id; ?>">
									<?php wp_nonce_field( 'ecwp_delete_template' ); ?>
									<button type="submit" class="ecwp-btn ecwp-btn-danger ecwp-btn-sm">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>

	<!-- Save new custom template -->
	<div class="ecwp-card">
		<div class="ecwp-card-header"><span class="dashicons dashicons-plus-alt"></span> Save New Template</div>
		<div class="ecwp-card-body">
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<input type="hidden" name="action" value="ecwp_save_template">
				<?php wp_nonce_field( 'ecwp_save_template' ); ?>
				<div class="ecwp-settings-layout">
					<div>
						<div class="ecwp-field">
							<label>Template Name <span class="required">*</span></label>
							<input type="text" name="name" class="ecwp-input" placeholder="My Custom Template" required>
						</div>
						<div class="ecwp-field">
							<label>Default Subject</label>
							<input type="text" name="subject" class="ecwp-input" placeholder="Optional default subject line">
						</div>
					</div>
					<div>
						<div class="ecwp-field" style="height:100%;">
							<label>HTML Content <span class="required">*</span></label>
							<textarea name="html_content" class="ecwp-input" rows="8" placeholder="Paste your HTML here..." required></textarea>
						</div>
					</div>
				</div>
				<div class="ecwp-form-actions">
					<button type="submit" class="ecwp-btn ecwp-btn-primary">Save Template</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Preview Modal -->
	<div id="ecwp-template-modal" style="display:none;position:fixed;inset:0;z-index:100000;background:rgba(0,0,0,.6);justify-content:center;align-items:center;">
		<div style="background:#fff;border-radius:8px;width:90%;max-width:800px;max-height:90vh;display:flex;flex-direction:column;">
			<div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
				<strong id="ecwp-modal-title">Template Preview</strong>
				<button type="button" onclick="document.getElementById('ecwp-template-modal').style.display='none';" style="background:none;border:none;font-size:20px;cursor:pointer;">✕</button>
			</div>
			<div style="flex:1;overflow:auto;padding:0;">
				<iframe id="ecwp-preview-frame" style="width:100%;height:60vh;border:none;" srcdoc=""></iframe>
			</div>
		</div>
	</div>

	<?php
	// Embed system template HTML as JSON for JS preview.
	$tpl_json = [];
	foreach ( $system_templates as $t ) {
		$tpl_json[ $t['id'] ] = [ 'name' => $t['name'], 'html' => $t['html'] ];
	}
	foreach ( $user_templates as $t ) {
		$tpl_json[ 'user_' . $t->id ] = [ 'name' => $t->name, 'html' => $t->html ];
	}
	?>
	<script>
	var ecwpTemplates = <?php echo json_encode( $tpl_json ); ?>;

	function ecwpPreviewTemplate( id ) {
		var tpl = ecwpTemplates[ id ];
		if ( ! tpl ) return;
		document.getElementById('ecwp-modal-title').textContent = tpl.name + ' — Preview';
		document.getElementById('ecwp-preview-frame').srcdoc = tpl.html;
		document.getElementById('ecwp-template-modal').style.display = 'flex';
	}
	function ecwpPreviewUserTemplate( id ) {
		ecwpPreviewTemplate( 'user_' + id );
	}
	function ecwpCopyTemplate( id, name ) {
		var tpl = ecwpTemplates[ id ];
		if ( ! tpl ) return;
		document.querySelector('input[name="name"]').value    = name + ' (copy)';
		document.querySelector('textarea[name="html_content"]').value = tpl.html;
		document.querySelector('.ecwp-card:last-of-type').scrollIntoView({ behavior: 'smooth' });
	}
	</script>

	<div class="ecwp-footer">
		Email Campaign WP <?php echo ECWP_VERSION; ?> &mdash; by <a href="https://ideaboss.io" target="_blank">ideaBoss</a>
	</div>
</div>

<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="wrap ecwp-wrap">
	<div class="ecwp-header"><div class="ecwp-header-inner">
		<div class="ecwp-logo"><span class="dashicons dashicons-email-alt2"></span> Email Campaign WP — HTML Editor</div>
		<a href="https://ideaboss.io" target="_blank" class="ecwp-brand-link">by ideaBoss</a>
	</div></div>

	<div class="ecwp-page-header">
		<h1 class="ecwp-page-title">
			HTML Editor<?php if ( $campaign ) : ?> — <em style="font-weight:400;"><?php echo esc_html( $campaign->name ); ?></em><?php endif; ?>
		</h1>
		<div style="display:flex;gap:10px;align-items:center;">
			<?php if ( $campaign ) : ?>
				<span id="ecwp-autosave-status" class="ecwp-hint" style="font-style:italic;"></span>
				<a href="<?php echo admin_url( "admin.php?page=ecwp-campaigns&action=edit&campaign_id={$campaign->id}" ); ?>" class="ecwp-btn ecwp-btn-secondary">
					← Back to Campaign
				</a>
			<?php else : ?>
				<a href="<?php echo admin_url( 'admin.php?page=ecwp-campaigns' ); ?>" class="ecwp-btn ecwp-btn-secondary">← Campaigns</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Editor Tabs -->
	<div class="ecwp-editor-tabs">
		<button type="button" class="ecwp-tab-btn ecwp-tab-active" data-tab="code">
			<span class="dashicons dashicons-editor-code"></span> Code Editor
		</button>
		<button type="button" class="ecwp-tab-btn" data-tab="blocks">
			<span class="dashicons dashicons-layout"></span> Block Builder
		</button>
		<button type="button" class="ecwp-tab-btn" data-tab="preview">
			<span class="dashicons dashicons-visibility"></span> Preview
		</button>
	</div>

	<!-- CODE EDITOR TAB -->
	<div id="ecwp-tab-code" class="ecwp-tab-pane ecwp-tab-pane-active">
		<div class="ecwp-card" style="margin-top:0;">
			<div class="ecwp-card-header">
				<span class="dashicons dashicons-editor-code"></span> HTML Source
				<span class="ecwp-hint" style="font-weight:normal;margin-left:8px;">Full HTML editor with syntax highlighting. Changes auto-save every 3 seconds.</span>
			</div>
			<div class="ecwp-card-body" style="padding:0;">
				<textarea id="ecwp-code-editor" name="html_content" style="width:100%;min-height:600px;font-family:monospace;font-size:13px;"><?php echo isset( $campaign ) ? esc_textarea( $campaign->html_content ) : ''; ?></textarea>
			</div>
		</div>
		<div class="ecwp-card">
			<div class="ecwp-card-body">
				<p class="ecwp-hint"><strong>Available placeholders:</strong>
					<code>{{first_name}}</code> &nbsp;
					<code>{{last_name}}</code> &nbsp;
					<code>{{email}}</code> &nbsp;
					<code>{{unsubscribe_url}}</code> &nbsp;
					<code>{{unsubscribe_link}}</code>
				</p>
			</div>
		</div>
	</div>

	<!-- BLOCK BUILDER TAB -->
	<div id="ecwp-tab-blocks" class="ecwp-tab-pane" style="display:none;">
		<div style="display:grid;grid-template-columns:280px 1fr;gap:20px;">

			<!-- Block palette -->
			<div>
				<div class="ecwp-card">
					<div class="ecwp-card-header"><span class="dashicons dashicons-plus"></span> Add Blocks</div>
					<div class="ecwp-card-body" style="padding:10px;">
						<div class="ecwp-block-palette">
							<button type="button" class="ecwp-block-btn" data-block="header">📧 Header / Logo</button>
							<button type="button" class="ecwp-block-btn" data-block="hero">🖼 Hero Banner</button>
							<button type="button" class="ecwp-block-btn" data-block="text">📝 Text Block</button>
							<button type="button" class="ecwp-block-btn" data-block="button">🔲 Button</button>
							<button type="button" class="ecwp-block-btn" data-block="image">📷 Image</button>
							<button type="button" class="ecwp-block-btn" data-block="divider">➖ Divider</button>
							<button type="button" class="ecwp-block-btn" data-block="columns">⊞ Two Columns</button>
							<button type="button" class="ecwp-block-btn" data-block="footer">👣 Footer / Unsubscribe</button>
						</div>
					</div>
				</div>

				<!-- Block Properties -->
				<div class="ecwp-card" id="ecwp-block-props" style="display:none;">
					<div class="ecwp-card-header"><span class="dashicons dashicons-admin-generic"></span> Block Properties</div>
					<div class="ecwp-card-body" id="ecwp-props-body">
						<!-- Filled by JS -->
					</div>
				</div>
			</div>

			<!-- Canvas -->
			<div class="ecwp-card">
				<div class="ecwp-card-header">
					<span class="dashicons dashicons-editor-table"></span> Email Canvas
					<span class="ecwp-hint" style="font-weight:normal;margin-left:8px;">Click a block to edit its properties. Drag handles to reorder.</span>
				</div>
				<div class="ecwp-card-body ecwp-no-pad">
					<div id="ecwp-canvas" style="min-height:400px;background:#f3f4f6;padding:20px;">
						<div id="ecwp-blocks-container" style="max-width:600px;margin:0 auto;background:#fff;border-radius:4px;overflow:hidden;">
							<!-- Blocks render here -->
						</div>
						<div id="ecwp-canvas-empty" style="text-align:center;padding:60px 20px;color:#9ca3af;">
							<span class="dashicons dashicons-email-alt" style="font-size:48px;width:48px;height:48px;color:#d1d5db;"></span>
							<p style="margin-top:12px;">Add blocks from the left panel to build your email</p>
						</div>
					</div>
				</div>
				<div class="ecwp-card-body" style="border-top:1px solid #e5e7eb;">
					<div style="display:flex;gap:10px;">
						<button type="button" class="ecwp-btn ecwp-btn-primary" id="ecwp-blocks-to-code">
							<span class="dashicons dashicons-editor-code"></span> Export to Code Editor
						</button>
						<button type="button" class="ecwp-btn ecwp-btn-secondary" id="ecwp-code-to-blocks">
							<span class="dashicons dashicons-update"></span> Import from Code Editor
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- PREVIEW TAB -->
	<div id="ecwp-tab-preview" class="ecwp-tab-pane" style="display:none;">
		<div class="ecwp-card" style="margin-top:0;">
			<div class="ecwp-card-header">
				<span class="dashicons dashicons-visibility"></span> Live Preview
				<div style="margin-left:auto;display:flex;gap:8px;">
					<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm" onclick="ecwpSetPreviewWidth(600)">Desktop</button>
					<button type="button" class="ecwp-btn ecwp-btn-secondary ecwp-btn-sm" onclick="ecwpSetPreviewWidth(375)">Mobile</button>
				</div>
			</div>
			<div class="ecwp-card-body" style="background:#e5e7eb;padding:24px;text-align:center;">
				<div id="ecwp-preview-wrap" style="margin:0 auto;max-width:100%;transition:max-width .2s;max-width:600px;">
					<iframe id="ecwp-live-preview" style="width:100%;height:600px;border:none;border-radius:4px;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.1);"
					        srcdoc="<p style='text-align:center;padding:40px;color:#9ca3af;'>Switch to this tab to refresh the preview.</p>">
					</iframe>
				</div>
			</div>
		</div>
		<div class="ecwp-card">
			<div class="ecwp-card-body">
				<p class="ecwp-hint">Preview shows placeholders un-replaced. Real emails will substitute them with each subscriber's data.</p>
			</div>
		</div>
	</div>

</div><!-- /wrap -->

<style>
.ecwp-editor-tabs {
	display: flex;
	gap: 4px;
	margin-bottom: 0;
	border-bottom: 2px solid #e5e7eb;
	margin-top: 4px;
}
.ecwp-tab-btn {
	padding: 10px 18px;
	background: none;
	border: none;
	border-bottom: 2px solid transparent;
	margin-bottom: -2px;
	font-size: 13px;
	font-weight: 600;
	color: #6b7280;
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 5px;
	transition: color .15s;
}
.ecwp-tab-btn:hover { color: #111827; }
.ecwp-tab-btn.ecwp-tab-active { color: #2563eb; border-bottom-color: #2563eb; }
.ecwp-block-palette { display: flex; flex-direction: column; gap: 6px; }
.ecwp-block-btn {
	width: 100%;
	text-align: left;
	padding: 10px 12px;
	background: #f9fafb;
	border: 1px solid #e5e7eb;
	border-radius: 6px;
	cursor: pointer;
	font-size: 13px;
	transition: background .1s;
}
.ecwp-block-btn:hover { background: #eff6ff; border-color: #93c5fd; }
.ecwp-email-block {
	position: relative;
	border: 2px solid transparent;
	transition: border-color .1s;
}
.ecwp-email-block:hover { border-color: #93c5fd; }
.ecwp-email-block.selected { border-color: #2563eb; }
.ecwp-block-controls {
	position: absolute;
	top: 4px;
	right: 4px;
	display: none;
	gap: 4px;
	z-index: 10;
}
.ecwp-email-block:hover .ecwp-block-controls,
.ecwp-email-block.selected .ecwp-block-controls { display: flex; }
.ecwp-block-ctrl-btn {
	width: 26px;
	height: 26px;
	background: #fff;
	border: 1px solid #d1d5db;
	border-radius: 4px;
	cursor: pointer;
	font-size: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0;
}
.ecwp-block-ctrl-btn:hover { background: #f3f4f6; }
</style>

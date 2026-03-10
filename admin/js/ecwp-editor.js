/* =========================================================
   Email Campaign WP — HTML Editor JS
   by ideaBoss (https://ideaboss.io)
   ========================================================= */

(function ($) {
	'use strict';

	/* ── Global state ────────────────────────────────────────────────── */
	var cm           = null;   // CodeMirror instance
	var blocks       = [];     // block builder state
	var selectedIdx  = -1;     // selected block index
	var autosaveTimer= null;
	var campaignId   = (ecwpEditorData && ecwpEditorData.campaign_id) ? parseInt(ecwpEditorData.campaign_id) : 0;

	/* ── Tab switching ───────────────────────────────────────────────── */
	$('.ecwp-tab-btn').on('click', function () {
		var tab = $(this).data('tab');
		$('.ecwp-tab-btn').removeClass('ecwp-tab-active');
		$(this).addClass('ecwp-tab-active');
		$('.ecwp-tab-pane').hide();
		$('#ecwp-tab-' + tab).show();

		if (tab === 'preview') {
			refreshPreview();
		}
		if (tab === 'code' && cm) {
			cm.refresh();
		}
	});

	/* ── CodeMirror init ─────────────────────────────────────────────── */
	$(document).ready(function () {
		var textarea = document.getElementById('ecwp-code-editor');
		if (!textarea) { return; }

		// WordPress ships CodeMirror via wp_enqueue_code_editor().
		if (window.wp && wp.codeEditor) {
			var editorSettings = wp.codeEditor.defaultSettings ? $.extend(true, {}, wp.codeEditor.defaultSettings) : {};
			editorSettings.codemirror = $.extend({}, editorSettings.codemirror, {
				mode:            'htmlmixed',
				lineNumbers:     true,
				lineWrapping:    true,
				autoCloseTags:   true,
				matchBrackets:   true,
				indentUnit:      2,
				tabSize:         2,
			});
			var editor = wp.codeEditor.initialize(textarea, editorSettings);
			cm = editor.codemirror;

			cm.on('change', function () {
				scheduleAutosave();
			});
		}
	});

	/* ── Auto-save ───────────────────────────────────────────────────── */
	function scheduleAutosave() {
		clearTimeout(autosaveTimer);
		autosaveTimer = setTimeout(doAutosave, 3000);
		setStatus('Unsaved changes…');
	}

	function doAutosave() {
		if (!campaignId) { return; }
		var html = cm ? cm.getValue() : $('#ecwp-code-editor').val();
		setStatus('Saving…');
		$.post(ecwpEditorData.ajaxUrl, {
			action:      'ecwp_autosave_html',
			nonce:       ecwpEditorData.nonce,
			campaign_id: campaignId,
			html:        html
		}, function (resp) {
			if (resp.success) {
				setStatus('Saved at ' + resp.data.time);
			} else {
				setStatus('Save failed.');
			}
		}).fail(function () {
			setStatus('Save failed (network error).');
		});
	}

	function setStatus(msg) {
		$('#ecwp-autosave-status').text(msg);
	}

	/* ── Preview ─────────────────────────────────────────────────────── */
	function refreshPreview() {
		var html = cm ? cm.getValue() : $('#ecwp-code-editor').val();
		var frame = document.getElementById('ecwp-live-preview');
		if (frame) { frame.srcdoc = html || '<p style="text-align:center;padding:40px;color:#9ca3af;">No HTML content yet.</p>'; }
	}

	window.ecwpSetPreviewWidth = function (w) {
		$('#ecwp-preview-wrap').css('max-width', w + 'px');
	};

	/* =================================================================
	   BLOCK BUILDER
	   ================================================================= */

	/* ── Block definitions ───────────────────────────────────────────── */
	var BLOCK_DEFS = {
		header: {
			label: 'Header / Logo',
			defaults: { bg: '#2563eb', text: 'Your Company Name', color: '#ffffff', fontSize: '24' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background:' + b.bg + ';padding:24px 20px;">'
					+ '<span style="color:' + b.color + ';font-family:Arial,sans-serif;font-size:' + b.fontSize + 'px;font-weight:700;">' + escHtml(b.text) + '</span>'
					+ '</td></tr></table>';
			},
			props: ['bg','text','color','fontSize'],
		},
		hero: {
			label: 'Hero Banner',
			defaults: { bg: '#1e3a8a', headline: 'Your Headline Here', subtext: 'Supporting text goes here.', color: '#ffffff' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background:' + b.bg + ';padding:48px 32px;">'
					+ '<h1 style="color:' + b.color + ';font-family:Arial,sans-serif;font-size:32px;margin:0 0 12px;">' + escHtml(b.headline) + '</h1>'
					+ '<p style="color:' + b.color + ';font-family:Arial,sans-serif;font-size:16px;margin:0;opacity:0.85;">' + escHtml(b.subtext) + '</p>'
					+ '</td></tr></table>';
			},
			props: ['bg','headline','subtext','color'],
		},
		text: {
			label: 'Text Block',
			defaults: { content: 'Hi {{first_name}},\n\nEnter your email content here.', bg: '#ffffff' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background:' + b.bg + ';padding:24px 32px;">'
					+ '<div style="font-family:Arial,sans-serif;font-size:15px;color:#374151;line-height:1.7;">' + b.content.replace(/\n/g,'<br>') + '</div>'
					+ '</td></tr></table>';
			},
			props: ['content','bg'],
		},
		button: {
			label: 'Button',
			defaults: { label: 'Click Here', url: '#', bg: '#2563eb', color: '#ffffff', align: 'center' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="' + b.align + '" style="padding:20px 32px;">'
					+ '<a href="' + b.url + '" style="display:inline-block;padding:12px 28px;background:' + b.bg + ';color:' + b.color + ';font-family:Arial,sans-serif;font-size:15px;font-weight:700;text-decoration:none;border-radius:6px;">'
					+ escHtml(b.label) + '</a>'
					+ '</td></tr></table>';
			},
			props: ['label','url','bg','color','align'],
		},
		image: {
			label: 'Image',
			defaults: { src: 'https://via.placeholder.com/600x200', alt: 'Image', link: '' },
			render: function (b) {
				var img = '<img src="' + b.src + '" alt="' + escHtml(b.alt) + '" style="display:block;width:100%;max-width:100%;">';
				if (b.link) { img = '<a href="' + b.link + '">' + img + '</a>'; }
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td>' + img + '</td></tr></table>';
			},
			props: ['src','alt','link'],
		},
		divider: {
			label: 'Divider',
			defaults: { color: '#e5e7eb', height: '1', padding: '16' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding:' + b.padding + 'px 32px;">'
					+ '<hr style="border:none;border-top:' + b.height + 'px solid ' + b.color + ';margin:0;">'
					+ '</td></tr></table>';
			},
			props: ['color','height','padding'],
		},
		columns: {
			label: 'Two Columns',
			defaults: { left: 'Left column content', right: 'Right column content', bg: '#ffffff' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
					+ '<td width="50%" valign="top" style="background:' + b.bg + ';padding:20px 16px 20px 32px;font-family:Arial,sans-serif;font-size:14px;color:#374151;">' + b.left.replace(/\n/g,'<br>') + '</td>'
					+ '<td width="50%" valign="top" style="background:' + b.bg + ';padding:20px 32px 20px 16px;font-family:Arial,sans-serif;font-size:14px;color:#374151;">' + b.right.replace(/\n/g,'<br>') + '</td>'
					+ '</tr></table>';
			},
			props: ['left','right','bg'],
		},
		footer: {
			label: 'Footer / Unsubscribe',
			defaults: { text: '© 2025 Your Company. All rights reserved.', bg: '#f9fafb', color: '#6b7280' },
			render: function (b) {
				return '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background:' + b.bg + ';padding:20px 32px;font-family:Arial,sans-serif;font-size:12px;color:' + b.color + ';">'
					+ escHtml(b.text) + '<br><br>'
					+ '<a href="{{unsubscribe_url}}" style="color:' + b.color + ';text-decoration:underline;">Unsubscribe</a>'
					+ '</td></tr></table>';
			},
			props: ['text','bg','color'],
		},
	};

	/* ── Prop labels ─────────────────────────────────────────────────── */
	var PROP_LABELS = {
		bg: 'Background Color', color: 'Text Color', text: 'Text',
		headline: 'Headline', subtext: 'Sub-text', content: 'Content',
		label: 'Button Label', url: 'Link URL', align: 'Alignment',
		src: 'Image URL', alt: 'Alt Text', link: 'Click URL',
		height: 'Height (px)', padding: 'Padding (px)',
		left: 'Left Column', right: 'Right Column',
		fontSize: 'Font Size (px)',
	};
	var COLOR_PROPS  = ['bg', 'color'];
	var ALIGN_OPTS   = ['left','center','right'];
	var TEXTAREA_PROPS = ['content','left','right','subtext','text'];

	/* ── Add block ───────────────────────────────────────────────────── */
	$('.ecwp-block-btn').on('click', function () {
		var type = $(this).data('block');
		var def  = BLOCK_DEFS[type];
		if (!def) { return; }
		var block = $.extend({ type: type }, def.defaults);
		blocks.push(block);
		renderCanvas();
		selectBlock(blocks.length - 1);
	});

	/* ── Canvas render ───────────────────────────────────────────────── */
	function renderCanvas() {
		var $container = $('#ecwp-blocks-container');
		var $empty     = $('#ecwp-canvas-empty');
		$container.empty();

		if (blocks.length === 0) {
			$empty.show();
			return;
		}
		$empty.hide();

		blocks.forEach(function (block, idx) {
			var def     = BLOCK_DEFS[block.type];
			var html    = def ? def.render(block) : '';
			var $block  = $('<div class="ecwp-email-block' + (idx === selectedIdx ? ' selected' : '') + '" data-idx="' + idx + '"></div>');
			$block.html(
				'<div class="ecwp-block-controls">'
				+ (idx > 0              ? '<button type="button" class="ecwp-block-ctrl-btn" title="Move Up"   data-action="up"     data-idx="' + idx + '">↑</button>' : '')
				+ (idx < blocks.length-1? '<button type="button" class="ecwp-block-ctrl-btn" title="Move Down" data-action="down"   data-idx="' + idx + '">↓</button>' : '')
				+ '<button type="button" class="ecwp-block-ctrl-btn" title="Delete" data-action="delete" data-idx="' + idx + '" style="color:#dc2626;">✕</button>'
				+ '</div>'
				+ html
			);
			$container.append($block);
		});

		// Block click → select
		$('.ecwp-email-block').on('click', function (e) {
			if ($(e.target).is('button') || $(e.target).closest('button').length) { return; }
			selectBlock(parseInt($(this).data('idx')));
		});

		// Control buttons
		$('.ecwp-block-ctrl-btn').on('click', function (e) {
			e.stopPropagation();
			var action = $(this).data('action');
			var idx    = parseInt($(this).data('idx'));
			if (action === 'up' && idx > 0) {
				var tmp = blocks[idx]; blocks[idx] = blocks[idx-1]; blocks[idx-1] = tmp;
				selectedIdx = idx - 1;
			} else if (action === 'down' && idx < blocks.length - 1) {
				var tmp = blocks[idx]; blocks[idx] = blocks[idx+1]; blocks[idx+1] = tmp;
				selectedIdx = idx + 1;
			} else if (action === 'delete') {
				blocks.splice(idx, 1);
				if (selectedIdx >= blocks.length) { selectedIdx = blocks.length - 1; }
			}
			renderCanvas();
			renderProps();
		});
	}

	/* ── Select block → show properties ─────────────────────────────── */
	function selectBlock(idx) {
		selectedIdx = idx;
		renderCanvas();
		renderProps();
	}

	function renderProps() {
		var $panel = $('#ecwp-block-props');
		var $body  = $('#ecwp-props-body');
		$body.empty();

		if (selectedIdx < 0 || selectedIdx >= blocks.length) {
			$panel.hide();
			return;
		}

		$panel.show();
		var block = blocks[selectedIdx];
		var def   = BLOCK_DEFS[block.type];
		if (!def) { return; }

		def.props.forEach(function (prop) {
			var label = PROP_LABELS[prop] || prop;
			var val   = block[prop] !== undefined ? block[prop] : '';
			var $row  = $('<div class="ecwp-field"></div>');
			$row.append('<label style="font-size:12px;">' + label + '</label>');

			var $input;
			if (COLOR_PROPS.indexOf(prop) !== -1) {
				$input = $('<input type="color">').val(val).css({ width: '48px', height: '34px', border: '1px solid #e5e7eb', borderRadius: '4px', padding: '2px', cursor: 'pointer' });
			} else if (prop === 'align') {
				$input = $('<select class="ecwp-input ecwp-input-sm"></select>');
				ALIGN_OPTS.forEach(function (o) {
					$input.append('<option value="' + o + '"' + (val === o ? ' selected' : '') + '>' + o.charAt(0).toUpperCase() + o.slice(1) + '</option>');
				});
			} else if (TEXTAREA_PROPS.indexOf(prop) !== -1) {
				$input = $('<textarea class="ecwp-input" rows="4" style="font-size:12px;"></textarea>').val(val);
			} else {
				$input = $('<input type="text" class="ecwp-input" style="font-size:12px;">').val(val);
			}

			// Live update on change
			$input.on('input change', (function (p) {
				return function () {
					blocks[selectedIdx][p] = $(this).val();
					renderCanvas();
				};
			})(prop));

			$row.append($input);
			$body.append($row);
		});
	}

	/* ── Export blocks → code editor ────────────────────────────────── */
	$('#ecwp-blocks-to-code').on('click', function () {
		if (blocks.length === 0) { alert('Add some blocks first.'); return; }

		var outer = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Email</title></head><body style="margin:0;padding:0;background:#f3f4f6;">'
			+ '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f4f6;"><tr><td align="center" style="padding:20px 0;">'
			+ '<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;">';

		blocks.forEach(function (block) {
			var def = BLOCK_DEFS[block.type];
			if (def) { outer += def.render(block); }
		});

		outer += '</table></td></tr></table></body></html>';

		if (cm) {
			cm.setValue(outer);
			cm.refresh();
		} else {
			$('#ecwp-code-editor').val(outer);
		}

		// Switch to code tab
		$('[data-tab="code"]').trigger('click');
		scheduleAutosave();
	});

	/* ── Import code → blocks (parses a simple note) ─────────────────── */
	$('#ecwp-code-to-blocks').on('click', function () {
		alert('Import parses simple block-builder HTML only.\nTo import existing custom HTML, use the Code Editor tab directly — your HTML is preserved when you open the Block Builder.');
	});

	/* ── Preview: global function ────────────────────────────────────── */
	window.ecwpRefreshPreview = refreshPreview;

	/* ── Utility ─────────────────────────────────────────────────────── */
	function escHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

}(jQuery));

/* =========================================================
   Email Campaign WP — Admin JS
   by ideaBoss (https://ideaboss.io)
   ========================================================= */

(function ($) {
	'use strict';

	// ── Confirm dialogs on destructive forms ────────────────────────────────
	$(document).on('submit', '.ecwp-confirm-form', function (e) {
		var msg = $(this).data('confirm') || 'Are you sure?';
		if (!confirm(msg)) {
			e.preventDefault();
			return false;
		}
	});

	// ── Toggle subscriber list when "assign all" checkbox is checked ─────────
	window.toggleSubscriberList = function (checkbox) {
		var wrap = document.getElementById('subscriber_list_wrap');
		if (!wrap) return;
		wrap.style.opacity = checkbox.checked ? '0.4' : '1';
		wrap.style.pointerEvents = checkbox.checked ? 'none' : 'auto';
	};

	// ── Filter subscriber checkboxes by typing ───────────────────────────────
	window.filterSubscribers = function (input) {
		var q     = input.value.toLowerCase();
		var items = input.closest('.ecwp-card-body').querySelectorAll('.ecwp-sub-item');
		items.forEach(function (item) {
			var text = item.textContent.toLowerCase();
			item.style.display = text.indexOf(q) !== -1 ? '' : 'none';
		});
	};

	// ── Generic table row filter ─────────────────────────────────────────────
	window.filterTable = function (input, tableId) {
		var q    = input.value.toLowerCase();
		var rows = document.querySelectorAll('#' + tableId + ' tbody tr');
		rows.forEach(function (row) {
			var text = row.textContent.toLowerCase();
			row.style.display = text.indexOf(q) !== -1 ? '' : 'none';
		});
	};

	// ── Show selected file name next to file inputs ──────────────────────────
	$(document).on('change', 'input[type="file"]', function () {
		var name = this.files && this.files[0] ? this.files[0].name : '';
		var hint = $(this).next('.ecwp-file-name');
		if (!hint.length) {
			hint = $('<span class="ecwp-hint ecwp-file-name"></span>').insertAfter(this);
		}
		hint.text(name ? '📄 ' + name : '');
	});

	// ── Auto-dismiss success notices after 4 seconds ─────────────────────────
	setTimeout(function () {
		$('.ecwp-notice-success').fadeOut(600);
	}, 4000);

	// ── Batch preview calculator (settings page) ─────────────────────────────
	function updateBatchPreview() {
		var sizeInput     = document.getElementById('ecwp_batch_size');
		var intervalInput = document.getElementById('ecwp_batch_interval');
		var preview       = document.getElementById('ecwp-batch-preview');
		if (!sizeInput || !intervalInput || !preview) return;

		var size     = Math.max(1, parseInt(sizeInput.value)     || 10);
		var interval = Math.max(1, parseInt(intervalInput.value) || 30);
		var batches  = Math.ceil(50 / size);
		var minutes  = (batches - 1) * interval;

		preview.textContent =
			'Example: 50 subscribers ÷ ' + size + ' = ' + batches +
			' batch' + (batches !== 1 ? 'es' : '') +
			', completing in ~' + minutes + ' minutes.';
	}

	var bsInput = document.getElementById('ecwp_batch_size');
	var biInput = document.getElementById('ecwp_batch_interval');
	if (bsInput) { bsInput.addEventListener('input', updateBatchPreview); }
	if (biInput) { biInput.addEventListener('input', updateBatchPreview); }

}(jQuery));

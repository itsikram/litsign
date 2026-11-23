/* SEO menu admin screens. Loaded only on SEO screens and edit screens with the SEO box. */
(function ($) {
	'use strict';

	var settings = window.wholesaleSeo || {};

	function length(text) {
		return Array.from(String(text || '')).length;
	}

	/* Character counters ------------------------------------------------ */

	function updateCounter($input, $out, limit) {
		var value = $input.val();
		var usingHint = !value && $input.attr('placeholder');
		var n = length(value || (usingHint ? $input.attr('placeholder') : ''));
		$out.text(n + ' / ' + limit + (usingHint ? ' (now)' : ''))
			.toggleClass('is-long', n > limit)
			.toggleClass('is-empty', n === 0);
	}

	function bindCounter($input, $out, limit) {
		if (!$input.length || !$out.length) {
			return;
		}
		$input.on('input', function () {
			updateCounter($input, $out, limit);
		});
		updateCounter($input, $out, limit);
	}

	$('[data-wseo-count]').each(function () {
		var $input = $(this);
		var $out = $('<span class="wseo-counter"></span>');
		$input.after($out);
		bindCounter($input, $out, parseInt($input.data('wseo-count'), 10));
	});

	$('[data-wseo-counter]').each(function () {
		var $out = $(this);
		bindCounter($('#' + $out.data('wseo-counter')), $out, parseInt($out.data('limit'), 10));
	});

	/* SEO box: search preview and focus keyword checks ------------------- */

	$('[data-wseo-box]').each(function () {
		var $box = $(this);
		var $title = $box.find('[data-wseo-input="title"]');
		var $description = $box.find('[data-wseo-input="description"]');
		var $focus = $box.find('[data-wseo-input="focus"]');
		var $checks = $box.find('[data-wseo-focus-checks]');

		function current($input) {
			return $input.val() || $input.attr('placeholder') || '';
		}

		function refresh() {
			$box.find('[data-wseo-serp="title"]').text(current($title));
			$box.find('[data-wseo-serp="description"]').text(current($description));

			var keyword = $.trim($focus.val() || '').toLowerCase();
			if (!keyword) {
				$checks.empty();
				return;
			}
			var slug = String($checks.data('slug') || '').toLowerCase();
			var tests = [
				['in the title', current($title).toLowerCase().indexOf(keyword) !== -1],
				['in the description', current($description).toLowerCase().indexOf(keyword) !== -1],
				['in the URL', slug.indexOf(keyword.replace(/\s+/g, '-')) !== -1]
			];
			$checks.html(tests.map(function (test) {
				return '<span class="wseo-check-' + (test[1] ? 'yes' : 'no') + '">' + (test[1] ? '✓' : '✗') + ' ' + test[0] + '</span>';
			}).join(' '));
		}

		$box.on('input change', 'input, textarea', refresh);
		refresh();
	});

	/* Media picker ------------------------------------------------------- */

	$(document).on('click', '.wseo-pick-image', function (event) {
		event.preventDefault();
		if (!window.wp || !wp.media) {
			return;
		}
		var $target = $('#' + $(this).data('target'));
		var frame = wp.media({ title: 'Choose image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var image = frame.state().get('selection').first().toJSON();
			$target.val(image.url).trigger('input');
		});
		frame.open();
	});

	/* Template preview (General & Titles) -------------------------------- */

	function renderTemplate(template, vars) {
		var text = String(template).replace(/\{(title|brand|category|price_from|state|city|sep)\}/g, function (match, key) {
			return vars[key] ? String(vars[key]) : '';
		});
		text = text.replace(/\s+/g, ' ').replace(/\(\s*\)|\[\s*\]/g, '');
		var sep = $.trim(vars.sep || '');
		if (sep) {
			var quoted = sep.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
			text = text.replace(new RegExp('(?:\\s*' + quoted + '\\s*){2,}', 'g'), ' ' + sep + ' ');
			text = text.replace(new RegExp('^\\s*' + quoted + '\\s*|\\s*' + quoted + '\\s*$', 'g'), '');
		}
		return text.replace(/\s+/g, ' ').replace(/^[\s,:;]+|[\s,:;]+$/g, '');
	}

	$('.wseo-template').each(function () {
		var $card = $(this);
		var vars = $card.data('wseo-vars') || {};
		var now = {};
		try {
			now = JSON.parse($card.find('.wseo-now').text() || '{}');
		} catch (e) {}

		function refresh() {
			var current = $.extend({}, vars, {
				brand: $('#wseo-brand').val() || 'Storefront Sign Online',
				sep: $('#wseo-separator').val() || '|'
			});
			['title', 'description'].forEach(function (field) {
				var template = $card.find('[data-wseo-tpl="' + field + '"]').val();
				$card.find('[data-wseo-out="' + field + '"]').text(template ? renderTemplate(template, current) : (now[field] || ''));
			});
		}

		$card.on('input', 'input, textarea', refresh);
		$('#wseo-brand, #wseo-separator').on('input change', refresh);
	});

	/* Dashboard scan ----------------------------------------------------- */

	$('#wseo-scan-start').on('click', function () {
		var $button = $(this).prop('disabled', true);
		var $progress = $('.wseo-progress').prop('hidden', false);
		var $bar = $progress.find('.wseo-progress-bar').css('width', '0');
		var $status = $('.wseo-scan-status').text('Listing pages and reading the sitemaps…');

		function fail(message) {
			$status.text('The scan stopped: ' + (message || 'no answer from the server') + '. Try again.');
			$button.prop('disabled', false);
		}

		function post(data) {
			return $.post(settings.ajaxUrl, $.extend({ action: 'wholesale_seo_scan', nonce: settings.scanNonce }, data));
		}

		post({ step: 'start' }).done(function (response) {
			if (!response || !response.success) {
				return fail(response && response.data);
			}
			var total = response.data.total;
			var batch = response.data.batch;

			(function next(offset) {
				if (offset >= total) {
					$status.text('Saving results…');
					post({ step: 'finish' }).done(function () {
						window.location.reload();
					}).fail(function () {
						fail();
					});
					return;
				}
				$status.text('Checked ' + offset + ' of ' + total + ' URLs…');
				post({ step: 'batch', offset: offset }).done(function (result) {
					if (!result || !result.success) {
						return fail(result && result.data);
					}
					$bar.css('width', Math.round(result.data.done / total * 100) + '%');
					next(offset + batch);
				}).fail(function () {
					fail();
				});
			})(0);
		}).fail(function () {
			fail();
		});
	});

	/* Bulk editor quick edit ---------------------------------------------- */

	$('.wseo-bulk').on('click', '.wseo-quick-edit', function () {
		var $row = $(this).closest('tr');
		var $quick = $row.next('.wseo-quick-row').prop('hidden', false);
		$quick.find('label').each(function () {
			var $field = $(this).find('input, textarea');
			var $out = $(this).find('.wseo-counter');
			if (!$out.data('bound')) {
				bindCounter($field, $out, parseInt($out.data('limit'), 10));
				$out.data('bound', true);
			}
		});
		$quick.find('input').first().trigger('focus');
	});

	$('.wseo-bulk').on('click', '.wseo-quick-cancel', function () {
		$(this).closest('.wseo-quick-row').prop('hidden', true);
	});

	$('.wseo-bulk').on('click', '.wseo-quick-save', function () {
		var $quick = $(this).closest('.wseo-quick-row');
		var $row = $quick.prev('tr');
		var $status = $quick.find('.wseo-quick-status').text('Saving…');
		var $buttons = $quick.find('button').prop('disabled', true);

		$.post(settings.ajaxUrl, {
			action: 'wholesale_seo_bulk_save',
			nonce: settings.bulkNonce,
			object_type: $row.data('object-type'),
			object_id: $row.data('object-id'),
			title: $quick.find('[data-field="title"]').val(),
			description: $quick.find('[data-field="description"]').val()
		}).done(function (response) {
			if (!response || !response.success) {
				$status.text((response && response.data) || 'Not saved.');
				return;
			}
			$row.find('[data-wseo-cell="title"]').text(response.data.title);
			$row.find('[data-wseo-cell="description"]').text(response.data.description);
			$status.text('Saved. The page now prints the values shown in the row.');
		}).fail(function () {
			$status.text('Not saved: no answer from the server.');
		}).always(function () {
			$buttons.prop('disabled', false);
		});
	});
})(jQuery);

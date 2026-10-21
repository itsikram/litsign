/**
 * Product edit screen: section tabs, friendly editors for options, channel letter
 * data and product group links, a live price example, unsaved-change tracking and
 * a check before Update. Loaded only on the product edit / new product screens
 * (inc/admin-product-editor.php).
 *
 * Data safety: the hidden JSON fields (#productAttrJson, #productClJson and the
 * _product_group_data textarea) are only rewritten after something in their editor
 * is changed. Untouched, they go back exactly as stored. Options and rows that
 * weren't edited keep their stored text byte for byte.
 */
(function ($) {
	'use strict';

	const D = window.ssoProductEditor || {};
	const isBlockEditor = () => !!(window.wp && wp.data && wp.data.select('core/editor') && document.body.classList.contains('block-editor-page'));

	/* ------------------------------------------------------------------ helpers */

	let uidCount = 0;
	const uid = (prefix) => 'sso-' + prefix + '-' + (++uidCount);
	const esc = (value) => String(value == null ? '' : value).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	// Same test as PHP is_numeric() for the values used here.
	const isNum = (value) => {
		const s = String(value == null ? '' : value).trim();
		return s !== '' && /^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?$/.test(s);
	};
	const num = (value) => (isNum(value) ? parseFloat(String(value).trim()) : 0);
	// PHP (float) cast: leading number or 0.
	const phpFloat = (value) => {
		const m = String(value == null ? '' : value).trim().match(/^[+-]?(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/);
		return m ? parseFloat(m[0]) : 0;
	};
	const money = (value) => '$' + (Math.round(value * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	// All spaces (not only the first two) become dashes; existing ids are never re-slugged.
	const slugify = (value) => String(value || '').trim().toLowerCase().replace(/[\s_]+/g, '-').replace(/[^a-z0-9-]/g, '').replace(/-+/g, '-').replace(/^-|-$/g, '');
	const titleFromId = (id) => String(id || '').replace(/-/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
	const looksLikeColor = (value) => /^\s*(#[0-9a-f]{3,8}|rgba?\([^)]*\))\s*$/i.test(String(value || ''));
	const swatch = (value) => (looksLikeColor(value) ? '<span class="sso-swatch" style="background:' + esc(String(value).trim()) + '" aria-hidden="true"></span>' : '');
	const store = {
		get(key) { try { return window.localStorage.getItem(key); } catch (e) { return null; } },
		set(key, value) { try { window.localStorage.setItem(key, value); } catch (e) { /* storage unavailable */ } },
	};
	const fieldRow = (key) => {
		let $el = $(document.getElementById(key));
		if (!$el.length) {
			$el = $('[name="' + key + '"], [name^="' + key + '["]').first();
		}
		return $el.closest('.cmb-row');
	};
	const fieldVal = (key) => {
		const el = document.getElementById(key);
		if (!el) return '';
		if (el.type === 'checkbox') return el.checked ? 'on' : '';
		return el.value;
	};

	/* ------------------------------------------------------- change tracking */

	const events = $({});
	const notify = () => events.trigger('change');
	let markDirtyTimer = null;
	const scheduleCheck = () => {
		clearTimeout(markDirtyTimer);
		markDirtyTimer = setTimeout(notify, 120);
	};

	/* ---------------------------------------------- show stored, not defaults */

	// CMB2 shows a field's default when the stored value is empty, and saving would
	// then write that default in. Show what is really stored instead.
	function showStoredValues() {
		if (D.isNew || !D.stored) return;
		Object.keys(D.stored).forEach((key) => {
			const el = document.getElementById(key);
			const stored = D.stored[key];
			if (!el || el.classList.contains('wp-editor-area') || el.classList.contains('cmb2-upload-file')) return;
			if (el.type === 'checkbox') {
				const on = stored !== null && stored !== '' && stored !== false;
				if (el.checked !== on) el.checked = on;
				return;
			}
			if (el.tagName === 'TEXTAREA' || el.type === 'text') {
				const value = stored === null ? '' : String(stored);
				if (el.value.replace(/\r\n/g, '\n') !== value.replace(/\r\n/g, '\n')) el.value = value;
			}
		});
	}

	/* ----------------------------------------- rich text: keep unedited HTML */

	// Before posting the meta boxes, WordPress has TinyMCE write every rich text
	// field back to its textarea, which reorders attributes and drops some (for
	// example loading="lazy") even when nobody edited the field. When the content
	// is the same as when the editor opened, send the stored HTML back instead.
	// Edits made in either the Visual or the Text tab change the content, so they
	// are never replaced.
	function guardRichText(editor) {
		if (!editor || editor.ssoGuarded || !editor.getElement || !editor.getElement() || editor.getElement().tagName !== 'TEXTAREA') return;
		editor.ssoGuarded = true;
		const original = editor.getElement().value;
		let opened = null;
		const capture = () => {
			try { opened = editor.getContent({ save: true }); } catch (e) { opened = null; }
		};
		if (editor.initialized) capture();
		else editor.on('init', capture);
		editor.on('SaveContent', (e) => {
			if (opened === null || (editor.isHidden && editor.isHidden())) return;
			if (e.content === opened) e.content = original;
		});
	}

	function guardAllRichText() {
		if (!window.tinymce) return;
		(tinymce.editors ? Object.values(tinymce.editors) : []).forEach(guardRichText);
		if (tinymce.on) tinymce.on('AddEditor', (e) => guardRichText(e.editor));
	}

	/* ============================================================ OPTIONS EDITOR */

	const PRICE_TYPES = [
		{ value: 'flat', label: 'Adds $ (flat)', help: 'Adds this dollar amount to the item price.' },
		{ value: '%', label: 'Adds % of price so far', help: 'Adds this percent of the price built up so far (base price plus the options above it). Order matters.' },
		{ value: 'sqft', label: '$ per sq ft', help: "Adds this amount for every square foot of the sign (at least the minimum sq ft)." },
		{ value: 'lft', label: '$ per linear ft (letters)', help: 'Channel letters: amount × letter height × number of letters ÷ 12.' },
		{ value: 'none', label: 'No price (text or color)', help: "Doesn't change the price. The value is shown as-is or used as a color." },
	];
	const FLAGS = [
		{ token: 'avoid-price', label: "Not priced here", help: 'Skipped when adding up option prices (for example channel letter height, or width/height dropdowns).' },
		{ token: 'size-width-inch', label: 'This is the width in inches', help: 'Its chosen value is the sign width; used with a height dropdown to work out square feet.' },
		{ token: 'size-height-inch', label: 'This is the height in inches', help: 'Its chosen value is the sign height; used with a width dropdown to work out square feet.' },
	];

	// Same rules as wholesale_parse_option_price() in inc/pricing.php.
	function parseOptionValue(value) {
		const parts = String(value).split('/');
		if (!isNum(parts[0])) {
			return { amount: '', type: 'none', tail: String(value) };
		}
		const second = parts.length > 1 ? parts[1].trim().toLowerCase() : '';
		if (['%', 'sqft', 'lft'].includes(second)) {
			return { amount: parts[0].trim(), type: second, tail: parts.slice(2).join('/') };
		}
		return { amount: parts[0].trim(), type: 'flat', tail: parts.slice(1).join('/') };
	}

	function composeOptionValue(option) {
		const amount = String(option.amount).trim();
		const tail = option.tail;
		if (option.type === 'none') return String(tail).trim();
		if (option.type === 'flat') {
			if (tail !== '') return amount + '/' + tail;
			// As the old editor did: a bare 0 carries its name so it stays distinct.
			return amount === '0' ? amount + '/' + option.label.replace(/"/g, '”').trim() : amount;
		}
		return amount + '/' + option.type + (tail !== '' ? '/' + tail : '');
	}

	function makeOption(label, value, isNew) {
		const parsed = parseOptionValue(value);
		return {
			key: uid('o'),
			label: String(label),
			value: String(value),
			origLabel: isNew ? null : String(label),
			origValue: isNew ? null : String(value),
			amount: parsed.amount,
			type: parsed.type,
			tail: parsed.tail,
			edited: !!isNew,
		};
	}

	function optionOut(option) {
		if (!option.edited) return { [option.origLabel]: option.origValue };
		return { [option.label.replace(/"/g, '”').trim()]: option.value.trim() };
	}

	const AttrEditor = {
		$root: null,
		$field: null,
		attrs: [],
		dirty: false,
		readable: true,
		openAdvanced: {},
		confirming: null,

		init() {
			this.$root = $('#sso-attr-editor');
			this.$field = $('#productAttrJson');
			if (!this.$root.length || !this.$field.length) return;
			const raw = this.$field.val();
			let data = [];
			if (raw.trim() !== '' && raw !== '[{') {
				try { data = JSON.parse(raw); } catch (e) { data = null; }
			}
			this.readable = Array.isArray(data);
			this.attrs = (Array.isArray(data) ? data : []).filter((a) => a && typeof a === 'object').map((a) => ({
				key: uid('a'),
				name: String(a.name == null ? '' : a.name),
				heading: a.heading == null ? 'normal' : String(a.heading),
				cssClass: a.cssClass == null ? '' : String(a.cssClass),
				isNew: false,
				options: (Array.isArray(a.options) ? a.options : []).reduce((list, option) => {
					if (option && typeof option === 'object') {
						Object.keys(option).forEach((label) => list.push(makeOption(label, option[label] == null ? '' : option[label], false)));
					}
					return list;
				}, []),
			}));
			this.render();
			this.bind();
		},

		write() {
			this.dirty = true;
			const out = this.attrs.map((a) => ({
				name: a.name,
				heading: a.heading,
				cssClass: a.cssClass,
				options: a.options.filter((o) => (o.edited ? o.label.trim() !== '' : true)).map(optionOut),
			}));
			this.$field.val(JSON.stringify(out));
			scheduleCheck();
		},

		find(key) { return this.attrs.find((a) => a.key === key); },
		findOption(attr, key) { return attr.options.find((o) => o.key === key); },

		render() {
			const parts = [];
			parts.push('<div class="sso-pe-intro"><p>Each option is a dropdown on the product page. Choices are added to the price from top to bottom. <button type="button" class="button-link sso-legend-toggle" aria-expanded="false">How prices work</button></p>');
			parts.push('<dl class="sso-legend" hidden>' + PRICE_TYPES.map((t) => '<dt>' + esc(t.label) + '</dt><dd>' + esc(t.help) + '</dd>').join('') + '</dl></div>');
			if (!this.attrs.length) {
				parts.push('<p class="sso-empty">This product has no options yet. Customers see only the base price.</p>');
			}
			parts.push('<div class="sso-attr-list">' + this.attrs.map((a, i) => this.attrHtml(a, i)).join('') + '</div>');
			const newId = uid('newattr');
			parts.push('<div class="sso-add-attr"><label for="' + newId + '">New option name</label><div class="sso-inline"><input type="text" id="' + newId + '" class="sso-new-attr-name" placeholder="Pole pocket" autocomplete="off"><button type="button" class="button sso-add-attr-btn">Add option</button></div><p class="sso-help sso-new-attr-id" aria-live="polite"></p></div>');
			this.$root.html(parts.join(''));
			this.makeSortable();
			this.refreshIssues();
		},

		attrHtml(a, index) {
			const tokens = a.cssClass.split(/\s+/).filter(Boolean);
			const isCl = Product.isChannelLetter();
			const nameId = uid('aname');
			const header = a.isNew
				? '<label class="screen-reader-text" for="' + nameId + '">Option name</label><input type="text" id="' + nameId + '" class="sso-attr-name" value="' + esc(a.name) + '" data-field="name" autocomplete="off"><span class="sso-id-chip" title="Saved as this id">id: ' + esc(a.name) + '</span>'
				: '<span class="sso-attr-title">' + esc(titleFromId(a.name)) + '</span><span class="sso-id-chip" title="Existing options keep their id, because saved carts and orders use it"><span class="dashicons dashicons-lock" aria-hidden="true"></span> id: ' + esc(a.name) + '</span>';
			const isHeight = a.name === 'height' && isCl;
			const rows = a.options.map((o, oi) => this.optionHtml(a, o, oi, isHeight)).join('');
			const advOpen = !!this.openAdvanced[a.key];
			const cssId = uid('css');
			const headingId = uid('head');
			const confirming = this.confirming === a.key;
			return '<section class="sso-card sso-attr" data-key="' + a.key + '" aria-label="Option ' + esc(titleFromId(a.name)) + '">' +
				'<header class="sso-card-head"><span class="sso-drag dashicons dashicons-move" title="Drag to reorder" aria-hidden="true"></span>' + header +
				'<span class="sso-count">' + a.options.length + (a.options.length === 1 ? ' choice' : ' choices') + '</span>' +
				'<span class="sso-actions">' +
				'<button type="button" class="button sso-icon" data-act="attr-up" aria-label="Move ' + esc(a.name) + ' up"' + (index === 0 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" class="button sso-icon" data-act="attr-down" aria-label="Move ' + esc(a.name) + ' down"' + (index === this.attrs.length - 1 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" class="button" data-act="attr-dup">Duplicate</button>' +
				(confirming
					? '<span class="sso-confirm" role="group" aria-label="Confirm removal">Remove this option and its ' + a.options.length + ' choices? <button type="button" class="button sso-danger" data-act="attr-remove-yes">Remove</button> <button type="button" class="button" data-act="cancel-confirm">Keep</button></span>'
					: '<button type="button" class="button sso-danger-link" data-act="attr-remove">Remove</button>') +
				'</span></header>' +
				(isHeight ? '<p class="sso-help">Channel letters: each choice is a letter height and its amount is the <strong>price per letter</strong> at that height.</p>' : '') +
				'<div class="sso-opt-head" aria-hidden="true"><span></span><span>Choice name</span><span>Amount</span><span>How it is charged</span><span></span></div>' +
				'<ol class="sso-opt-list">' + rows + '</ol>' +
				'<div class="sso-attr-foot"><button type="button" class="button" data-act="opt-add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> Add choice</button>' +
				'<button type="button" class="button-link sso-adv-toggle" data-act="adv" aria-expanded="' + advOpen + '">' + (advOpen ? 'Hide advanced' : 'Advanced') + '</button></div>' +
				'<div class="sso-adv"' + (advOpen ? '' : ' hidden') + '>' +
				'<fieldset class="sso-flags"><legend>Special behavior</legend>' + FLAGS.map((f) => {
					const id = uid('flag');
					return '<div class="sso-flag"><input type="checkbox" id="' + id + '" data-flag="' + f.token + '"' + (tokens.includes(f.token) ? ' checked' : '') + '><label for="' + id + '">' + esc(f.label) + '</label><span class="sso-help">' + esc(f.help) + '</span></div>';
				}).join('') + '</fieldset>' +
				'<div class="sso-grid2"><div><label for="' + cssId + '">CSS class</label><input type="text" id="' + cssId + '" data-field="cssClass" value="' + esc(a.cssClass) + '" autocomplete="off"><p class="sso-help">Used by the product page. Example: select-' + esc(a.name || 'size') + '</p></div>' +
				'<div><label for="' + headingId + '">Display type</label><select id="' + headingId + '" data-field="heading">' +
				['normal', 'flat', 'percent', 'lft', 'sqft'].concat(['normal', 'flat', 'percent', 'lft', 'sqft'].includes(a.heading) ? [] : [a.heading]).map((h) => '<option value="' + esc(h) + '"' + (h === a.heading ? ' selected' : '') + '>' + esc({ normal: 'Normal', flat: 'Flat', percent: 'Percent', lft: 'Linear ft', sqft: 'Square ft' }[h] || h) + '</option>').join('') +
				'</select><p class="sso-help">Saved with the option but not used for pricing. Each choice\'s "How it is charged" sets its price.</p></div></div>' +
				'</div>' +
				'<div class="sso-issues" aria-live="polite"></div>' +
				'</section>';
		},

		optionHtml(a, o, oi, isHeight) {
			const nameId = uid('oname');
			const amountId = uid('oamt');
			const typeId = uid('otype');
			const isNone = o.type === 'none';
			const confirming = this.confirming === o.key;
			const typeOptions = PRICE_TYPES.map((t) => '<option value="' + t.value + '"' + (t.value === o.type ? ' selected' : '') + '>' + esc(t.label) + '</option>').join('');
			const tip = isHeight ? 'price per letter' : (PRICE_TYPES.find((t) => t.value === o.type) || {}).help || '';
			return '<li class="sso-opt" data-key="' + o.key + '">' +
				'<span class="sso-drag dashicons dashicons-menu" tabindex="0" role="button" aria-label="Reorder ' + esc(o.label || 'choice') + ' (Alt + arrow keys)" title="Drag, or Alt + up/down arrow"></span>' +
				'<span><label class="screen-reader-text" for="' + nameId + '">Choice name</label><input type="text" id="' + nameId + '" data-field="label" value="' + esc(o.label) + '" placeholder="Single sided" autocomplete="off"></span>' +
				'<span class="sso-amount"><label class="screen-reader-text" for="' + amountId + '">' + (isNone ? 'Value (no price)' : 'Amount') + '</label>' + (isNone ? swatch(o.tail) : '') +
				'<input type="text" id="' + amountId + '" data-field="' + (isNone ? 'tail' : 'amount') + '" value="' + esc(isNone ? o.tail : o.amount) + '" placeholder="' + (isNone ? 'White' : '10') + '" inputmode="' + (isNone ? 'text' : 'decimal') + '" autocomplete="off"></span>' +
				'<span><label class="screen-reader-text" for="' + typeId + '">How it is charged</label><select id="' + typeId + '" data-field="type" title="' + esc(tip) + '">' + typeOptions + '</select></span>' +
				'<span class="sso-opt-actions">' +
				'<button type="button" class="button sso-icon" data-act="opt-dup" aria-label="Duplicate ' + esc(o.label || 'choice') + '"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>' +
				(confirming
					? '<span class="sso-confirm">Remove? <button type="button" class="button sso-danger" data-act="opt-remove-yes">Yes</button> <button type="button" class="button" data-act="cancel-confirm">No</button></span>'
					: '<button type="button" class="button sso-icon sso-danger-link" data-act="opt-remove" aria-label="Remove ' + esc(o.label || 'choice') + '"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>') +
				'</span>' +
				'<span class="sso-raw" title="Stored value">' + (isNone ? '' : swatch(o.tail)) + '<code>' + esc(o.edited ? composeOptionValue(o) : o.origValue) + '</code></span>' +
				'<span class="sso-row-issue" aria-live="polite"></span>' +
				'</li>';
		},

		makeSortable() {
			if (!$.fn.sortable) return;
			const self = this;
			this.$root.find('.sso-attr-list').sortable({
				handle: '.sso-card-head .sso-drag',
				items: '> .sso-attr',
				axis: 'y',
				update() {
					const order = $(this).children('.sso-attr').map((i, el) => el.getAttribute('data-key')).get();
					self.attrs.sort((x, y) => order.indexOf(x.key) - order.indexOf(y.key));
					self.write();
					self.render();
				},
			});
			this.$root.find('.sso-opt-list').sortable({
				handle: '.sso-drag',
				items: '> .sso-opt',
				axis: 'y',
				update() {
					const attr = self.find($(this).closest('.sso-attr').data('key'));
					const order = $(this).children('.sso-opt').map((i, el) => el.getAttribute('data-key')).get();
					attr.options.sort((x, y) => order.indexOf(x.key) - order.indexOf(y.key));
					self.write();
					self.render();
				},
			});
		},

		bind() {
			const self = this;
			const $root = this.$root;

			$root.on('click', '.sso-legend-toggle', function () {
				const $legend = $root.find('.sso-legend');
				const open = $legend.prop('hidden');
				$legend.prop('hidden', !open);
				$(this).attr('aria-expanded', String(open));
			});

			$root.on('input', '.sso-new-attr-name', function () {
				const id = slugify(this.value);
				$root.find('.sso-new-attr-id').text(id ? 'Will be saved as: ' + id : '');
			});
			$root.on('keydown', '.sso-new-attr-name', (e) => {
				if (e.key === 'Enter') {
					e.preventDefault();
					$root.find('.sso-add-attr-btn').trigger('click');
				}
			});
			$root.on('click', '.sso-add-attr-btn', () => {
				const $input = $root.find('.sso-new-attr-name');
				const id = slugify($input.val());
				const $msg = $root.find('.sso-new-attr-id');
				if (!id) {
					$msg.addClass('sso-error-text').text('Enter a name for the option, like "Pole pocket".');
					$input.trigger('focus');
					return;
				}
				if (self.attrs.some((a) => a.name.toLowerCase() === id)) {
					$msg.addClass('sso-error-text').text('This product already has an option with the id "' + id + '". Pick another name.');
					$input.trigger('focus');
					return;
				}
				const attr = { key: uid('a'), name: id, heading: 'normal', cssClass: 'select-' + id, isNew: true, options: [makeOption('', '0', true)] };
				self.attrs.push(attr);
				self.write();
				self.render();
				$root.find('.sso-attr[data-key="' + attr.key + '"] .sso-opt input[data-field="label"]').first().trigger('focus');
			});

			$root.on('click', '[data-act]', function (e) {
				const act = this.getAttribute('data-act');
				if (this.getAttribute('aria-disabled') === 'true') return;
				const $attr = $(this).closest('.sso-attr');
				const attr = self.find($attr.data('key'));
				const $opt = $(this).closest('.sso-opt');
				const option = attr && $opt.length ? self.findOption(attr, $opt.data('key')) : null;
				const i = attr ? self.attrs.indexOf(attr) : -1;
				let focus = null;
				switch (act) {
					case 'adv':
						self.openAdvanced[attr.key] = !self.openAdvanced[attr.key];
						self.render();
						self.$root.find('.sso-attr[data-key="' + attr.key + '"] .sso-adv-toggle').trigger('focus');
						return;
					case 'attr-up':
					case 'attr-down': {
						const j = act === 'attr-up' ? i - 1 : i + 1;
						if (j < 0 || j >= self.attrs.length) return;
						self.attrs.splice(j, 0, self.attrs.splice(i, 1)[0]);
						focus = '.sso-attr[data-key="' + attr.key + '"] [data-act="' + act + '"]';
						break;
					}
					case 'attr-dup': {
						let base = attr.name + '-copy';
						let id = base;
						for (let n = 2; self.attrs.some((a) => a.name.toLowerCase() === id.toLowerCase()); n++) id = base + '-' + n;
						const copy = {
							key: uid('a'), name: id, heading: attr.heading,
							cssClass: attr.cssClass.split(/\s+/).map((t) => (t === 'select-' + attr.name ? 'select-' + id : t)).join(' '),
							isNew: true,
							options: attr.options.map((o) => makeOption(o.edited ? o.label : o.origLabel, o.edited ? composeOptionValue(o) : o.origValue, true)),
						};
						self.attrs.splice(i + 1, 0, copy);
						focus = '.sso-attr[data-key="' + copy.key + '"] .sso-attr-name';
						break;
					}
					case 'attr-remove':
						self.confirming = attr.key;
						self.render();
						self.$root.find('[data-act="attr-remove-yes"]').trigger('focus');
						return;
					case 'attr-remove-yes':
						self.confirming = null;
						self.attrs.splice(i, 1);
						focus = '.sso-new-attr-name';
						break;
					case 'cancel-confirm':
						self.confirming = null;
						self.render();
						return;
					case 'opt-add': {
						const o = makeOption('', '0', true);
						attr.options.push(o);
						focus = '.sso-opt[data-key="' + o.key + '"] input[data-field="label"]';
						break;
					}
					case 'opt-dup': {
						const copy = makeOption(option.edited ? option.label : option.origLabel, option.edited ? composeOptionValue(option) : option.origValue, true);
						copy.label = copy.label + ' (copy)';
						if (copy.type === 'flat' && copy.tail.trim() === (option.edited ? option.label : option.origLabel).trim()) copy.tail = copy.label;
						copy.value = composeOptionValue(copy);
						attr.options.splice(attr.options.indexOf(option) + 1, 0, copy);
						focus = '.sso-opt[data-key="' + copy.key + '"] input[data-field="label"]';
						break;
					}
					case 'opt-remove':
						self.confirming = option.key;
						self.render();
						self.$root.find('[data-act="opt-remove-yes"]').trigger('focus');
						return;
					case 'opt-remove-yes':
						self.confirming = null;
						attr.options.splice(attr.options.indexOf(option), 1);
						focus = '.sso-attr[data-key="' + attr.key + '"] [data-act="opt-add"]';
						break;
					default:
						return;
				}
				e.preventDefault();
				self.write();
				self.render();
				if (focus) self.$root.find(focus).first().trigger('focus');
			});

			// Typing updates the data without re-rendering, so focus stays put.
			$root.on('input change', '.sso-attr [data-field], .sso-attr [data-flag]', function (e) {
				const attr = self.find($(this).closest('.sso-attr').data('key'));
				if (!attr) return;
				if (this.hasAttribute('data-flag')) {
					const token = this.getAttribute('data-flag');
					let tokens = attr.cssClass.split(/\s+/).filter(Boolean).filter((t) => t !== token);
					if (this.checked) tokens.push(token);
					attr.cssClass = tokens.join(' ');
					$(this).closest('.sso-attr').find('input[data-field="cssClass"]').val(attr.cssClass);
					self.write();
					return;
				}
				const field = this.getAttribute('data-field');
				const $opt = $(this).closest('.sso-opt');
				if (!$opt.length) {
					if (field === 'name') {
						const id = slugify(this.value);
						const old = attr.name;
						attr.name = id;
						attr.cssClass = attr.cssClass.split(/\s+/).map((t) => (t === 'select-' + old ? 'select-' + id : t)).join(' ');
						$(this).siblings('.sso-id-chip').text('id: ' + (id || '(empty)'));
					} else {
						attr[field] = this.value;
					}
					self.write();
					return;
				}
				const option = self.findOption(attr, $opt.data('key'));
				const before = { label: option.label, tail: option.tail, type: option.type };
				option[field] = this.value;
				if (field === 'label' && option.type === 'flat' && before.tail.trim() !== '' && before.tail.trim() === before.label.trim()) {
					option.tail = this.value.replace(/"/g, '”').trim();
				}
				if (field === 'type') {
					if (this.value === 'none') {
						option.tail = option.tail !== '' ? option.tail : option.label;
					} else if (before.type === 'none') {
						option.amount = isNum(option.amount) ? option.amount : '0';
						option.tail = looksLikeColor(option.tail) ? option.tail : '';
					}
				}
				option.edited = true;
				option.value = composeOptionValue(option);
				self.write();
				if (field === 'type') {
					self.render();
					self.$root.find('.sso-opt[data-key="' + option.key + '"] select[data-field="type"]').trigger('focus');
				} else {
					$opt.find('.sso-raw code').text(option.value);
					self.refreshIssues();
				}
			});

			// Keyboard reordering of choices: Alt + arrow keys on the handle or any field.
			$root.on('keydown', '.sso-opt', function (e) {
				if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) return;
				const attr = self.find($(this).closest('.sso-attr').data('key'));
				const option = self.findOption(attr, $(this).data('key'));
				const i = attr.options.indexOf(option);
				const j = e.key === 'ArrowUp' ? i - 1 : i + 1;
				if (j < 0 || j >= attr.options.length) return;
				e.preventDefault();
				const focusField = $(e.target).attr('data-field');
				attr.options.splice(j, 0, attr.options.splice(i, 1)[0]);
				self.write();
				self.render();
				self.$root.find('.sso-opt[data-key="' + option.key + '"] ' + (focusField ? '[data-field="' + focusField + '"]' : '.sso-drag')).trigger('focus');
			});
		},

		issues() {
			const list = [];
			if (!this.attrs.length && !this.readable && !this.dirty) return list;
			const seen = {};
			this.attrs.forEach((a) => {
				const where = 'Options › ' + (titleFromId(a.name) || 'New option');
				const at = { tab: 'options', key: a.key };
				const lower = a.name.toLowerCase();
				if (!a.name) list.push(Object.assign({ level: 'error', msg: where + ': enter an option name.' }, at));
				else if (seen[lower]) list.push(Object.assign({ level: 'error', msg: where + ': another option already uses the id "' + a.name + '". Ids must be unique.' }, at));
				seen[lower] = true;
				if (!a.options.length) list.push(Object.assign({ level: 'warning', msg: where + ' has no choices, so it shows an empty dropdown.' }, at));
				const labels = {};
				a.options.forEach((o, n) => {
					const row = where + ', choice ' + (n + 1) + (o.label ? ' (' + o.label + ')' : '');
					const oat = { tab: 'options', key: a.key, opt: o.key };
					const level = o.edited || a.isNew ? 'error' : 'warning';
					if (!o.label.trim()) list.push(Object.assign({ level, msg: row + ': enter a choice name or remove the row.', row: 'Enter a choice name or remove this row.' }, oat));
					if (o.type !== 'none' && !isNum(o.amount)) list.push(Object.assign({ level, msg: row + ': the amount must be a number, like 12.50.', row: 'The amount must be a number, like 12.50.' }, oat));
					else if (o.type !== 'none' && num(o.amount) < 0) list.push(Object.assign({ level: 'warning', msg: row + ': the amount is negative, so this choice lowers the price.', row: 'Negative amount: this lowers the price.' }, oat));
					const key = o.label.trim().toLowerCase();
					if (key && labels[key]) list.push(Object.assign({ level: 'warning', msg: row + ': two choices have the same name.', row: 'Another choice has this name.' }, oat));
					labels[key] = true;
				});
			});
			return list;
		},

		refreshIssues() {
			const issues = this.issues();
			this.$root.find('.sso-opt').removeClass('sso-has-error sso-has-warning').find('.sso-row-issue').text('');
			this.$root.find('.sso-attr .sso-issues').empty();
			this.$root.find('input').removeAttr('aria-invalid');
			issues.forEach((issue) => {
				if (issue.opt) {
					const $row = this.$root.find('.sso-opt[data-key="' + issue.opt + '"]');
					$row.addClass(issue.level === 'error' ? 'sso-has-error' : 'sso-has-warning');
					const $msg = $row.find('.sso-row-issue');
					$msg.text(($msg.text() ? $msg.text() + ' ' : '') + issue.row);
					if (issue.level === 'error') {
						$row.find(/name/.test(issue.row) ? 'input[data-field="label"]' : 'input[data-field="amount"]').attr('aria-invalid', 'true');
					}
				} else if (issue.key) {
					this.$root.find('.sso-attr[data-key="' + issue.key + '"] > .sso-issues').append($('<p>').addClass('sso-' + issue.level + '-text').text(issue.msg));
				}
			});
			scheduleCheck();
		},

		onSaved() {
			// After a successful save the stored ids are fixed.
			this.attrs.forEach((a) => { a.isNew = false; });
			this.dirty = false;
			this.render();
		},
	};

	/* ===================================================== CHANNEL LETTER EDITOR */

	const CL_KNOWN = {
		'cost-per-inch': { heading: 'Cost per inch', label: 'Letter height', value: 'Price per letter ($)', cost: 'Not used for this table', help: 'Builder price table: each row is a letter height (8 to 45 inches) and the price of one letter at that height. Sizes in between round down.' },
		'trimcap-color': { label: 'Color name', value: 'Color (rgb or #hex)', help: 'Trimcap colors offered in the builder.' },
		'trimcap-size': { label: 'Size', value: 'Value', help: 'Trimcap sizes offered in the builder.' },
		'return-color': { label: 'Color name', value: 'Color (rgb or #hex)', help: 'Return (side) colors offered in the builder.' },
		'return-size': { label: 'Size', value: 'Value (inches)', help: 'Return depths offered in the builder.' },
	};
	const clInfo = (id) => {
		if (CL_KNOWN[id]) return CL_KNOWN[id];
		if (/^color-/.test(id)) return { label: 'Color name', value: 'Color (rgb or #hex)', cost: 'Cost per inch of letter height ($)', help: 'Face colors in this group. Its cost is added per letter for each inch of height when one of these colors is picked.' };
		return { label: 'Choice', value: 'Value', help: '' };
	};

	const ClEditor = {
		$root: null,
		$field: null,
		groups: [],
		dirty: false,
		readable: true,
		confirming: null,

		init() {
			this.$root = $('#sso-cl-editor');
			this.$field = $('#productClJson');
			if (!this.$root.length || !this.$field.length) return;
			const raw = this.$field.val();
			let data = [];
			if (raw.trim() !== '' && raw !== '[{') {
				try { data = JSON.parse(raw); } catch (e) { data = null; }
			}
			this.readable = Array.isArray(data);
			this.groups = (Array.isArray(data) ? data : []).filter((g) => g && typeof g === 'object').map((g) => ({
				key: uid('g'),
				id: String(g.id == null ? '' : g.id),
				heading: g.heading == null ? '' : String(g.heading),
				cost: g.cost == null ? '' : String(g.cost),
				isNew: false,
				open: false,
				extra: Object.keys(g).filter((k) => !['id', 'heading', 'cost', 'options'].includes(k)).reduce((o, k) => Object.assign(o, { [k]: g[k] }), {}),
				options: (Array.isArray(g.options) ? g.options : []).reduce((list, option) => {
					if (option && typeof option === 'object') {
						Object.keys(option).forEach((label) => list.push({ key: uid('c'), label, value: option[label] == null ? '' : String(option[label]), edited: false }));
					}
					return list;
				}, []),
			}));
			this.render();
			this.bind();
		},

		write() {
			this.dirty = true;
			const out = this.groups.map((g) => Object.assign({
				id: g.id,
				heading: g.heading,
				cost: g.cost,
				options: g.options.filter((o) => (o.edited ? o.label.trim() !== '' : true)).map((o) => ({ [o.edited ? o.label.replace(/"/g, '”') : o.label]: o.value })),
			}, g.extra));
			this.$field.val(JSON.stringify(out));
			scheduleCheck();
		},

		find(key) { return this.groups.find((g) => g.key === key); },

		render() {
			const parts = ['<div class="sso-pe-intro"><p>Prices and colors used by the online channel letter builder. Changes here affect the builder price and the cart.</p></div>'];
			if (!this.groups.length) parts.push('<p class="sso-empty">No channel letter data. The builder can\'t price this product until a "cost-per-inch" table is added.</p>');
			parts.push('<div class="sso-cl-list">' + this.groups.map((g, i) => this.groupHtml(g, i)).join('') + '</div>');
			const newId = uid('newgroup');
			parts.push('<div class="sso-add-attr"><label for="' + newId + '">New group id</label><div class="sso-inline"><input type="text" id="' + newId + '" class="sso-new-group-id" list="sso-cl-ids" placeholder="color-3mt" autocomplete="off"><datalist id="sso-cl-ids"><option value="cost-per-inch"><option value="color-"><option value="trimcap-color"><option value="trimcap-size"><option value="return-color"><option value="return-size"></datalist><button type="button" class="button sso-add-group-btn">Add group</button></div><p class="sso-help sso-new-group-msg" aria-live="polite">Use "cost-per-inch" for the price table and "color-…" for face color groups.</p></div>');
			this.$root.html(parts.join(''));
			if ($.fn.sortable) {
				const self = this;
				this.$root.find('.sso-cl-list').sortable({
					handle: '.sso-card-head .sso-drag', items: '> .sso-attr', axis: 'y',
					update() {
						const order = $(this).children('.sso-attr').map((i, el) => el.getAttribute('data-key')).get();
						self.groups.sort((x, y) => order.indexOf(x.key) - order.indexOf(y.key));
						self.write(); self.render();
					},
				});
				this.$root.find('.sso-opt-list').sortable({
					handle: '.sso-drag', items: '> .sso-opt', axis: 'y',
					update() {
						const g = self.find($(this).closest('.sso-attr').data('key'));
						const order = $(this).children('.sso-opt').map((i, el) => el.getAttribute('data-key')).get();
						g.options.sort((x, y) => order.indexOf(x.key) - order.indexOf(y.key));
						self.write(); self.render();
					},
				});
			}
			this.refreshIssues();
		},

		groupHtml(g, index) {
			const info = clInfo(g.id);
			const headingId = uid('gh');
			const costId = uid('gc');
			const idId = uid('gid');
			const confirming = this.confirming === g.key;
			const rows = g.options.map((o) => {
				const lid = uid('cl');
				const vid = uid('cv');
				const rowConfirm = this.confirming === o.key;
				return '<li class="sso-opt sso-cl-opt" data-key="' + o.key + '">' +
					'<span class="sso-drag dashicons dashicons-menu" tabindex="0" role="button" aria-label="Reorder ' + esc(o.label) + ' (Alt + arrow keys)"></span>' +
					'<span><label class="screen-reader-text" for="' + lid + '">' + esc(info.label) + '</label><input type="text" id="' + lid + '" data-field="label" value="' + esc(o.label) + '" autocomplete="off"></span>' +
					'<span class="sso-amount">' + swatch(o.value) + '<label class="screen-reader-text" for="' + vid + '">' + esc(info.value) + '</label><input type="text" id="' + vid + '" data-field="value" value="' + esc(o.value) + '" autocomplete="off"></span>' +
					'<span class="sso-opt-actions">' + (rowConfirm
						? '<span class="sso-confirm">Remove? <button type="button" class="button sso-danger" data-act="row-remove-yes">Yes</button> <button type="button" class="button" data-act="cancel-confirm">No</button></span>'
						: '<button type="button" class="button sso-icon sso-danger-link" data-act="row-remove" aria-label="Remove ' + esc(o.label || 'row') + '"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>') + '</span>' +
					'<span class="sso-row-issue" aria-live="polite"></span></li>';
			}).join('');
			return '<section class="sso-card sso-attr" data-key="' + g.key + '">' +
				'<header class="sso-card-head"><span class="sso-drag dashicons dashicons-move" title="Drag to reorder" aria-hidden="true"></span>' +
				'<button type="button" class="button-link sso-collapse" data-act="toggle" aria-expanded="' + g.open + '"><span class="dashicons dashicons-arrow-' + (g.open ? 'down' : 'right') + '-alt2" aria-hidden="true"></span><span class="sso-attr-title">' + esc(g.heading || titleFromId(g.id)) + '</span></button>' +
				(g.isNew ? '<label class="screen-reader-text" for="' + idId + '">Group id</label><input type="text" id="' + idId + '" class="sso-attr-name" data-field="id" value="' + esc(g.id) + '" autocomplete="off">' : '<span class="sso-id-chip"><span class="dashicons dashicons-lock" aria-hidden="true"></span> id: ' + esc(g.id) + '</span>') +
				'<span class="sso-count">' + g.options.length + ' rows</span>' +
				'<span class="sso-actions">' +
				'<button type="button" class="button sso-icon" data-act="g-up" aria-label="Move up"' + (index === 0 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>' +
				'<button type="button" class="button sso-icon" data-act="g-down" aria-label="Move down"' + (index === this.groups.length - 1 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>' +
				(confirming
					? '<span class="sso-confirm">Remove this group and its ' + g.options.length + ' rows? <button type="button" class="button sso-danger" data-act="g-remove-yes">Remove</button> <button type="button" class="button" data-act="cancel-confirm">Keep</button></span>'
					: '<button type="button" class="button sso-danger-link" data-act="g-remove">Remove</button>') +
				'</span></header>' +
				'<div class="sso-card-body"' + (g.open ? '' : ' hidden') + '>' +
				(info.help ? '<p class="sso-help">' + esc(info.help) + '</p>' : '') +
				'<div class="sso-grid2"><div><label for="' + headingId + '">Heading shown in the builder</label><input type="text" id="' + headingId + '" data-field="heading" value="' + esc(g.heading) + '" autocomplete="off"></div>' +
				'<div><label for="' + costId + '">' + esc(info.cost || 'Cost') + '</label><input type="text" id="' + costId + '" data-field="cost" value="' + esc(g.cost) + '" inputmode="decimal" autocomplete="off"></div></div>' +
				'<div class="sso-opt-head sso-cl-head" aria-hidden="true"><span></span><span>' + esc(info.label) + '</span><span>' + esc(info.value) + '</span><span></span></div>' +
				'<ol class="sso-opt-list">' + rows + '</ol>' +
				'<div class="sso-attr-foot"><button type="button" class="button" data-act="row-add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> Add row</button></div>' +
				'</div><div class="sso-issues" aria-live="polite"></div></section>';
		},

		bind() {
			const self = this;
			const $root = this.$root;
			$root.on('click', '.sso-add-group-btn', () => {
				const $input = $root.find('.sso-new-group-id');
				const id = slugify($input.val());
				const $msg = $root.find('.sso-new-group-msg');
				if (!id || id === 'color') {
					$msg.addClass('sso-error-text').text('Enter a group id, like "color-3mt" or "cost-per-inch".');
					$input.trigger('focus');
					return;
				}
				if (self.groups.some((g) => g.id === id)) {
					$msg.addClass('sso-error-text').text('There is already a group with the id "' + id + '".');
					$input.trigger('focus');
					return;
				}
				const g = { key: uid('g'), id, heading: CL_KNOWN[id] && CL_KNOWN[id].heading || titleFromId(id), cost: '0', isNew: true, open: true, extra: {}, options: [{ key: uid('c'), label: '', value: '', edited: true }] };
				self.groups.push(g);
				self.write();
				self.render();
				$root.find('.sso-attr[data-key="' + g.key + '"] .sso-opt input').first().trigger('focus');
			});
			$root.on('keydown', '.sso-new-group-id', (e) => {
				if (e.key === 'Enter') { e.preventDefault(); $root.find('.sso-add-group-btn').trigger('click'); }
			});
			$root.on('click', '[data-act]', function (e) {
				const act = this.getAttribute('data-act');
				if (this.getAttribute('aria-disabled') === 'true') return;
				const g = self.find($(this).closest('.sso-attr').data('key'));
				const $row = $(this).closest('.sso-opt');
				const row = g && $row.length ? g.options.find((o) => o.key === $row.data('key')) : null;
				const i = g ? self.groups.indexOf(g) : -1;
				let focus = null;
				let changed = true;
				switch (act) {
					case 'toggle':
						g.open = !g.open;
						changed = false;
						focus = '.sso-attr[data-key="' + g.key + '"] .sso-collapse';
						break;
					case 'g-up':
					case 'g-down': {
						const j = act === 'g-up' ? i - 1 : i + 1;
						if (j < 0 || j >= self.groups.length) return;
						self.groups.splice(j, 0, self.groups.splice(i, 1)[0]);
						focus = '.sso-attr[data-key="' + g.key + '"] [data-act="' + act + '"]';
						break;
					}
					case 'g-remove':
					case 'row-remove':
						self.confirming = act === 'g-remove' ? g.key : row.key;
						self.render();
						$root.find('[data-act="' + act + '-yes"]').trigger('focus');
						return;
					case 'g-remove-yes':
						self.confirming = null;
						self.groups.splice(i, 1);
						focus = '.sso-new-group-id';
						break;
					case 'row-remove-yes':
						self.confirming = null;
						g.options.splice(g.options.indexOf(row), 1);
						focus = '.sso-attr[data-key="' + g.key + '"] [data-act="row-add"]';
						break;
					case 'cancel-confirm':
						self.confirming = null;
						changed = false;
						break;
					case 'row-add': {
						const o = { key: uid('c'), label: '', value: '', edited: true };
						g.options.push(o);
						focus = '.sso-opt[data-key="' + o.key + '"] input[data-field="label"]';
						break;
					}
					default:
						return;
				}
				e.preventDefault();
				if (changed) self.write();
				self.render();
				if (focus) $root.find(focus).first().trigger('focus');
			});
			$root.on('input change', '.sso-attr [data-field]', function () {
				const g = self.find($(this).closest('.sso-attr').data('key'));
				const field = this.getAttribute('data-field');
				const $row = $(this).closest('.sso-opt');
				if ($row.length) {
					const row = g.options.find((o) => o.key === $row.data('key'));
					row[field] = this.value;
					row.edited = true;
					if (field === 'value') $(this).siblings('.sso-swatch').remove().end().before(swatch(this.value));
				} else {
					g[field] = field === 'id' ? slugify(this.value) : this.value;
					if (field === 'heading') $(this).closest('.sso-attr').find('.sso-collapse .sso-attr-title').text(this.value || titleFromId(g.id));
				}
				self.write();
				self.refreshIssues();
			});
			$root.on('keydown', '.sso-opt', function (e) {
				if (!e.altKey || (e.key !== 'ArrowUp' && e.key !== 'ArrowDown')) return;
				const g = self.find($(this).closest('.sso-attr').data('key'));
				const row = g.options.find((o) => o.key === $(this).data('key'));
				const i = g.options.indexOf(row);
				const j = e.key === 'ArrowUp' ? i - 1 : i + 1;
				if (j < 0 || j >= g.options.length) return;
				e.preventDefault();
				g.options.splice(j, 0, g.options.splice(i, 1)[0]);
				self.write();
				self.render();
				$root.find('.sso-opt[data-key="' + row.key + '"] .sso-drag').trigger('focus');
			});
		},

		costTable() {
			const table = {};
			this.groups.filter((g) => g.id === 'cost-per-inch').forEach((g) => g.options.forEach((o) => { table[parseInt(o.label, 10) || 0] = phpFloat(o.value); }));
			return table;
		},

		issues() {
			const list = [];
			const ids = {};
			this.groups.forEach((g) => {
				const where = 'Channel letters › ' + (g.heading || g.id || 'New group');
				const at = { tab: 'cl', key: g.key };
				if (!g.id) list.push(Object.assign({ level: 'error', msg: where + ': enter a group id.' }, at));
				else if (ids[g.id]) list.push(Object.assign({ level: g.isNew ? 'error' : 'warning', msg: where + ': the id "' + g.id + '" is used twice.' }, at));
				ids[g.id] = true;
				if (g.cost !== '' && !isNum(g.cost)) list.push(Object.assign({ level: g.isNew || this.dirty ? 'error' : 'warning', msg: where + ': the cost must be a number, like 1.60.' }, at));
				g.options.forEach((o, n) => {
					const row = where + ', row ' + (n + 1);
					const oat = { tab: 'cl', key: g.key, opt: o.key };
					const level = o.edited ? 'error' : 'warning';
					if (!o.label.trim()) list.push(Object.assign({ level, msg: row + ': enter a name or remove the row.', row: 'Enter a name or remove this row.' }, oat));
					if (g.id === 'cost-per-inch') {
						if (!/^\s*\d+/.test(o.label)) list.push(Object.assign({ level, msg: row + ': the height must start with a number of inches, like "12 Inch".', row: 'Start with the height in inches, like "12 Inch".' }, oat));
						if (!isNum(o.value)) list.push(Object.assign({ level, msg: row + ': the price must be a number, like 222.50.', row: 'The price must be a number.' }, oat));
						else if (num(o.value) <= 0) list.push(Object.assign({ level: 'warning', msg: row + ': the price is ' + o.value + '.', row: 'Price is zero or negative.' }, oat));
					}
				});
				if (g.id === 'cost-per-inch') {
					const have = Object.keys(this.costTable()).map(Number);
					const missing = [];
					for (let h = 8; h <= 45; h++) if (!have.includes(h)) missing.push(h);
					if (missing.length) list.push(Object.assign({ level: 'warning', msg: where + ': no price for ' + missing.join(', ') + ' inch letters, so the builder can\'t price those sizes.' }, at));
				}
			});
			if (Product.isChannelLetter() && !this.groups.some((g) => g.id === 'cost-per-inch')) {
				list.push({ level: 'warning', tab: 'cl', msg: 'Channel letters: there is no "cost-per-inch" price table, so the builder shows "call us for a quote".' });
			}
			return list;
		},

		refreshIssues() {
			const issues = this.issues();
			this.$root.find('.sso-opt').removeClass('sso-has-error sso-has-warning').find('.sso-row-issue').text('');
			this.$root.find('.sso-attr > .sso-issues').empty();
			issues.forEach((issue) => {
				if (issue.opt) {
					const $row = this.$root.find('.sso-opt[data-key="' + issue.opt + '"]');
					$row.addClass(issue.level === 'error' ? 'sso-has-error' : 'sso-has-warning');
					const $msg = $row.find('.sso-row-issue');
					$msg.text(($msg.text() ? $msg.text() + ' ' : '') + issue.row);
					const $card = $row.closest('.sso-attr');
					const g = this.find($card.data('key'));
					if (g && !g.open) { g.open = true; $card.find('> .sso-card-body').prop('hidden', false); }
				} else if (issue.key) {
					this.$root.find('.sso-attr[data-key="' + issue.key + '"] > .sso-issues').append($('<p>').addClass('sso-' + issue.level + '-text').text(issue.msg));
				}
			});
			scheduleCheck();
		},

		onSaved() {
			this.groups.forEach((g) => { g.isNew = false; });
			this.dirty = false;
			this.render();
		},
	};

	/* ==================================================== PRODUCT GROUP LINKS */

	const NULL_GROUP = '[{"slug":"null","title":"null"}]';

	const GroupEditor = {
		$field: null,
		$root: null,
		rows: [],
		readable: true,
		dirty: false,

		init() {
			this.$field = $('#_product_group_data');
			if (!this.$field.length) return;
			const $row = this.$field.closest('.cmb-row');
			this.$root = $('<div id="sso-group-editor" class="sso-pe-editor"></div>');
			$row.find('.cmb-td').prepend(this.$root);
			this.$field.wrap('<details class="sso-raw-json"></details>').before('<summary>Raw JSON (advanced)</summary>');
			this.load(this.$field.val());
			this.render();
			this.bind();
		},

		load(raw) {
			let data;
			try { data = JSON.parse(raw); } catch (e) { data = null; }
			this.readable = Array.isArray(data);
			if (!this.readable) { this.rows = []; return; }
			this.rows = data.filter((r) => r && typeof r === 'object').map((r) => ({ key: uid('r'), slug: r.slug == null ? '' : String(r.slug), title: r.title == null ? '' : String(r.title) }));
			if (this.rows.length && this.rows[0].slug === 'null') this.rows = [];
		},

		write() {
			this.dirty = true;
			const rows = this.rows.map((r) => ({ slug: r.slug, title: r.title }));
			this.$field.val(rows.length ? JSON.stringify(rows) : NULL_GROUP);
			scheduleCheck();
		},

		render() {
			const products = D.products || [];
			const current = (products.find((p) => p.slug === (document.getElementById('post_name') || {}).value) || {}).slug;
			const parts = ['<p class="sso-help">Buttons on the product page that switch between related versions, like "Graphic Only" and "Graphic + Frame". Leave the list empty for no buttons.</p>'];
			if (!this.readable) parts.push('<p class="sso-error-text">The stored group links can\'t be read. Fix the raw JSON below, or add rows here to replace it.</p>');
			parts.push('<ol class="sso-group-list">' + this.rows.map((r, i) => {
				const tid = uid('gt');
				const sid = uid('gs');
				const known = !r.slug || products.some((p) => p.slug === r.slug);
				return '<li class="sso-group-row" data-key="' + r.key + '">' +
					'<span><label for="' + tid + '">Button text</label><input type="text" id="' + tid + '" data-field="title" value="' + esc(r.title) + '" placeholder="Graphic Only" autocomplete="off"></span>' +
					'<span><label for="' + sid + '">Links to</label><select id="' + sid + '" data-field="slug">' +
					'<option value=""' + (r.slug === '' ? ' selected' : '') + '>This product</option>' +
					(known ? '' : '<option value="' + esc(r.slug) + '" selected>' + esc(r.slug) + ' (not found)</option>') +
					products.map((p) => '<option value="' + esc(p.slug) + '"' + (p.slug === r.slug ? ' selected' : '') + '>' + esc(p.title) + (p.status !== 'publish' ? ' (' + esc(p.status) + ')' : '') + (p.slug === current ? ' (this product)' : '') + '</option>').join('') +
					'</select></span>' +
					'<span class="sso-opt-actions">' +
					'<button type="button" class="button sso-icon" data-act="up" aria-label="Move up"' + (i === 0 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>' +
					'<button type="button" class="button sso-icon" data-act="down" aria-label="Move down"' + (i === this.rows.length - 1 ? ' aria-disabled="true"' : '') + '><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>' +
					(this.confirming === r.key
						? '<span class="sso-confirm">Remove? <button type="button" class="button sso-danger" data-act="remove-yes">Yes</button> <button type="button" class="button" data-act="cancel-confirm">No</button></span>'
						: '<button type="button" class="button sso-icon sso-danger-link" data-act="remove" aria-label="Remove ' + esc(r.title || 'link') + '"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>') +
					'</span>' + (known ? '' : '<span class="sso-row-issue sso-warning-text">No product with this address was found.</span>') + '</li>';
			}).join('') + '</ol>');
			parts.push('<button type="button" class="button" data-act="add"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> Add link button</button>');
			this.$root.html(parts.join(''));
		},

		bind() {
			const self = this;
			this.$root.on('click', '[data-act]', function () {
				if (this.getAttribute('aria-disabled') === 'true') return;
				const act = this.getAttribute('data-act');
				const key = $(this).closest('.sso-group-row').data('key');
				const i = self.rows.findIndex((r) => r.key === key);
				let focus = null;
				if (act === 'add') {
					const r = { key: uid('r'), slug: '', title: '' };
					self.rows.push(r);
					focus = '.sso-group-row[data-key="' + r.key + '"] input';
				} else if (act === 'remove') {
					self.confirming = key;
					self.render();
					self.$root.find('[data-act="remove-yes"]').trigger('focus');
					return;
				} else if (act === 'remove-yes') {
					self.confirming = null;
					self.rows.splice(i, 1);
					focus = '[data-act="add"]';
				} else if (act === 'cancel-confirm') {
					self.confirming = null;
					self.render();
					return;
				} else {
					const j = act === 'up' ? i - 1 : i + 1;
					if (j < 0 || j >= self.rows.length) return;
					self.rows.splice(j, 0, self.rows.splice(i, 1)[0]);
					focus = '.sso-group-row[data-key="' + key + '"] [data-act="' + act + '"]';
				}
				self.write();
				self.render();
				if (focus) self.$root.find(focus).first().trigger('focus');
			});
			this.$root.on('input change', '[data-field]', function () {
				const r = self.rows.find((x) => x.key === $(this).closest('.sso-group-row').data('key'));
				r[this.getAttribute('data-field')] = this.value;
				self.readable = true;
				self.write();
			});
			this.$field.on('input', function () {
				self.dirty = true;
				self.load(this.value);
				self.render();
				scheduleCheck();
			});
		},

		issues() {
			const list = [];
			if (!this.$field || !this.$field.length) return list;
			let data;
			try { data = JSON.parse(this.$field.val()); } catch (e) { data = null; }
			if (!Array.isArray(data) || !data.length) {
				list.push({ level: this.dirty ? 'error' : 'warning', tab: 'settings', field: '_product_group_data', msg: 'Product group links: the raw JSON is not valid, so the product page can\'t read it.' });
			}
			this.rows.forEach((r, n) => {
				if (!r.title.trim()) list.push({ level: 'error', tab: 'settings', field: '_product_group_data', msg: 'Product group links, row ' + (n + 1) + ': enter the button text.' });
			});
			return list;
		},

		onSaved() { this.dirty = false; },
	};

	/* =============================================================== PRODUCT */

	const Product = {
		// Terms ticked right now (block editor store, classic checklist, or the saved terms).
		termIds() {
			if (isBlockEditor()) {
				const ids = wp.data.select('core/editor').getEditedPostAttribute('product_category');
				if (Array.isArray(ids)) return ids.map(Number);
			}
			const $checked = $('#product_categorychecklist input:checked, #product_category-all input:checked');
			if ($('#product_categorychecklist').length) return $checked.map((i, el) => Number(el.value)).get();
			return (D.postTerms || []).map(Number);
		},
		// inc/pricing.php treats a product as channel letters when its first category
		// (WordPress sorts them by name) is "channel-letters".
		primaryTerm() {
			const ids = this.termIds();
			const terms = (D.terms || []).filter((t) => ids.includes(t.id)).sort((a, b) => a.name.toLowerCase().localeCompare(b.name.toLowerCase()));
			return terms[0] || null;
		},
		isChannelLetter() {
			const term = this.primaryTerm();
			return !!term && term.slug === 'channel-letters';
		},
		hasCalculator() {
			return !(document.getElementById('_hide_calculator') || {}).checked;
		},
		typeLabel() {
			if (this.isChannelLetter()) return 'Channel letters';
			const attrs = AttrEditor.attrs;
			const sized = attrs.some((a) => /size-width-inch/.test(a.cssClass)) && attrs.some((a) => /size-height-inch/.test(a.cssClass));
			if (sized) return 'Sized by width and height dropdowns';
			if (this.hasCalculator()) return 'Priced per sq ft (size calculator)';
			return attrs.length ? 'Fixed price with options' : 'Fixed price';
		},
	};

	/* =============================================================== SECTIONS */

	const SECTIONS = [
		{ id: 'pricing', label: 'Pricing and size', fields: ['_hide_calculator', '_size_presets', '_price_per_sqft', '_discount_percent', '_min_order_price', '_min_order_per_side', '_promo_percent', '_promo_start', '_promo_end', '_min_sqft', '_min_height', '_max_height', '_min_width', '_max_width', '_max_size', '_starting_at_text', '_starting_at_options'] },
		{ id: 'options', label: 'Options', boxes: ['product_attr'] },
		{ id: 'cl', label: 'Channel letters', boxes: ['product_cl_data', 'product_extra_options'], fields: ['_trimcap_color', '_return_color', '_info_content_face_text', '_info_content_face_image', '_info_content_return_text', '_info_content_return_image', '_info_content_trimcap_text', '_info_content_trimcap_image'] },
		{ id: 'descriptions', label: 'Descriptions and FAQ', boxes: ['product_additional_info'], fields: ['_product_short_desc', '_product_list_desc', '_product_review_text'] },
		{ id: 'images', label: 'Images', fields: ['_product_gallery'], boxes: ['wholesale-product-image-alt', 'product-image-alt', 'postimagediv'] },
		{ id: 'settings', label: 'Visibility and settings', fields: ['_show_in_list', '_stock_status', '_backorder_date', '_has_upload_artwork', '_product_turnaround', '_order_by_index', '_product_group_data'] },
		{ id: 'seo', label: 'SEO', boxes: ['wholesale-seo'] },
		{ id: 'all', label: 'All fields' },
	];

	// Fields that only matter in some cases. They stay in the form either way.
	const CONDITIONS = {
		_min_height: () => Product.hasCalculator() && !Product.isChannelLetter(),
		_max_height: () => Product.hasCalculator() && !Product.isChannelLetter(),
		_min_width: () => Product.hasCalculator() && !Product.isChannelLetter(),
		_max_width: () => Product.hasCalculator() && !Product.isChannelLetter(),
		_standard_ps_cost: () => fieldVal('_is_ps_option') === 'on',
		_backorder_date: () => fieldVal('_stock_status') === 'backorder',
		_backlit_cost: () => fieldVal('_is_lit_option') === 'on',
		_eight_ft_cable_cost: () => fieldVal('_is_cable_option') === 'on',
	};
	const CONDITION_REASON = {
		_min_height: 'the size calculator is off (or this is a channel letter product)',
		_standard_ps_cost: '"Offer a power supply" is off',
		_backlit_cost: '"Offer back lighting" is off',
		_eight_ft_cable_cost: '"Offer an 8 ft cable" is off',
		_backorder_date: 'Stock status is not Backorder',
	};

	const Sections = {
		current: 'pricing',
		showHidden: false,
		$bar: null,

		init() {
			const $first = $('#product_details, #product_attr').first();
			if (!$first.length) return false;
			const $container = $first.closest('.meta-box-sortables');
			const tabs = SECTIONS.map((s) => '<button type="button" role="tab" class="sso-tab" id="sso-tab-' + s.id + '" data-tab="' + s.id + '" aria-controls="sso-panel" aria-selected="false" tabindex="-1">' + esc(s.label) + '<span class="sso-tab-badge" hidden></span></button>').join('');
			this.$bar = $('<div class="sso-pe-bar" id="sso-pe-bar">' +
				'<div class="sso-pe-top"><div class="sso-pe-type"><span class="sso-type-label"></span><span class="sso-type-cat"></span></div>' +
				'<div class="sso-pe-status" role="status" aria-live="polite"><span class="sso-status-dot" aria-hidden="true"></span><span class="sso-status-text">No changes</span></div>' +
				'<button type="button" class="button sso-check-btn" aria-expanded="false" aria-controls="sso-check-panel">Check fields</button></div>' +
				'<div class="sso-check-panel" id="sso-check-panel" hidden></div>' +
				'<div class="sso-tabs" role="tablist" aria-label="Product sections">' + tabs + '</div></div>');
			// Only the bar stays pinned; the section intro (price example, notes) scrolls with the fields.
			$container.before(this.$bar, '<div class="sso-panel-intro" id="sso-panel" role="tabpanel"></div>');

			// Order rows inside the details box by section (CSS order; no DOM moves, so editors keep working).
			let order = 0;
			SECTIONS.forEach((s) => (s.fields || []).forEach((key) => {
				fieldRow(key).attr('data-sso-section', s.id).css('order', ++order);
			}));
			$('#product_details .cmb2-metabox').addClass('sso-flex');

			const saved = store.get('ssoProductEditorTab');
			if (saved && SECTIONS.some((s) => s.id === saved)) this.current = saved;

			const self = this;
			this.$bar.on('click', '.sso-tab', function () { self.show(this.getAttribute('data-tab'), true); });
			this.$bar.on('keydown', '.sso-tab', function (e) {
				const $tabs = self.$bar.find('.sso-tab');
				const i = $tabs.index(this);
				let j = null;
				if (e.key === 'ArrowRight') j = (i + 1) % $tabs.length;
				if (e.key === 'ArrowLeft') j = (i - 1 + $tabs.length) % $tabs.length;
				if (e.key === 'Home') j = 0;
				if (e.key === 'End') j = $tabs.length - 1;
				if (j === null) return;
				e.preventDefault();
				$tabs.eq(j).trigger('focus').trigger('click');
			});
			$(document).on('click', '#sso-panel .sso-show-hidden', () => { this.showHidden = !this.showHidden; this.apply(); });
			return true;
		},

		show(id, focusTab) {
			this.current = id;
			store.set('ssoProductEditorTab', id);
			this.apply();
			if (focusTab) this.$bar.find('#sso-tab-' + id).trigger('focus');
		},

		boxIds() {
			return SECTIONS.reduce((list, s) => list.concat(s.boxes || []), ['product_details']);
		},

		apply() {
			const id = this.current;
			const section = SECTIONS.find((s) => s.id === id);
			const all = id === 'all';
			const isCl = Product.isChannelLetter();
			this.$bar.find('.sso-tab').each(function () {
				const on = this.getAttribute('data-tab') === id;
				this.setAttribute('aria-selected', String(on));
				this.tabIndex = on ? 0 : -1;
				this.classList.toggle('sso-tab-muted', this.getAttribute('data-tab') === 'cl' && !isCl);
			});
			$('#sso-panel').attr('aria-labelledby', 'sso-tab-' + id);

			// Boxes: our known boxes show only in their section; other boxes always show.
			const known = this.boxIds();
			known.forEach((boxId) => {
				const $box = $('#' + boxId);
				if (!$box.length) return;
				const inSection = all || (section.boxes || []).includes(boxId) || (boxId === 'product_details' && (section.fields || []).length > 0);
				$box.toggleClass('sso-pe-off', !inSection);
				if (inSection && !all) $box.removeClass('closed');
			});

			// Rows in the details box, plus conditional rows anywhere.
			let hiddenCount = 0;
			const reasons = [];
			$('#product_details .cmb-row').each(function () {
				const sec = this.getAttribute('data-sso-section');
				this.classList.toggle('sso-pe-off', !all && sec !== id);
			});
			Object.keys(CONDITIONS).forEach((key) => {
				const $row = fieldRow(key);
				if (!$row.length) return;
				const active = CONDITIONS[key]();
				const inView = all || $row.attr('data-sso-section') === id || (section.boxes || []).includes($row.closest('.postbox').attr('id'));
				$row.toggleClass('sso-pe-cond-off', !active && !this.showHidden).toggleClass('sso-pe-cond-shown', !active && this.showHidden);
				if (!active && inView) {
					hiddenCount++;
					const reason = CONDITION_REASON[key] || CONDITION_REASON._min_height;
					if (!reasons.includes(reason)) reasons.push(reason);
				}
			});

			// Retitle the shared details box to the section it is showing.
			$('#product_details .hndle, #product_details .postbox-header h2').first().text(all ? 'Product details' : section.label);

			const intro = [];
			if (id === 'cl' && !isCl) {
				intro.push('<div class="notice notice-info inline"><p>This product isn\'t in the Channel Letters category, so these settings aren\'t used on the site. Any saved values are kept.</p></div>');
			}
			if (id === 'pricing') intro.push(Preview.html());
			if (hiddenCount) {
				intro.push('<p class="sso-hidden-note">' + hiddenCount + ' field' + (hiddenCount === 1 ? ' is' : 's are') + ' hidden because ' + esc(reasons.join(' and ')) + '. ' + (hiddenCount === 1 ? 'Its saved value is' : 'Their saved values are') + ' kept. <button type="button" class="button-link sso-show-hidden">' + (this.showHidden ? 'Hide them' : 'Show them') + '</button></p>');
			}
			if (id === 'pricing' && !Product.hasCalculator() && !isCl) {
				intro.push('<p class="sso-help">The size calculator is off, so the base price is minimum sq ft × price per sq ft (usually 1 × the item price).</p>');
			}
			$('#sso-panel').html(intro.join(''));
			if (id === 'pricing') Preview.bind();
			Checks.paintBadges();
		},

		refreshType() {
			if (!this.$bar) return;
			const term = Product.primaryTerm();
			this.$bar.find('.sso-type-label').text(Product.typeLabel());
			this.$bar.find('.sso-type-cat').text(term ? 'Category: ' + $('<span>').html(term.name).text() : 'No category picked');
		},
	};

	/* ============================================================= PRICE PREVIEW */

	// A copy of wholesale_price_quote() / wholesale_cl_design_quote() in inc/pricing.php,
	// run on the values on screen (including unsaved ones).
	const Preview = {
		input: { width: '24', height: '36', qty: '1', letters: 'OPEN', clHeight: '12', face: '', ps: false, lit: false, cable: false, picks: {} },

		attrs() {
			return AttrEditor.attrs.map((a) => ({
				name: a.name,
				cssClass: a.cssClass,
				options: a.options.filter((o) => (o.edited ? o.label.trim() !== '' : true)).map((o) => ({ label: o.edited ? o.label : o.origLabel, value: o.edited ? o.value : o.origValue })),
			}));
		},

		chosen(attr) {
			const pick = this.input.picks[attr.name];
			return attr.options.find((o) => o.label === pick) || attr.options[0] || null;
		},

		parse(value) {
			const parts = String(value).trim().split('/');
			if (!isNum(parts[0])) return { amount: 0, type: 'fixed' };
			const type = parts.length > 1 ? parts[1].trim().toLowerCase() : '';
			return { amount: parseFloat(parts[0].trim()), type: ['%', 'sqft', 'lft'].includes(type) ? type : 'fixed' };
		},

		quote() {
			const isCl = Product.isChannelLetter();
			const attrs = this.attrs();
			const lines = [];
			const minSqft = Math.max(0, phpFloat(fieldVal('_min_sqft')));
			const ppsf = Math.max(0, phpFloat(fieldVal('_price_per_sqft')));
			let sqft = minSqft;
			let letterCount = 0;
			let letterHeight = 0;
			let isSqftCalc = false;
			let running;

			if (isCl) {
				letterCount = (this.input.letters.match(/\S/gu) || []).length;
				const heightAttr = attrs.find((a) => a.name === 'height');
				const option = heightAttr ? this.chosen(heightAttr) : null;
				if (!letterCount) return { error: 'Enter some letters to see a price.' };
				if (!option) return { error: 'This product has no "height" option, so the product page can\'t price letters.' };
				const perLetter = phpFloat(option.value);
				letterHeight = parseInt(option.label, 10) || 0;
				running = letterCount * perLetter;
				lines.push([letterCount + ' letters at ' + letterHeight + '"', running]);
			} else {
				const w = attrs.find((a) => /size-width-inch/.test(a.cssClass));
				const h = attrs.find((a) => /size-height-inch/.test(a.cssClass));
				if (w && h && this.chosen(w) && this.chosen(h)) {
					sqft = (phpFloat(this.chosen(w).value) / 12) * (phpFloat(this.chosen(h).value) / 12);
				} else if (Product.hasCalculator()) {
					const hf = num(this.input.height) / 12;
					const wf = num(this.input.width) / 12;
					if (hf <= 0 || wf <= 0) return { error: 'Enter a width and height.' };
					isSqftCalc = true;
					sqft = hf * wf;
				}
				running = sqft * ppsf;
				lines.push([Product.hasCalculator() || (w && h) ? sqft.toFixed(2) + ' sq ft × ' + money(ppsf) : 'Base price (' + minSqft + ' × ' + money(ppsf) + ')', running]);
			}

			attrs.forEach((attr) => {
				if (attr.options.length < 2 || /avoid-price/.test(attr.cssClass) || (attr.name === 'height' && isCl)) return;
				// The calculator's typed size replaces a preset "size" dropdown (wholesale_attr_is_preset_size()).
				if (isSqftCalc && String(attr.name).trim().toLowerCase() === 'size' && !/size-(width|height)-inch/.test(attr.cssClass)) return;
				const option = this.chosen(attr);
				const price = this.parse(option.value);
				let amount;
				if (price.type === '%') amount = running * price.amount / 100;
				else if (price.type === 'sqft') amount = (isSqftCalc ? sqft : Math.max(minSqft, sqft)) * price.amount;
				else if (price.type === 'lft') amount = price.amount * (letterHeight * letterCount / 12);
				else amount = price.amount;
				if (Math.round(amount * 100) !== 0) lines.push([titleFromId(attr.name) + ': ' + option.label, amount]);
				running += amount;
			});

			if (running <= 0) return { error: 'This works out to $0. Check the price per sq ft and options.', lines };
			// wholesale_product_min_order(): empty uses the site default, 0 means none. The
			// minimum is what one item costs after the everyday price adjustment.
			const minField = fieldVal('_min_order_price').trim();
			const floor = isNum(minField) ? Math.max(0, num(minField)) : (D.sqftMinPrice || 15);
			const keep = (100 - Math.min(100, Math.max(0, phpFloat(fieldVal('_discount_percent'))))) / 100;
			if (isSqftCalc && keep > 0 && floor > 0 && running * keep < floor) {
				lines.push(['Minimum order (' + money(floor) + ')', floor / keep - running]);
				running = floor / keep;
			}
			return this.finish(running, lines);
		},

		finish(running, lines) {
			const qty = Math.min(1000, Math.max(1, parseInt(this.input.qty, 10) || 1));
			const subtotal = running * qty;
			const discountPercent = Math.min(100, Math.max(0, phpFloat(fieldVal('_discount_percent'))));
			const discount = subtotal * discountPercent / 100;
			if (qty > 1) lines.push(['× ' + qty + ' (quantity)', subtotal - running]);
			if (discount) lines.push(['Discount ' + (Math.round(discountPercent * 100) / 100) + '%', -discount]);
			return { lines, total: Math.round((subtotal - discount) * 100) / 100 };
		},

		builderQuote() {
			const table = ClEditor.costTable();
			if (!Object.keys(table).length) return { error: 'No "cost-per-inch" table yet, so the builder can\'t price this product.' };
			const count = (this.input.letters.match(/\S/gu) || []).length;
			const height = num(this.input.clHeight);
			if (!count) return { error: 'Enter some letters.' };
			const rate = table[Math.floor(height)];
			if (rate === undefined) return { error: 'No price for ' + height + '" letters in the table.' };
			let faceRate = phpFloat(fieldVal('_default_color_cost'));
			let faceLabel = 'default color';
			if (this.input.face) {
				const g = ClEditor.groups.find((x) => x.key === this.input.face);
				if (g) { faceRate = phpFloat(g.cost); faceLabel = g.heading || g.id; }
			}
			const letters = rate * count + height * count * faceRate;
			const lines = [[count + ' letters at ' + height + '" × ' + money(rate), rate * count]];
			if (height * count * faceRate) lines.push(['Face color (' + faceLabel + ', ' + money(faceRate) + '/in)', height * count * faceRate]);
			const metaOr = (key, fallback) => { const v = fieldVal(key); return v && v !== '0' ? phpFloat(v) : fallback; };
			let extras = 0;
			if (this.input.ps) { const v = metaOr('_standard_ps_cost', 90); extras += v; lines.push(['Power supply', v]); }
			if (this.input.lit && fieldVal('_is_lit_option')) { const v = letters * metaOr('_backlit_cost', 100) / 100; extras += v; lines.push(['Back lit (' + metaOr('_backlit_cost', 100) + '%)', v]); }
			if (this.input.cable) { const v = metaOr('_eight_ft_cable_cost', 70); extras += v; lines.push(['8 ft cable', v]); }
			return this.finish(Math.round((letters + extras) * 100) / 100, lines);
		},

		html() {
			const isCl = Product.isChannelLetter();
			const attrs = this.attrs().filter((a) => a.options.length > 1);
			const f = (id, label, value, extra) => '<span class="sso-pv-field"><label for="sso-pv-' + id + '">' + label + '</label><input type="text" id="sso-pv-' + id + '" data-pv="' + id + '" value="' + esc(value) + '" ' + (extra || '') + '></span>';
			const parts = ['<section class="sso-preview" aria-label="Price example"><h3><span class="dashicons dashicons-calculator" aria-hidden="true"></span> Price example</h3><div class="sso-pv-inputs">'];
			if (isCl) {
				parts.push(f('letters', 'Letters', this.input.letters, 'autocomplete="off"'));
			} else if (Product.hasCalculator() && !(attrs.some((a) => /size-width-inch/.test(a.cssClass)) && attrs.some((a) => /size-height-inch/.test(a.cssClass)))) {
				parts.push(f('width', 'Width (in)', this.input.width, 'inputmode="decimal"') + f('height', 'Height (in)', this.input.height, 'inputmode="decimal"'));
			}
			parts.push(f('qty', 'Quantity', this.input.qty, 'inputmode="numeric"'));
			parts.push('</div>');
			if (attrs.length) {
				parts.push('<details class="sso-pv-picks"><summary>Choices used (' + attrs.length + ')</summary><div class="sso-pv-inputs">' + attrs.map((a) => {
					const id = uid('pick');
					const chosen = this.chosen(a);
					return '<span class="sso-pv-field"><label for="' + id + '">' + esc(titleFromId(a.name)) + '</label><select id="' + id + '" data-pick="' + esc(a.name) + '">' + a.options.map((o) => '<option' + (chosen && o.label === chosen.label ? ' selected' : '') + '>' + esc(o.label) + '</option>').join('') + '</select></span>';
				}).join('') + '</div></details>');
			}
			const result = (title, q) => '<div class="sso-pv-result"><h4>' + title + '</h4>' + (q.lines || []).map((l) => '<div class="sso-pv-line"><span>' + esc(l[0]) + '</span><span>' + (l[1] < 0 ? '−' + money(-l[1]) : money(l[1])) + '</span></div>').join('') +
				(q.error ? '<p class="sso-warning-text">' + esc(q.error) + '</p>' : '<div class="sso-pv-total"><span>Cart total</span><span>' + money(q.total) + '</span></div>') + '</div>';
			parts.push('<div class="sso-pv-results">');
			if (isCl) {
				const heights = Object.keys(ClEditor.costTable()).map(Number).sort((a, b) => a - b);
				const colorGroups = ClEditor.groups.filter((g) => /^color-/.test(g.id));
				parts.push('<div class="sso-pv-result"><div class="sso-pv-inputs">' +
					'<span class="sso-pv-field"><label for="sso-pv-clHeight">Letter height</label><select id="sso-pv-clHeight" data-pv="clHeight">' + heights.map((h) => '<option value="' + h + '"' + (String(h) === String(this.input.clHeight) ? ' selected' : '') + '>' + h + '"</option>').join('') + '</select></span>' +
					'<span class="sso-pv-field"><label for="sso-pv-face">Face color</label><select id="sso-pv-face" data-pv="face"><option value="">Default</option>' + colorGroups.map((g) => '<option value="' + g.key + '"' + (g.key === this.input.face ? ' selected' : '') + '>' + esc(g.heading || g.id) + '</option>').join('') + '</select></span>' +
					'<span class="sso-pv-check"><input type="checkbox" id="sso-pv-ps" data-pv="ps"' + (this.input.ps ? ' checked' : '') + '><label for="sso-pv-ps">Power supply</label></span>' +
					'<span class="sso-pv-check"><input type="checkbox" id="sso-pv-lit" data-pv="lit"' + (this.input.lit ? ' checked' : '') + '><label for="sso-pv-lit">Back lit</label></span>' +
					'<span class="sso-pv-check"><input type="checkbox" id="sso-pv-cable" data-pv="cable"' + (this.input.cable ? ' checked' : '') + '><label for="sso-pv-cable">8 ft cable</label></span></div></div>');
				parts.push(result('Online builder', this.builderQuote()));
				parts.push(result('Product page (letters × height price)', this.quote()));
			} else {
				parts.push(result('Product page', this.quote()));
			}
			parts.push('</div><p class="sso-help">Uses the values on this screen, including unsaved ones, with the same math as the cart. Same-day turnaround is not included.</p></section>');
			return parts.join('');
		},

		bind() {
			const self = this;
			$('#sso-panel').off('.pv').on('change.pv input.pv', '[data-pv], [data-pick]', function (e) {
				if (this.hasAttribute('data-pick')) self.input.picks[this.getAttribute('data-pick')] = this.value;
				else self.input[this.getAttribute('data-pv')] = this.type === 'checkbox' ? this.checked : this.value;
				if (e.type === 'input' && this.tagName === 'INPUT' && this.type === 'text') {
					clearTimeout(self.t);
					self.t = setTimeout(() => self.refresh(this.id), 350);
				} else {
					self.refresh(this.id);
				}
			});
		},

		refresh(focusId) {
			if (Sections.current !== 'pricing') return;
			const $panel = $('#sso-panel');
			const open = $panel.find('.sso-pv-picks').prop('open');
			const sel = focusId ? document.getElementById(focusId) : null;
			const caret = sel && sel.selectionStart;
			const $old = $panel.find('.sso-preview');
			if (!$old.length) return;
			$old.replaceWith(this.html());
			$panel.find('.sso-pv-picks').prop('open', !!open);
			if (focusId) {
				const el = document.getElementById(focusId);
				if (el) { el.focus(); if (caret != null && el.setSelectionRange) el.setSelectionRange(caret, caret); }
			}
		},
	};

	/* ================================================================= CHECKS */

	const NUMERIC = {
		_price_per_sqft: 'Price per square foot', _discount_percent: 'Discount', _min_order_price: 'Minimum order price', _size_presets: 'Size presets', _min_sqft: 'Minimum square feet',
		_min_height: 'Minimum height', _max_height: 'Maximum height', _min_width: 'Minimum width', _max_width: 'Maximum width',
		_standard_ps_cost: 'Power supply price', _backlit_cost: 'Back-lit upcharge', _eight_ft_cable_cost: '8 ft cable price',
		_default_color_cost: 'Default face color cost', _order_by_index: 'Sort position',
	};
	const SECTION_OF = {};
	SECTIONS.forEach((s) => (s.fields || []).forEach((f) => { SECTION_OF[f] = s.id; }));
	['_standard_ps_cost', '_backlit_cost', '_eight_ft_cable_cost', '_default_color_cost'].forEach((f) => { SECTION_OF[f] = 'cl'; });

	const Checks = {
		baseline: {},
		list: [],

		takeBaseline() {
			Object.keys(NUMERIC).forEach((key) => { this.baseline[key] = fieldVal(key); });
		},

		fieldIssues() {
			const list = [];
			const changed = (key) => fieldVal(key) !== this.baseline[key];
			Object.keys(NUMERIC).forEach((key) => {
				if (!document.getElementById(key)) return;
				const value = fieldVal(key).trim();
				if (value === '') return;
				const at = { tab: SECTION_OF[key] || 'all', field: key };
				if (!isNum(value)) list.push(Object.assign({ level: changed(key) ? 'error' : 'warning', msg: NUMERIC[key] + ' must be a number (now "' + value + '").' }, at));
				else if (num(value) < 0) list.push(Object.assign({ level: changed(key) ? 'error' : 'warning', msg: NUMERIC[key] + ' is negative.' }, at));
			});
			const discount = fieldVal('_discount_percent').trim();
			if (isNum(discount) && num(discount) > 100) list.push({ level: changed('_discount_percent') ? 'error' : 'warning', tab: 'pricing', field: '_discount_percent', msg: 'Discount is above 100%. The cart caps it at 100%, which makes the product free.' });
			const isCl = Product.isChannelLetter();
			if (!isCl && Product.hasCalculator()) {
				[['_min_height', '_max_height', 'height'], ['_min_width', '_max_width', 'width']].forEach(([min, max, label]) => {
					const a = fieldVal(min); const b = fieldVal(max);
					if (isNum(a) && isNum(b) && num(a) > num(b)) list.push({ level: 'warning', tab: 'pricing', field: min, msg: 'Minimum ' + label + ' (' + a + ') is more than maximum ' + label + ' (' + b + ').' });
				});
			}
			if (!isCl && document.getElementById('_price_per_sqft') && num(fieldVal('_price_per_sqft')) === 0) {
				list.push({ level: 'warning', tab: 'pricing', field: '_price_per_sqft', msg: 'Price per square foot is empty or 0, so the base price is $0.' });
			}
			if (!Product.primaryTerm()) list.push({ level: 'warning', tab: 'all', msg: 'No category is picked.' });
			return list;
		},

		run() {
			this.list = [].concat(this.fieldIssues(), AttrEditor.$root ? AttrEditor.issues() : [], ClEditor.$root ? ClEditor.issues() : [], GroupEditor.issues());
			// Channel letter issues only matter for channel letter products, unless edited.
			if (!Product.isChannelLetter() && !ClEditor.dirty) this.list = this.list.filter((i) => i.tab !== 'cl');
			this.paint();
			Gate.update();
			return this.list;
		},

		errors() { return this.list.filter((i) => i.level === 'error'); },

		paint() {
			const errors = this.errors().length;
			const warnings = this.list.length - errors;
			const $btn = $('.sso-check-btn');
			$btn.text(errors ? errors + ' problem' + (errors > 1 ? 's' : '') + (warnings ? ', ' + warnings + ' warning' + (warnings > 1 ? 's' : '') : '') : warnings ? warnings + ' warning' + (warnings > 1 ? 's' : '') : 'No problems found')
				.toggleClass('sso-btn-error', !!errors).toggleClass('sso-btn-warning', !errors && !!warnings);
			const $panel = $('#sso-check-panel');
			if (!$panel.prop('hidden')) this.paintPanel();
			this.paintBadges();
		},

		paintPanel() {
			const $panel = $('#sso-check-panel');
			if (!this.list.length) {
				$panel.html('<p class="sso-ok-text">No problems found.</p>');
				return;
			}
			const item = (issue, i) => '<li class="sso-' + issue.level + '"><span class="dashicons dashicons-' + (issue.level === 'error' ? 'dismiss' : 'warning') + '" aria-hidden="true"></span><span class="screen-reader-text">' + (issue.level === 'error' ? 'Problem: ' : 'Warning: ') + '</span><button type="button" class="button-link" data-issue="' + i + '">' + esc(issue.msg) + '</button></li>';
			const errors = this.errors().length;
			$panel.html((errors ? '<p><strong>Fix the problems before updating.</strong> Warnings don\'t stop the update.</p>' : '<p>These warnings don\'t stop the update.</p>') +
				'<ul class="sso-issue-list">' + this.list.map(item).join('') + '</ul>' +
				(errors ? '<p><button type="button" class="button sso-update-anyway">' + (Gate.allowOnce ? 'Update is allowed once' : 'Allow updating anyway') + '</button></p>' : ''));
		},

		paintBadges() {
			$('.sso-tab').each(function () {
				const tab = this.getAttribute('data-tab');
				const n = Checks.list.filter((i) => i.level === 'error' && i.tab === tab).length;
				const $b = $(this).find('.sso-tab-badge');
				$b.prop('hidden', !n).text(n ? n : '');
				$b.attr('aria-label', n ? n + ' problem' + (n > 1 ? 's' : '') : '');
			});
		},

		go(issue) {
			Sections.show(issue.tab === 'all' ? Sections.current : issue.tab);
			let $target = $();
			if (issue.opt) $target = $('.sso-opt[data-key="' + issue.opt + '"] input').first();
			else if (issue.key) $target = $('.sso-attr[data-key="' + issue.key + '"]').find('input, button').first();
			else if (issue.field) {
				Sections.showHidden = Sections.showHidden || fieldRow(issue.field).hasClass('sso-pe-cond-off');
				if (Sections.showHidden) Sections.apply();
				$target = $(document.getElementById(issue.field));
				if (issue.field === '_product_group_data') $target = $('#sso-group-editor').find('input, select, button').first();
			}
			if ($target.length) {
				$target.closest('details').prop('open', true);
				$target[0].scrollIntoView({ block: 'center' });
				$target.trigger('focus');
			}
		},

		bind() {
			$(document).on('click', '.sso-check-btn', function () {
				const $panel = $('#sso-check-panel');
				const open = $panel.prop('hidden');
				$panel.prop('hidden', !open);
				$(this).attr('aria-expanded', String(open));
				if (open) Checks.paintPanel();
			});
			$(document).on('click', '#sso-check-panel [data-issue]', function () {
				Checks.go(Checks.list[Number(this.getAttribute('data-issue'))]);
			});
			$(document).on('click', '.sso-update-anyway', () => {
				Gate.allowOnce = true;
				Gate.update();
				Checks.paintPanel();
			});
		},
	};

	/* ====================================== SAVE GATE, STATUS AND UNSAVED WARNING */

	const Gate = {
		allowOnce: false,
		locked: false,
		submitting: false,

		update() {
			const errors = Checks.errors().length;
			if (!isBlockEditor()) return;
			const shouldLock = errors > 0 && !this.allowOnce;
			const notices = wp.data.dispatch('core/notices');
			if (shouldLock && !this.locked) {
				wp.data.dispatch('core/editor').lockPostSaving('sso-product-check');
				this.locked = true;
			} else if (!shouldLock && this.locked) {
				wp.data.dispatch('core/editor').unlockPostSaving('sso-product-check');
				this.locked = false;
			}
			if (shouldLock) {
				notices.createNotice('error', 'Update is paused: ' + errors + ' product field' + (errors > 1 ? 's need' : ' needs') + ' fixing.', {
					id: 'sso-product-check', isDismissible: false,
					actions: [{ label: 'Show problems', onClick: () => Gate.reveal() }],
				});
			} else {
				notices.removeNotice('sso-product-check');
			}
		},

		reveal() {
			const $panel = $('#sso-check-panel');
			$panel.prop('hidden', false);
			$('.sso-check-btn').attr('aria-expanded', 'true');
			Checks.paintPanel();
			const first = Checks.errors()[0];
			if (first) Checks.go(first);
			else if ($panel[0]) $panel[0].scrollIntoView({ block: 'center' });
		},

		// Classic editor: stop the form when there are problems, or warnings not yet seen.
		bindClassic() {
			const $form = $('#post');
			if (!$form.length || isBlockEditor()) return;
			let warned = false;
			$form.on('submit', (e) => {
				const submitter = e.originalEvent && e.originalEvent.submitter;
				if (submitter && /save-post|post-preview/.test(submitter.id)) return; // drafts and previews
				Checks.run();
				const errors = Checks.errors().length;
				const warnings = Checks.list.length - errors;
				if ((errors && !this.allowOnce) || (warnings && !warned && !errors)) {
					e.preventDefault();
					warned = true;
					$('#publish').removeClass('disabled');
					$('#publishing-action .spinner, #submitpost .spinner').removeClass('is-active');
					this.reveal();
					$('#sso-check-panel').prepend(errors ? '' : '<p><strong>Review the warnings, then click Update again.</strong></p>');
					return;
				}
				this.submitting = true;
				Status.set('saving');
			});
		},
	};

	const Status = {
		state: 'clean',
		baseline: '',
		lastSaved: null,

		signature() {
			if (window.tinymce && tinymce.editors) {
				try { tinymce.triggerSave(); } catch (e) { /* editor not ready */ }
			}
			return $('.postbox :input[name]').not('[type=button], [type=submit], .sso-pe-editor :input').map(function () {
				if ((this.type === 'checkbox' || this.type === 'radio') && !this.checked) return null;
				return this.name + '=' + this.value;
			}).get().join('\u0001');
		},

		takeBaseline() { this.baseline = this.signature(); },
		isDirty() { return this.signature() !== this.baseline || AttrEditor.dirty || ClEditor.dirty || GroupEditor.dirty; },

		set(state) {
			this.state = state;
			const text = {
				clean: this.lastSaved ? 'Saved ' + this.lastSaved.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : 'No unsaved changes',
				dirty: 'Unsaved changes',
				saving: 'Saving…',
				failed: 'Save failed. Your changes are still on screen.',
			}[state];
			$('.sso-pe-status').attr('data-state', state).find('.sso-status-text').text(text);
		},

		refresh() {
			if (this.state === 'saving') return;
			const coreDirty = isBlockEditor() && wp.data.select('core/editor').isEditedPostDirty();
			this.set(this.isDirty() || coreDirty ? 'dirty' : (this.state === 'failed' ? 'failed' : 'clean'));
		},

		bind() {
			window.addEventListener('beforeunload', (e) => {
				if (Gate.submitting || this.state === 'saving') return;
				if (this.isDirty()) {
					e.preventDefault();
					e.returnValue = '';
				}
			});
			if (!isBlockEditor()) {
				if (/[?&]message=(1|4|6|7|8|9|10)\b/.test(window.location.search)) {
					this.lastSaved = new Date();
				}
				return;
			}
			let wasSaving = false;
			wp.data.subscribe(() => {
				const editor = wp.data.select('core/editor');
				const editPost = wp.data.select('core/edit-post');
				const saving = (editor.isSavingPost() && !editor.isAutosavingPost()) || (editPost && editPost.isSavingMetaBoxes && editPost.isSavingMetaBoxes());
				if (saving && !wasSaving) this.set('saving');
				if (!saving && wasSaving) {
					if (editor.didPostSaveRequestSucceed()) {
						this.lastSaved = new Date();
						AttrEditor.onSaved && AttrEditor.$root && AttrEditor.onSaved();
						ClEditor.$root && ClEditor.onSaved();
						GroupEditor.onSaved();
						Gate.allowOnce = false;
						Checks.takeBaseline();
						this.state = 'clean';
						// Meta boxes are posted just after the post itself; take the new baseline after that.
						setTimeout(() => { this.takeBaseline(); this.refresh(); Checks.run(); }, 50);
					} else {
						this.set('failed');
					}
				}
				wasSaving = saving;
			});
		},
	};

	/* =================================================================== BOOT */

	// As early as possible, so no editor writes back before the guard is in place.
	guardAllRichText();

	$(function () {
		if (!document.body.classList.contains('post-type-product')) return;

		guardAllRichText();
		showStoredValues();
		AttrEditor.init();
		ClEditor.init();
		GroupEditor.init();
		if (!Sections.init()) return;
		Checks.takeBaseline();
		Checks.bind();
		Gate.bindClassic();

		const refreshAll = () => {
			Sections.refreshType();
			Sections.apply();
			Checks.run();
			Status.refresh();
		};
		events.on('change', () => {
			Checks.run();
			Status.refresh();
			if (Sections.current === 'pricing') Preview.refresh();
		});

		// Fields that change what is shown.
		$(document).on('change', '#_hide_calculator, #_is_ps_option, #_is_lit_option, #_is_cable_option, #_stock_status, #product_categorychecklist input', refreshAll);
		$(document).on('input change', '.postbox :input', (e) => {
			if ($(e.target).closest('.sso-pe-editor, .sso-pe-bar').length) return;
			scheduleCheck();
		});
		const watchEditor = (ed) => ed.on('input change keyup undo redo', scheduleCheck);
		if (window.tinymce) {
			tinymce.editors && Object.values(tinymce.editors).forEach((ed) => ed && ed.on && watchEditor(ed));
			tinymce.on && tinymce.on('AddEditor', (e) => watchEditor(e.editor));
		}

		// Category changes in the block editor.
		if (isBlockEditor()) {
			let lastTerms = JSON.stringify(Product.termIds());
			wp.data.subscribe(() => {
				const now = JSON.stringify(Product.termIds());
				if (now !== lastTerms) { lastTerms = now; refreshAll(); }
				if (Status.state !== 'saving') {
					const dirty = wp.data.select('core/editor').isEditedPostDirty();
					if (dirty !== Status.lastCoreDirty) { Status.lastCoreDirty = dirty; Status.refresh(); }
				}
			});
		}

		// Ctrl+S: the block editor only saves on its own when the post content changed,
		// so also save when only product fields changed. Problems still stop it.
		if (isBlockEditor()) {
			document.addEventListener('keydown', (e) => {
				if (!(e.ctrlKey || e.metaKey) || e.shiftKey || e.altKey || String(e.key).toLowerCase() !== 's') return;
				const editor = wp.data.select('core/editor');
				if (editor.isSavingPost()) return;
				if (editor.isPostSavingLocked()) { Gate.reveal(); return; }
				if (!editor.isEditedPostDirty() && Status.isDirty()) {
					e.preventDefault();
					wp.data.dispatch('core/editor').savePost();
				}
			}, true);
		}

		Status.bind();
		Sections.refreshType();
		Sections.show(Sections.current);
		// Let CMB2 and TinyMCE finish setting up before recording what "unchanged" means.
		// (js/admin-script.js also sets some checkboxes on load, without a change event.)
		setTimeout(() => { Sections.refreshType(); Sections.apply(); Status.takeBaseline(); Checks.run(); Status.refresh(); }, 800);
	});
})(jQuery);

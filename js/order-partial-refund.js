/*
 * Order edit screen: partial refund tools (inc/order-partial-refund.php).
 *
 *  - Payment box: "Full refund" + "Partial refund" buttons for the Converge refund dialog.
 *  - "Pick what to refund" (items, quantity, shipping, + the items' share of tax) fills the
 *    partial amount in the Converge dialog and in the Wallet payment box.
 *  - "Record a refund" dialog for money returned outside the site (no money is moved).
 *
 * Nothing here sends money itself: it only fills the existing forms, which keep their own
 * review / confirm steps. Inputs have no name attributes, so nothing travels with the order's
 * Update button.
 */
(function () {
	'use strict';

	var cfg = window.wholesaleOrderRefund;
	if (!cfg) {
		return;
	}

	var money = function (value) {
		return '$' + Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	};
	var round2 = function (value) {
		return Math.round(Number(value || 0) * 100) / 100;
	};
	var parseMoney = function (raw) {
		raw = String(raw || '').trim().replace(/^\$/, '').replace(/,/g, '');
		return /^\d+(\.\d{1,2})?$/.test(raw) ? round2(parseFloat(raw)) : NaN;
	};

	// Small DOM builder: el('div', {class: 'x'}, ['text', otherNode]). Text is always set as text.
	var el = function (tag, attrs, children) {
		var node = document.createElement(tag);
		Object.keys(attrs || {}).forEach(function (key) {
			var value = attrs[key];
			if (value === null || value === undefined || value === false) {
				return;
			}
			if (key === 'text') {
				node.textContent = value;
			} else if (value === true) {
				node.setAttribute(key, '');
			} else {
				node.setAttribute(key, value);
			}
		});
		[].concat(children || []).forEach(function (child) {
			if (child === null || child === undefined || child === false) {
				return;
			}
			node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
		});
		return node;
	};
	var icon = function (name) {
		return el('span', { class: 'dashicons dashicons-' + name, 'aria-hidden': 'true' });
	};

	// Sets an amount box and lets its own script react (validation, button label).
	var setAmount = function (input, value) {
		input.value = value > 0 ? value.toFixed(2) : '';
		input.dispatchEvent(new Event('input', { bubbles: true }));
	};

	var hasPicks = (cfg.items && cfg.items.length) || cfg.shipping > 0;

	/**
	 * "Pick what to refund": items (with quantity), shipping. Tax was charged on the item
	 * subtotal only, so each item brings its share of the tax along.
	 */
	var Picker = function (onPick) {
		var rows = [];
		var root = el('div', { class: 'opr-pick', hidden: true });
		root.appendChild(el('p', { class: 'opr-pick__head' }, ['Pick what to refund ', el('small', { text: 'or type the amount' })]));

		var taxFor = function (itemAmount) {
			if (!(cfg.tax > 0) || !(cfg.subtotal > 0)) {
				return 0;
			}
			if (Math.abs(itemAmount - cfg.subtotal) < 0.005) {
				return cfg.tax;
			}
			return round2(cfg.tax * itemAmount / cfg.subtotal);
		};

		var addRow = function (row, name, detail, extra) {
			row.box = el('input', { type: 'checkbox' });
			row.amountEl = el('span', { class: 'opr-pick__amt' });
			var nameEl = el('span', { class: 'opr-pick__name' }, [name, detail ? el('small', { text: detail }) : null, extra || null]);
			row.node = el('label', { class: 'opr-pick__row' }, [row.box, nameEl, row.amountEl]);
			root.appendChild(row.node);
			rows.push(row);
		};

		(cfg.items || []).forEach(function (item) {
			var row = { kind: 'item', item: item, unit: item.amount / item.qty, qty: null };
			var extra = null;
			if (item.qty > 1) {
				row.qty = el('select', { 'aria-label': 'How many to refund' });
				for (var n = 1; n <= Math.min(item.qty, 500); n++) {
					row.qty.appendChild(el('option', { value: String(n), text: String(n) }));
				}
				row.qty.value = String(Math.min(item.qty, 500));
				extra = el('span', { class: 'opr-pick__qty' }, ['Qty ', row.qty, ' of ' + item.qty]);
			}
			addRow(row, item.title, item.job ? 'Job: ' + item.job : '', extra);
		});
		if (cfg.shipping > 0) {
			addRow({ kind: 'shipping' }, 'Shipping', cfg.shippingMethod || '', null);
		}

		var totalEl = el('strong');
		root.appendChild(el('div', { class: 'opr-pick__foot' }, [el('span', { text: 'Selected' }), totalEl]));
		var note = el('p', { class: 'opr-pick__note', hidden: true });
		root.appendChild(note);

		var itemAmount = function (row) {
			var count = row.qty ? parseInt(row.qty.value, 10) : row.item.qty;
			return count >= row.item.qty ? row.item.amount : round2(row.unit * count);
		};

		var compute = function () {
			var items = 0;
			var shipping = 0;
			var any = false;
			rows.forEach(function (row) {
				if (!row.box.checked) {
					return;
				}
				any = true;
				if (row.kind === 'item') {
					items += itemAmount(row);
				} else {
					shipping += cfg.shipping;
				}
			});
			items = round2(items);
			return { any: any, total: round2(items + taxFor(items) + shipping) };
		};

		var update = function (fire) {
			rows.forEach(function (row) {
				row.amountEl.textContent = '';
				if (row.kind === 'item') {
					var amount = itemAmount(row);
					var tax = taxFor(amount);
					row.amountEl.appendChild(document.createTextNode(money(amount)));
					if (tax > 0) {
						row.amountEl.appendChild(el('small', { text: '+ ' + money(tax) + ' tax' }));
					}
				} else {
					row.amountEl.textContent = money(cfg.shipping);
				}
			});
			var result = compute();
			var capped = Math.min(result.total, cfg.remaining);
			totalEl.textContent = money(result.any ? capped : 0);
			var over = result.any && result.total > cfg.remaining + 0.001;
			note.hidden = !over;
			note.textContent = over ? 'That comes to ' + money(result.total) + ', but only ' + money(cfg.remaining) + ' is left to refund, so the amount is ' + money(cfg.remaining) + '.' : '';
			if (fire) {
				onPick(result.any ? capped : 0);
			}
		};

		rows.forEach(function (row) {
			row.box.addEventListener('change', function () {
				update(true);
			});
			if (row.qty) {
				row.qty.addEventListener('change', function () {
					row.box.checked = true;
					update(true);
				});
			}
		});
		update(false);

		return {
			el: root,
			// Untick everything (the amount was typed by hand).
			clear: function () {
				rows.forEach(function (row) {
					row.box.checked = false;
				});
				update(false);
			},
			// Untick everything and put quantities back to the full amount.
			reset: function () {
				rows.forEach(function (row) {
					row.box.checked = false;
					if (row.qty) {
						row.qty.value = String(Math.min(row.item.qty, 500));
					}
				});
				update(false);
			}
		};
	};

	// ------------------------------------------------------------------
	// Scheduled partial refunds (payment not settled yet)
	// ------------------------------------------------------------------

	var schedPost = function (action, data) {
		var body = new FormData();
		body.append('action', action);
		body.append('post_id', cfg.postId);
		body.append('nonce', cfg.scheduleNonce);
		Object.keys(data || {}).forEach(function (key) {
			body.append(key, data[key]);
		});
		return fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (response) {
				return response.json().catch(function () {
					return { ok: false, error: 'The server returned an unexpected response (HTTP ' + response.status + ').' };
				});
			})
			.catch(function () {
				return { ok: false, error: 'Could not reach the server. Check your connection and try again.' };
			});
	};

	/**
	 * The yellow box shown in the Refund dialog while the payment hasn't settled, with a form
	 * to schedule the partial refund for after settlement.
	 */
	var Scheduler = function () {
		var active = cfg.schedule && (cfg.schedule.state === 'waiting' || cfg.schedule.state === 'sending');
		var lead = 'This card payment hasn\'t settled in Converge yet, so right now only the whole charge can be cancelled (voided). ';
		var root = el('div', { class: 'opr-void-note opr-void', hidden: true });
		var intro = el('p', { class: 'opr-void__text' });
		root.appendChild(intro);
		var inert = { el: root, show: function () {}, reset: function () {}, done: function () { return false; } };
		if (active) {
			intro.textContent = lead + 'A partial refund of ' + money(cfg.schedule.amount) + ' is already scheduled and goes out once it settles.';
			return inert;
		}
		if (!cfg.canSchedule || !cfg.scheduleNonce) {
			intro.textContent = lead + 'Partial refunds open once it settles, usually by the next business day.';
			return inert;
		}
		intro.textContent = lead + 'You can schedule the partial refund instead: the site sends it by itself once the payment settles, usually by the next business day.';

		var max = round2(cfg.remaining - 0.01);
		var finished = false;
		var expanded = false;
		var openButton = el('button', { type: 'button', class: 'button opr-sched-open', text: 'Schedule a partial refund' });
		var amountInput = el('input', { type: 'text', id: 'opr-s-amount', inputmode: 'decimal', autocomplete: 'off', placeholder: '0.00' });
		var error = el('p', { class: 'opr-error', hidden: true });
		var reason = el('textarea', { id: 'opr-s-reason', rows: '2', maxlength: '500', placeholder: 'e.g. Customer removed one sign' });
		var notify = el('input', { type: 'checkbox', checked: !!cfg.email, disabled: !cfg.email });
		var submit = el('button', { type: 'button', class: 'button button-primary', disabled: true, text: 'Schedule refund' });
		var confirmText = el('p', { class: 'opr-sched-confirm__text' });
		var yes = el('button', { type: 'button', class: 'button button-primary', text: 'Yes, schedule it' });
		var back = el('button', { type: 'button', class: 'button', text: 'Back' });
		var confirmRow = el('div', { class: 'opr-sched-confirm', hidden: true }, [confirmText, el('div', { class: 'opr-sched-actions' }, [yes, back])]);
		var result = el('p', { class: 'opr-sched-result', role: 'status', hidden: true });
		var picker = hasPicks ? Picker(function (value) {
			setAmount(amountInput, value);
		}) : null;
		if (picker) {
			picker.el.hidden = false;
		}
		var form = el('div', { class: 'opr-sched-form', hidden: true }, [
			el('div', { class: 'opr-sched-row' }, [
				el('label', { for: 'opr-s-amount', class: 'opr-sched-label', text: 'Amount to refund' }),
				el('span', { class: 'opr-money' }, [el('span', { 'aria-hidden': 'true', text: '$' }), amountInput])
			]),
			picker ? picker.el : null,
			error,
			el('label', { for: 'opr-s-reason', class: 'opr-sched-label opr-sched-label--block' }, ['Reason ', el('small', { text: 'Internal note, not shown to the customer' })]),
			reason,
			el('label', { class: 'opr-check' }, [notify, el('span', null, ['Email the customer when it\'s sent', cfg.email ? el('small', { text: cfg.email }) : null])]),
			el('div', { class: 'opr-sched-actions' }, [submit]),
			confirmRow
		]);
		root.appendChild(openButton);
		root.appendChild(form);
		root.appendChild(result);

		var value = function () {
			return parseMoney(amountInput.value);
		};
		var validate = function (showErrors) {
			var amount = value();
			var message = '';
			if (amountInput.value.trim() === '') {
				message = showErrors ? 'Enter the amount to refund.' : '';
			} else if (isNaN(amount) || amount < 0.01) {
				message = 'Enter a valid amount, like 25.00.';
			} else if (amount > max + 0.001) {
				message = 'A scheduled refund has to be less than the full ' + money(cfg.remaining) + '. To refund everything, void the full charge now.';
			}
			error.textContent = message;
			error.hidden = !message;
			var ok = !isNaN(amount) && amount >= 0.01 && amount <= max + 0.001;
			submit.disabled = !ok;
			submit.textContent = ok ? 'Schedule refund of ' + money(amount) : 'Schedule refund';
			return ok;
		};
		var expand = function () {
			if (expanded || finished) {
				return;
			}
			expanded = true;
			form.hidden = false;
			openButton.hidden = true;
			amountInput.focus();
		};
		var closeConfirm = function () {
			confirmRow.hidden = true;
			submit.hidden = false;
			yes.disabled = false;
			back.disabled = false;
			yes.textContent = 'Yes, schedule it';
		};

		openButton.addEventListener('click', expand);
		amountInput.addEventListener('input', function (event) {
			if (event.isTrusted && picker) {
				picker.clear();
			}
			closeConfirm();
			validate(false);
		});
		amountInput.addEventListener('keydown', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				submit.click();
			}
		});
		submit.addEventListener('click', function () {
			if (!validate(true)) {
				return;
			}
			confirmText.textContent = 'Schedule ' + money(value()) + ' to go back to card ' + cfg.card + ' once the payment settles? ' + (notify.checked ? 'The customer is emailed when it\'s sent.' : 'The customer won\'t be emailed.') + ' You can cancel it until then.';
			confirmRow.hidden = false;
			submit.hidden = true;
			yes.focus();
		});
		back.addEventListener('click', closeConfirm);
		yes.addEventListener('click', function () {
			yes.disabled = true;
			back.disabled = true;
			yes.textContent = 'Scheduling…';
			schedPost('wholesale_refund_schedule', {
				amount: value().toFixed(2),
				reason: reason.value.trim(),
				notify: notify.checked ? '1' : ''
			}).then(function (response) {
				if (response.ok) {
					finished = true;
					form.hidden = true;
					intro.hidden = true;
					result.textContent = response.message + ' Close this window to see it in the Payment box.';
					result.hidden = false;
					return;
				}
				closeConfirm();
				error.textContent = response.error || 'The refund could not be scheduled.';
				error.hidden = false;
			});
		});

		return {
			el: root,
			show: function (wantPartial) {
				if (wantPartial) {
					expand();
				}
			},
			reset: function () {
				if (finished) {
					return;
				}
				expanded = false;
				form.hidden = true;
				openButton.hidden = false;
				amountInput.value = '';
				reason.value = '';
				notify.checked = !!cfg.email;
				result.hidden = true;
				if (picker) {
					picker.reset();
				}
				closeConfirm();
				validate(false);
				error.hidden = true;
			},
			done: function () {
				return finished;
			}
		};
	};

	/**
	 * Payment box card for a scheduled refund: waiting (Check now / Cancel), or one that needs a look.
	 */
	var setupScheduleCard = function (actionsRow) {
		var s = cfg.schedule;
		var payBox = document.querySelector('.wholesale-fulfillment');
		if (!s || !payBox) {
			return;
		}
		var state = s.stale ? 'unknown' : s.state;
		var title;
		var text;
		if (state === 'waiting') {
			title = 'Partial refund scheduled';
			text = s.otherSite
				? 'It was scheduled on another copy of the site (' + s.otherSite + '), so only that site sends it.'
				: 'It goes back to card ' + cfg.card + ' automatically once Converge settles the payment. The site checks every hour.';
		} else if (state === 'sending') {
			title = 'Scheduled refund being sent';
			text = 'It is being sent to Converge right now. Reload the page in a minute.';
		} else if (state === 'failed') {
			title = 'Scheduled refund not sent';
			text = s.message ? s.message.charAt(0).toUpperCase() + s.message.slice(1) : 'It could not be sent. No money was moved.';
		} else {
			title = 'Check this refund in Converge';
			text = 'Converge didn\'t answer when the scheduled refund was sent, so it isn\'t known whether it went through. Check the transaction in Converge before refunding again. The site won\'t try again.';
		}

		var message = el('p', { class: 'opr-sched__msg', role: 'status', hidden: true });
		var card = el('div', { class: 'opr-sched', 'data-state': state }, [
			el('p', { class: 'opr-sched__head' }, [icon(state === 'waiting' ? 'clock' : 'warning'), el('strong', { text: title }), el('strong', { class: 'opr-sched__amount', text: money(s.amount) })]),
			el('p', { text: text }),
			el('small', { text: 'Scheduled ' + s.when + ' · ' + (s.notify ? 'customer emailed when sent' : 'no customer email') }),
			state === 'waiting' && s.lastCheck ? el('small', { text: 'Last checked ' + s.lastCheck + (s.message ? ': ' + s.message : '') }) : null,
			s.reason ? el('small', { class: 'opr-history__reason', text: s.reason }) : null
		]);

		if (cfg.scheduleNonce) {
			var buttons = [];
			var arm = function (button, label, armedLabel, action) {
				var timer = null;
				button.addEventListener('click', function () {
					if (button.dataset.armed !== '1') {
						button.dataset.armed = '1';
						button.textContent = armedLabel;
						timer = setTimeout(function () {
							button.dataset.armed = '';
							button.textContent = label;
						}, 5000);
						return;
					}
					clearTimeout(timer);
					action();
				});
			};
			var busy = function (on) {
				buttons.forEach(function (button) {
					button.disabled = on;
				});
			};
			var say = function (text, reload) {
				message.textContent = text;
				message.hidden = !text;
				if (reload) {
					setTimeout(function () {
						window.location.reload();
					}, 1500);
				}
			};
			if (state === 'waiting' && !s.otherSite) {
				var check = el('button', { type: 'button', class: 'button button-small', text: 'Check now' });
				check.addEventListener('click', function () {
					busy(true);
					say('Checking with Converge…');
					schedPost('wholesale_refund_schedule_run').then(function (response) {
						busy(false);
						if (!response.ok) {
							say(response.error || 'The check failed.');
							return;
						}
						say(response.message, response.state !== 'waiting');
					});
				});
				buttons.push(check);
			}
			if (state !== 'sending') {
				var cancelLabel = state === 'waiting' ? 'Cancel' : 'Dismiss';
				var cancel = el('button', { type: 'button', class: 'button button-small opr-sched__cancel', text: cancelLabel });
				arm(cancel, cancelLabel, state === 'waiting' ? 'Yes, cancel it' : 'Yes, dismiss', function () {
					busy(true);
					schedPost('wholesale_refund_schedule_cancel').then(function (response) {
						busy(false);
						say(response.ok ? response.message : (response.error || 'It could not be cancelled.'), !!response.ok);
					});
				});
				buttons.push(cancel);
			}
			if (buttons.length) {
				card.appendChild(el('div', { class: 'opr-sched__actions' }, buttons));
			}
		}
		card.appendChild(message);

		var statusSelect = document.getElementById('wholesale_payment_status');
		var statusRow = statusSelect && statusSelect.closest('p');
		if (actionsRow) {
			actionsRow.insertAdjacentElement('beforebegin', card);
		} else if (statusRow && payBox.contains(statusRow)) {
			statusRow.parentNode.insertBefore(card, statusRow);
		} else {
			payBox.appendChild(card);
		}
	};

	// ------------------------------------------------------------------
	// Converge: Full / Partial buttons + picker inside the existing Refund dialog
	// ------------------------------------------------------------------

	var setupConverge = function () {
		var opener = document.querySelector('.wr-open');
		if (!opener || opener.closest('.opr-actions')) {
			return null;
		}
		var row = el('div', { class: 'opr-actions' });
		opener.parentNode.insertBefore(row, opener);
		row.appendChild(opener);
		opener.textContent = '';
		opener.appendChild(icon('undo'));
		opener.appendChild(document.createTextNode(' Full refund'));
		var partialButton = el('button', { type: 'button', class: 'button opr-partial', 'aria-haspopup': 'dialog' }, [icon('money-alt'), ' Partial refund']);
		row.appendChild(partialButton);

		var dialog = document.getElementById('wr-dialog');
		var fieldset = dialog && dialog.querySelector('.wr-amount');
		var partialRadio = dialog && dialog.querySelector('[name="wr_kind"][value="partial"]');
		var partialOption = dialog && dialog.querySelector('.wr-option--partial');
		var amountInput = dialog && dialog.querySelector('#wr-amount');
		var methodBox = dialog && dialog.querySelector('.wr-method');
		if (!fieldset || !partialRadio || !partialOption || !amountInput) {
			// Unknown dialog layout: Partial simply opens the dialog.
			partialButton.addEventListener('click', function () {
				opener.click();
			});
			return row;
		}

		var wantPartial = false;
		var focusPending = false;
		var picker = hasPicks ? Picker(function (value) {
			setAmount(amountInput, value);
		}) : null;
		var scheduler = Scheduler(dialog);
		var voidNote = scheduler.el;
		if (picker) {
			partialOption.insertAdjacentElement('afterend', picker.el);
			picker.el.insertAdjacentElement('afterend', voidNote);
		} else {
			partialOption.insertAdjacentElement('afterend', voidNote);
		}
		// A scheduled refund is still waiting: say so at the top of the dialog.
		var active = cfg.schedule && (cfg.schedule.state === 'waiting' || cfg.schedule.state === 'sending');
		if (active && methodBox) {
			methodBox.insertAdjacentElement('beforebegin', el('p', { class: 'opr-void-note opr-sched-warning', text: 'A partial refund of ' + money(cfg.schedule.amount) + ' is already scheduled for this order. It is sent automatically once the payment settles. To refund by hand instead, cancel it in the Payment box first.' }));
		}

		var sync = function () {
			var partialOn = partialRadio.checked && !partialRadio.disabled;
			if (picker) {
				picker.el.hidden = !partialOn;
			}
			var isVoid = partialRadio.disabled && methodBox && methodBox.dataset.tone === 'info';
			voidNote.hidden = !isVoid;
			if (isVoid) {
				scheduler.show(wantPartial);
			}
			if (focusPending && !fieldset.disabled && !partialRadio.disabled) {
				focusPending = false;
				partialRadio.checked = true;
				amountInput.focus();
				if (picker) {
					picker.el.hidden = false;
				}
			}
		};

		// Registered after the dialog's own handler, so it runs after the dialog has reset itself.
		opener.addEventListener('click', function () {
			wantPartial = false;
			focusPending = false;
			if (picker) {
				picker.reset();
			}
			scheduler.reset();
			sync();
		});
		dialog.addEventListener('close', function () {
			if (scheduler.done()) {
				window.location.reload();
			}
		});
		partialButton.addEventListener('click', function () {
			opener.click();
			wantPartial = true;
			focusPending = true;
			if (!partialRadio.disabled) {
				partialRadio.checked = true;
			}
			sync();
		});

		var observer = new MutationObserver(sync);
		observer.observe(fieldset, { attributes: true, attributeFilter: ['disabled'] });
		observer.observe(partialRadio, { attributes: true, attributeFilter: ['disabled'] });
		if (methodBox) {
			observer.observe(methodBox, { attributes: true, attributeFilter: ['data-tone'] });
		}
		dialog.querySelectorAll('[name="wr_kind"]').forEach(function (radio) {
			radio.addEventListener('change', sync);
		});
		amountInput.addEventListener('input', function (event) {
			if (event.isTrusted && picker) {
				picker.clear();
			}
			sync();
		});
		// No reset on the dialog's "close" event: it fires a moment later and could undo a
		// Partial click made right after closing. Every opening resets through opener's click.
		return row;
	};

	// ------------------------------------------------------------------
	// Wallet payment box (Stripe / PayPal): Full / Partial switch + picker
	// ------------------------------------------------------------------

	var setupWallet = function () {
		var box = document.querySelector('[data-sfs-wallet-refund]');
		var amountInput = box && box.querySelector('[data-refund-amount]');
		if (!amountInput || box.querySelector('.opr-seg')) {
			return;
		}
		var remaining = cfg.remaining;
		try {
			remaining = Number(JSON.parse(box.dataset.config || '{}').remaining) || remaining;
		} catch (error) {
			// Keep the page's number.
		}
		var amountRow = amountInput.closest('p') || amountInput;
		var fullButton = el('button', { type: 'button', 'aria-pressed': 'true', text: 'Full ' + money(remaining) });
		var partialButton = el('button', { type: 'button', 'aria-pressed': 'false', text: 'Partial refund' });
		amountRow.parentNode.insertBefore(el('div', { class: 'opr-seg', role: 'group', 'aria-label': 'Refund amount' }, [fullButton, partialButton]), amountRow);
		var picker = hasPicks ? Picker(function (value) {
			setAmount(amountInput, value);
		}) : null;
		if (picker) {
			amountRow.insertAdjacentElement('afterend', picker.el);
		}
		var mode = function (partial) {
			fullButton.setAttribute('aria-pressed', partial ? 'false' : 'true');
			partialButton.setAttribute('aria-pressed', partial ? 'true' : 'false');
			if (picker) {
				picker.el.hidden = !partial;
			}
		};
		fullButton.addEventListener('click', function () {
			mode(false);
			if (picker) {
				picker.reset();
			}
			setAmount(amountInput, remaining);
		});
		partialButton.addEventListener('click', function () {
			mode(true);
			if (picker) {
				picker.reset();
			}
			setAmount(amountInput, 0);
			amountInput.focus();
		});
		amountInput.addEventListener('input', function (event) {
			if (!event.isTrusted) {
				return;
			}
			if (picker) {
				picker.clear();
			}
			var value = parseFloat(amountInput.value);
			mode(!(Math.abs(value - remaining) < 0.005));
		});
	};

	// ------------------------------------------------------------------
	// Record a refund made outside the site (Payments page endpoint)
	// ------------------------------------------------------------------

	var setupRecord = function (actionsRow) {
		if (!cfg.record) {
			return;
		}
		var payBox = document.querySelector('.wholesale-fulfillment');
		if (!payBox || document.querySelector('.opr-dialog')) {
			return;
		}

		var trigger = cfg.kind === 'none'
			? el('button', { type: 'button', class: 'button opr-record-main', 'aria-haspopup': 'dialog' }, [icon('undo'), ' Record a refund'])
			: el('button', { type: 'button', class: 'opr-record-link', 'aria-haspopup': 'dialog', text: 'Refunded outside the site? Record it' });
		var statusSelect = document.getElementById('wholesale_payment_status');
		var statusRow = statusSelect && statusSelect.closest('p');
		if (actionsRow) {
			actionsRow.insertAdjacentElement('afterend', trigger);
		} else if (statusRow && payBox.contains(statusRow)) {
			statusRow.parentNode.insertBefore(trigger, statusRow);
		} else {
			payBox.appendChild(trigger);
		}

		var channels = ['Converge (on Converge\'s website)', 'PayPal', 'Stripe', 'Check', 'Zelle', 'Cash', 'Bank transfer / ACH', 'Store credit', 'Other'];
		var preset = cfg.kind === 'converge' ? channels[0] : (cfg.gateway === 'paypal' ? 'PayPal' : (cfg.gateway === 'stripe' ? 'Stripe' : ''));
		var remaining = cfg.remaining;

		// --- Build the dialog ---
		var alertBox = el('div', { class: 'opr-alert', role: 'alert', hidden: true });
		var fullRadio = el('input', { type: 'radio', name: 'opr_kind', value: 'full', checked: true });
		var partialRadio = el('input', { type: 'radio', name: 'opr_kind', value: 'partial' });
		var amountInput = el('input', { type: 'text', inputmode: 'decimal', autocomplete: 'off', placeholder: '0.00', 'aria-label': 'Partial refund amount' });
		var amountError = el('p', { class: 'opr-error', hidden: true });
		var channelSelect = el('select', { id: 'opr-channel' }, [el('option', { value: '', text: 'Choose…' })].concat(channels.map(function (name) {
			return el('option', { value: name, text: name });
		})));
		channelSelect.value = preset;
		var referenceInput = el('input', { type: 'text', id: 'opr-reference', maxlength: '120', placeholder: 'e.g. Converge return ID or check number' });
		var reasonInput = el('textarea', { id: 'opr-reason', rows: '2', maxlength: '500', placeholder: 'e.g. One sign arrived damaged' });
		var notifyBox = el('input', { type: 'checkbox', id: 'opr-notify', disabled: !cfg.email });
		var picker = hasPicks ? Picker(function (value) {
			setAmount(amountInput, value);
		}) : null;

		var formStep = el('div', { 'data-step': 'form' }, [
			el('p', { class: 'opr-info', text: 'Use this after the money was already sent back outside the site: in Converge\'s website, PayPal, by check, Zelle, cash and so on. The site only saves the refund on this order. It does not move any money.' }),
			el('div', { class: 'opr-stats' }, [
				el('div', null, [el('span', { text: 'Paid' }), el('strong', { text: money(cfg.charged) })]),
				el('div', null, [el('span', { text: 'Refunded' }), el('strong', { text: money(cfg.refunded) })]),
				el('div', { class: 'is-key' }, [el('span', { text: 'Available' }), el('strong', { text: money(remaining) })])
			]),
			el('p', { class: 'opr-legend', text: 'Amount refunded' }),
			el('label', { class: 'opr-option' }, [fullRadio, el('span', { class: 'opr-option__label', text: 'Full refund' }), el('span', { class: 'opr-option__value', text: money(remaining) })]),
			el('label', { class: 'opr-option' }, [partialRadio, el('span', { class: 'opr-option__label', text: 'Partial refund' }), el('span', { class: 'opr-money' }, [el('span', { 'aria-hidden': 'true', text: '$' }), amountInput])]),
			picker ? picker.el : null,
			amountError,
			el('div', { class: 'opr-field' }, [el('label', { for: 'opr-channel', text: 'Refunded via' }), channelSelect]),
			el('div', { class: 'opr-field' }, [el('label', { for: 'opr-reference' }, ['Reference ', el('span', { text: 'Optional' })]), referenceInput]),
			el('div', { class: 'opr-field' }, [el('label', { for: 'opr-reason' }, ['Reason ', el('span', { text: 'Internal note, not shown to the customer' })]), reasonInput]),
			el('label', { class: 'opr-check' }, [notifyBox, el('span', null, ['Email the customer a refund confirmation', cfg.email ? el('small', { text: cfg.email }) : null])])
		]);

		var confirmAmount = el('p', { class: 'opr-confirm__amount' });
		var confirmLead = el('p', { class: 'opr-confirm__lead' });
		var summary = {
			payment: el('dd'),
			email: el('dd'),
			reference: el('dd'),
			reason: el('dd')
		};
		var summaryRow = function (label, dd) {
			return el('div', null, [el('dt', { text: label }), dd]);
		};
		var referenceRow = summaryRow('Reference', summary.reference);
		var reasonRow = summaryRow('Reason', summary.reason);
		var confirmStep = el('div', { 'data-step': 'confirm', hidden: true }, [
			el('div', { class: 'opr-confirm' }, [el('p', { class: 'opr-confirm__eyebrow', text: 'You\'re recording a refund of' }), confirmAmount, confirmLead]),
			el('dl', { class: 'opr-summary' }, [summaryRow('Payment after', summary.payment), summaryRow('Customer email', summary.email), referenceRow, reasonRow])
		]);
		var workingStep = el('div', { 'data-step': 'working', hidden: true }, [
			el('div', { class: 'opr-state' }, [el('span', { class: 'spinner is-active', style: 'float:none;margin:0;' }), el('p', { class: 'opr-state__title', text: 'Saving the refund…' })])
		]);
		var doneText = el('p', { class: 'opr-state__text' });
		var doneStep = el('div', { 'data-step': 'done', hidden: true }, [
			el('div', { class: 'opr-state' }, [el('p', { class: 'opr-state__title', text: 'Refund recorded' }), doneText])
		]);

		var backButton = el('button', { type: 'button', class: 'button', text: 'Cancel' });
		var nextButton = el('button', { type: 'button', class: 'button button-primary', text: 'Review' });
		var closeX = el('button', { type: 'button', class: 'opr-x', 'aria-label': 'Close' }, [icon('no-alt')]);
		var dialog = el('dialog', { class: 'opr-dialog', 'aria-labelledby': 'opr-title' }, [
			el('div', { class: 'opr-dialog__head' }, [
				el('div', null, [el('h2', { id: 'opr-title', text: 'Record a refund' }), el('p', { text: 'Order #' + cfg.number + ' · no money is moved by the site' })]),
				closeX
			]),
			el('div', { class: 'opr-dialog__body' }, [alertBox, formStep, confirmStep, workingStep, doneStep]),
			el('div', { class: 'opr-dialog__foot' }, [backButton, nextButton])
		]);
		document.body.appendChild(dialog);

		// --- Behaviour ---
		var state = { step: 'form', busy: false, finished: false };
		var steps = [formStep, confirmStep, workingStep, doneStep];

		var kind = function () {
			return partialRadio.checked ? 'partial' : 'full';
		};
		var amount = function () {
			return kind() === 'full' ? remaining : parseMoney(amountInput.value);
		};
		var showAlert = function (message) {
			alertBox.textContent = message || '';
			alertBox.hidden = !message;
		};
		var validate = function (showErrors) {
			var value = amount();
			var error = '';
			if (kind() === 'partial') {
				if (amountInput.value.trim() === '') {
					error = showErrors ? 'Enter the amount that was refunded.' : '';
				} else if (isNaN(value) || value < 0.01) {
					error = 'Enter a valid amount, like 25.00.';
				} else if (value > remaining + 0.001) {
					error = 'You can record up to ' + money(remaining) + '.';
				}
			}
			if (!error && showErrors && !channelSelect.value) {
				error = 'Choose how the money was refunded.';
			}
			amountError.textContent = error;
			amountError.hidden = !error;
			if (picker) {
				picker.el.hidden = kind() !== 'partial';
			}
			var ok = !isNaN(value) && value >= 0.01 && value <= remaining + 0.001;
			if (state.step === 'form') {
				nextButton.textContent = ok ? 'Review refund of ' + money(value) : 'Review';
			}
			return ok && (!showErrors || !!channelSelect.value);
		};
		var go = function (step) {
			state.step = step;
			steps.forEach(function (node) {
				node.hidden = node.getAttribute('data-step') !== step;
			});
			backButton.hidden = step === 'working' || step === 'done';
			nextButton.hidden = step === 'working';
			closeX.hidden = step === 'working';
			if (step === 'form') {
				backButton.textContent = 'Cancel';
				validate(false);
			} else if (step === 'confirm') {
				backButton.textContent = 'Back';
				nextButton.textContent = 'Record ' + money(amount()) + ' refund';
				nextButton.focus();
			} else if (step === 'done') {
				nextButton.textContent = 'Done';
				nextButton.focus();
			}
		};
		var fillConfirm = function () {
			var value = amount();
			var left = round2(remaining - value);
			confirmAmount.textContent = money(value);
			confirmLead.textContent = 'Refunded via ' + channelSelect.value + '. The site does not move any money.';
			summary.payment.textContent = left < 0.01 ? 'Refunded' : 'Partly refunded (' + money(left) + ' still paid)';
			summary.email.textContent = notifyBox.checked ? 'Send confirmation to ' + cfg.email : 'Don\'t email';
			summary.reference.textContent = referenceInput.value.trim();
			referenceRow.hidden = !referenceInput.value.trim();
			summary.reason.textContent = reasonInput.value.trim();
			reasonRow.hidden = !reasonInput.value.trim();
		};
		var submit = function () {
			var value = amount();
			go('working');
			state.busy = true;
			showAlert('');
			var body = new FormData();
			body.append('action', 'wholesale_pa_action');
			body.append('nonce', cfg.record.nonce);
			body.append('do', 'record_refund');
			body.append('id', cfg.postId);
			body.append('amount', value.toFixed(2));
			body.append('channel', channelSelect.value);
			body.append('reference', referenceInput.value.trim());
			body.append('reason', reasonInput.value.trim());
			body.append('notify', notifyBox.checked ? '1' : '');
			fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
				.then(function (response) {
					return response.json().catch(function () {
						return { success: false, data: { message: 'The server returned an unexpected response (HTTP ' + response.status + ').' } };
					});
				})
				.then(function (result) {
					state.busy = false;
					var message = result && result.data && result.data.message ? result.data.message : '';
					if (result && result.success) {
						state.finished = true;
						doneText.textContent = message || 'Saved on the order.';
						go('done');
						return;
					}
					showAlert(message || 'The refund could not be saved.');
					go('form');
				})
				.catch(function () {
					state.busy = false;
					state.finished = true;
					showAlert('The connection dropped before the server answered. Reload this order and check its refunds before trying again.');
					go('form');
					nextButton.hidden = true;
					backButton.textContent = 'Close';
				});
		};
		var close = function () {
			if (state.busy) {
				return;
			}
			dialog.close();
			if (state.finished) {
				window.location.reload();
			}
		};

		trigger.addEventListener('click', function () {
			showAlert('');
			fullRadio.checked = true;
			amountInput.value = '';
			channelSelect.value = preset;
			referenceInput.value = '';
			reasonInput.value = '';
			notifyBox.checked = false;
			if (picker) {
				picker.reset();
			}
			go('form');
			if (typeof dialog.showModal === 'function') {
				dialog.showModal();
			} else {
				dialog.setAttribute('open', '');
			}
		});
		dialog.addEventListener('cancel', function (event) {
			event.preventDefault();
			close();
		});
		dialog.addEventListener('click', function (event) {
			if (event.target === dialog && state.step === 'form') {
				close();
			}
		});
		closeX.addEventListener('click', close);
		backButton.addEventListener('click', function () {
			if (state.step === 'confirm') {
				go('form');
			} else {
				close();
			}
		});
		nextButton.addEventListener('click', function () {
			if (state.step === 'form') {
				if (validate(true)) {
					showAlert('');
					fillConfirm();
					go('confirm');
				}
			} else if (state.step === 'confirm') {
				submit();
			} else if (state.step === 'done') {
				close();
			}
		});
		[fullRadio, partialRadio].forEach(function (radio) {
			radio.addEventListener('change', function () {
				if (kind() === 'partial') {
					amountInput.focus();
				}
				validate(false);
			});
		});
		var choosePartial = function () {
			if (!partialRadio.checked) {
				partialRadio.checked = true;
			}
			validate(false);
		};
		amountInput.addEventListener('focus', choosePartial);
		amountInput.addEventListener('input', function (event) {
			if (event.isTrusted && picker) {
				picker.clear();
			}
			choosePartial();
		});
		amountInput.addEventListener('keydown', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				nextButton.click();
			}
		});
		channelSelect.addEventListener('change', function () {
			validate(false);
		});
	};

	// ------------------------------------------------------------------
	// Refund list in the Payment box
	// ------------------------------------------------------------------

	var setupHistory = function () {
		var history = cfg.history || [];
		if (!history.length) {
			return;
		}
		// The Converge panel already lists the same entries (newest first): just label the ones
		// recorded by hand, so they don't read as Converge returns.
		var panelItems = document.querySelectorAll('.wr-panel .wr-history li');
		if (panelItems.length) {
			if (panelItems.length !== history.length) {
				return;
			}
			panelItems.forEach(function (item, index) {
				var entry = history[index];
				if (entry.method !== 'manual') {
					return;
				}
				var badge = item.querySelector('.order-badge');
				if (badge) {
					badge.textContent = 'Recorded';
				}
				item.querySelectorAll('small').forEach(function (small) {
					var first = small.firstChild;
					if (first && first.nodeType === 3 && first.nodeValue.indexOf('Converge ref') === 0) {
						first.nodeValue = 'Ref ';
					}
				});
				item.appendChild(el('small', { text: (entry.channel ? 'Via ' + entry.channel + '. ' : '') + 'Recorded only; the site moved no money.' }));
			});
			return;
		}
		// The Wallet payment box lists its own refunds.
		if (cfg.kind === 'wallet') {
			return;
		}
		var payBox = document.querySelector('.wholesale-fulfillment');
		if (!payBox) {
			return;
		}
		var list = el('ul', { class: 'opr-history' }, history.map(function (entry) {
			var label = entry.method === 'manual' ? 'Recorded' : (entry.method === 'void' ? 'Voided' : 'Refunded');
			return el('li', null, [
				el('div', { class: 'opr-history__top' }, [
					el('strong', { text: '−' + money(entry.amount) }),
					el('span', { class: 'order-badge order-badge--muted', text: label })
				]),
				el('small', { text: entry.when }),
				entry.channel ? el('small', { text: 'Via ' + entry.channel }) : null,
				entry.reference ? el('small', { text: 'Ref ' + entry.reference }) : null,
				entry.reason ? el('small', { class: 'opr-history__reason', text: entry.reason }) : null
			]);
		}));
		var wrap = el('div', { class: 'opr-history-wrap' }, [
			el('h4', { class: 'opr-history__title', text: 'Refunds' }),
			list,
			el('p', { class: 'opr-net' }, [el('span', { text: 'Net payment' }), el('strong', { text: money(cfg.remaining) })])
		]);
		var statusSelect = document.getElementById('wholesale_payment_status');
		var statusRow = statusSelect && statusSelect.closest('p');
		if (statusRow && payBox.contains(statusRow)) {
			statusRow.parentNode.insertBefore(wrap, statusRow);
		} else {
			payBox.appendChild(wrap);
		}
	};

	var init = function () {
		var actionsRow = setupConverge();
		setupWallet();
		setupHistory();
		setupScheduleCard(actionsRow);
		setupRecord(actionsRow);
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

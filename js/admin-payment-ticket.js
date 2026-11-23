/**
 * Payment ticket editor: line items bound to catalog products (with their
 * options) or custom items, live totals, proof picker and link copy.
 */
(function () {
    'use strict';

    var data = window.wptData;
    var root = document.getElementById('wpt-items');
    if (!data || !root) {
        return;
    }

    var catalog = data.catalog || [];
    var byId = {};
    catalog.forEach(function (product) { byId[product.id] = product; });
    var locked = !!data.locked;
    var money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    var form = document.getElementById('post');
    var uid = 0;
    var dirty = false;

    function el(tag, attrs, children) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (key) {
            var value = attrs[key];
            if (value === null || value === undefined || value === false) {
                return;
            }
            if (key === 'text') {
                node.textContent = value;
            } else if (key === 'class') {
                node.className = value;
            } else if (key.indexOf('on') === 0) {
                node.addEventListener(key.slice(2), value);
            } else {
                node.setAttribute(key, value === true ? '' : value);
            }
        });
        (children || []).forEach(function (child) {
            if (child) {
                node.appendChild(typeof child === 'string' ? document.createTextNode(child) : child);
            }
        });
        return node;
    }

    function icon(name) {
        return el('span', { class: 'dashicons dashicons-' + name, 'aria-hidden': 'true' });
    }

    function parseMoney(value) {
        var number = parseFloat(String(value || '').replace(/[^0-9.]/g, ''));
        return isFinite(number) && number > 0 ? Math.round(number * 100) / 100 : 0;
    }

    function formatInput(value) {
        return value ? Number(value).toFixed(2) : '';
    }

    /* ------------------------------------------------------------------ */
    /* Line items                                                          */
    /* ------------------------------------------------------------------ */

    function emptyState() {
        var existing = root.querySelector('.wpt-empty');
        var hasLines = root.querySelector('.wpt-line');
        if (hasLines && existing) {
            existing.remove();
        } else if (!hasLines && !existing) {
            root.appendChild(el('div', { class: 'wpt-empty' }, [
                icon('products'),
                el('strong', { text: locked ? 'No items on this ticket' : 'No items yet' }),
                locked ? null : el('span', { text: 'Add a product from your catalog, with its options, or a custom item such as installation.' })
            ]));
        }
    }

    function renumber() {
        root.querySelectorAll('.wpt-line').forEach(function (line, index) {
            line.querySelector('.wpt-line__index').textContent = String(index + 1);
        });
    }

    function addLine(item, mode, focus) {
        item = item || {};
        var id = item.key || ('n' + Date.now().toString(36) + (uid++));
        var prefix = 'wpt_items[' + id + ']';
        var design = item.design || null;
        var isCustom = mode === 'custom' || (!item.product_id && item.title);

        var line = el('div', { class: 'wpt-line', 'data-line': id });
        var media = el('div', { class: 'wpt-line__media' });
        var head = el('div', { class: 'wpt-line__head' });
        var opts = el('div', { class: 'wpt-line__opts' });
        var designBox = el('div', { class: 'wpt-design', hidden: true });
        var hint = el('span', { class: 'wpt-line__hint' });
        var productInput = el('input', { type: 'hidden', name: prefix + '[product_id]', value: item.product_id || '' });

        var width = el('input', { type: 'text', inputmode: 'decimal', id: id + '-w', name: prefix + '[width]', value: item.width || '', placeholder: '—', disabled: locked });
        var height = el('input', { type: 'text', inputmode: 'decimal', id: id + '-h', name: prefix + '[height]', value: item.height || '', placeholder: '—', disabled: locked });
        var notes = el('textarea', { id: id + '-n', name: prefix + '[notes]', rows: '2', placeholder: 'Notes for this item: letter text, font, colors, mounting…', disabled: locked });
        notes.value = item.notes || '';

        var qty = el('input', { type: 'number', min: '1', step: '1', id: id + '-q', name: prefix + '[qty]', value: item.qty || 1, disabled: locked });
        var price = el('input', { type: 'text', inputmode: 'decimal', id: id + '-p', name: prefix + '[unit_price]', value: formatInput(item.unit_price), placeholder: '0.00', disabled: locked });
        var lineTotal = el('strong', { class: 'wpt-num wpt-line__total' });

        function setMedia(thumb, custom) {
            media.textContent = '';
            if (thumb) {
                media.appendChild(el('img', { src: thumb, alt: '' }));
            } else {
                media.appendChild(icon(custom ? 'hammer' : 'format-image'));
            }
        }

        function renderOptions(product, chosen) {
            opts.textContent = '';
            if (!product || !product.attrs.length) {
                return;
            }
            var chosenMap = {};
            (chosen || []).forEach(function (option) { chosenMap[option.name] = option.value; });
            product.attrs.forEach(function (attr, index) {
                var selectId = id + '-o' + index;
                var select = el('select', { id: selectId, name: prefix + '[opt][' + attr.name + ']', disabled: locked }, [
                    el('option', { value: '', text: '— Not specified —' })
                ]);
                attr.options.forEach(function (title) {
                    var option = el('option', { value: title, text: title });
                    if (chosenMap[attr.name] === title) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                opts.appendChild(el('p', { class: 'wpt-field wpt-field--compact' }, [
                    el('label', { for: selectId, text: attr.label }),
                    select
                ]));
            });
        }

        function useBuilderPrice() {
            price.value = formatInput(design.cost);
            recalcLine();
            recalc();
            markDirty();
        }

        // Channel letter lines are designed in the storefront builder.
        function renderDesign(product) {
            designBox.textContent = '';
            designBox.hidden = !(product && product.cl);
            // A builder design already defines text, font, colors and size.
            opts.hidden = !!(design && product && product.cl);
            media.classList.toggle('is-design', !!(design && product && product.cl));
            if (designBox.hidden) {
                return;
            }

            var open = locked ? null : el('button', {
                type: 'submit',
                name: 'wpt_action',
                value: 'builder',
                class: 'button' + (design ? '' : ' button-primary'),
                onclick: function () { document.getElementById('wpt-builder-line').value = id; }
            }, [icon('art'), design ? ' Edit design in builder' : ' Open channel letter builder']);

            if (!design) {
                designBox.appendChild(el('div', { class: 'wpt-design__empty' }, [
                    el('span', { class: 'wpt-design__text' }, [
                        el('strong', { text: 'Design these channel letters' }),
                        el('span', { text: 'Open the builder to set the text, font, colors and size. The builder price is added to this line. Unsaved changes on this ticket are saved first.' })
                    ]),
                    open
                ]));
                return;
            }

            var summary = el('ul', { class: 'wpt-design__summary' });
            (design.summary || []).forEach(function (line) { summary.appendChild(el('li', { text: line })); });
            var priceRow = el('span', { class: 'wpt-design__price' }, [
                'Builder price ', el('strong', { class: 'wpt-num', text: money.format(design.cost || 0) })
            ]);
            if (!locked && Math.abs(parseMoney(price.value) - design.cost) > 0.004) {
                priceRow.appendChild(el('button', { type: 'button', class: 'button-link', text: 'Use as unit price', onclick: function (event) { event.target.remove(); useBuilderPrice(); } }));
            }

            designBox.appendChild(el('a', { class: 'wpt-design__preview', href: design.url, target: '_blank', rel: 'noopener', title: 'Open full-size design' }, [
                el('img', { src: design.url, alt: 'Channel letter design' })
            ]));
            designBox.appendChild(el('div', { class: 'wpt-design__body' }, [
                el('strong', { class: 'wpt-design__title' }, [icon('yes-alt'), ' Channel letter design attached']),
                summary,
                priceRow,
                locked ? null : el('span', { class: 'wpt-design__actions' }, [
                    open,
                    el('button', {
                        type: 'button',
                        class: 'button-link button-link-delete',
                        text: 'Remove design',
                        onclick: function () {
                            line.appendChild(el('input', { type: 'hidden', name: prefix + '[remove_design]', value: '1' }));
                            design = null;
                            renderDesign(product);
                            setMedia(product.thumb, false);
                            markDirty();
                        }
                    })
                ])
            ]));
        }

        function setHint(product) {
            // Channel letters are priced by the builder, not per square foot.
            hint.textContent = product && product.price && !product.cl ? 'Catalog price ' + money.format(product.price) + ' / sq ft' : '';
        }

        function showProduct(product, chosen) {
            head.textContent = '';
            productInput.value = product.id;
            setMedia(design ? design.url : product.thumb, false);
            head.appendChild(el('div', { class: 'wpt-line__title' }, [
                el('strong', { text: product.title }),
                product.cat ? el('span', { class: 'wpt-tag', text: product.cat }) : null
            ]));
            if (!locked) {
                head.appendChild(el('button', { type: 'button', class: 'button-link wpt-line__change', text: 'Change product', onclick: function () { showPicker(); } }));
            }
            renderOptions(product, chosen);
            renderDesign(product);
            setHint(product);
        }

        function showPicker() {
            head.textContent = '';
            opts.textContent = '';
            productInput.value = '';
            setMedia('', false);
            setHint(null);
            designBox.hidden = true;
            head.appendChild(picker(function (product) {
                design = null;
                showProduct(product, []);
                markDirty();
                var first = opts.querySelector('select') || price;
                first.focus();
            }));
            head.querySelector('input').focus();
        }

        function showCustom() {
            setMedia('', true);
            var titleId = id + '-t';
            head.appendChild(el('div', { class: 'wpt-line__title wpt-line__title--custom' }, [
                el('label', { for: titleId, class: 'screen-reader-text', text: 'Item name' }),
                el('input', { type: 'text', id: titleId, name: prefix + '[title]', value: item.title || '', placeholder: 'Item name, e.g. Installation labor', disabled: locked, required: !locked }),
                el('span', { class: 'wpt-tag wpt-tag--custom', text: 'Custom item' })
            ]));
        }

        function recalcLine() {
            var quantity = Math.max(1, parseInt(qty.value, 10) || 1);
            lineTotal.textContent = money.format(quantity * parseMoney(price.value));
        }

        var remove = locked ? null : el('button', {
            type: 'button',
            class: 'wpt-line__remove',
            'aria-label': 'Remove item',
            title: 'Remove item',
            onclick: function () {
                line.remove();
                renumber();
                emptyState();
                recalc();
                markDirty();
            }
        }, [icon('no-alt')]);

        price.addEventListener('blur', function () { price.value = formatInput(parseMoney(price.value)); });

        line.appendChild(el('span', { class: 'wpt-line__index', 'aria-hidden': 'true' }));
        line.appendChild(media);
        line.appendChild(el('div', { class: 'wpt-line__main' }, [
            productInput,
            head,
            opts,
            designBox,
            el('div', { class: 'wpt-line__size' }, [
                el('span', { class: 'wpt-label', text: 'Size (inches)' }),
                el('span', { class: 'wpt-size' }, [
                    el('label', { for: id + '-w', class: 'screen-reader-text', text: 'Width in inches' }), width,
                    el('span', { class: 'wpt-size__x', text: 'W ×' }),
                    el('label', { for: id + '-h', class: 'screen-reader-text', text: 'Height in inches' }), height,
                    el('span', { class: 'wpt-size__x', text: 'H' })
                ])
            ]),
            el('p', { class: 'wpt-field wpt-field--compact wpt-line__notes' }, [
                el('label', { for: id + '-n', class: 'screen-reader-text', text: 'Notes' }), notes
            ])
        ]));
        line.appendChild(el('div', { class: 'wpt-line__price' }, [
            el('p', { class: 'wpt-field wpt-field--compact' }, [el('label', { for: id + '-q', text: 'Qty' }), qty]),
            el('p', { class: 'wpt-field wpt-field--compact' }, [
                el('label', { for: id + '-p', text: 'Unit price' }),
                el('span', { class: 'wpt-money' }, [el('span', { 'aria-hidden': 'true', text: '$' }), price])
            ]),
            hint,
            el('div', { class: 'wpt-line__sum' }, [el('span', { text: 'Line total' }), lineTotal])
        ]));
        if (remove) {
            line.appendChild(remove);
        }

        line.addEventListener('input', recalcLine);
        recalcLine();

        var existing = byId[item.product_id];
        if (isCustom) {
            showCustom();
        } else if (existing) {
            showProduct(existing, item.options);
        } else if (item.product_id) {
            // The product was deleted or unpublished since the ticket was made.
            head.appendChild(el('div', { class: 'wpt-line__title' }, [
                el('strong', { text: item.title || 'Product #' + item.product_id }),
                el('span', { class: 'wpt-tag wpt-tag--warn', text: 'No longer in catalog' })
            ]));
            setMedia(item.thumb, false);
            (item.options || []).forEach(function (option) {
                opts.appendChild(el('p', { class: 'wpt-field wpt-field--compact' }, [
                    el('span', { class: 'wpt-label', text: option.label }),
                    el('span', { text: option.value })
                ]));
            });
        }

        root.appendChild(line);
        renumber();
        emptyState();

        if (!isCustom && !existing && !item.product_id) {
            showPicker();
        } else if (focus) {
            (line.querySelector('input[type="text"]:not([readonly])') || qty).focus();
        }
        return line;
    }

    /* ------------------------------------------------------------------ */
    /* Product picker (searchable combobox)                               */
    /* ------------------------------------------------------------------ */

    function picker(onPick) {
        var listId = 'wpt-results-' + (uid++);
        var input = el('input', {
            type: 'search',
            class: 'wpt-picker__input',
            placeholder: 'Search ' + catalog.length + ' products by name or category…',
            role: 'combobox',
            'aria-expanded': 'false',
            'aria-controls': listId,
            'aria-autocomplete': 'list',
            'aria-label': 'Search products',
            autocomplete: 'off'
        });
        var list = el('ul', { class: 'wpt-picker__list', id: listId, role: 'listbox', hidden: true });
        var wrap = el('div', { class: 'wpt-picker' }, [icon('search'), input, list]);
        var results = [];
        var active = -1;

        function search(query) {
            var terms = query.toLowerCase().split(/\s+/).filter(Boolean);
            return catalog.filter(function (product) {
                var hay = (product.title + ' ' + product.cat).toLowerCase();
                return terms.every(function (term) { return hay.indexOf(term) !== -1; });
            }).slice(0, 40);
        }

        function setActive(index) {
            var items = list.querySelectorAll('li[role="option"]');
            items.forEach(function (li, i) { li.setAttribute('aria-selected', i === index ? 'true' : 'false'); });
            active = index;
            if (items[index]) {
                items[index].scrollIntoView({ block: 'nearest' });
                input.setAttribute('aria-activedescendant', items[index].id);
            }
        }

        function render() {
            results = search(input.value);
            list.textContent = '';
            if (!results.length) {
                list.appendChild(el('li', { class: 'wpt-picker__none', text: 'No products match “' + input.value + '”.' }));
            }
            results.forEach(function (product, index) {
                list.appendChild(el('li', {
                    id: listId + '-' + index,
                    role: 'option',
                    'aria-selected': 'false',
                    onmousedown: function (event) { event.preventDefault(); onPick(product); },
                    onmousemove: function () { if (active !== index) { setActive(index); } }
                }, [
                    product.thumb ? el('img', { src: product.thumb, alt: '', loading: 'lazy' }) : el('span', { class: 'wpt-picker__noimg' }, [icon('format-image')]),
                    el('span', { class: 'wpt-picker__text' }, [
                        el('strong', { text: product.title }),
                        el('small', { text: [product.cat, product.attrs.length ? product.attrs.length + ' options' : ''].filter(Boolean).join(' · ') })
                    ])
                ]));
            });
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            setActive(results.length ? 0 : -1);
        }

        input.addEventListener('focus', render);
        input.addEventListener('input', render);
        input.addEventListener('blur', function () {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (list.hidden) { render(); } else { setActive(Math.min(active + 1, results.length - 1)); }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(Math.max(active - 1, 0));
            } else if (event.key === 'Enter') {
                event.preventDefault();
                if (results[active]) { onPick(results[active]); }
            } else if (event.key === 'Escape') {
                list.hidden = true;
                input.setAttribute('aria-expanded', 'false');
            }
        });

        return wrap;
    }

    /* ------------------------------------------------------------------ */
    /* Totals                                                              */
    /* ------------------------------------------------------------------ */

    var discountInput = document.getElementById('ticket_discount');
    var shippingInput = document.getElementById('ticket_shipping');
    var taxInput = document.getElementById('ticket_tax_rate');

    function recalc() {
        var subtotal = 0;
        root.querySelectorAll('.wpt-line').forEach(function (line) {
            var qtyInput = line.querySelector('input[name$="[qty]"]');
            var priceInput = line.querySelector('input[name$="[unit_price]"]');
            subtotal += Math.max(1, parseInt(qtyInput.value, 10) || 1) * parseMoney(priceInput.value);
        });
        subtotal = Math.round(subtotal * 100) / 100;
        var discount = Math.min(parseMoney(discountInput && discountInput.value), subtotal);
        var taxable = subtotal - discount;
        var rate = Math.min(100, parseMoney(taxInput && taxInput.value));
        var tax = Math.round(taxable * rate) / 100;
        var shipping = parseMoney(shippingInput && shippingInput.value);
        var grand = taxable + shipping + tax;

        setText('[data-wpt-subtotal]', money.format(subtotal));
        setText('[data-wpt-tax]', money.format(tax));
        setText('[data-wpt-grand]', money.format(grand));
    }

    function setText(selector, text) {
        document.querySelectorAll(selector).forEach(function (node) { node.textContent = text; });
    }

    [discountInput, shippingInput].forEach(function (input) {
        if (input) {
            input.addEventListener('blur', function () { input.value = formatInput(parseMoney(input.value)); });
        }
    });

    var siteTax = document.querySelector('[data-wpt-site-tax]');
    if (siteTax && taxInput) {
        siteTax.addEventListener('click', function () {
            taxInput.value = String(data.siteTaxRate);
            recalc();
            markDirty();
        });
    }

    document.getElementById('wholesale_ticket_items').addEventListener('input', recalc);

    /* ------------------------------------------------------------------ */
    /* Boot                                                                */
    /* ------------------------------------------------------------------ */

    (data.items || []).forEach(function (item) { addLine(item); });
    emptyState();
    recalc();

    document.querySelectorAll('[data-wpt-add]').forEach(function (button) {
        button.addEventListener('click', function () {
            var line = addLine({}, button.getAttribute('data-wpt-add'), true);
            line.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            recalc();
            markDirty();
        });
    });

    // Enter inside the editor should never submit (and send) the ticket.
    root.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && event.target.tagName === 'INPUT') {
            event.preventDefault();
        }
    });

    /* ------------------------------------------------------------------ */
    /* Proof, copy link, confirmations, unsaved changes                    */
    /* ------------------------------------------------------------------ */

    var proof = document.querySelector('[data-wpt-proof]');
    if (proof && window.wp && wp.media) {
        var proofId = proof.querySelector('[data-wpt-proof-id]');
        var proofName = proof.querySelector('[data-wpt-proof-name]');
        var proofThumb = proof.querySelector('[data-wpt-proof-thumb]');
        var choose = proof.querySelector('[data-wpt-proof-choose]');
        var removeProof = proof.querySelector('[data-wpt-proof-remove]');
        var frame;

        if (choose) {
            choose.addEventListener('click', function () {
                if (!frame) {
                    frame = wp.media({ title: 'Attach design proof', button: { text: 'Attach to ticket' }, library: { type: ['image', 'application/pdf'] }, multiple: false });
                    frame.on('select', function () {
                        var file = frame.state().get('selection').first().toJSON();
                        proofId.value = file.id;
                        proofName.textContent = file.filename || file.title;
                        proofThumb.textContent = '';
                        var thumb = file.sizes && (file.sizes.thumbnail || file.sizes.full);
                        proofThumb.appendChild(thumb ? el('img', { src: thumb.url, alt: '' }) : icon('media-document'));
                        choose.textContent = 'Replace';
                        removeProof.hidden = false;
                        markDirty();
                    });
                }
                frame.open();
            });
        }
        if (removeProof) {
            removeProof.addEventListener('click', function () {
                proofId.value = '';
                proofName.textContent = 'No file attached';
                proofThumb.textContent = '';
                proofThumb.appendChild(icon('media-document'));
                choose.textContent = 'Attach proof';
                removeProof.hidden = true;
                markDirty();
            });
        }
    }

    document.querySelectorAll('[data-wpt-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.querySelector(button.getAttribute('data-wpt-copy'));
            input.select();
            var done = function () {
                button.textContent = 'Copied';
                setTimeout(function () { button.textContent = 'Copy'; }, 1800);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value).then(done, function () { document.execCommand('copy'); done(); });
            } else {
                document.execCommand('copy');
                done();
            }
        });
    });

    document.querySelectorAll('[data-wpt-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!window.confirm(button.getAttribute('data-wpt-confirm'))) {
                event.preventDefault();
            }
        });
    });

    function markDirty() {
        dirty = true;
    }

    if (form) {
        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function (event) {
            var submitter = event.submitter;
            if (submitter && submitter.value === 'send') {
                var total = document.querySelector('.wpt-summary [data-wpt-grand]');
                var email = document.getElementById('ticket_customer_email');
                if (!window.confirm('Email this payment request for ' + (total ? total.textContent : '') + ' to ' + (email && email.value ? email.value : 'the customer') + '?')) {
                    event.preventDefault();
                    return;
                }
            }
            dirty = false;
        });
    }
    window.addEventListener('beforeunload', function (event) {
        if (dirty && !locked) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();

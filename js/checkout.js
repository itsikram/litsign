/*
 * Checkout and payment-ticket forms: inline validation, input formatting, remembered
 * details and payment. Card details are either typed on the page and charged
 * server-to-server (direct) or entered in Converge's secure window (PayWithConverge);
 * either way the server fixes the amount and verifies the result. See inc/payments.php.
 */
(() => {
  const settings = window.wholesalePayment;
  if (!settings) return;

  const money = (value) => '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const digits = (value) => String(value || '').replace(/\D+/g, '');

  // ------------------------------------------------------------------
  // Converge + AJAX
  // ------------------------------------------------------------------

  let converge = null;
  const loadConverge = (src) => {
    if (window.PayWithConverge) return Promise.resolve();
    if (converge) return converge;
    converge = new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = src;
      script.onload = resolve;
      script.onerror = () => {
        converge = null;
        reject(new Error('The secure payment window could not be loaded.'));
      };
      document.head.appendChild(script);
    });
    return converge;
  };

  const post = async (action, data) => {
    data.append('action', action);
    data.append('nonce', settings.nonce);
    const response = await fetch(settings.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' });
    let json;
    try {
      json = await response.json();
    } catch (error) {
      throw new Error('Something went wrong. Please try again, or call us at 866-436-2101.');
    }
    if (!json.ok) {
      const error = new Error(json.error || 'Something went wrong. Please try again.');
      error.field = json.field || '';
      throw error;
    }
    return json;
  };

  // ------------------------------------------------------------------
  // Card helpers
  // ------------------------------------------------------------------

  const cardBrand = (number) => {
    if (/^4/.test(number)) return 'Visa';
    if (/^(5[1-5]|2[2-7])/.test(number)) return 'Mastercard';
    if (/^3[47]/.test(number)) return 'Amex';
    if (/^(6011|65|64[4-9])/.test(number)) return 'Discover';
    return '';
  };

  const luhn = (number) => {
    let sum = 0;
    for (let i = 0; i < number.length; i++) {
      let digit = Number(number[number.length - 1 - i]);
      if (i % 2) {
        digit *= 2;
        if (digit > 9) digit -= 9;
      }
      sum += digit;
    }
    return sum % 10 === 0;
  };

  const formatCard = (number) => {
    const groups = cardBrand(number) === 'Amex' ? [4, 6, 5] : [4, 4, 4, 4, 3];
    const parts = [];
    let rest = number;
    for (const size of groups) {
      if (!rest) break;
      parts.push(rest.slice(0, size));
      rest = rest.slice(size);
    }
    return parts.join(' ');
  };

  // "MM / YY" as the customer types; "4" becomes "04 / ". Autofill and paste often
  // bring "4/28", "04/2028" or "042028", so those are read as month + year first.
  const formatExpiry = (value, deleting) => {
    const parts = String(value).match(/^\s*(\d{1,2})\s*[/\-.\s]\s*(\d{2}|\d{4})\s*$/);
    const six = digits(value).length === 6 && /^(0[1-9]|1[0-2])20\d\d$/.test(digits(value));
    if (parts) return String(parts[1]).padStart(2, '0') + ' / ' + parts[2].slice(-2);
    if (six) return digits(value).slice(0, 2) + ' / ' + digits(value).slice(4);
    let raw = digits(value).slice(0, 4);
    if (raw.length === 1 && Number(raw) > 1) raw = '0' + raw;
    if (raw.length > 2) return raw.slice(0, 2) + ' / ' + raw.slice(2);
    if (raw.length === 2 && !deleting) return raw + ' / ';
    return raw;
  };

  const formatPhone = (value) => {
    let raw = digits(value);
    if (raw.length === 11 && raw[0] === '1') raw = raw.slice(1);
    return raw.length === 10 ? `(${raw.slice(0, 3)}) ${raw.slice(3, 6)}-${raw.slice(6)}` : value.trim();
  };

  const EMAIL_FIXES = {
    'gmial.com': 'gmail.com', 'gmai.com': 'gmail.com', 'gmail.co': 'gmail.com', 'gamil.com': 'gmail.com', 'gnail.com': 'gmail.com', 'gmail.con': 'gmail.com', 'gmaill.com': 'gmail.com',
    'yahoo.co': 'yahoo.com', 'yaho.com': 'yahoo.com', 'yahooo.com': 'yahoo.com', 'yahoo.con': 'yahoo.com',
    'hotmial.com': 'hotmail.com', 'hotmai.com': 'hotmail.com', 'hotmail.co': 'hotmail.com', 'hotmail.con': 'hotmail.com',
    'outlok.com': 'outlook.com', 'outlook.co': 'outlook.com', 'outlook.con': 'outlook.com',
    'iclod.com': 'icloud.com', 'icloud.co': 'icloud.com', 'aol.co': 'aol.com', 'comcast.com': 'comcast.net',
  };

  // ------------------------------------------------------------------
  // Inline validation
  // ------------------------------------------------------------------

  const labelFor = (input) => {
    const label = input.id ? document.querySelector(`label[for="${input.id}"]`) : null;
    if (!label) return (input.getAttribute('aria-label') || input.name || 'this field').toLowerCase();
    const clone = label.cloneNode(true);
    clone.querySelectorAll('.checkout-optional').forEach((el) => el.remove());
    // Lowercase for the sentence, but keep acronyms like "ZIP" and "CVV".
    return clone.textContent.trim().split(/\s+/).map((word) => (word.length > 1 && word === word.toUpperCase() ? word : word.toLowerCase())).join(' ');
  };

  const isActive = (input) => !input.disabled && input.type !== 'hidden' && !input.closest('[hidden]');

  const checkField = (input) => {
    const value = input.value.trim();
    const name = input.name;
    if (!value) {
      if (!input.required) return '';
      if (name === 'card_exp') return 'Enter the expiration date (MM / YY).';
      return `${input.tagName === 'SELECT' ? 'Choose' : 'Enter'} your ${labelFor(input)}.`;
    }
    if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) return 'Enter a valid email, like name@example.com.';
    if (input.type === 'tel' && digits(value).length < 10) return 'Enter a 10-digit phone number, including area code.';
    if (/_zip$/.test(name) && !/^\d{5}(-?\d{4})?$/.test(value)) return 'Enter a 5-digit ZIP code.';
    if (name === 'card_number') {
      const number = digits(value);
      if (number.length < 13 || number.length > 19 || !luhn(number)) return 'This card number doesn’t look right. Please check it.';
    }
    if (name === 'card_exp') {
      const raw = digits(value);
      const month = Number(raw.slice(0, 2));
      const year = 2000 + Number(raw.slice(2, 4));
      const now = new Date();
      if (raw.length !== 4 || month < 1 || month > 12) return 'Enter the expiration date as MM / YY.';
      if (year < now.getFullYear() || (year === now.getFullYear() && month < now.getMonth() + 1)) return 'This card has expired.';
    }
    if (name === 'card_cvv') {
      const number = input.form && input.form.elements.card_number ? digits(input.form.elements.card_number.value) : '';
      const length = cardBrand(number) === 'Amex' ? 4 : 3;
      if (digits(value).length !== length && !(number === '' && /^\d{3,4}$/.test(value))) return `Enter the ${length}-digit security code.`;
    }
    if (input.minLength > 0 && value.length < input.minLength) return `Use at least ${input.minLength} characters.`;
    return '';
  };

  const setError = (input, message) => {
    const holder = input.closest('.checkout-card-input') || input;
    const errorId = `${input.id || input.name}-error`;
    let error = document.getElementById(errorId);
    if (message && !error) {
      error = document.createElement('small');
      error.id = errorId;
      error.className = 'checkout-error';
      holder.insertAdjacentElement('afterend', error);
    }
    if (error) {
      error.textContent = message;
      error.hidden = !message;
    }
    const described = (input.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== errorId);
    if (message) described.push(errorId);
    if (described.length) input.setAttribute('aria-describedby', described.join(' '));
    else input.removeAttribute('aria-describedby');
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
    input.closest('.checkout-field, p')?.classList.toggle('has-error', Boolean(message));
  };

  const validateField = (input) => {
    if (!isActive(input)) {
      setError(input, '');
      return true;
    }
    const message = checkField(input);
    setError(input, message);
    return !message;
  };

  const fieldsOf = (form) => Array.from(form.querySelectorAll('input, select, textarea'))
    .filter((input) => input.name && !['checkbox', 'radio', 'hidden', 'submit'].includes(input.type));

  const validateForm = (form) => {
    const invalid = fieldsOf(form).filter((input) => !validateField(input));
    if (invalid.length) {
      invalid[0].focus({ preventScroll: true });
      invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return invalid;
  };

  const setupFields = (form) => {
    fieldsOf(form).forEach((input) => {
      // Check a field once the customer leaves it; clear the error as soon as it's fixed.
      input.addEventListener('blur', () => {
        if (input.value.trim() || input.getAttribute('aria-invalid') === 'true') validateField(input);
      });
      input.addEventListener(input.tagName === 'SELECT' ? 'change' : 'input', () => {
        if (input.getAttribute('aria-invalid') === 'true') validateField(input);
      });
    });

    const cardNumber = form.querySelector('[name="card_number"]');
    const brandBadge = form.querySelector('[data-card-brand]');
    if (cardNumber) {
      cardNumber.addEventListener('input', () => {
        const number = digits(cardNumber.value).slice(0, 19);
        const atEnd = cardNumber.selectionStart === cardNumber.value.length;
        cardNumber.value = formatCard(number);
        if (atEnd) cardNumber.setSelectionRange(cardNumber.value.length, cardNumber.value.length);
        if (brandBadge) {
          brandBadge.textContent = cardBrand(number);
          brandBadge.hidden = !brandBadge.textContent;
        }
        // Move on to the expiry once a complete, valid number is in.
        const next = form.querySelector('[name="card_exp"]');
        const full = cardBrand(number) === 'Amex' ? 15 : 16;
        if (next && atEnd && number.length === full && luhn(number) && !next.value) next.focus();
      });
    }

    const expiry = form.querySelector('[name="card_exp"]');
    if (expiry) {
      expiry.addEventListener('input', (event) => {
        expiry.value = formatExpiry(expiry.value, event.inputType && event.inputType.startsWith('delete'));
        const cvv = form.querySelector('[name="card_cvv"]');
        if (cvv && digits(expiry.value).length === 4 && !checkField(expiry) && !cvv.value) cvv.focus();
      });
    }

    form.querySelectorAll('[name="card_cvv"], [name$="_zip"]').forEach((input) => {
      input.addEventListener('input', () => {
        input.value = input.name === 'card_cvv' ? digits(input.value).slice(0, 4) : input.value.replace(/[^\d-]/g, '').slice(0, 10);
      });
    });

    form.querySelectorAll('input[type="tel"]').forEach((input) => {
      input.addEventListener('blur', () => { input.value = formatPhone(input.value); });
    });

    const email = form.querySelector('[name="billing_email"]');
    const suggest = form.querySelector('[data-email-suggest]');
    if (email && suggest) {
      const check = () => {
        const [user, domain] = email.value.trim().toLowerCase().split('@');
        const fix = domain && EMAIL_FIXES[domain];
        suggest.hidden = !fix;
        if (fix) {
          suggest.dataset.value = `${user}@${fix}`;
          suggest.textContent = `Did you mean ${user}@${fix}?`;
        }
      };
      email.addEventListener('blur', check);
      suggest.addEventListener('click', () => {
        email.value = suggest.dataset.value;
        suggest.hidden = true;
        validateField(email);
      });
    }
  };

  // ------------------------------------------------------------------
  // Checkout page extras: totals, toggles, remembered details
  // ------------------------------------------------------------------

  const STORAGE_KEY = 'ssoCheckoutDetails';
  const storage = {
    get() {
      try { return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || 'null'); } catch (error) { return null; }
    },
    set(value) {
      try { window.localStorage.setItem(STORAGE_KEY, JSON.stringify(value)); } catch (error) { /* Private mode: nothing to remember. */ }
    },
    clear() {
      try { window.localStorage.removeItem(STORAGE_KEY); } catch (error) { /* Ignore. */ }
    },
  };

  const setupCheckoutPage = (form) => {
    // Separate shipping address.
    const same = form.querySelector('#sameShippingAddress');
    const shipFields = form.querySelector('#shippingAddressFields');
    const syncShipping = () => {
      shipFields.hidden = same.checked;
      shipFields.querySelectorAll('[data-ship-required]').forEach((input) => { input.required = !same.checked; });
    };
    if (same && shipFields) {
      same.addEventListener('change', syncShipping);
      syncShipping();
    }

    // Totals follow the chosen shipping speed.
    const totals = form.querySelector('.checkout-totals');
    const updateTotals = () => {
      const chosen = form.querySelector('input[name="shipping_method"]:checked');
      const shipping = chosen ? parseFloat(chosen.dataset.shippingAmount) : 0;
      const grand = parseFloat(totals.dataset.subtotal) + shipping + parseFloat(totals.dataset.tax);
      form.querySelector('[data-shipping-total]').textContent = money(shipping);
      form.querySelectorAll('[data-grand-total]').forEach((el) => { el.textContent = money(grand); });
    };
    form.querySelectorAll('input[name="shipping_method"]').forEach((radio) => radio.addEventListener('change', updateTotals));

    // "+ Add apartment" links and the order note only open when needed.
    const reveal = (button) => {
      const target = form.querySelector(`#${button.dataset.reveal}`);
      if (!target) return;
      target.hidden = false;
      button.hidden = true;
    };
    form.querySelectorAll('[data-reveal]').forEach((button) => {
      button.addEventListener('click', () => {
        reveal(button);
        form.querySelector(`#${button.dataset.reveal}`).focus();
      });
    });
    const syncReveals = () => form.querySelectorAll('[data-reveal]').forEach((button) => {
      const target = form.querySelector(`#${button.dataset.reveal}`);
      if (target && target.value.trim()) reveal(button);
    });

    const mobileSummary = form.querySelector('.checkout-mobile-summary');
    if (mobileSummary) {
      mobileSummary.addEventListener('toggle', () => {
        mobileSummary.querySelector('[data-show-label]').textContent = mobileSummary.open ? 'Hide summary' : 'Show summary';
      });
    }

    // Optional account.
    const createAccount = form.querySelector('#createAccount');
    if (createAccount) {
      const group = form.querySelector('#accountPasswordGroup');
      const password = form.querySelector('#accountPassword');
      createAccount.addEventListener('change', () => {
        group.hidden = !createAccount.checked;
        password.required = createAccount.checked;
        if (createAccount.checked) password.focus();
        else setError(password, '');
      });
    }

    // Remember contact and address details (never card data or passwords) on this device.
    const remember = form.querySelector('#rememberDetails');
    const remembered = form.querySelector('[data-remembered]');
    const savedFields = () => fieldsOf(form).filter((input) => /^(billing|shipping)_/.test(input.name) && input.type !== 'password');
    const save = () => {
      if (!remember || !remember.checked) return;
      const values = { same_shipping_address: same ? same.checked : true };
      savedFields().forEach((input) => { values[input.name] = input.value; });
      storage.set(values);
    };
    const saved = storage.get();
    if (saved && typeof saved === 'object') {
      let filled = 0;
      savedFields().forEach((input) => {
        const value = saved[input.name];
        if (typeof value === 'string' && value && !input.value) {
          if (input.tagName === 'SELECT' && !Array.from(input.options).some((option) => option.value === value)) return;
          input.value = value;
          filled++;
        }
      });
      if (same && saved.same_shipping_address === false) {
        same.checked = false;
        syncShipping();
      }
      if (filled && remembered) remembered.hidden = false;
    }
    syncReveals();

    let saveTimer = null;
    form.addEventListener('input', () => {
      clearTimeout(saveTimer);
      saveTimer = setTimeout(save, 400);
    });
    form.addEventListener('change', save);
    if (remember) {
      remember.addEventListener('change', () => {
        if (remember.checked) save();
        else storage.clear();
      });
    }
    const forget = form.querySelector('[data-forget]');
    if (forget) {
      forget.addEventListener('click', () => {
        storage.clear();
        savedFields().forEach((input) => {
          if (saved && saved[input.name] === input.value) input.value = '';
          setError(input, '');
        });
        if (same) {
          same.checked = true;
          syncShipping();
        }
        remembered.hidden = true;
        form.querySelector('[name="billing_email"]').focus();
      });
    }
  };

  // ------------------------------------------------------------------
  // Payment
  // ------------------------------------------------------------------

  const setup = (form, kind) => {
    const button = form.querySelector('[data-pay-button]');
    const status = form.querySelector('[data-pay-status]');
    let idleLabel = null;

    setupFields(form);
    if (kind === 'cart') setupCheckoutPage(form);
    // Our inline messages replace the browser's one-at-a-time bubbles (plain posts keep them).
    if (!(kind === 'ticket' && !settings.cardEnabled)) form.noValidate = true;

    const setBusy = (busy, label) => {
      if (!button) return;
      // Remember the idle label at the moment we go busy: it shows the current total.
      if (busy && idleLabel === null) idleLabel = button.innerHTML;
      button.disabled = busy;
      if (busy) {
        button.innerHTML = '<span class="checkout-spinner" aria-hidden="true"></span>';
        button.append(label);
      } else if (idleLabel !== null) {
        button.innerHTML = idleLabel;
        idleLabel = null;
      }
      form.classList.toggle('is-paying', busy);
    };
    const showStatus = (message, type = 'error', scroll = true) => {
      if (!status) return;
      status.textContent = message;
      status.dataset.type = type;
      status.hidden = !message;
      if (message && type === 'error' && scroll) status.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };
    // A server error about one field is shown next to that field.
    const showError = (error) => {
      const input = error.field && (form.elements[error.field] || (error.field === 'card_exp' && form.elements.card_exp_month));
      if (input && input.focus) {
        setError(input, error.message);
        input.focus({ preventScroll: true });
        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        showStatus('Please check the highlighted field.', 'error', false);
        return;
      }
      showStatus(error.message);
    };

    const complete = async (ref, response) => {
      setBusy(true, 'Confirming your payment…');
      showStatus('Payment approved. Finishing your order, please don’t close this page.', 'info');
      const data = new FormData();
      data.append('ref', ref);
      data.append('txn_id', response.ssl_txn_id || '');
      data.append('card', response.ssl_card_number || '');
      data.append('approval_code', response.ssl_approval_code || '');
      const password = form.querySelector('[name="account_password"]');
      if (password) data.append('account_password', password.value);
      try {
        const result = await post('wholesale_payment_complete', data);
        window.location.href = result.redirect;
      } catch (error) {
        setBusy(false);
        showStatus(error.message);
      }
    };

    form.addEventListener('submit', async (event) => {
      // Ticket confirmations without card payment can use the plain form post.
      if (kind === 'ticket' && !settings.cardEnabled) return;
      event.preventDefault();
      if (form.classList.contains('is-paying')) return;

      const invalid = validateForm(form);
      if (invalid.length) {
        showStatus(invalid.length === 1 ? 'Please fix the highlighted field.' : `Please fix the ${invalid.length} highlighted fields.`, 'error', false);
        return;
      }

      showStatus('');
      const busyLabel = !settings.cardEnabled ? 'Placing your order…' : (settings.method === 'direct' ? 'Processing payment…' : 'Opening secure payment…');
      setBusy(true, busyLabel);

      const data = new FormData(form);
      data.append('kind', kind);
      try {
        const start = await post('wholesale_payment_start', data);
        if (start.redirect) {
          window.location.href = start.redirect;
          return;
        }

        await loadConverge(start.script);
        setBusy(true, 'Waiting for payment…');
        window.PayWithConverge.open({ ssl_txn_auth_token: start.token }, {
          onApproval: (response) => complete(start.ref, response || {}),
          onDeclined: (response) => {
            setBusy(false);
            const reason = response && response.ssl_result_message ? ` (${response.ssl_result_message})` : '';
            showStatus(`Your card was declined${reason}. No charge was made. Please try another card or call us at 866-436-2101.`);
          },
          onError: (error) => {
            setBusy(false);
            showStatus(`The payment could not be completed${error ? `: ${error}` : ''}. No charge was made. Please try again.`);
          },
          onCancelled: () => {
            setBusy(false);
            showStatus('Payment cancelled. Your order has not been placed.', 'info');
          },
        });
      } catch (error) {
        setBusy(false);
        showError(error);
      }
    });

    // Restore the button when the visitor comes back with the browser's back button.
    window.addEventListener('pageshow', () => setBusy(false));
  };

  const init = () => {
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) setup(checkoutForm, 'cart');
    const ticketForm = document.getElementById('ticketPayForm');
    if (ticketForm) setup(ticketForm, 'ticket');
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

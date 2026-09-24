/*
 * Checkout and payment-ticket forms. Card details are entered in Converge's secure
 * window (PayWithConverge); the server fixes the amount and verifies the result.
 * See inc/payments.php.
 */
(() => {
  const settings = window.wholesalePayment;
  if (!settings) return;

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
    if (!json.ok) throw new Error(json.error || 'Something went wrong. Please try again.');
    return json;
  };

  const setup = (form, kind) => {
    const button = form.querySelector('[data-pay-button]');
    const status = form.querySelector('[data-pay-status]');
    let idleLabel = null;

    const setBusy = (busy, label) => {
      if (!button) return;
      // Remember the idle label at the moment we go busy: it shows the current total.
      if (busy && idleLabel === null) idleLabel = button.innerHTML;
      button.disabled = busy;
      if (busy) {
        button.textContent = label;
      } else if (idleLabel !== null) {
        button.innerHTML = idleLabel;
        idleLabel = null;
      }
      form.classList.toggle('is-paying', busy);
    };
    const showStatus = (message, type = 'error') => {
      if (!status) return;
      status.textContent = message;
      status.dataset.type = type;
      status.hidden = !message;
      if (message && type === 'error') status.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
      if (!form.reportValidity()) return;

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
        showStatus(error.message);
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

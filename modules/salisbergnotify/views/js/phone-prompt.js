/**
 * Shows a one-time prompt in the back office asking a chosen alert recipient to
 * add the phone number their booking texts go to. Dismissing it, or saving a
 * number, stops it coming back for the rest of the browser session.
 */
(function () {
  'use strict';
  if (typeof sbNotifyPhone === 'undefined' || !sbNotifyPhone.url) { return; }
  var DISMISS = 'sbNotifyPhoneDismissed';
  try { if (window.sessionStorage.getItem(DISMISS) === '1') { return; } } catch (e) {}

  function dismiss() { try { window.sessionStorage.setItem(DISMISS, '1'); } catch (e) {} }

  function build() {
    var name = (sbNotifyPhone.name || '').toString();
    var overlay = document.createElement('div');
    overlay.className = 'sbnp-overlay';
    overlay.innerHTML =
      '<div class="sbnp-card" role="dialog" aria-modal="true" aria-labelledby="sbnp-title">' +
      '<h3 id="sbnp-title">Get booking alerts by text</h3>' +
      '<p class="sbnp-lead"></p>' +
      '<label class="sbnp-label" for="sbnp-phone">Your mobile number</label>' +
      '<input id="sbnp-phone" type="tel" inputmode="tel" class="sbnp-input" autocomplete="tel" placeholder="024 000 0000" />' +
      '<p class="sbnp-error" hidden></p>' +
      '<div class="sbnp-actions">' +
      '<button type="button" class="sbnp-skip">Not now</button>' +
      '<button type="button" class="sbnp-save">Save number</button>' +
      '</div></div>';
    var lead = overlay.querySelector('.sbnp-lead');
    lead.textContent = (name ? name + ', y' : 'Y') +
      'ou are set to receive text alerts for new bookings and payments. Add the number they should go to.';
    document.body.appendChild(overlay);

    var input = overlay.querySelector('#sbnp-phone');
    var err = overlay.querySelector('.sbnp-error');
    var save = overlay.querySelector('.sbnp-save');
    input.focus();

    function close() { if (overlay.parentNode) { overlay.parentNode.removeChild(overlay); } }
    function showError(msg) { err.textContent = msg; err.hidden = false; }

    overlay.querySelector('.sbnp-skip').addEventListener('click', function () { dismiss(); close(); });
    overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) { dismiss(); close(); } });
    input.addEventListener('keydown', function (e) { if (e.key === 'Enter') { save.click(); } });

    save.addEventListener('click', function () {
      var phone = (input.value || '').trim();
      if (phone.replace(/\D+/g, '').length < 9) { showError('Enter a valid phone number.'); return; }
      save.disabled = true; err.hidden = true;
      var body = 'ajax=1&action=save&phone=' + encodeURIComponent(phone);
      fetch(sbNotifyPhone.url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: body
      }).then(function (r) { return r.json(); }).then(function (d) {
        if (d && d.ok) { dismiss(); close(); }
        else { showError((d && d.error) || 'Could not save. Please try again.'); save.disabled = false; }
      }).catch(function () { showError('Could not save. Please try again.'); save.disabled = false; });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else { build(); }
})();

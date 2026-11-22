/**
 * Asks anyone in the back office with no phone saved to add it. It is the same
 * number as Phone (for texts) on their employee form. "Not now" hides it on
 * this browser until the next day (P-22); saving a number stops it for good.
 */
(function () {
  'use strict';
  if (typeof sbNotifyPhone === 'undefined' || !sbNotifyPhone.url) { return; }
  var DISMISS = 'sbNotifyPhoneDismissed';
  var today = new Date().toISOString().slice(0, 10);
  try { if (window.localStorage.getItem(DISMISS) === today) { return; } } catch (e) {}

  function dismiss() { try { window.localStorage.setItem(DISMISS, today); } catch (e) {} }

  function build() {
    var name = (sbNotifyPhone.name || '').toString();
    var overlay = document.createElement('div');
    overlay.className = 'sbnp-overlay';
    overlay.innerHTML =
      '<div class="sbnp-card" role="dialog" aria-modal="true" aria-labelledby="sbnp-title">' +
      '<h3 id="sbnp-title">Add your phone number</h3>' +
      '<p class="sbnp-lead"></p>' +
      '<label class="sbnp-label" for="sbnp-phone">Your mobile number</label>' +
      '<input id="sbnp-phone" type="tel" inputmode="tel" class="sbnp-input" autocomplete="tel" placeholder="024 000 0000" />' +
      '<p class="sbnp-error" hidden></p>' +
      '<div class="sbnp-actions">' +
      '<button type="button" class="sbnp-skip">Not now</button>' +
      '<button type="button" class="sbnp-save">Save number</button>' +
      '</div></div>';
    var lead = overlay.querySelector('.sbnp-lead');
    lead.textContent = (name ? name + ', p' : 'P') + (sbNotifyPhone.recipient
      ? 'lease add your mobile number. You receive text alerts for new bookings and payments.'
      : 'lease add your mobile number so the hotel can reach you, for example with booking alerts.');
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

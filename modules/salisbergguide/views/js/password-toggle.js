/**
 * Show/hide button (eye icon) for every password field, on the website and in
 * the back office. Plain JavaScript, no dependencies.
 *
 * The button is placed over the right edge of the field instead of wrapping
 * the field, so existing form layouts are left exactly as they are.
 */
(function () {
    'use strict';

    var EYE = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var EYE_OFF = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19C5 19 1 12 1 12a20.3 20.3 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a20.6 20.6 0 0 1-3.17 4.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
    var SIZE = 30;

    function place(input, button) {
        if (!input.offsetWidth) {
            button.style.display = 'none';
            return;
        }
        button.style.display = '';
        button.style.top = (input.offsetTop + Math.round((input.offsetHeight - SIZE) / 2)) + 'px';
        button.style.left = (input.offsetLeft + input.offsetWidth - SIZE - 4) + 'px';
    }

    function setState(input, button, visible) {
        input.type = visible ? 'text' : 'password';
        button.innerHTML = visible ? EYE_OFF : EYE;
        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        button.title = visible ? 'Hide password' : 'Show password';
    }

    function enhance(input) {
        if (input.getAttribute('data-sb-pw') || !input.parentNode) {
            return;
        }
        input.setAttribute('data-sb-pw', '1');

        var parent = input.parentNode;
        if (window.getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }

        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'sb-pw-toggle';
        button.tabIndex = 0;
        setState(input, button, false);
        parent.insertBefore(button, input.nextSibling);
        input.style.paddingRight = (SIZE + 10) + 'px';

        button.addEventListener('click', function (event) {
            event.preventDefault();
            setState(input, button, input.type === 'password');
            input.focus();
        });

        // Never submit or leave a password showing as plain text
        if (input.form) {
            input.form.addEventListener('submit', function () {
                setState(input, button, false);
            });
        }

        var reposition = function () { place(input, button); };
        reposition();
        window.addEventListener('resize', reposition);
        if (window.ResizeObserver) {
            new ResizeObserver(reposition).observe(input);
            new ResizeObserver(reposition).observe(parent);
        } else {
            setInterval(reposition, 800);
        }
    }

    function scan(root) {
        var inputs = (root || document).querySelectorAll('input[type="password"]:not([data-sb-pw])');
        for (var i = 0; i < inputs.length; i++) {
            enhance(inputs[i]);
        }
    }

    function start() {
        scan(document);
        // Fields added later (checkout sign-in, "Change password…" in the back office)
        if (window.MutationObserver) {
            new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes.length) {
                        scan(document);
                        return;
                    }
                }
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();

/**
 * "Add to home screen" for phones. Chrome and Edge offer the install prompt.
 * iPhone has no prompt, so the button explains Share, then Add to Home Screen.
 * The choice to hide it is remembered on that browser.
 */
(function () {
    'use strict';

    var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
    if (standalone) {
        return;
    }

    var deferred = null;

    function isDesk() {
        return !!document.querySelector('link[rel="manifest"][href*="desk-manifest"]');
    }

    function storageKey() {
        return isDesk() ? 'sb-pwa-dismiss-desk' : 'sb-pwa-dismiss';
    }

    function dismissed() {
        try {
            return localStorage.getItem(storageKey()) === '1';
        } catch (e) {
            return false;
        }
    }

    function rememberDismiss() {
        try {
            localStorage.setItem(storageKey(), '1');
        } catch (e) {}
    }

    function bar() {
        var existing = document.querySelector('.sb-pwa');
        if (existing) {
            return existing;
        }
        var node = document.createElement('div');
        node.className = 'sb-pwa';
        node.hidden = true;
        node.innerHTML = ''
            + '<p class="sb-pwa-install-text">' + (isDesk() ? 'Add the desk to your home screen.' : 'Add Salisberg to your home screen.') + '</p>'
            + '<p class="sb-pwa-ios-text">On iPhone, tap Share, then Add to Home Screen.</p>'
            + '<button type="button" class="sb-pwa-install">Add to home screen</button>'
            + '<button type="button" class="sb-pwa-dismiss" aria-label="Dismiss">Close</button>';
        document.body.appendChild(node);
        node.addEventListener('click', function (event) {
            if (event.target.closest('.sb-pwa-dismiss')) {
                node.hidden = true;
                rememberDismiss();
            }
            if (event.target.closest('.sb-pwa-install') && deferred) {
                deferred.prompt();
                deferred.userChoice.then(function () {
                    node.hidden = true;
                    deferred = null;
                });
            }
        });
        return node;
    }

    function show(mode) {
        if (dismissed()) {
            return;
        }
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', function () { show(mode); });
            return;
        }
        var node = bar();
        node.setAttribute('data-mode', mode);
        node.hidden = false;
    }

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferred = event;
        show('install');
    });

    var iphone = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    if (iphone) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { show('ios'); });
        } else {
            show('ios');
        }
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(function () {});
    }
})();

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
    var iphone = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

    function isDesk() {
        return !!document.querySelector('link[rel="manifest"][href*="desk-manifest"]');
    }

    function storageKey() {
        return isDesk() ? 'sb-pwa-dismiss-desk' : 'sb-pwa-dismiss';
    }

    // Close hides the bar for 30 days on this browser (P-22).
    var REST = 30 * 24 * 3600 * 1000;

    function dismissed() {
        try {
            if (localStorage.getItem(storageKey()) === '1') {
                rememberDismiss(); // closed before the 30-day rule: count from now
            }
            var at = parseInt(localStorage.getItem(storageKey()), 10);
            return at > 1 && Date.now() - at < REST;
        } catch (e) {
            return false;
        }
    }

    function rememberDismiss() {
        try {
            localStorage.setItem(storageKey(), String(Date.now()));
        } catch (e) {}
    }

    // Offered on one page only, so it never covers a form: the Dashboard in the
    // back office, the homepage on the website (it covered checkout on phones).
    function rightPage() {
        if (isDesk()) {
            return /[?&]controller=AdminDashboard\b/.test(window.location.search);
        }
        return document.body && document.body.id === 'index';
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
            + '<p class="sb-pwa-help-text">' + (iphone
                ? 'In Safari, tap Share, then Add to Home Screen.'
                : 'Open the browser menu (the three dots), then tap Install app or Add to Home screen.') + '</p>'
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
        if (!document.body) {
            document.addEventListener('DOMContentLoaded', function () { show(mode); });
            return;
        }
        if (dismissed() || !rightPage()) {
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

    // "Add to home screen" in the back office account menu: always there (unless
    // the app is already installed). It opens the browser's install prompt when
    // there is one, and otherwise shows the steps for this phone.
    function accountMenuItem() {
        var item = document.getElementById('sb-install-item');
        if (!item) {
            return;
        }
        item.hidden = false;
        document.getElementById('sb-install-link').addEventListener('click', function (event) {
            event.preventDefault();
            var node = bar();
            if (deferred) {
                deferred.prompt();
                deferred.userChoice.then(function () {
                    node.hidden = true;
                    deferred = null;
                });
                return;
            }
            node.setAttribute('data-mode', 'help');
            node.hidden = false;
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', accountMenuItem);
    } else {
        accountMenuItem();
    }

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

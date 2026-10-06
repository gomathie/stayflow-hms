/**
 * Light/dark switch for the back office.
 *
 * The look is defined by colour tokens in admin/themes/default/css/overrides.css;
 * this script only sets html[data-sb-theme] and remembers the choice in the
 * browser. With no saved choice it follows the device's own light/dark setting.
 * Plain JavaScript, no dependencies.
 */
(function () {
    'use strict';

    var KEY = 'sb-admin-theme';
    var MOON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';
    var SUN = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';

    function saved() {
        try {
            return window.localStorage.getItem(KEY);
        } catch (e) {
            return null;
        }
    }

    function current() {
        var value = saved();
        if (value === 'dark' || value === 'light') {
            return value;
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-sb-theme', theme);
        var button = document.getElementById('sb-theme-toggle');
        if (button) {
            var next = theme === 'dark' ? 'light' : 'dark';
            button.innerHTML = theme === 'dark' ? SUN : MOON;
            button.title = 'Switch to ' + next + ' mode';
            button.setAttribute('aria-label', button.title);
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        }
    }

    // Runs while the page head is loading, so the page never flashes the wrong theme
    apply(current());

    function addButton() {
        var box = document.getElementById('header_employee_box');
        if (!box || document.getElementById('sb-theme-toggle')) {
            return;
        }
        var item = document.createElement('li');
        var button = document.createElement('a');
        button.id = 'sb-theme-toggle';
        button.href = '#';
        button.setAttribute('role', 'button');
        item.appendChild(button);
        box.insertBefore(item, box.firstChild);

        button.addEventListener('click', function (event) {
            event.preventDefault();
            var theme = document.documentElement.getAttribute('data-sb-theme') === 'dark' ? 'light' : 'dark';
            try {
                window.localStorage.setItem(KEY, theme);
            } catch (e) {
                // private browsing: the choice lasts for this page only
            }
            apply(theme);
        });
        apply(current());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addButton);
    } else {
        addButton();
    }
})();

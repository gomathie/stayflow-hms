/**
 * Light/dark switch, for the back office and for the website.
 *
 * The look is defined by colour tokens in the stylesheets
 * (admin/themes/default/css/overrides.css for the back office,
 * themes/hotel-reservation-theme/css/salisberg.css for the website); this
 * script only sets html[data-sb-theme] and remembers the choice in the browser.
 *
 *  - Back office: with no saved choice it follows the device's own setting.
 *  - Website: light unless the visitor chooses dark. The module sets
 *    window.sbThemeScope = 'site' there (salisbergguide.php), and header.tpl
 *    applies a saved choice before the page is drawn.
 *
 * The two keep separate choices. Plain JavaScript, no dependencies.
 */
(function () {
    'use strict';

    var SITE = window.sbThemeScope === 'site';
    var KEY = SITE ? 'sb-site-theme' : 'sb-admin-theme';
    var MOON = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>';
    var SUN = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>';

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
        if (SITE) {
            return 'light';
        }
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(theme) {
        document.documentElement.setAttribute('data-sb-theme', theme);
        var button = document.getElementById('sb-theme-toggle');
        if (button) {
            var next = theme === 'dark' ? 'light' : 'dark';
            button.title = 'Switch to ' + next + ' mode';
            button.setAttribute('aria-label', button.title);
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        }
    }

    // Runs as soon as the script loads, so the page does not flash the wrong theme
    apply(current());

    /**
     * Where the switch goes: first in the account group of the back office top
     * bar, or just before the cart in the website header.
     */
    function mount(button) {
        var box = document.getElementById('header_employee_box');
        if (box) {
            var item = document.createElement('li');
            item.appendChild(button);
            box.insertBefore(item, box.firstChild);

            return true;
        }
        var menu = document.querySelector('header .header-top-menu');
        if (menu) {
            var holder = document.createElement('div');
            holder.className = 'header-top-item sb-theme-item';
            holder.appendChild(button);
            var cart = menu.querySelector('.shopping_cart');
            var before = cart ? cart.closest('.header-top-item') : null;
            menu.insertBefore(holder, before && before.parentNode === menu ? before : null);

            return true;
        }

        return false;
    }

    function addButton() {
        if (document.getElementById('sb-theme-toggle')) {
            return;
        }
        var button = document.createElement('a');
        button.id = 'sb-theme-toggle';
        button.href = '#';
        button.setAttribute('role', 'button');
        button.innerHTML = SUN + MOON;
        if (!mount(button)) {
            return;
        }

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

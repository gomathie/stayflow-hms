/**
 * Back office page title bar: when the page title and the buttons beside it
 * (Add new, Print, View invoice...) do not fit on one line, the buttons move
 * to a line of their own under the title instead of covering it.
 *
 * The title and buttons are placed by the stock theme with fixed positions,
 * so whether they collide depends on the title's length and the window width.
 * This measures them, sets the class sb-head-stacked on the bar when they
 * would overlap, lines the buttons up under the start of the title
 * (--sb-head-pad), and moves the page content down by the extra height of the
 * bar. The look is in admin/themes/default/css/overrides.css (section 6).
 * Plain JavaScript.
 */
(function () {
    'use strict';

    function textRight(title) {
        var range = document.createRange();
        range.selectNodeContents(title);
        var rects = range.getClientRects();
        var right = 0;
        for (var i = 0; i < rects.length; i++) {
            right = Math.max(right, rects[i].right);
        }
        return right;
    }

    function buttonsLeft(bar) {
        var left = Infinity;
        var items = bar.querySelectorAll('.toolbar_btn, .btn');
        for (var i = 0; i < items.length; i++) {
            var r = items[i].getBoundingClientRect();
            if (r.width > 0 && r.height > 0) {
                left = Math.min(left, r.left);
            }
        }
        return left;
    }

    // Some pages (a booking) draw two title bars in the same place, each with
    // its own buttons, so every bar is checked on its own.
    function arrange() {
        var content = document.getElementById('content');
        var heads = document.querySelectorAll('.page-head');
        if (!content || !heads.length) {
            return;
        }
        content.style.removeProperty('padding-top');
        var extra = 0;
        for (var i = 0; i < heads.length; i++) {
            var head = heads[i];
            var title = head.querySelector('h2.page-title');
            var bar = head.querySelector('.toolbarBox');
            head.classList.remove('sb-head-stacked');
            if (!title || !bar) {
                continue;
            }
            var before = head.getBoundingClientRect().height;
            if (textRight(title) + 16 <= buttonsLeft(bar)) {
                continue;
            }
            head.style.setProperty('--sb-head-pad', getComputedStyle(title).paddingLeft);
            head.classList.add('sb-head-stacked');
            extra = Math.max(extra, Math.round(head.getBoundingClientRect().height - before));
        }
        if (extra > 0) {
            content.style.paddingTop = (parseFloat(getComputedStyle(content).paddingTop) + extra) + 'px';
        }
    }

    var timer;
    function later() {
        clearTimeout(timer);
        timer = setTimeout(arrange, 120);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', arrange);
    } else {
        arrange();
    }
    window.addEventListener('load', arrange);
    window.addEventListener('resize', later);
})();

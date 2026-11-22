/**
 * "What's new" pop-up in the back office: the latest update's items for the
 * signed-in person's role, with View more (Guides > What's New) and a link to
 * their guide. The server decides when it is due (once per person per update,
 * salisbergguide::addNewsPopup) and passes sbNews. Plain JavaScript.
 */
(function () {
    'use strict';
    if (typeof sbNews === 'undefined' || !sbNews.items) {
        return;
    }

    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) { e.className = cls; }
        if (text) { e.textContent = text; }
        return e;
    }

    function show() {
        var last = document.activeElement;
        var overlay = el('div', 'sbnews-overlay bootstrap');
        var card = el('div', 'sbnews-card');
        card.setAttribute('role', 'dialog');
        card.setAttribute('aria-modal', 'true');
        card.setAttribute('aria-labelledby', 'sbnews-title');

        var head = el('div', 'sbnews-head');
        head.appendChild(el('span', 'sbnews-kicker', "What's new"));
        var title = el('h3', '', sbNews.date);
        title.id = 'sbnews-title';
        head.appendChild(title);
        var close = el('button', 'sbnews-close');
        close.type = 'button';
        close.setAttribute('aria-label', 'Close');
        close.innerHTML = '&times;';
        head.appendChild(close);
        card.appendChild(head);

        // the items are the What's New page's own markup for this role (server-rendered, escaped there)
        var list = el('ul', 'sbnews-list');
        list.innerHTML = sbNews.items;
        // at most five, the rest are on What's New
        var items = list.querySelectorAll(':scope > li');
        for (var i = 5; i < items.length; i += 1) { items[i].remove(); }
        var body = el('div', 'sbnews-body');
        body.appendChild(list);
        if (items.length > 5) {
            body.appendChild(el('p', 'sbnews-more', 'And ' + (items.length - 5) + ' more: see View more.'));
        }
        card.appendChild(body);

        var actions = el('div', 'sbnews-actions');
        if (sbNews.guide) {
            var guide = el('a', 'btn btn-default', sbNews.guideLabel || 'Open the guide');
            guide.href = sbNews.guide;
            actions.appendChild(guide);
        }
        if (sbNews.more) {
            var more = el('a', 'btn btn-primary', 'View more');
            more.href = sbNews.more;
            actions.appendChild(more);
        } else {
            var ok = el('button', 'btn btn-primary', 'Got it');
            ok.type = 'button';
            ok.addEventListener('click', hide);
            actions.appendChild(ok);
        }
        card.appendChild(actions);
        overlay.appendChild(card);
        document.body.appendChild(overlay);

        function hide() {
            overlay.remove();
            document.removeEventListener('keydown', onKey);
            if (last && last.focus) { last.focus(); }
        }
        function onKey(e) { if (e.key === 'Escape') { hide(); } }
        close.addEventListener('click', hide);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { hide(); } });
        document.addEventListener('keydown', onKey);
        close.focus();
    }

    // one box at a time: wait while the phone-number prompt is open
    function showWhenFree() {
        if (document.querySelector('.sbnp-overlay')) {
            setTimeout(showWhenFree, 700);
            return;
        }
        show();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { setTimeout(showWhenFree, 300); });
    } else {
        setTimeout(showWhenFree, 300);
    }
}());

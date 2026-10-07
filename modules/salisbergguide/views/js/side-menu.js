/**
 * Back office side menu: a small arrow beside every section that has pages
 * under it. Clicking the arrow lists those pages under the section; clicking
 * it again folds them away. Clicking the section's name still opens its first
 * page, as before.
 *
 * This replaces the stock behaviour of showing the pages in a box beside the
 * menu while the pointer rests on a section. The look is in
 * admin/themes/default/css/overrides.css (section 5). Plain JavaScript.
 */
(function () {
    'use strict';

    function setState(section, arrow, open) {
        section.classList.toggle('sb-open', open);
        section.classList.toggle('sb-closed', !open);
        arrow.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function addArrows() {
        var sections = document.querySelectorAll('#nav-sidebar ul.menu > li.maintab.has_submenu');
        Array.prototype.forEach.call(sections, function (section) {
            if (section.querySelector('.sb-menu-arrow')) {
                return;
            }
            var title = section.querySelector('a.title');
            var list = section.querySelector('ul.submenu');
            if (!title || !list) {
                return;
            }
            var name = (title.textContent || '').replace(/\s+/g, ' ').trim();
            var arrow = document.createElement('button');
            arrow.type = 'button';
            arrow.className = 'sb-menu-arrow';
            arrow.setAttribute('aria-label', 'Show or hide the pages under ' + name);
            section.insertBefore(arrow, list);

            // the section you are in starts open; the others start folded
            setState(section, arrow, section.classList.contains('active'));

            arrow.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                setState(section, arrow, !section.classList.contains('sb-open'));
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addArrows);
    } else {
        addArrows();
    }
})();

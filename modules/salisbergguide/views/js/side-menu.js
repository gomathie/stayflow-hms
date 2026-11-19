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

    /*
     * Group headings (SIMPLIFY-REVIEW P-8): the sections are put in group
     * order and a small heading is placed above the first section of each
     * group the signed-in person can see. A group with none of its sections
     * visible gets no heading, so each role sees only its own groups.
     */
    var GROUPS = [
        { label: 'Front desk', tabs: ['AdminDashboard', 'AdminParentOrders', 'AdminParentCustomer'] },
        { label: 'Hotel', tabs: ['AdminCatalog', 'AdminHotelReservationSystemManagement', 'AdminPriceRule'] },
        { label: 'System', tabs: ['AdminParentModules', 'AdminParentLocalization', 'AdminParentPreferences', 'AdminTools', 'AdminAdmin'] },
        { label: 'Help', tabs: ['AdminSalisbergGuide'] }
    ];

    function addGroups() {
        var menu = document.querySelector('#nav-sidebar ul.menu');
        if (!menu || menu.querySelector('.sb-menu-group')) {
            return;
        }
        var placed = {};
        GROUPS.forEach(function (group) {
            var heading = null;
            group.tabs.forEach(function (tab) {
                var item = document.getElementById('maintab-' + tab);
                if (!item || item.parentNode !== menu) {
                    return;
                }
                if (!heading) {
                    heading = document.createElement('li');
                    heading.className = 'sb-menu-group';
                    heading.setAttribute('role', 'presentation');
                    heading.textContent = group.label;
                    menu.appendChild(heading);
                }
                menu.appendChild(item);
                placed[tab] = true;
            });
        });
        foldSystem(menu);
        // anything not in a group (a new module's section) stays visible, at the end
        Array.prototype.forEach.call(menu.querySelectorAll(':scope > li.maintab'), function (item) {
            if (!placed[item.id.replace('maintab-', '')]) {
                menu.appendChild(item);
            }
        });
    }

    /*
     * SIMPLIFY-REVIEW P-9: the technical sections sit under one "System"
     * entry, closed by default and opened while you are on one of its pages.
     * Only the sections the person may open are in it, so a manager sees
     * their few pages there and staff, who have none, see no System entry.
     * Permissions are unchanged; this is the menu's layout only.
     */
    function foldSystem(menu) {
        var system = null;
        GROUPS.forEach(function (g) { if (g.label === 'System') { system = g; } });
        var items = [];
        system.tabs.forEach(function (tab) {
            var item = document.getElementById('maintab-' + tab);
            if (item && item.parentNode === menu) {
                items.push(item);
            }
        });
        if (!items.length) {
            return;
        }
        var heading = items[0].previousElementSibling;
        if (!heading || !heading.classList.contains('sb-menu-group')) {
            return;
        }
        var open = items.some(function (item) { return item.classList.contains('active'); });
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'sb-system-toggle';
        toggle.textContent = 'System';
        heading.textContent = '';
        heading.classList.add('sb-system-group');
        heading.removeAttribute('role');
        heading.appendChild(toggle);
        function apply() {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            heading.classList.toggle('sb-open', open);
            // a class, not hidden: on phones the icon rail has no headings, so the sections stay
            items.forEach(function (item) { item.classList.toggle('sb-system-folded', !open); });
        }
        toggle.addEventListener('click', function () { open = !open; apply(); });
        apply();
    }

    function init() {
        addGroups();
        addArrows();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

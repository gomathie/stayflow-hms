/**
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License version 3.0
* that is bundled with this package in the file LICENSE.md
* It is also available through the world-wide-web at this URL:
* https://opensource.org/license/osl-3-0-php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to support@qloapps.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to https://store.webkul.com/customisation-guidelines for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/license/osl-3-0-php Open Software License version 3.0
 */

/*
 * Salisberg: tonight's rooms as one bar in three parts (in house, free, out of
 * service), replacing the donut (owner, 2026-11-19). Colours come from the
 * back office theme, --sb-occ-booked, --sb-occ-free and --sb-occ-blocked in
 * overrides.css, validated for light and dark. Numbers and percentages sit in
 * the legend, so nothing is clipped at any width.
 */
function sb_occupancy_bar(widget_name, chart_details) {
    var bar = document.getElementById('sbOccBar');
    var legend = document.getElementById('sbOccLegend');
    if (!bar || !legend) {
        return;
    }
    bar.innerHTML = '';
    legend.innerHTML = '';
    (chart_details.data || []).forEach(function (part) {
        var seg = document.createElement('span');
        seg.className = 'sb-occ-seg sb-occ-seg-' + part.key;
        seg.style.width = (part.percent || 0) + '%';
        seg.title = part.label + ': ' + part.value + ' (' + part.percent + '%)';
        bar.appendChild(seg);

        var item = document.createElement('li');
        var dot = document.createElement('i');
        dot.className = 'sb-occ-dot sb-occ-seg-' + part.key;
        var label = document.createElement('span');
        label.textContent = part.label;
        var value = document.createElement('strong');
        value.textContent = part.value + ' · ' + part.percent + '%';
        item.appendChild(dot);
        item.appendChild(label);
        item.appendChild(value);
        legend.appendChild(item);
    });
}

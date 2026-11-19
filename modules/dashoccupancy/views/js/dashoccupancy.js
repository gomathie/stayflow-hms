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

function pie_chart_occupancy(widget_name, chart_details) {
    $('#availablePieChart svg').html('');
    occupacygraph = nv.addGraph(function() {
        var chart = nv.models.pieChart()
            .x(function(d) { return d.label })
            .y(function(d) { return d.value })
            .showLabels(true)
            .showLegend(false)
            .labelThreshold(.01)
            .labelType('percent')
            .donut(true)
            .donutRatio(0.35)
            .color(sbOccupancyColours());

        // create content for the tooltip of chart
        chart.tooltip.contentGenerator((obj, element) => {
            var tooltipContent = '<p><b>' + obj.data.value + '</b> (' + obj.data.percent + '%)<p>';
            return getTooltipContent(obj.data.label, tooltipContent, obj.color);
        });

        d3.select('#availablePieChart svg')
            .datum(chart_details.data)
            .transition().duration(350)
            .call(chart);
        nv.utils.windowResize(chart.update);

        return chart;
    });
}

$(document).ready(function() {
    $('#date-start').change(function() {
        start = Date.parseDate($('#date-start').val(), 'Y-m-d');
        end = Date.parseDate($('#date-end').val(), 'Y-m-d');

        if (end.getDate() == start.getDate()) {
            end.setDate(end.getDate() + 1);
        }

        $('#dashoccupancy_date_range').html(sprintf(date_subtitle, start.format(date_format), end.format(date_format)));
    });

    $('#date-end').change(function() {
        start = Date.parseDate($('#date-start').val(), 'Y-m-d');
        end = Date.parseDate($('#date-end').val(), 'Y-m-d');

        if (end.getDate() == start.getDate()) {
            end.setDate(end.getDate() + 1);
        }

        $('#dashoccupancy_date_range').html(sprintf(date_subtitle, start.format(date_format), end.format(date_format)));
    });
});

/* Donut colours come from the back office theme (light or dark), set as
   --sb-occ-booked, --sb-occ-free and --sb-occ-blocked in overrides.css. */
function sbOccupancyColours() {
    var css = window.getComputedStyle(document.documentElement);
    var pick = function (name, fallback) {
        var value = css.getPropertyValue(name);
        return value && value.trim() ? value.trim() : fallback;
    };
    return [pick('--sb-occ-booked', '#0f8a60'), pick('--sb-occ-free', '#cf931f'), pick('--sb-occ-blocked', '#4a7fc0')];
}

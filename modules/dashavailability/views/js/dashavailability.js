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

var dashavailability_data;
var dashavailability_chart;

// Colour of "free rooms", shared with the Occupancy panel (--sb-occ-free).
function sbAvailColour() {
    var c = getComputedStyle(document.documentElement).getPropertyValue('--sb-occ-free');
    return $.trim(c) || '#cf931f';
}

function sbAvailDay(ts) {
    var d = new Date(ts * 1000);
    var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return days[d.getDay()] + ' ' + d.getDate() + ' ' + months[d.getMonth()];
}

function line_chart_availability(widget_name, chart_details) {
    var colour = sbAvailColour();
    $.each(chart_details.data, function (i, series) {
        series.color = colour;
        series.area = true;
    });

    nv.addGraph(function() {
        var chart = nv.models.lineChart()
            .useInteractiveGuideline(true)
            .showLegend(false)
            .x(function(d) { return (d !== undefined ? d[0] : 0); })
            .y(function(d) { return (d !== undefined ? parseInt(d[1], 10) : 0); })
            .forceY([0])
            .margin({ top: 16, left: 40, right: 40, bottom: 36 });

        // One date label per ~90px of chart, at most 7, so they never collide
        chart.xAxis
            .tickFormat(function(d) { return sbAvailDay(d); })
            .tickValues(function(values) {
                var points = values[0].values;
                var room = Math.max(2, Math.min(7, Math.floor(($("#availability_line_chart1").width() || 600) / 90)));
                var step = Math.max(1, Math.ceil(points.length / room));
                var ticks = [];
                for (var i = step; i < points.length - 1; i += step) {
                    ticks.push(points[i][0]);
                }
                return ticks;
            });

        chart.yAxis.tickFormat(function(d) {
            return Number.isInteger(d) ? d : '';
        });

        chart.interactiveLayer.tooltip.contentGenerator(function(obj) {
            if (typeof obj.series[0] === 'undefined') {
                return '';
            }
            var content = '<p><b>' + sbAvailDay(obj.value) + '</b></p>'
                + '<p>' + avail_rooms_txt + ': <b>' + obj.series[0].value + '</b></p>';
            return getTooltipContent(obj.series[0].key, content, obj.series[0].color);
        });

        dashavailability_data = chart_details.data;
        dashavailability_chart = chart;
        d3.select('#availability_line_chart1 svg')
            .datum(dashavailability_data)
            .call(chart);
        nv.utils.windowResize(chart.update);

        return chart;
    });
}

function refreshAvailabilityBarData() {
    var days = parseInt($('#dashavailability .avail-bar-btn.bar-btn-active').attr('data-days'), 10) || 7;
    fetchAvailablityBarData(days, $('#bardate').val());
}

function fetchAvailablityBarData(days, date_from) {
    refreshDashboard('dashavailability', true, JSON.stringify({
        date_from: date_from || $('#bardate').val(),
        days: days
    }));
}

function availDatePicker() {
    $('#bardate').datepicker('show');
}

$(document).on('click', '#dashavailability .avail-bar-btn', function() {
    $('#dashavailability .avail-bar-btn').removeClass('bar-btn-active').attr('aria-pressed', 'false');
    $(this).addClass('bar-btn-active').attr('aria-pressed', 'true');
    refreshAvailabilityBarData();
});

$(document).ready(function() {
    $('#bardate').datepicker({
        dateFormat: 'yy-mm-dd',
        beforeShow: function(input, inst) {
            setTimeout(function() {
                var btn = $('#avail_datepicker');
                inst.dpDiv.css({ top: btn.offset().top + btn.outerHeight() + 4, left: btn.offset().left });
            }, 0);
        },
        onSelect: function(date) {
            var p = date.split('-');
            $('#dashavailability .bar-date').text(sbAvailDay(new Date(p[0], p[1] - 1, p[2]).getTime() / 1000).replace(/^\w+ /, '') + ' ' + p[0]);
            refreshAvailabilityBarData();
        }
    });
});

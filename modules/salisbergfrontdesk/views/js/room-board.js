/* Copyright (c) Salisberg. SPDX-License-Identifier: MIT */
(function () {
    'use strict';

    function byId(id) {
        return document.getElementById(id);
    }

    function dateAtMidnight(value) {
        return value ? new Date(value + 'T00:00:00') : null;
    }

    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function syncNightsFromDates() {
        var arrival = dateAtMidnight(byId('sb-arrival').value);
        var departure = dateAtMidnight(byId('sb-departure').value);
        if (arrival && departure && departure > arrival) {
            byId('sb-nights').value = Math.round((departure - arrival) / 86400000);
        }
    }

    function syncDepartureFromNights() {
        var arrival = dateAtMidnight(byId('sb-arrival').value);
        var nights = Math.max(1, parseInt(byId('sb-nights').value, 10) || 1);
        if (arrival) {
            arrival.setDate(arrival.getDate() + nights);
            byId('sb-departure').value = formatDate(arrival);
        }
    }

    function selectRoom(card) {
        var cards = document.querySelectorAll('.sb-room-card');
        var status = byId('sb-selected-status');
        var image = byId('sb-selected-image');
        var available = card.getAttribute('data-available') === '1';
        var panel = byId('sb-selected-room');
        var warning = byId('sb-selection-warning');
        var button = byId('sb-book-room');
        var i;

        for (i = 0; i < cards.length; i += 1) {
            cards[i].classList.remove('is-selected');
        }
        card.classList.add('is-selected');
        byId('sb-selected-room-id').value = card.getAttribute('data-room-id');
        byId('sb-selected-number').textContent = card.getAttribute('data-room-num');
        byId('sb-selected-type').textContent = card.getAttribute('data-room-type');
        byId('sb-selected-capacity').textContent = card.getAttribute('data-capacity');
        byId('sb-selected-rate').textContent = card.getAttribute('data-rate');
        byId('sb-selected-nightly').textContent = card.getAttribute('data-nightly');
        byId('sb-selected-taxes').textContent = card.getAttribute('data-taxes');
        byId('sb-selected-total').textContent = card.getAttribute('data-total');
        status.className = 'sb-status sb-status-' + card.getAttribute('data-ready-code');
        status.innerHTML = '<i></i>';
        status.appendChild(document.createTextNode(card.getAttribute('data-ready-label')));
        if (card.getAttribute('data-image')) {
            image.src = card.getAttribute('data-image');
            image.hidden = false;
        } else {
            image.hidden = true;
        }
        panel.hidden = false;
        if (button) {
            button.disabled = !available;
        }
        warning.hidden = available;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var arrival = byId('sb-arrival');
        var departure = byId('sb-departure');
        var nights = byId('sb-nights');
        var cards = document.querySelectorAll('.sb-room-card');
        var i;
        if (arrival && departure && nights) {
            arrival.addEventListener('change', function () {
                if (departure.value <= arrival.value) {
                    syncDepartureFromNights();
                } else {
                    syncNightsFromDates();
                }
                departure.min = formatDate(new Date(dateAtMidnight(arrival.value).getTime() + 86400000));
            });
            departure.addEventListener('change', syncNightsFromDates);
            nights.addEventListener('change', syncDepartureFromNights);
        }
        for (i = 0; i < cards.length; i += 1) {
            cards[i].addEventListener('click', function () {
                selectRoom(this);
            });
        }
    });
}());

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
        var nightly = byId('sb-selected-nightly');
        var nightlyLabel = byId('sb-selected-nightly-label');
        var available = card.getAttribute('data-available') === '1';
        var panel = byId('sb-selected-room');
        var warning = byId('sb-selection-warning');
        var button = byId('sb-book-room');
        var mobileButton = byId('sb-mobile-book-room');
        var mobileBar = byId('sb-mobile-booking');
        var i;

        if (!available) {
            return;
        }

        for (i = 0; i < cards.length; i += 1) {
            cards[i].classList.remove('is-selected');
            cards[i].setAttribute('aria-pressed', 'false');
        }
        card.classList.add('is-selected');
        card.setAttribute('aria-pressed', 'true');
        byId('sb-selected-room-id').value = card.getAttribute('data-room-id');
        byId('sb-selected-number').textContent = card.getAttribute('data-room-num');
        byId('sb-selected-type').textContent = card.getAttribute('data-room-type');
        byId('sb-selected-capacity').textContent = card.getAttribute('data-capacity');
        byId('sb-selected-rate').textContent = card.getAttribute('data-rate');
        nightly.textContent = card.getAttribute('data-nightly');
        nightly.hidden = card.getAttribute('data-rate-varies') !== '1';
        nightlyLabel.hidden = nightly.hidden;
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
        if (mobileButton) {
            mobileButton.disabled = !available;
        }
        if (mobileBar) {
            mobileBar.hidden = false;
            byId('sb-mobile-room').textContent = 'Room ' + card.getAttribute('data-room-num');
            byId('sb-mobile-total').textContent = card.getAttribute('data-total') + ' total';
        }
        warning.hidden = available;
    }

    function setupRoomTools() {
        var board = document.querySelector('.sb-room-board');
        var cards = Array.prototype.slice.call(document.querySelectorAll('.sb-room-card'));
        var groups = Array.prototype.slice.call(document.querySelectorAll('.sb-room-group'));
        var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-room-filter]'));
        var search = byId('sb-room-search');
        var compact = byId('sb-compact-rooms');
        var empty = byId('sb-filter-empty');
        var filter = 'available';

        if (!board || !search || !compact) {
            return;
        }

        function matches(card) {
            var term = search.value.trim().toLowerCase();
            var statusMatch = filter === 'all'
                || (filter === 'available' && card.getAttribute('data-available') === '1')
                || (filter === 'ready' && card.getAttribute('data-available') === '1' && card.getAttribute('data-ready') === '1')
                || (filter === 'cleaning' && card.getAttribute('data-available') === '1' && card.getAttribute('data-ready-code') === 'cleaning');
            var searchMatch = !term || card.getAttribute('data-room-search').toLowerCase().indexOf(term) !== -1;

            return statusMatch && searchMatch;
        }

        function applyFilters() {
            var visible = 0;
            cards.forEach(function (card) {
                card.hidden = !matches(card);
                if (!card.hidden) {
                    visible += 1;
                }
            });
            groups.forEach(function (group) {
                var groupCards = Array.prototype.slice.call(group.querySelectorAll('.sb-room-card'));
                var groupVisible = groupCards.filter(function (card) {
                    return !card.hidden;
                }).length;
                var count = group.querySelector('[data-room-count]');

                group.hidden = groupVisible === 0;
                if (count) {
                    count.querySelector('strong').textContent = groupVisible;
                    count.querySelector('span').textContent = groupVisible === 1
                        ? count.getAttribute('data-singular')
                        : count.getAttribute('data-plural');
                }
            });
            if (empty) {
                empty.hidden = visible !== 0;
            }
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                filter = button.getAttribute('data-room-filter');
                buttons.forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });
                applyFilters();
            });
        });
        search.addEventListener('input', applyFilters);
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                var first = document.querySelector('.sb-room-card:not([hidden])');
                if (first) {
                    selectRoom(first);
                }
            }
        });

        try {
            compact.checked = window.localStorage.getItem('sb-room-board-compact') !== '0';
        } catch (ignore) {
            compact.checked = true;
        }
        function applyDensity() {
            board.classList.toggle('is-compact', compact.checked);
            try {
                window.localStorage.setItem('sb-room-board-compact', compact.checked ? '1' : '0');
            } catch (ignore) {
                // The view still works when storage is blocked.
            }
        }
        compact.addEventListener('change', applyDensity);
        applyDensity();
        applyFilters();
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
        setupRoomTools();
    });
}());

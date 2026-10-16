/* Copyright (c) Salisberg. SPDX-License-Identifier: MIT */
(function () {
    'use strict';

    var currentGuests = [];

    function byId(id) {
        return document.getElementById(id);
    }

    function dateAtMidnight(value) {
        return value ? new Date(value + 'T00:00:00') : null;
    }

    function formatDate(date) {
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    }

    function setGuest(guest) {
        var duplicate = document.querySelector('.sb-duplicate-guest');
        byId('sb-guest-id').value = guest.id_customer;
        byId('sb-selected-guest-name').textContent = guest.firstname + ' ' + guest.lastname;
        byId('sb-selected-guest-contact').textContent = [guest.phone, guest.email].filter(Boolean).join(' · ');
        byId('sb-selected-guest').hidden = false;
        byId('sb-new-guest').hidden = true;
        byId('sb-guest-results').innerHTML = '';
        currentGuests = [];
        if (duplicate) {
            duplicate.hidden = true;
        }
    }

    function renderGuests(rows) {
        var target = byId('sb-guest-results');
        currentGuests = rows;
        target.innerHTML = '';
        if (!rows.length) {
            var empty = document.createElement('p');
            empty.className = 'sb-search-empty';
            empty.textContent = 'No matching guests found.';
            target.appendChild(empty);
            return;
        }
        rows.forEach(function (guest) {
            var row = document.createElement('div');
            var copy = document.createElement('div');
            var name = document.createElement('strong');
            var contact = document.createElement('span');
            var button = document.createElement('button');
            row.className = 'sb-guest-result';
            name.textContent = guest.firstname + ' ' + guest.lastname;
            contact.textContent = [guest.phone, guest.email, guest.company].filter(Boolean).join(' · ');
            copy.appendChild(name);
            copy.appendChild(contact);
            button.type = 'button';
            button.className = 'btn btn-default';
            button.textContent = 'Use guest';
            button.addEventListener('click', function () {
                setGuest(guest);
            });
            row.appendChild(copy);
            row.appendChild(button);
            target.appendChild(row);
        });
    }

    function setupGuestSearch() {
        var root = document.querySelector('.sb-walkin');
        var input = byId('sb-guest-search');
        var timer;
        if (!root || !input) {
            return;
        }
        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            if (input.value.trim().length < 2) {
                byId('sb-guest-results').innerHTML = '';
                currentGuests = [];
                return;
            }
            timer = window.setTimeout(function () {
                var query = input.value.trim();
                var body = new URLSearchParams();
                body.append('q', query);
                fetch(root.getAttribute('data-search-url'), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: body.toString()
                }).then(function (response) {
                    return response.json();
                }).then(function (data) {
                    if (input.value.trim() === query) {
                        renderGuests(data.guests || []);
                    }
                }).catch(function () {
                    byId('sb-guest-results').textContent = 'Guest search could not be loaded. Try again.';
                });
            }, 250);
        });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && currentGuests.length) {
                event.preventDefault();
                setGuest(currentGuests[0]);
            }
        });
        var change = byId('sb-change-guest');
        if (change) {
            change.addEventListener('click', function () {
                byId('sb-guest-id').value = '';
                byId('sb-selected-guest').hidden = true;
                byId('sb-new-guest').hidden = false;
                ['guest-firstname', 'guest-lastname', 'guest-phone', 'guest-email', 'guest-company'].forEach(function (id) {
                    var field = byId(id);
                    if (field) {
                        field.value = '';
                    }
                });
                input.focus();
            });
        }
        document.querySelectorAll('[data-use-guest]').forEach(function (button) {
            button.addEventListener('click', function () {
                setGuest({
                    id_customer: button.getAttribute('data-id'),
                    firstname: button.getAttribute('data-firstname'),
                    lastname: button.getAttribute('data-lastname'),
                    phone: button.getAttribute('data-phone'),
                    email: button.getAttribute('data-email')
                });
            });
        });
        if (!byId('sb-guest-id').value) {
            input.focus();
        }
    }

    // A click anywhere in a date box opens the calendar, not only the small icon at
    // its right edge (the browser already handles the icon itself).
    function openCalendarOnClick(field) {
        if (typeof field.showPicker !== 'function') {
            return;
        }
        field.addEventListener('click', function (event) {
            if (event.clientX > field.getBoundingClientRect().right - 34) {
                return;
            }
            try {
                field.showPicker();
            } catch (e) {
                // already open or refused by the browser: nothing to do
            }
        });
    }

    function setupStayDates() {
        var arrival = byId('walkin-arrival');
        var departure = byId('walkin-departure');
        var nights = byId('walkin-nights');
        if (!arrival || !departure || !nights) {
            return;
        }
        function datesToNights() {
            var start = dateAtMidnight(arrival.value);
            var end = dateAtMidnight(departure.value);
            if (start && end && end > start) {
                nights.value = Math.round((end - start) / 86400000);
            }
        }
        function nightsToDeparture() {
            var start = dateAtMidnight(arrival.value);
            var length = Math.max(1, parseInt(nights.value, 10) || 1);
            if (start) {
                start.setDate(start.getDate() + length);
                departure.value = formatDate(start);
            }
        }
        openCalendarOnClick(arrival);
        openCalendarOnClick(departure);
        arrival.addEventListener('change', function () {
            if (departure.value <= arrival.value) {
                nightsToDeparture();
            } else {
                datesToNights();
            }
        });
        departure.addEventListener('change', datesToNights);
        nights.addEventListener('change', nightsToDeparture);
    }

    function setupPayment() {
        var form = byId('sb-payment-step');
        if (!form) {
            return;
        }
        var partial = byId('sb-partial-field');
        var methodField = byId('sb-payment-method-field');
        var tendered = byId('sb-tendered-field');
        var reference = byId('sb-reference-field');
        var confirmed = byId('sb-confirmed-field');
        var confirmedInput = confirmed.querySelector('input');
        var amount = byId('payment-amount');
        var cashTendered = byId('cash-tendered');
        var tenderedTouched = cashTendered.value !== '' && parseFloat(cashTendered.value || '0') > 0;
        function selected(name) {
            var field = form.querySelector('input[name="' + name + '"]:checked');
            return field ? field.value : '';
        }
        function refresh() {
            var method = selected('method');
            var arrangement = selected('arrangement');
            var isLater = arrangement === 'later';
            methodField.hidden = isLater;
            methodField.querySelectorAll('input').forEach(function (input) {
                input.disabled = isLater;
            });
            partial.hidden = arrangement !== 'partial';
            tendered.hidden = method !== 'cash' || isLater;
            reference.hidden = method === 'cash' || isLater;
            confirmed.hidden = isLater;
            confirmedInput.disabled = isLater;
            byId('sb-confirmed-label').textContent = method === 'cash'
                ? 'Cash received and verified'
                : 'Payment received and verified';
            if (arrangement === 'full') {
                amount.value = form.getAttribute('data-total');
            }
            if (method === 'cash' && !isLater && !tenderedTouched) {
                cashTendered.value = arrangement === 'full' ? form.getAttribute('data-total') : amount.value;
            }
            updateChange();
        }
        function updateChange() {
            var applied = selected('arrangement') === 'full'
                ? parseFloat(form.getAttribute('data-total'))
                : parseFloat(amount.value || '0');
            var given = parseFloat(cashTendered.value || '0');
            byId('sb-change-due').textContent = given >= applied && applied > 0
                ? 'Change due: ' + (given - applied).toFixed(2)
                : '';
        }
        form.addEventListener('change', refresh);
        amount.addEventListener('input', function () {
            if (selected('method') === 'cash' && selected('arrangement') === 'partial' && !tenderedTouched) {
                cashTendered.value = amount.value;
            }
            updateChange();
        });
        cashTendered.addEventListener('input', function () {
            tenderedTouched = true;
            updateChange();
        });
        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        setupStayDates();
        setupGuestSearch();
        setupPayment();
        document.querySelectorAll('.sb-step-nav .is-disabled').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
            });
        });
        var finalForm = byId('sb-finalize-form');
        if (finalForm) {
            var lastSubmitter = null;
            finalForm.addEventListener('click', function (event) {
                var button = event.target.closest('button[type="submit"]');
                if (button && finalForm.contains(button)) {
                    lastSubmitter = button;
                }
            });
            finalForm.addEventListener('submit', function (event) {
                var submitter = event.submitter || lastSubmitter;
                if (submitter && submitter.name) {
                    var submittedAction = document.createElement('input');
                    submittedAction.type = 'hidden';
                    submittedAction.name = submitter.name;
                    submittedAction.value = submitter.value;
                    finalForm.appendChild(submittedAction);
                }
                finalForm.querySelectorAll('button[type="submit"]').forEach(function (button) {
                    button.disabled = true;
                });
            });
        }
    });
}());

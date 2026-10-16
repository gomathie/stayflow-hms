$(document).on('click', 'a.delete', function (event) {
    var bookingIdMatch = this.href.match(/[?&]id_order=(\d+)/);
    if (!bookingIdMatch) {
        return;
    }

    event.preventDefault();
    var bookingId = bookingIdMatch[1];
    if (!window.confirm('Move booking #' + bookingId + ' to the recycle bin?')) {
        return;
    }

    var reason = window.prompt('Why is this booking being deleted?');
    if (reason === null) {
        return;
    }
    reason = reason.trim();
    if (!reason) {
        window.alert('Enter a reason before deleting the booking.');
        return;
    }
    if (reason.length > 1000) {
        window.alert('The reason must be no more than 1000 characters.');
        return;
    }

    var form = document.createElement('form');
    form.method = 'post';
    form.action = window.location.href;
    [
        ['deleteorder', '1'],
        ['id_order', bookingId],
        ['sb_delete_reason', reason]
    ].forEach(function (field) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = field[0];
        input.value = field[1];
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
});

// Guard against a double click creating the booking twice
$(document).ready(function () {
    $(document).on('click', '.confirm_order', function (e) {
        e.preventDefault();
        $(this).attr('disabled', 'disabled');
        $(this).closest('form').get(0).submit();
    });
});

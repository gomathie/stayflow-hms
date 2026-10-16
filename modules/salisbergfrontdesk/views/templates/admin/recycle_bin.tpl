{*
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 *}
<div class="panel sb-recycle-bin">
    <div class="panel-heading">
        <i class="icon-trash"></i>
        {l s='Deleted bookings' mod='salisbergfrontdesk'}
    </div>
    <p class="help-block">
        {l s='Archived bookings are removed from active bookings and room availability. Their full booking value remains included in the totals below.' mod='salisbergfrontdesk'}
    </p>

    {if $sb_deleted_totals}
        <div class="sb-recycle-totals">
            {foreach from=$sb_deleted_totals item=total}
                <div class="sb-recycle-total">
                    <span>{$total.booking_count|intval} {l s='bookings' mod='salisbergfrontdesk'}</span>
                    <strong>{$total.booking_total_display|escape:'html':'UTF-8'}</strong>
                </div>
            {/foreach}
        </div>
    {/if}

    {if $sb_deleted_bookings}
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{l s='Booking' mod='salisbergfrontdesk'}</th>
                        <th>{l s='Guest' mod='salisbergfrontdesk'}</th>
                        <th>{l s='Booking value' mod='salisbergfrontdesk'}</th>
                        <th>{l s='Deleted' mod='salisbergfrontdesk'}</th>
                        <th>{l s='Deleted by' mod='salisbergfrontdesk'}</th>
                        <th>{l s='Reason' mod='salisbergfrontdesk'}</th>
                        {if $sb_can_restore}<th>{l s='Action' mod='salisbergfrontdesk'}</th>{/if}
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$sb_deleted_bookings item=booking}
                        <tr>
                            <td><strong>{$booking.reference|escape:'html':'UTF-8'}</strong></td>
                            <td>
                                <strong>{$booking.guest_name|escape:'html':'UTF-8'}</strong>
                                {if $booking.guest_phone}<br><a href="tel:{$booking.guest_phone|escape:'html':'UTF-8'}">{$booking.guest_phone|escape:'html':'UTF-8'}</a>{/if}
                                {if $booking.guest_email}<br><a href="mailto:{$booking.guest_email|escape:'html':'UTF-8'}">{$booking.guest_email|escape:'html':'UTF-8'}</a>{/if}
                            </td>
                            <td>{$booking.booking_total_display|escape:'html':'UTF-8'}</td>
                            <td>{$booking.deleted_at_display|escape:'html':'UTF-8'}</td>
                            <td>{$booking.employee_name|escape:'html':'UTF-8'}</td>
                            <td>{$booking.reason|escape:'html':'UTF-8'}</td>
                            {if $sb_can_restore}
                                <td>
                                    <form method="post" action="{$currentIndex|escape:'html':'UTF-8'}&amp;token={$sb_recycle_bin_token|escape:'html':'UTF-8'}">
                                        <input type="hidden" name="id_order" value="{$booking.id_order|intval}">
                                        <button type="submit" name="sb_restore_booking" value="1" class="btn btn-default">
                                            <i class="icon-undo"></i>
                                            {l s='Restore' mod='salisbergfrontdesk'}
                                        </button>
                                    </form>
                                </td>
                            {/if}
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {else}
        <div class="alert alert-info">{l s='There are no deleted bookings.' mod='salisbergfrontdesk'}</div>
    {/if}
</div>

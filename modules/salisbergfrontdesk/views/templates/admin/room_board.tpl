{*
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 *}
<div class="sb-frontdesk sb-room-board">
    <div class="sb-fd-heading">
        <div>
            <h2>{l s='Front desk' mod='salisbergfrontdesk'}</h2>
            <p>{l s='Room board' mod='salisbergfrontdesk'}</p>
        </div>
        <div class="sb-fd-links">
            <a class="btn btn-default" href="{$sb_today_link|escape:'html':'UTF-8'}">{l s='Today' mod='salisbergfrontdesk'}</a>
            <a class="btn btn-default" href="{$sb_housekeeping_link|escape:'html':'UTF-8'}">{l s='Housekeeping' mod='salisbergfrontdesk'}</a>
        </div>
    </div>

    {if $sb_active_drafts}
        <section class="panel sb-draft-panel">
            <div class="sb-draft-panel-heading">
                <div><h3>{l s='Unfinished bookings' mod='salisbergfrontdesk'}</h3><p>{l s='Resume work already started or discard it to release the room.' mod='salisbergfrontdesk'}</p></div>
                <span class="sb-chip">{count($sb_active_drafts)|intval}</span>
            </div>
            <div class="sb-draft-list">
                {foreach from=$sb_active_drafts item=draft}
                    <article>
                        <div><strong>{$draft.room_num|escape:'html':'UTF-8'}{if $draft.room_type} · {$draft.room_type|escape:'html':'UTF-8'}{/if}</strong><span>{$draft.arrival|escape:'html':'UTF-8'} – {$draft.departure|escape:'html':'UTF-8'} · {$draft.guest|escape:'html':'UTF-8'} · {$draft.stage|escape:'html':'UTF-8'}</span></div>
                        <div class="sb-draft-actions">
                            <a class="btn btn-default" href="{$sb_walkin_link|escape:'html':'UTF-8'}&amp;id_walkin_draft={$draft.id_walkin_draft|intval}">{l s='Resume' mod='salisbergfrontdesk'}</a>
                            <form method="post" action="{$sb_action|escape:'html':'UTF-8'}" onsubmit="return confirm('{l s='Discard this unfinished booking and release its room?' mod='salisbergfrontdesk' js=1}');">
                                <input type="hidden" name="id_walkin_draft" value="{$draft.id_walkin_draft|intval}">
                                <button type="submit" name="sb_discard_draft" value="1" class="btn btn-default">{l s='Discard' mod='salisbergfrontdesk'}</button>
                            </form>
                        </div>
                    </article>
                {/foreach}
            </div>
        </section>
    {/if}

    <form method="post" action="{$sb_action|escape:'html':'UTF-8'}" id="sb-room-board-form">
        <input type="hidden" name="id_hotel" value="{$sb_stay.id_hotel|intval}">
        <input type="hidden" name="id_room" id="sb-selected-room-id" value="{if $sb_selected_room}{$sb_selected_room.id_room|intval}{/if}">
        <div class="panel sb-board-filters">
            <div class="sb-filter-field{if isset($sb_field_errors.arrival)} has-error{/if}">
                <label for="sb-arrival">{l s='Arrival' mod='salisbergfrontdesk'}</label>
                <input type="date" id="sb-arrival" name="arrival" value="{$sb_stay.arrival|escape:'html':'UTF-8'}" min="{$sb_business_date|escape:'html':'UTF-8'}">
                {if isset($sb_field_errors.arrival)}<p class="help-block">{$sb_field_errors.arrival|escape:'html':'UTF-8'}</p>{/if}
            </div>
            <div class="sb-filter-field{if isset($sb_field_errors.departure)} has-error{/if}">
                <label for="sb-departure">{l s='Departure' mod='salisbergfrontdesk'}</label>
                <input type="date" id="sb-departure" name="departure" value="{$sb_stay.departure|escape:'html':'UTF-8'}">
                {if isset($sb_field_errors.departure)}<p class="help-block">{$sb_field_errors.departure|escape:'html':'UTF-8'}</p>{/if}
            </div>
            <div class="sb-filter-field{if isset($sb_field_errors.nights)} has-error{/if}">
                <label for="sb-nights">{l s='Nights' mod='salisbergfrontdesk'}</label>
                <input type="number" id="sb-nights" name="nights" min="1" max="365" value="{$sb_stay.nights|intval}">
            </div>
            <div class="sb-filter-field{if isset($sb_field_errors.guests)} has-error{/if}">
                <label for="sb-adults">{l s='Adults' mod='salisbergfrontdesk'}</label>
                <input type="number" id="sb-adults" name="adults" min="1" max="20" value="{$sb_stay.adults|intval}">
            </div>
            <div class="sb-filter-field">
                <label for="sb-children">{l s='Children' mod='salisbergfrontdesk'}</label>
                <input type="number" id="sb-children" name="children" min="0" max="20" value="{$sb_stay.children|intval}">
            </div>
            <button type="submit" class="btn btn-primary sb-update-board">{l s='Update availability' mod='salisbergfrontdesk'}</button>
        </div>

        {if !$sb_field_errors}
            <div class="alert alert-info sb-board-note">
                <strong>{l s='Availability for the complete selected stay' mod='salisbergfrontdesk'}</strong>
                <span>{$sb_stay_display.arrival|escape:'html':'UTF-8'} – {$sb_stay_display.departure|escape:'html':'UTF-8'} · {$sb_stay.nights|intval} {if $sb_stay.nights == 1}{l s='night' mod='salisbergfrontdesk'}{else}{l s='nights' mod='salisbergfrontdesk'}{/if} · {$sb_stay.adults|intval} {if $sb_stay.adults == 1}{l s='adult' mod='salisbergfrontdesk'}{else}{l s='adults' mod='salisbergfrontdesk'}{/if}{if $sb_stay.children}, {$sb_stay.children|intval} {if $sb_stay.children == 1}{l s='child' mod='salisbergfrontdesk'}{else}{l s='children' mod='salisbergfrontdesk'}{/if}{/if}.
                <small>{l s='Availability and current room readiness are shown separately.' mod='salisbergfrontdesk'}</small>
            </div>
        {/if}

        <div class="panel sb-board-tools">
            <div class="sb-room-search"><i class="icon-search"></i><label class="sr-only" for="sb-room-search">{l s='Find a room number or type' mod='salisbergfrontdesk'}</label><input type="search" id="sb-room-search" placeholder="{l s='Find room number or type' mod='salisbergfrontdesk'}" autocomplete="off"></div>
            <div class="sb-room-filters" role="group" aria-label="{l s='Filter rooms' mod='salisbergfrontdesk'}">
                <button type="button" class="is-active" data-room-filter="available">{l s='Available' mod='salisbergfrontdesk'} <span>{$sb_room_counts.available|intval}</span></button>
                <button type="button" data-room-filter="ready">{l s='Ready now' mod='salisbergfrontdesk'} <span>{$sb_room_counts.ready|intval}</span></button>
                <button type="button" data-room-filter="cleaning">{l s='Needs cleaning' mod='salisbergfrontdesk'} <span>{$sb_room_counts.cleaning|intval}</span></button>
                <button type="button" data-room-filter="all">{l s='All rooms' mod='salisbergfrontdesk'} <span>{$sb_room_counts.all|intval}</span></button>
            </div>
            <label class="sb-compact-toggle"><input type="checkbox" id="sb-compact-rooms" checked> <span>{l s='Compact view' mod='salisbergfrontdesk'}</span></label>
        </div>

        <div class="sb-board-layout">
            <div class="sb-room-groups">
                {if $sb_groups}
                    {foreach from=$sb_groups item=group}
                        <section class="sb-room-group">
                            <div class="sb-room-group-title">
                                <h3>{$group.name|escape:'html':'UTF-8'}</h3>
                                <span class="sb-room-count" data-room-count data-singular="{l s='room' mod='salisbergfrontdesk'}" data-plural="{l s='rooms' mod='salisbergfrontdesk'}"><strong>{count($group.rooms)|intval}</strong> <span>{l s='rooms' mod='salisbergfrontdesk'}</span></span>
                            </div>
                            <div class="sb-room-grid">
                                {foreach from=$group.rooms item=room}
                                    <button type="button"
                                        class="sb-room-card{if $sb_selected_room && $sb_selected_room.id_room == $room.id_room} is-selected{/if}{if !$room.available} is-unavailable{/if}"
                                        data-room-id="{$room.id_room|intval}"
                                        data-room-num="{$room.room_num|escape:'html':'UTF-8'}"
                                        data-room-type="{$room.room_type|escape:'html':'UTF-8'}"
                                        data-ready-code="{$room.readiness.code|escape:'html':'UTF-8'}"
                                        data-ready-label="{$room.readiness.label|escape:'html':'UTF-8'}"
                                        data-ready="{if $room.readiness.ready}1{else}0{/if}"
                                        data-available="{if $room.available}1{else}0{/if}"
                                        data-rate="{$room.quote.average_text|escape:'html':'UTF-8'}"
                                        data-nightly="{$room.quote.nightly_text|escape:'html':'UTF-8'}"
                                        data-rate-varies="{if $room.quote.varies}1{else}0{/if}"
                                        data-total="{$room.quote.total_text|escape:'html':'UTF-8'}"
                                        data-taxes="{$room.quote.taxes_text|escape:'html':'UTF-8'}"
                                        data-capacity="{$room.max_adults|intval} adults, {$room.max_children|intval} children"
                                        data-image="{$room.image|escape:'html':'UTF-8'}"
                                        data-room-search="{$room.room_num|escape:'html':'UTF-8'} {$room.room_type|escape:'html':'UTF-8'}"
                                        aria-pressed="{if $sb_selected_room && $sb_selected_room.id_room == $room.id_room}true{else}false{/if}"
                                        {if !$room.available}aria-disabled="true"{/if}>
                                        <span class="sb-room-card-top"><strong>{$room.room_num|escape:'html':'UTF-8'}</strong><span>{$room.room_type|escape:'html':'UTF-8'}</span></span>
                                        {if $sb_recommended_room_id == $room.id_room}<span class="sb-recommended">{l s='Recommended' mod='salisbergfrontdesk'}</span>{/if}
                                        {if $room.image}<img src="{$room.image|escape:'html':'UTF-8'}" alt="">{/if}
                                        <span class="sb-status sb-status-{$room.readiness.code|escape:'html':'UTF-8'}"><i></i>{$room.readiness.label|escape:'html':'UTF-8'}</span>
                                        <span class="sb-availability{if !$room.available} is-no{/if}">{if $room.available}{l s='Available for selected stay' mod='salisbergfrontdesk'}{else}{l s='Not available for selected stay' mod='salisbergfrontdesk'}{/if}</span>
                                        <span class="sb-capacity">{l s='Capacity:' mod='salisbergfrontdesk'} {$room.max_adults|intval} {l s='adults' mod='salisbergfrontdesk'} · {$room.max_children|intval} {l s='children' mod='salisbergfrontdesk'}</span>
                                        <span class="sb-room-rate"><strong>{if $room.quote.varies}{l s='From' mod='salisbergfrontdesk'} {$room.quote.from_text|escape:'html':'UTF-8'}{else}{$room.quote.average_text|escape:'html':'UTF-8'}{/if}</strong><small>{if $room.quote.varies}{l s='rates vary by night' mod='salisbergfrontdesk'}{else}{l s='per night' mod='salisbergfrontdesk'}{/if}</small></span>
                                    </button>
                                {/foreach}
                            </div>
                        </section>
                    {/foreach}
                {else}
                    <div class="panel sb-empty-state">
                        <i class="icon-bed"></i>
                        <h3>{l s='No rooms match this stay' mod='salisbergfrontdesk'}</h3>
                        <p>{l s='Try different dates or a smaller guest count.' mod='salisbergfrontdesk'}</p>
                    </div>
                {/if}
                <div class="panel sb-empty-state" id="sb-filter-empty" hidden>
                    <i class="icon-search"></i>
                    <h3>{l s='No rooms match this filter' mod='salisbergfrontdesk'}</h3>
                    <p>{l s='Try another status or clear the room search.' mod='salisbergfrontdesk'}</p>
                </div>
            </div>

            <aside class="panel sb-selected-room" id="sb-selected-room"{if !$sb_selected_room} hidden{/if}>
                <h3 class="sb-selected-title">{l s='Selected room' mod='salisbergfrontdesk'}</h3>
                <div class="sb-selected-heading">
                    <img id="sb-selected-image" src="{if $sb_selected_room}{$sb_selected_room.image|escape:'html':'UTF-8'}{/if}" alt=""{if !$sb_selected_room || !$sb_selected_room.image} hidden{/if}>
                    <div><strong id="sb-selected-number">{if $sb_selected_room}{$sb_selected_room.room_num|escape:'html':'UTF-8'}{/if}</strong><span id="sb-selected-type">{if $sb_selected_room}{$sb_selected_room.room_type|escape:'html':'UTF-8'}{/if}</span><span id="sb-selected-status" class="sb-status{if $sb_selected_room} sb-status-{$sb_selected_room.readiness.code|escape:'html':'UTF-8'}{/if}"><i></i>{if $sb_selected_room}{$sb_selected_room.readiness.label|escape:'html':'UTF-8'}{/if}</span></div>
                </div>
                <dl class="sb-summary-list">
                    <dt>{l s='Check-in' mod='salisbergfrontdesk'}</dt><dd>{$sb_stay_display.arrival|escape:'html':'UTF-8'}</dd>
                    <dt>{l s='Check-out' mod='salisbergfrontdesk'}</dt><dd>{$sb_stay_display.departure|escape:'html':'UTF-8'}</dd>
                    <dt>{l s='Nights' mod='salisbergfrontdesk'}</dt><dd>{$sb_stay.nights|intval}</dd>
                    <dt>{l s='Guests' mod='salisbergfrontdesk'}</dt><dd>{$sb_stay.adults|intval} {if $sb_stay.adults == 1}{l s='adult' mod='salisbergfrontdesk'}{else}{l s='adults' mod='salisbergfrontdesk'}{/if}{if $sb_stay.children} · {$sb_stay.children|intval} {if $sb_stay.children == 1}{l s='child' mod='salisbergfrontdesk'}{else}{l s='children' mod='salisbergfrontdesk'}{/if}{/if}</dd>
                    <dt>{l s='Capacity' mod='salisbergfrontdesk'}</dt><dd id="sb-selected-capacity">{if $sb_selected_room}{$sb_selected_room.max_adults|intval} adults, {$sb_selected_room.max_children|intval} children{/if}</dd>
                </dl>
                <hr>
                <dl class="sb-summary-list">
                    <dt>{l s='Rate per night' mod='salisbergfrontdesk'}</dt><dd id="sb-selected-rate">{if $sb_selected_room}{$sb_selected_room.quote.average_text|escape:'html':'UTF-8'}{/if}</dd>
                    <dt id="sb-selected-nightly-label"{if !$sb_selected_room || !$sb_selected_room.quote.varies} hidden{/if}>{l s='Nightly rates' mod='salisbergfrontdesk'}</dt><dd id="sb-selected-nightly"{if !$sb_selected_room || !$sb_selected_room.quote.varies} hidden{/if}>{if $sb_selected_room}{$sb_selected_room.quote.nightly_text|escape:'html':'UTF-8'}{/if}</dd>
                    <dt>{l s='Taxes' mod='salisbergfrontdesk'}</dt><dd id="sb-selected-taxes">{if $sb_selected_room}{$sb_selected_room.quote.taxes_text|escape:'html':'UTF-8'}{/if}</dd>
                </dl>
                <div class="sb-selected-total"><span>{l s='Estimated stay total' mod='salisbergfrontdesk'}</span><strong id="sb-selected-total">{if $sb_selected_room}{$sb_selected_room.quote.total_text|escape:'html':'UTF-8'}{/if}</strong></div>
                <p class="help-block">{l s='The guided workspace shows the final total including configured fees, discounts and tax.' mod='salisbergfrontdesk'}</p>
                {if $sb_can_book}
                    <button type="submit" name="sb_start_booking" value="1" id="sb-book-room" class="btn btn-primary btn-block"{if !$sb_selected_room || !$sb_selected_room.available} disabled{/if}>
                        {if $sb_is_walkin}{l s='Book walk-in' mod='salisbergfrontdesk'}{else}{l s='Create reservation' mod='salisbergfrontdesk'}{/if}
                    </button>
                {/if}
                <div class="alert alert-warning" id="sb-selection-warning"{if !$sb_selected_room || $sb_selected_room.available} hidden{/if}>{l s='This room is not available for the complete selected stay.' mod='salisbergfrontdesk'}</div>
            </aside>
        </div>
        {if $sb_can_book}
            <div class="sb-mobile-booking" id="sb-mobile-booking"{if !$sb_selected_room} hidden{/if}>
                <div><strong id="sb-mobile-room">{if $sb_selected_room}{l s='Room' mod='salisbergfrontdesk'} {$sb_selected_room.room_num|escape:'html':'UTF-8'}{/if}</strong><span id="sb-mobile-total">{if $sb_selected_room}{$sb_selected_room.quote.total_text|escape:'html':'UTF-8'} {l s='total' mod='salisbergfrontdesk'}{/if}</span></div>
                <button type="submit" name="sb_start_booking" value="1" id="sb-mobile-book-room" class="btn btn-primary"{if !$sb_selected_room || !$sb_selected_room.available} disabled{/if}>{l s='Continue' mod='salisbergfrontdesk'}</button>
            </div>
        {/if}
    </form>
</div>

{*
 * Copyright (c) Salisberg
 * SPDX-License-Identifier: MIT
 *}
<div class="sb-frontdesk sb-walkin" data-search-url="{$sb_search_url|escape:'html':'UTF-8'}">
    {if $sb_success}
        <div class="panel sb-walkin-success">
            <span class="sb-success-icon"><i class="icon-ok"></i></span>
            <p class="sb-kicker">{if $sb_success.checked_in}{l s='Guest checked in' mod='salisbergfrontdesk'}{else}{l s='Reservation saved' mod='salisbergfrontdesk'}{/if}</p>
            <h2>{$sb_success.guest|escape:'html':'UTF-8'} · {$sb_success.room_num|escape:'html':'UTF-8'}</h2>
            <p>{l s='Booking reference' mod='salisbergfrontdesk'} <strong>{$sb_success.reference|escape:'html':'UTF-8'}</strong></p>
            <dl class="sb-success-details">
                <dt>{l s='Stay' mod='salisbergfrontdesk'}</dt><dd>{$sb_success.arrival|escape:'html':'UTF-8'} – {$sb_success.departure|escape:'html':'UTF-8'}</dd>
                <dt>{l s='Room' mod='salisbergfrontdesk'}</dt><dd>{$sb_success.room_num|escape:'html':'UTF-8'} · {$sb_success.room_type|escape:'html':'UTF-8'}</dd>
                <dt>{l s='Total' mod='salisbergfrontdesk'}</dt><dd>{$sb_success.total|escape:'html':'UTF-8'}</dd>
                <dt>{l s='Confirmed payment' mod='salisbergfrontdesk'}</dt><dd>{$sb_success.paid|escape:'html':'UTF-8'}</dd>
                <dt>{l s='Outstanding balance' mod='salisbergfrontdesk'}</dt><dd>{$sb_success.balance|escape:'html':'UTF-8'}</dd>
            </dl>
            <div class="sb-success-actions">
                <a class="btn btn-primary" href="{$sb_order_link|escape:'html':'UTF-8'}">{l s='View stay' mod='salisbergfrontdesk'}</a>
                {if $sb_success.has_payment}<a class="btn btn-default" href="{$sb_pdf_link|escape:'html':'UTF-8'}" target="_blank">{l s='Print receipt' mod='salisbergfrontdesk'}</a>{/if}
                <a class="btn btn-default" href="{$sb_board_link|escape:'html':'UTF-8'}">{l s='Return to room board' mod='salisbergfrontdesk'}</a>
                <a class="btn btn-default" href="{$sb_board_link|escape:'html':'UTF-8'}">{l s='Start another walk-in' mod='salisbergfrontdesk'}</a>
            </div>
        </div>
    {else}
        <div class="sb-fd-heading">
            <div>
                <a class="sb-back-link" href="{$sb_board_link|escape:'html':'UTF-8'}"><i class="icon-arrow-left"></i> {l s='Back to room board' mod='salisbergfrontdesk'}</a>
                <h2>{l s='Walk-in booking' mod='salisbergfrontdesk'} · {$sb_room.room_num|escape:'html':'UTF-8'}</h2>
            </div>
        </div>

        <div class="sb-walkin-layout">
            <nav class="sb-step-nav" aria-label="{l s='Booking steps' mod='salisbergfrontdesk'}">
                <a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=1" class="{if $sb_step == 1}is-current{elseif $sb_draft.step > 1}is-complete{/if}"><span>{if $sb_draft.step > 1}<i class="icon-ok"></i>{else}1{/if}</span><strong>{l s='Stay' mod='salisbergfrontdesk'}</strong><small>{l s='Room, dates and rate' mod='salisbergfrontdesk'}</small></a>
                <a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=2" class="{if $sb_step == 2}is-current{elseif $sb_draft.step > 2}is-complete{/if}"><span>{if $sb_draft.step > 2}<i class="icon-ok"></i>{else}2{/if}</span><strong>{l s='Guest' mod='salisbergfrontdesk'}</strong><small>{l s='Guest information' mod='salisbergfrontdesk'}</small></a>
                <a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=3" class="{if $sb_step == 3}is-current{elseif $sb_draft.step > 3}is-complete{/if}{if $sb_draft.step < 3} is-disabled{/if}"><span>{if $sb_draft.step > 3}<i class="icon-ok"></i>{else}3{/if}</span><strong>{l s='Payment' mod='salisbergfrontdesk'}</strong><small>{l s='Method and amount' mod='salisbergfrontdesk'}</small></a>
                <a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=4" class="{if $sb_step == 4}is-current{/if}{if $sb_draft.step < 4} is-disabled{/if}"><span>4</span><strong>{l s='Review' mod='salisbergfrontdesk'}</strong><small>{l s='Confirm and complete' mod='salisbergfrontdesk'}</small></a>
            </nav>

            <main class="panel sb-step-panel">
                {if $sb_step == 1}
                    <h3>{l s='Stay details' mod='salisbergfrontdesk'}</h3>
                    <p>{l s='Changes are checked against live availability and recalculate the booking.' mod='salisbergfrontdesk'}</p>
                    <form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;step=1" class="sb-step-form">
                        <div class="sb-form-grid">
                            <div><label for="walkin-arrival">{l s='Arrival' mod='salisbergfrontdesk'}</label><input type="date" id="walkin-arrival" name="arrival" value="{$sb_state.stay.arrival|escape:'html':'UTF-8'}" required></div>
                            <div><label for="walkin-departure">{l s='Departure' mod='salisbergfrontdesk'}</label><input type="date" id="walkin-departure" name="departure" value="{$sb_state.stay.departure|escape:'html':'UTF-8'}" required></div>
                            <div><label for="walkin-nights">{l s='Nights' mod='salisbergfrontdesk'}</label><input type="number" id="walkin-nights" name="nights" value="{$sb_state.stay.nights|intval}" min="1" max="365"></div>
                            <div><label for="walkin-adults">{l s='Adults' mod='salisbergfrontdesk'}</label><input type="number" id="walkin-adults" name="adults" value="{$sb_state.stay.adults|intval}" min="1" max="20"></div>
                            <div><label for="walkin-children">{l s='Children' mod='salisbergfrontdesk'}</label><input type="number" id="walkin-children" name="children" value="{$sb_state.stay.children|intval}" min="0" max="20"></div>
                            <div class="sb-span-2"><label for="walkin-room">{l s='Room' mod='salisbergfrontdesk'}</label><select id="walkin-room" name="id_room">{foreach from=$sb_rooms item=room}<option value="{$room.id_room|intval}"{if $room.id_room == $sb_state.stay.id_room} selected{/if}>{$room.room_num|escape:'html':'UTF-8'} · {$room.room_type|escape:'html':'UTF-8'} · {$room.quote.total_text|escape:'html':'UTF-8'}</option>{/foreach}</select></div>
                        </div>
                        <div class="sb-rate-plan"><strong>{l s='Rate' mod='salisbergfrontdesk'}</strong><span>{l s='Current configured room rate, including automatic pricing rules' mod='salisbergfrontdesk'}</span></div>
                        {if $sb_tax_choice}
                            <label class="sb-check"><input type="checkbox" name="charge_tax" value="1"{if $sb_state.charge_tax} checked{/if}> <span>{l s='Charge tax for this booking' mod='salisbergfrontdesk'}</span></label>
                        {else}<input type="hidden" name="charge_tax" value="{if $sb_state.charge_tax}1{else}0{/if}">{/if}
                        <div class="sb-step-actions"><a class="btn btn-default" href="{$sb_board_link|escape:'html':'UTF-8'}">{l s='Change from room board' mod='salisbergfrontdesk'}</a><button class="btn btn-primary" type="submit" name="sb_save_stay" value="1">{l s='Save stay and continue' mod='salisbergfrontdesk'} <i class="icon-arrow-right"></i></button></div>
                    </form>
                {elseif $sb_step == 2}
                    <h3>{l s='Guest information' mod='salisbergfrontdesk'}</h3>
                    <form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;step=2" class="sb-step-form" id="sb-guest-step">
                        <label for="sb-guest-search">{l s='Search existing guests' mod='salisbergfrontdesk'}</label>
                        <div class="sb-search-field"><i class="icon-search"></i><input type="search" id="sb-guest-search" placeholder="{l s='Name, phone or email' mod='salisbergfrontdesk'}" autocomplete="off"></div>
                        <p class="help-block">{l s='Search first to avoid creating a duplicate guest profile.' mod='salisbergfrontdesk'}</p>
                        <div id="sb-guest-results" class="sb-guest-results" aria-live="polite"></div>
                        <input type="hidden" name="id_customer" id="sb-guest-id" value="{if isset($sb_state.guest.id_customer)}{$sb_state.guest.id_customer|intval}{/if}">
                        <div id="sb-selected-guest" class="sb-selected-guest"{if empty($sb_state.guest.id_customer)} hidden{/if}>
                            <div><strong id="sb-selected-guest-name">{if isset($sb_state.guest.firstname)}{$sb_state.guest.firstname|escape:'html':'UTF-8'} {$sb_state.guest.lastname|escape:'html':'UTF-8'}{/if}</strong><span id="sb-selected-guest-contact">{if isset($sb_state.guest.phone)}{$sb_state.guest.phone|escape:'html':'UTF-8'}{if $sb_state.guest.email} · {$sb_state.guest.email|escape:'html':'UTF-8'}{/if}{/if}</span></div>
                            <button type="button" class="btn btn-default" id="sb-change-guest">{l s='Change guest' mod='salisbergfrontdesk'}</button>
                        </div>
                        <div class="sb-or"><span>{l s='or add a new guest' mod='salisbergfrontdesk'}</span></div>
                        <div id="sb-new-guest"{if !empty($sb_state.guest.id_customer)} hidden{/if}>
                            <div class="sb-form-grid">
                                <div><label for="guest-firstname">{l s='First name' mod='salisbergfrontdesk'} <em>*</em></label><input type="text" id="guest-firstname" name="firstname" maxlength="32"></div>
                                <div><label for="guest-lastname">{l s='Last name' mod='salisbergfrontdesk'} <em>*</em></label><input type="text" id="guest-lastname" name="lastname" maxlength="32"></div>
                                <div><label for="guest-phone">{l s='Phone number' mod='salisbergfrontdesk'} <em>*</em></label><input type="tel" id="guest-phone" name="phone" maxlength="32"></div>
                                <div><label for="guest-email">{l s='Email (optional)' mod='salisbergfrontdesk'}</label><input type="email" id="guest-email" name="email" maxlength="128"></div>
                            </div>
                        </div>
                        <details class="sb-additional-details"><summary>{l s='Additional guest details (optional)' mod='salisbergfrontdesk'}</summary><div class="sb-form-grid"><div class="sb-span-2"><label for="guest-company">{l s='Company or billing name' mod='salisbergfrontdesk'}</label><input type="text" id="guest-company" name="company" value="{if isset($sb_state.guest.company)}{$sb_state.guest.company|escape:'html':'UTF-8'}{/if}"></div><div class="sb-span-2"><label for="guest-requests">{l s='Special requests' mod='salisbergfrontdesk'}</label><textarea id="guest-requests" name="special_requests" rows="3">{if isset($sb_state.guest.special_requests)}{$sb_state.guest.special_requests|escape:'html':'UTF-8'}{/if}</textarea></div></div></details>
                        <div class="sb-step-actions"><a class="btn btn-default" href="{$sb_action|escape:'html':'UTF-8'}&amp;step=1"><i class="icon-arrow-left"></i> {l s='Back to stay' mod='salisbergfrontdesk'}</a><button class="btn btn-primary" type="submit" name="sb_save_guest" value="1">{l s='Continue to payment' mod='salisbergfrontdesk'} <i class="icon-arrow-right"></i></button></div>
                    </form>
                {elseif $sb_step == 3}
                    <h3>{l s='Payment' mod='salisbergfrontdesk'}</h3>
                    <form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;step=3" class="sb-step-form" id="sb-payment-step" data-total="{$sb_state.quote.total|escape:'html':'UTF-8'}">
                        <div class="sb-charge-lines">
                            <div><span>{l s='Accommodation' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.accommodation_text|escape:'html':'UTF-8'}</strong></div>
                            {if $sb_state.quote.extras > 0}<div><span>{l s='Fees and extras' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.extras_text|escape:'html':'UTF-8'}</strong></div>{/if}
                            {if $sb_state.quote.discounts > 0}<div><span>{l s='Discounts' mod='salisbergfrontdesk'}</span><strong>− {$sb_state.quote.discounts_text|escape:'html':'UTF-8'}</strong></div>{/if}
                            <div><span>{l s='Taxes' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.taxes_text|escape:'html':'UTF-8'}</strong></div>
                            <div class="is-total"><span>{l s='Total stay cost' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.total_text|escape:'html':'UTF-8'}</strong></div>
                        </div>
                        <fieldset><legend>{l s='Payment method' mod='salisbergfrontdesk'}</legend><div class="sb-payment-methods">{foreach from=$sb_methods item=method}<label><input type="radio" name="method" value="{$method.code|escape:'html':'UTF-8'}"{if (isset($sb_state.payment.method) && $sb_state.payment.method == $method.code) || (!isset($sb_state.payment.method) && $method@first)} checked{/if}><span><strong>{$method.title|escape:'html':'UTF-8'}</strong><small>{$method.hint|escape:'html':'UTF-8'}</small></span></label>{/foreach}</div></fieldset>
                        <fieldset><legend>{l s='Payment arrangement' mod='salisbergfrontdesk'}</legend><div class="sb-payment-arrangements"><label><input type="radio" name="arrangement" value="full"{if !isset($sb_state.payment.arrangement) || $sb_state.payment.arrangement == 'full'} checked{/if}> {l s='Full payment' mod='salisbergfrontdesk'}</label><label><input type="radio" name="arrangement" value="partial"{if isset($sb_state.payment.arrangement) && $sb_state.payment.arrangement == 'partial'} checked{/if}> {l s='Partial payment' mod='salisbergfrontdesk'}</label><label><input type="radio" name="arrangement" value="later"{if isset($sb_state.payment.arrangement) && $sb_state.payment.arrangement == 'later'} checked{/if}> {l s='Pay later' mod='salisbergfrontdesk'}</label></div></fieldset>
                        <div class="sb-form-grid sb-payment-details">
                            <div id="sb-partial-field"><label for="payment-amount">{l s='Payment applied' mod='salisbergfrontdesk'}</label><input type="number" id="payment-amount" name="amount" min="0" step="0.01" value="{if isset($sb_state.payment.intended_amount)}{$sb_state.payment.intended_amount|escape:'html':'UTF-8'}{/if}"></div>
                            <div id="sb-tendered-field"><label for="cash-tendered">{l s='Cash tendered' mod='salisbergfrontdesk'}</label><input type="number" id="cash-tendered" name="tendered" min="0" step="0.01" value="{if isset($sb_state.payment.cash_tendered)}{$sb_state.payment.cash_tendered|escape:'html':'UTF-8'}{/if}"><small id="sb-change-due"></small></div>
                            <div id="sb-reference-field"><label for="payment-reference">{l s='Transaction reference' mod='salisbergfrontdesk'}</label><input type="text" id="payment-reference" name="reference" value="{if isset($sb_state.payment.reference)}{$sb_state.payment.reference|escape:'html':'UTF-8'}{/if}"><small>{l s='A reference alone does not confirm payment.' mod='salisbergfrontdesk'}</small></div>
                            <div id="sb-confirmed-field"><label class="sb-check"><input type="checkbox" name="confirmed" value="1"{if !empty($sb_state.payment.confirmed)} checked{/if}> <span id="sb-confirmed-label">{l s='I confirm this payment was received' mod='salisbergfrontdesk'}</span></label></div>
                        </div>
                        <div class="alert alert-info">{l s='Only confirmed money is recorded as paid. Pending Mobile Money or bank transfers remain outstanding.' mod='salisbergfrontdesk'}</div>
                        <div class="sb-step-actions"><a class="btn btn-default" href="{$sb_action|escape:'html':'UTF-8'}&amp;step=2"><i class="icon-arrow-left"></i> {l s='Back to guest' mod='salisbergfrontdesk'}</a><button class="btn btn-primary" type="submit" name="sb_save_payment" value="1">{l s='Continue to review' mod='salisbergfrontdesk'} <i class="icon-arrow-right"></i></button></div>
                    </form>
                {else}
                    <h3>{l s='Review booking' mod='salisbergfrontdesk'}</h3>
                    {if $sb_price_changed}
                        <div class="alert alert-warning"><strong>{l s='The price changed after the earlier quote.' mod='salisbergfrontdesk'}</strong> {$sb_state.price_changed.from_text|escape:'html':'UTF-8'} → {$sb_state.price_changed.to_text|escape:'html':'UTF-8'}. {l s='Review the new total and explicitly accept it below.' mod='salisbergfrontdesk'}</div>
                    {/if}
                    <div class="sb-review-sections">
                        <section><div><h4>{l s='Stay' mod='salisbergfrontdesk'}</h4><a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=1">{l s='Edit' mod='salisbergfrontdesk'}</a></div><p><strong>{$sb_room.room_num|escape:'html':'UTF-8'} · {$sb_room.room_type|escape:'html':'UTF-8'}</strong><br>{$sb_state.stay.arrival|escape:'html':'UTF-8'} – {$sb_state.stay.departure|escape:'html':'UTF-8'} · {$sb_state.stay.nights|intval} {l s='night(s)' mod='salisbergfrontdesk'}<br>{$sb_state.stay.adults|intval} {l s='adult(s)' mod='salisbergfrontdesk'} · {$sb_state.stay.children|intval} {l s='child(ren)' mod='salisbergfrontdesk'}</p><span class="sb-status sb-status-{$sb_room.readiness.code|escape:'html':'UTF-8'}"><i></i>{$sb_room.readiness.label|escape:'html':'UTF-8'}</span></section>
                        <section><div><h4>{l s='Guest' mod='salisbergfrontdesk'}</h4><a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=2">{l s='Edit' mod='salisbergfrontdesk'}</a></div><p><strong>{$sb_state.guest.firstname|escape:'html':'UTF-8'} {$sb_state.guest.lastname|escape:'html':'UTF-8'}</strong><br>{$sb_state.guest.phone|escape:'html':'UTF-8'}{if $sb_state.guest.email}<br>{$sb_state.guest.email|escape:'html':'UTF-8'}{/if}</p></section>
                        <section><div><h4>{l s='Payment' mod='salisbergfrontdesk'}</h4><a href="{$sb_action|escape:'html':'UTF-8'}&amp;step=3">{l s='Edit' mod='salisbergfrontdesk'}</a></div><p><strong>{$sb_state.payment.method_title|escape:'html':'UTF-8'}</strong><br>{if $sb_state.payment.confirmed}{l s='Confirmed' mod='salisbergfrontdesk'}: {$sb_state.payment.confirmed_amount|escape:'html':'UTF-8'}{else}{l s='Not recorded as paid' mod='salisbergfrontdesk'}{/if}{if $sb_state.payment.reference}<br>{l s='Reference' mod='salisbergfrontdesk'}: {$sb_state.payment.reference|escape:'html':'UTF-8'}{/if}</p></section>
                        <section><div><h4>{l s='Charges' mod='salisbergfrontdesk'}</h4></div><div class="sb-charge-lines"><div><span>{l s='Accommodation' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.accommodation_text|escape:'html':'UTF-8'}</strong></div>{if $sb_state.quote.extras > 0}<div><span>{l s='Fees and extras' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.extras_text|escape:'html':'UTF-8'}</strong></div>{/if}<div><span>{l s='Taxes' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.taxes_text|escape:'html':'UTF-8'}</strong></div><div class="is-total"><span>{l s='Total' mod='salisbergfrontdesk'}</span><strong>{$sb_state.quote.total_text|escape:'html':'UTF-8'}</strong></div><div><span>{l s='Confirmed payment' mod='salisbergfrontdesk'}</span><strong>{$sb_state.payment.confirmed_amount|escape:'html':'UTF-8'}</strong></div></div></section>
                    </div>
                    <form method="post" action="{$sb_action|escape:'html':'UTF-8'}&amp;step=4" id="sb-finalize-form">
                        {if $sb_price_changed}<label class="sb-check sb-accept-price"><input type="checkbox" name="accept_price" value="1" required> <span>{l s='I accept the updated total shown above.' mod='salisbergfrontdesk'}</span></label>{/if}
                        {if !$sb_room.readiness.ready}<div class="alert alert-warning">{l s='This room is available for the stay but is not ready for immediate check-in. Save the reservation; it will remain awaiting the room and will not mark the room occupied.' mod='salisbergfrontdesk'}</div>{/if}
                        <div class="sb-step-actions"><a class="btn btn-default" href="{$sb_action|escape:'html':'UTF-8'}&amp;step=3"><i class="icon-arrow-left"></i> {l s='Back to payment' mod='salisbergfrontdesk'}</a>{if $sb_room.readiness.ready}<button class="btn btn-primary" type="submit" name="sb_finalize" value="checkin">{l s='Confirm & check in' mod='salisbergfrontdesk'} <i class="icon-ok"></i></button>{else}<button class="btn btn-primary" type="submit" name="sb_finalize" value="reserve">{l s='Save reservation — awaiting room' mod='salisbergfrontdesk'}</button>{/if}</div>
                    </form>
                {/if}
            </main>

            <aside class="panel sb-stay-summary">
                <h3>{l s='Stay summary' mod='salisbergfrontdesk'}</h3>
                <div class="sb-selected-heading">{if $sb_room.image}<img src="{$sb_room.image|escape:'html':'UTF-8'}" alt="">{/if}<div><strong>{$sb_room.room_num|escape:'html':'UTF-8'}</strong><span>{$sb_room.room_type|escape:'html':'UTF-8'}</span><span class="sb-status sb-status-{$sb_room.readiness.code|escape:'html':'UTF-8'}"><i></i>{$sb_room.readiness.label|escape:'html':'UTF-8'}</span></div></div>
                <dl class="sb-summary-list"><dt>{l s='Check-in' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.stay.arrival|escape:'html':'UTF-8'}</dd><dt>{l s='Check-out' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.stay.departure|escape:'html':'UTF-8'}</dd><dt>{l s='Nights' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.stay.nights|intval}</dd><dt>{l s='Guests' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.stay.adults|intval} + {$sb_state.stay.children|intval}</dd></dl>
                <hr>
                <dl class="sb-summary-list"><dt>{l s='Accommodation' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.quote.accommodation_text|escape:'html':'UTF-8'}</dd>{if $sb_state.quote.extras > 0}<dt>{l s='Fees and extras' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.quote.extras_text|escape:'html':'UTF-8'}</dd>{/if}<dt>{l s='Taxes' mod='salisbergfrontdesk'}</dt><dd>{$sb_state.quote.taxes_text|escape:'html':'UTF-8'}</dd><dt class="sb-summary-total">{l s='Total' mod='salisbergfrontdesk'}</dt><dd class="sb-summary-total">{$sb_state.quote.total_text|escape:'html':'UTF-8'}</dd></dl>
                <hr>
                <p><strong>{l s='Payment' mod='salisbergfrontdesk'}</strong><br>{if isset($sb_state.payment.status)}{if $sb_state.payment.confirmed}{l s='Confirmed' mod='salisbergfrontdesk'}{else}{l s='Not recorded as paid' mod='salisbergfrontdesk'}{/if}{else}{l s='Not recorded' mod='salisbergfrontdesk'}{/if}</p>
            </aside>
        </div>
    {/if}
</div>

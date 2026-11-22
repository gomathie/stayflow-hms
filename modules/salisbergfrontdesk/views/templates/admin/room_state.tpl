{* One room state as a coloured chip. $sb_state is clean, dirty or out. *}
{if $sb_state == 'out'}<span class="sb-chip sb-chip-bad">{l s='Out of service' mod='salisbergfrontdesk'}</span>{elseif $sb_state == 'dirty'}<span class="sb-chip sb-chip-warn">{l s='Dirty' mod='salisbergfrontdesk'}</span>{else}<span class="sb-chip sb-chip-good">{l s='Clean' mod='salisbergfrontdesk'}</span>{/if}

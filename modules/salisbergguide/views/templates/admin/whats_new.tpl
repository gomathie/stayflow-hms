{*
 What's New: recent changes, newest first, in plain words for the people who
 use the back office. One <section> per date.

 Each reader sees only what concerns their role:
   - no wrapper ............................ everyone who can open the page
   - {if $sb_can_read_admin_guide} ......... Hotel Manager and the developer team
   - {if $sb_is_admin} ..................... the developer team only

 Add an entry here for every change a user will notice (AGENTS.md, rule 30).
 The full record, including technical changes, is CHANGELOG.md.
 Avoid apostrophes inside {l s='...'} strings.
*}
<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-guide">
	<div class="sb-intro">
		<h2>{l s='What is New' mod='salisbergguide'}</h2>
		<p>{l s='Recent changes to the system, newest first. How to use each feature is explained in the guides.' mod='salisbergguide'}</p>
	</div>

	<section>
		<h3>{l s='7 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='This page.' mod='salisbergguide'}</strong> {l s='Changes are now listed here as they are made.' mod='salisbergguide'}</li>
			<li><strong>{l s='Dark mode.' mod='salisbergguide'}</strong> {l s='Lists and forms that still showed white or pale blue areas in dark mode have been corrected.' mod='salisbergguide'}</li>
			<li><strong>{l s='Larger menu text.' mod='salisbergguide'}</strong> {l s='The menu on the left is a little bigger and easier to read.' mod='salisbergguide'}</li>
			<li><strong>{l s='Contact form.' mod='salisbergguide'}</strong> {l s='Guests can no longer attach a file to a message sent from the website. Messages arrive as before under Customers, Customer Service.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='The sample guest account that came with the system (John Doe) has been retired.' mod='salisbergguide'}</li>
			<li><strong>{l s='Website footer.' mod='salisbergguide'}</strong> {l s='Payment accepted now shows Cash and Mobile Money in place of the card logos. Home, Our Properties, Interior and Contact Us are listed under Explore. The empty Follow us on heading is hidden until social links are entered, and the sample founding year 2010 is gone from the copyright line.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='The vendor name no longer appears in back office text, and the vendor store page (Modules Catalog) is out of the menu.' mod='salisbergguide'}</li>
			<li>{l s='Who can open this page is set in Administration, Permissions, on the row named after this page.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='6 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='New look.' mod='salisbergguide'}</strong> {l s='The back office has a cleaner layout, with your initials and name at the top right.' mod='salisbergguide'}</li>
			<li><strong>{l s='Light or dark.' mod='salisbergguide'}</strong> {l s='The small switch with a sun and a moon at the top right changes the look. It is remembered on that computer.' mod='salisbergguide'}</li>
			<li><strong>{l s='Bookings menu.' mod='salisbergguide'}</strong> {l s='The Orders section of the menu is now called Bookings.' mod='salisbergguide'}</li>
			<li><strong>{l s='Menu boxes stay open.' mod='salisbergguide'}</strong> {l s='Rest the pointer on a menu section and its pages appear in a box beside it. The box no longer closes before you can click.' mod='salisbergguide'}</li>
			<li><strong>{l s='Show password.' mod='salisbergguide'}</strong> {l s='Every password field has an eye icon that shows what you typed.' mod='salisbergguide'}</li>
			<li><strong>{l s='Cash and Mobile Money.' mod='salisbergguide'}</strong> {l s='Guests choose to pay cash at the hotel or by Mobile Money. The booking waits as Awaiting payment until you record the money on it.' mod='salisbergguide'}</li>
			<li><strong>{l s='Too many attempts.' mod='salisbergguide'}</strong> {l s='After ten wrong passwords in ten minutes from the same connection, sign-in pauses for a few minutes. It clears by itself.' mod='salisbergguide'}</li>
			<li><strong>{l s='Clearer buttons.' mod='salisbergguide'}</strong> {l s='Save, Save and stay and Cancel are ordinary buttons at the bottom of each form.' mod='salisbergguide'}</li>
			<li><strong>{l s='Guides.' mod='salisbergguide'}</strong> {l s='Step-by-step help is in the Guides section of the menu.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Hotel Manager role.' mod='salisbergguide'}</strong> {l s='A new role for the person who runs the hotel: rooms, prices, bookings, guests, website pages, staff accounts and reports. See the Admin Guide, section 1.' mod='salisbergguide'}</li>
			<li><strong>{l s='Prices in Ghana cedis.' mod='salisbergguide'}</strong> {l s='All prices are shown in GH₵.' mod='salisbergguide'}</li>
			<li><strong>{l s='Guest reviews switched off.' mod='salisbergguide'}</strong> {l s='The sample reviews on the homepage are hidden until there are real ones. See the Admin Guide, section 9.' mod='salisbergguide'}</li>
			<li><strong>{l s='Website.' mod='salisbergguide'}</strong> {l s='The website has a new design, a shorter menu on computers, and the phone homepage button now says Book Now.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='Menu sections can be shown or hidden: Admin Guide, section 15.' mod='salisbergguide'}</li>
			<li>{l s='Advanced Parameters is explained page by page: Admin Guide, section 16.' mod='salisbergguide'}</li>
			<li>{l s='Nightly encrypted backups, security fixes, sign-in and form limits. Details are in CHANGELOG.md and AGENTS.md in the code.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='5 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li>{l s='The system was set up as Salisberg Hotels, with the hotel logo and colours.' mod='salisbergguide'}</li>
		</ul>
	</section>
</div>

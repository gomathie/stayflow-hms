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
		<h3>{l s='13 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Fixed: front desk staff could not finish a booking in Book Now.' mod='salisbergguide'}</strong> {l s='The rooms stayed in the cart and no booking was made. Staff accounts were missing a right on Booking Carts that the last step depends on. It is now given automatically.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='Bookings, Add new attaches the guest to the cart through AdminCarts, which needs add and edit there. Hotel Staff and Hotel Manager were created with view only. Raised in salisbergguide::grantCartEditing(), called from ensureSetup(). The three things a booking profile needs are listed in the Admin Guide, section 1.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='12 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Manage Hotel menu.' mod='salisbergguide'}</strong> {l s='The Hotel Reservation System section of the menu is now called Manage Hotel.' mod='salisbergguide'}</li>
			<li><strong>{l s='Rooms and Services menu.' mod='salisbergguide'}</strong> {l s='The Catalog section of the menu is now called Rooms and Services.' mod='salisbergguide'}</li>
			<li><strong>{l s='Room types updated.' mod='salisbergguide'}</strong> {l s='The hotel now has six real room types to book: Standard Room 1, 2 and 3, Deluxe Room 1 and 2, and Executive Room 1, each with its own price, bed and amenities.' mod='salisbergguide'}</li>
			<li><strong>{l s='Cleaner website links.' mod='salisbergguide'}</strong> {l s='A room page now reads salisberg.com/salisberg-hotels/1-standard-room-1.html instead of a long address with index.php in it. Older links still work.' mod='salisbergguide'}</li>
			<li><strong>{l s='Fixed: Staff and Manager accounts could not see bookings they had just made, and the dashboard showed no figures.' mod='salisbergguide'}</strong> {l s='Both roles were missing a hotel access right that has nothing to do with the usual Permissions page. It is now granted automatically. If Bookings or the dashboard still look empty for you, ask the developer team to check it.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='Inside Manage Hotel, the page where the hotel address and details are edited is now called Hotel Details (it used to share the Manage Hotel name).' mod='salisbergguide'}</li>
			<li><strong>{l s='Website footer.' mod='salisbergguide'}</strong> {l s='A new Location and Contact column shows the address, phone and email already shown on the Contact page.' mod='salisbergguide'}</li>
			<li><strong>{l s='Important: new room types need a room number before going live.' mod='salisbergguide'}</strong> {l s='Guests can still reach a confirmation page for a room type with nothing in its Rooms tab, but the booking is not recorded. See the Admin Guide, section 3.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='CSV Import now also accepts .xlsx files, converted to CSV right after upload (no change to the import logic itself). Every sample file has a matching .xlsx download, and Room Types has an extra example already filled in with the hotel data. See the Admin Guide, section 16.' mod='salisbergguide'}</li>
			<li>{l s='Fixed: the sample-file downloads on the CSV Import page were all returning 404 on a real deploy, because .dockerignore excluded the whole docs folder.' mod='salisbergguide'}</li>
			<li>{l s='Fixed: QloApps htl_access (the per-hotel row behind the Hotel Permissions panel on the Permissions page, separate from the main grid) was never granted to Hotel Staff or Hotel Manager, so every hotel-scoped page and widget silently showed nothing for either role. Granted automatically in salisbergguide::grantHotelAccess(), called from ensureSetup() on every deploy.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='7 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Small tidy-ups to the look.' mod='salisbergguide'}</strong> {l s='Text you type into fields now uses the same typeface as the rest of the screen, a few section headings are no longer in capital letters, and a top bar item reached with the Tab key is highlighted. Nothing has moved.' mod='salisbergguide'}</li>
			<li><strong>{l s='This page.' mod='salisbergguide'}</strong> {l s='Changes are now listed here as they are made.' mod='salisbergguide'}</li>
			<li><strong>{l s='Bank transfer.' mod='salisbergguide'}</strong> {l s='Guests can now choose to pay by bank transfer once the hotel bank account has been entered. Like Mobile Money, the booking waits as Awaiting payment until you record the money. See the Staff Guide, section 5.' mod='salisbergguide'}</li>
			<li><strong>{l s='Dark mode.' mod='salisbergguide'}</strong> {l s='Lists and forms that still showed white or pale blue areas in dark mode have been corrected.' mod='salisbergguide'}</li>
			<li><strong>{l s='Menu arrows.' mod='salisbergguide'}</strong> {l s='Each menu section with pages under it has a small arrow. Click it to list the pages and click again to fold them away. Pages no longer pop out beside the menu when the pointer passes over.' mod='salisbergguide'}</li>
			<li><strong>{l s='Sign-in page.' mod='salisbergguide'}</strong> {l s='The back office sign-in page is simpler: the logo and one form.' mod='salisbergguide'}</li>
			<li><strong>{l s='Tips and pop-ups.' mod='salisbergguide'}</strong> {l s='The small explanations that appear when you point at something, and pop-up windows, are tidier and easier to read in both light and dark.' mod='salisbergguide'}</li>
			<li><strong>{l s='Larger menu text.' mod='salisbergguide'}</strong> {l s='The menu on the left is a little bigger and easier to read.' mod='salisbergguide'}</li>
			<li><strong>{l s='Contact form.' mod='salisbergguide'}</strong> {l s='Guests can no longer attach a file to a message sent from the website. Messages arrive as before under Customers, Customer Service.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Printable reports.' mod='salisbergguide'}</strong> {l s='Bookings, Reports gives three reports for today, this week, this month or any dates you choose: bookings, arrivals and departures, and income by day. Each can be downloaded as a PDF. See the Admin Guide, section 14.' mod='salisbergguide'}</li>
			<li>{l s='The sample guest account that came with the system (John Doe) has been retired.' mod='salisbergguide'}</li>
			<li><strong>{l s='Dark mode on the website.' mod='salisbergguide'}</strong> {l s='Visitors can switch the website between light and dark with the sun and moon switch in the header. It starts light and remembers each visitor’s choice on their device.' mod='salisbergguide'}</li>
			<li><strong>{l s='Website footer.' mod='salisbergguide'}</strong> {l s='Payment accepted now shows Cash and Mobile Money in place of the card logos. Home, Our Properties, Interior and Contact Us are listed under Explore. The empty Follow us on heading is hidden until social links are entered, and the sample founding year 2010 is gone from the copyright line.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='Bundled libraries updated: jQuery 1.12.4, Bootstrap scripts 3.4.1 and the text editor (TinyMCE) 4.9.11. Bank transfer details are entered in Salisberg Pay settings: Admin Guide, section 6.' mod='salisbergguide'}</li>
			<li>{l s='The system now runs on PHP 8.3 (8.1 no longer receives security fixes). The MySQL version is a setting, MYSQL_VERSION, and stays on 8.0 until it is changed on purpose. A check script, docker/smoke-test.sh, confirms the main pages after any change.' mod='salisbergguide'}</li>
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

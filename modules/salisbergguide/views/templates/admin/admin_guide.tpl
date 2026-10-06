<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-guide">
	<div class="sb-intro">
		<h2>{l s='Admin Guide' mod='salisbergguide'}</h2>
		<p>{l s='Setting up and running the hotel in the system. Only the hotel manager and the developer team can see this page. Staff tasks (bookings, payments, check-in) are in the Staff Guide.' mod='salisbergguide'}</p>
	</div>

	<div class="sb-toc">
		<strong>{l s='In this guide' mod='salisbergguide'}</strong>
		<ol>
			<li><a href="#sb-a1">{l s='Staff accounts and what they can see' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a2">{l s='Hotel details' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a3">{l s='Room types and rooms' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a4">{l s='Prices, seasons and discounts' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a5">{l s='Extra services' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a6">{l s='Payment methods: cash and Mobile Money' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a7">{l s='Currency and taxes' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a8">{l s='Cancellations and refunds' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a9">{l s='Website content' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a10">{l s='Email' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a11">{l s='Closing the site for maintenance' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a12">{l s='Backups' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a13">{l s='Things that must be done by the developer' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a14">{l s='Reports' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a15">{l s='Showing or hiding menu sections' mod='salisbergguide'}</a></li>
			<li><a href="#sb-a16">{l s='Advanced Parameters, page by page' mod='salisbergguide'}</a></li>
		</ol>
	</div>

	<section id="sb-a1">
		<h3>1. {l s='Staff accounts and what they can see' mod='salisbergguide'}</h3>
		<p>{l s='Each person signs in with their own account. What they can open depends on their profile.' mod='salisbergguide'}</p>
		<table>
			<tr><th>{l s='Profile' mod='salisbergguide'}</th><th>{l s='Who it is for' mod='salisbergguide'}</th><th>{l s='What it can do' mod='salisbergguide'}</th></tr>
			<tr><td>SuperAdmin</td><td>{l s='The developer team' mod='salisbergguide'}</td><td>{l s='Everything, including how the system is installed and built: modules, payment settings, currency and taxes, email, maintenance, backups, profiles and permissions.' mod='salisbergguide'}</td></tr>
			<tr><td>Hotel Manager</td><td>{l s='The person who runs the hotel' mod='salisbergguide'}</td><td>{l s='Everything about the hotel itself: bookings (including deleting), guests, room types, prices and discounts, extra services, refund rules and requests, hotel details, website pages and homepage content, staff accounts, reports, and both guides. Cannot open modules, system settings, profiles or permissions.' mod='salisbergguide'}</td></tr>
			<tr><td>Hotel Staff</td><td>{l s='Front desk and reservations' mod='salisbergguide'}</td><td>{l s='Make and edit bookings, record payments, check guests in and out, manage guest records, answer messages, view refund requests, read the Staff Guide. Cannot delete anything, and cannot see prices setup, settings, modules, staff accounts or this guide.' mod='salisbergguide'}</td></tr>
		</table>
		<h4>{l s='Add a member of staff' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Employees</span> {l s='and click Add new employee.' mod='salisbergguide'}</li>
			<li>{l s='Enter their name, their own email address and a strong password.' mod='salisbergguide'}</li>
			<li>{l s='Set Permission profile to Hotel Staff for front desk and reservations, or Hotel Manager for someone who runs the hotel. Only the developer team can give out SuperAdmin.' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Save</span> {l s='and give them the back office address. Ask them to change the password on first sign-in.' mod='salisbergguide'}</li>
		</ol>
		<h4>{l s='When someone leaves' mod='salisbergguide'}</h4>
		<p>{l s='Open their record in Employees and switch Active to No the same day. Do not delete the account: their name stays on the bookings they handled.' mod='salisbergguide'}</p>
		<h4>{l s='Change what a profile can do' mod='salisbergguide'}</h4>
		{if $sb_is_admin}
		<p>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Permissions</span>, {l s='pick the profile on the left, and tick View, Add, Edit or Delete for each page. To create another role (for example Housekeeping), add it in' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Profiles</span> {l s='first.' mod='salisbergguide'}</p>
		{else}
		<p>{l s='What each profile may open is set by the developer team. Tell them if a member of staff needs a page they cannot see, or should lose one.' mod='salisbergguide'}</p>
		{/if}
		<p class="sb-tip">{l s='Back office sessions end after 12 hours, so everyone signs in again each working day.' mod='salisbergguide'}</p>
		<h4>{l s='Sign-in and form limits' mod='salisbergguide'}</h4>
		<p>{l s='To slow down password guessing and spam, the site limits how often the same internet connection can use certain forms. When a limit is reached the person sees "Too many attempts" and the time to wait.' mod='salisbergguide'}</p>
		<table>
			<tr><th>{l s='Form' mod='salisbergguide'}</th><th>{l s='Limit per connection' mod='salisbergguide'}</th></tr>
			<tr><td>{l s='Back office sign-in' mod='salisbergguide'}</td><td>{l s='10 tries in 10 minutes' mod='salisbergguide'}</td></tr>
			<tr><td>{l s='Guest sign-in on the website' mod='salisbergguide'}</td><td>{l s='10 tries in 10 minutes' mod='salisbergguide'}</td></tr>
			<tr><td>{l s='Password reset requests' mod='salisbergguide'}</td><td>{l s='5 an hour' mod='salisbergguide'}</td></tr>
			<tr><td>{l s='New guest accounts' mod='salisbergguide'}</td><td>{l s='10 an hour' mod='salisbergguide'}</td></tr>
			<tr><td>{l s='Contact form messages' mod='salisbergguide'}</td><td>{l s='6 an hour' mod='salisbergguide'}</td></tr>
		</table>
		<p>{l s='Everyone at the hotel usually shares one internet connection, so several staff mistyping passwords at once can reach the limit together. It clears by itself; nobody needs to be unblocked. The limits are changed by the developer.' mod='salisbergguide'}</p>
		<p class="sb-warn">{if $sb_is_admin}{l s='Keep the number of SuperAdmin accounts small, and never share one login between people. A SuperAdmin can change prices, payment details and every other setting.' mod='salisbergguide'}{else}{l s='Never share one login between people. A Hotel Manager can change prices and delete bookings, so give that profile only to people who run the hotel.' mod='salisbergguide'}{/if}</p>
		<a class="sb-go" href="{$sb_links.AdminEmployees|escape:'html':'UTF-8'}">{l s='Open Employees' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-a2">
		<h3>2. {l s='Hotel details' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; Manage Hotel</span> {l s='and open the hotel.' mod='salisbergguide'}</li>
			<li>{l s='Work through the tabs:' mod='salisbergguide'}
				<ul>
					<li><strong>Information</strong> &ndash; {l s='name, descriptions, phone, email, address, star rating, check-in and check-out times.' mod='salisbergguide'}</li>
					<li><strong>Images</strong> &ndash; {l s='photos of the property.' mod='salisbergguide'}</li>
					<li><strong>Restrictions</strong> &ndash; {l s='how far ahead guests can book and minimum notice.' mod='salisbergguide'}</li>
					<li><strong>Refund Policies</strong> &ndash; {l s='which refund rules apply to this hotel (see section 8).' mod='salisbergguide'}</li>
					<li><strong>Features</strong> &ndash; {l s='facilities shown on the room pages.' mod='salisbergguide'}</li>
					<li><strong>Seo</strong> &ndash; {l s='the title and description search engines show.' mod='salisbergguide'}</li>
				</ul>
			</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Save and stay</span> {l s='after each tab.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='The hotel name, tag line and large header photo on the homepage are set separately, in' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; General Settings</span>, {l s='under Website Configuration.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminAddHotel|escape:'html':'UTF-8'}">{l s='Open Manage Hotel' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-a3">
		<h3>3. {l s='Room types and rooms' mod='salisbergguide'}</h3>
		<p>{l s='A room type is what guests choose on the website (for example Deluxe Room). Each type contains the actual numbered rooms.' mod='salisbergguide'}</p>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Catalog &rsaquo; Manage Room Types</span>. {l s='Click a type to edit it, or Add new.' mod='salisbergguide'}</li>
			<li>{l s='Fill in the tabs:' mod='salisbergguide'}
				<ul>
					<li><strong>Information</strong> &ndash; {l s='name, hotel, descriptions, and whether it is shown on the website.' mod='salisbergguide'}</li>
					<li><strong>Prices</strong> &ndash; {l s='the price per night and its tax rule.' mod='salisbergguide'}</li>
					<li><strong>Images</strong> &ndash; {l s='photos; the cover image is the one used in lists.' mod='salisbergguide'}</li>
					<li><strong>Features</strong> &ndash; {l s='amenities such as Wi-Fi.' mod='salisbergguide'}</li>
					<li><strong>Rooms</strong> &ndash; {l s='the individual rooms of this type and whether each is available.' mod='salisbergguide'}</li>
					<li><strong>Occupancy</strong> &ndash; {l s='how many adults and children it sleeps.' mod='salisbergguide'}</li>
					<li><strong>Service Products</strong> / <strong>Additional Facilities</strong> &ndash; {l s='extras offered with this room.' mod='salisbergguide'}</li>
					<li><strong>Length of Stay</strong> &ndash; {l s='minimum and maximum nights.' mod='salisbergguide'}</li>
				</ul>
			</li>
			<li>{l s='Save. The room type appears on the website when it is enabled and has at least one available room.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='To take one room out of service (repairs, deep clean), change that room in the Rooms tab rather than disabling the whole type.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminProducts|escape:'html':'UTF-8'}">{l s='Open Manage Room Types' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-a4">
		<h3>4. {l s='Prices, seasons and discounts' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Normal price:' mod='salisbergguide'}</strong> {l s='the Prices tab of the room type (section 3).' mod='salisbergguide'}</li>
			<li><strong>{l s='Different prices for dates or days of the week' mod='salisbergguide'}</strong> {l s='(holidays, weekends, low season):' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; Advanced Price Rules</span>. {l s='A rule can raise or lower the price, for one room type or several, for a date range or specific weekdays.' mod='salisbergguide'}</li>
			<li><strong>{l s='Voucher codes and promotions:' mod='salisbergguide'}</strong> <span class="sb-path">Manage Discounts &rsaquo; Cart Rules</span>. {l s='Set the code, the amount or percentage off, the validity dates and how many times it can be used.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-warn">{l s='After changing prices, open the website and search for a few dates to confirm guests see what you expect. Existing bookings keep the price they were made at.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminHotelFeaturePricesSettings|escape:'html':'UTF-8'}">{l s='Open Advanced Price Rules' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-a5">
		<h3>5. {l s='Extra services' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Paid extras' mod='salisbergguide'}</strong> {l s='such as breakfast, dinner or airport transfer:' mod='salisbergguide'} <span class="sb-path">Catalog &rsaquo; Manage Service Products</span>.</li>
			<li><strong>{l s='Room add-ons' mod='salisbergguide'}</strong> {l s='such as an extra bed:' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; Additional Facilities</span>.</li>
		</ul>
		<p>{l s='Attach them to room types in that type\'s Service Products and Additional Facilities tabs.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-a6">
		<h3>6. {l s='Payment methods: cash and Mobile Money' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<p>{l s='Guests can book and pay with cash at the hotel or by Mobile Money. Both are confirmed by staff; nothing is charged automatically.' mod='salisbergguide'}</p>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Modules and Services &rsaquo; Manage Modules</span> {l s='and search for Salisberg Pay.' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Configure</span>.</li>
			<li>{l s='Switch Cash at the hotel and Mobile Money on or off.' mod='salisbergguide'}</li>
			<li>{l s='Enter the network, the registered account name and the Mobile Money number, then Save.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-warn">{l s='Mobile Money is hidden from guests until a number is saved. Check the number twice: guests will send money to exactly what is typed here. Anyone with a SuperAdmin login can change it, which is one more reason to keep those accounts few.' mod='salisbergguide'}</p>
		<p>{l s='Which countries and customer groups may use each payment method is set in' mod='salisbergguide'} <span class="sb-path">Modules and Services &rsaquo; Payment</span>.</p>
		<a class="sb-go" href="{$sb_links.AdminModules|escape:'html':'UTF-8'}&amp;configure=salisbergpay">{l s='Open Salisberg Pay settings' mod='salisbergguide'} &rarr;</a>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a7">
		<h3>7. {l s='Currency and taxes' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<ul>
			<li><strong>{l s='Currency:' mod='salisbergguide'}</strong> <span class="sb-path">Localization &rsaquo; Currencies</span>. {l s='The shop runs in Ghana cedis (GH₵). Do not delete this currency; bookings are stored in it.' mod='salisbergguide'}</li>
			<li><strong>{l s='Tax rates:' mod='salisbergguide'}</strong> <span class="sb-path">Localization &rsaquo; Taxes</span> {l s='holds each rate.' mod='salisbergguide'} <span class="sb-path">Localization &rsaquo; Tax Rules</span> {l s='groups rates and says which country they apply to. A room type picks one tax rule in its Prices tab.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-tip">{l s='Ask your accountant which taxes and levies apply to accommodation before changing these. The sample tax rules that came with the system are not Ghana rates.' mod='salisbergguide'}</p>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a8">
		<h3>8. {l s='Cancellations and refunds' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Set the rules in' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; Manage Order Refund Rules</span>: {l s='how much is kept depending on how many days before check-in the guest cancels.' mod='salisbergguide'}</li>
			<li>{l s='Attach rules to the hotel in Manage Hotel, Refund Policies tab.' mod='salisbergguide'}</li>
			<li>{l s='Guest requests arrive in' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; Manage Order Refund Requests</span>. {l s='Open one, decide, and set its status. The system does not send money: return cash or Mobile Money yourself, then record it on the booking.' mod='salisbergguide'}</li>
		</ol>
	</section>

	<section id="sb-a9">
		<h3>9. {l s='Website content' mod='salisbergguide'}</h3>
		<table>
			<tr><th>{l s='What' mod='salisbergguide'}</th><th>{l s='Where' mod='salisbergguide'}</th></tr>
			<tr><td>{l s='Homepage hotel name, tag line, short description and header photo' mod='salisbergguide'}</td><td><span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; General Settings</span></td></tr>
			<tr><td>{l s='Homepage photo gallery' mod='salisbergguide'}</td><td><span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; Hotel Interior Block</span></td></tr>
			<tr><td>{l s='Homepage amenities' mod='salisbergguide'}</td><td><span class="sb-path">Hotel Reservation System &rsaquo; General Settings &rsaquo; Hotel Amenities Block</span></td></tr>
			{if $sb_is_admin}
			<tr><td>{l s='Homepage guest reviews, room list, footer payment icons' mod='salisbergguide'}</td><td><span class="sb-path">Modules and Services &rsaquo; Manage Modules</span>, {l s='then Configure on the block' mod='salisbergguide'}</td></tr>
			{/if}
			<tr><td>{l s='Pages such as About Us, Terms, Legal Notice' mod='salisbergguide'}</td><td><span class="sb-path">Preferences &rsaquo; CMS</span></td></tr>
			{if $sb_is_admin}
			<tr><td>{l s='Page titles and descriptions for search engines' mod='salisbergguide'}</td><td><span class="sb-path">Preferences &rsaquo; SEO &amp; URLs</span></td></tr>
			{/if}
			<tr><td>{l s='Who receives messages from the Contact form' mod='salisbergguide'}</td><td><span class="sb-path">Customers &rsaquo; Contacts</span></td></tr>
			<tr><td>{l s='Whether guests can attach a file to a Contact form message (switched off: the form takes messages only)' mod='salisbergguide'}</td><td><span class="sb-path">Customers &rsaquo; Customer Service</span>, {l s='Contact options, Allow file uploading' mod='salisbergguide'}</td></tr>
		</table>
		<p class="sb-tip">{l s='The Hotel Reviews feature (guests rating their stay after check-out) is switched off for now. Ask the developer to switch it on when you want to start collecting reviews; it cannot be enabled from the Modules page because it is switched off again at every update.' mod='salisbergguide'}</p>
		<h4>{l s='Homepage guest reviews ("What our guests say")' mod='salisbergguide'}</h4>
		{if $sb_is_admin}
		<p>{l s='This section is switched off, because the system came with invented sample reviews. To show real ones:' mod='salisbergguide'}</p>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Modules and Services &rsaquo; Manage Modules</span>, {l s='search for Testimonial, and click Enable on the testimonial block.' mod='salisbergguide'}</li>
			<li>{l s='Click Configure on it. Delete the three samples and add your own: what the guest said, their name, and a photo only if they agreed to it.' mod='salisbergguide'}</li>
			<li>{l s='To put the Testimonials link back in the website menu, ask the developer.' mod='salisbergguide'}</li>
		</ol>
		{else}
		<p>{l s='This section is switched off, because the system came with invented sample reviews. When you have real reviews to show, ask the developer team to switch it on.' mod='salisbergguide'}</p>
		{/if}
		<p class="sb-warn">{l s='Only publish reviews from real guests who agreed to be quoted. Do not re-enable the block while the samples are still in it.' mod='salisbergguide'}</p>
		<p class="sb-tip">{l s='The logo, colours, fonts and page layout are part of the site design and are changed by the developer, not here.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-a10">
		<h3>10. {l s='Email' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<p>{l s='Booking confirmations and password resets are sent by email. Set how they are sent in' mod='salisbergguide'} <span class="sb-path">Advanced Parameters &rsaquo; E-mail</span>.</p>
		<ol>
			<li>{l s='Choose to send through your own SMTP server and enter the details from your email provider (server, port, username, password, encryption).' mod='salisbergguide'}</li>
			<li>{l s='Use the Test your email configuration box at the bottom to send yourself a test.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='Without SMTP, messages are likely to land in spam or not arrive at all. Make a test booking with your own email address after any change here.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminEmails|escape:'html':'UTF-8'}">{l s='Open E-mail settings' mod='salisbergguide'} &rarr;</a>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a11">
		<h3>11. {l s='Closing the site for maintenance' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<p>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Preferences &rsaquo; Maintenance</span> {l s='and set Enable Site to No. Visitors see a maintenance page. Set it back to Yes when you are done, and check the website yourself afterwards.' mod='salisbergguide'}</p>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a12">
		<h3>12. {l s='Backups' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<ul>
			<li>{l s='The database and the settings file are backed up automatically every night, encrypted, and the last 14 days are kept on the hosting server. Ask the developer to confirm copies are also sent somewhere off the server.' mod='salisbergguide'}</li>
			<li>{l s='Restoring a backup is done by the developer. Tell them the date and roughly the time you want to go back to.' mod='salisbergguide'}</li>
			<li>{l s='You can also make one by hand in' mod='salisbergguide'} <span class="sb-path">Advanced Parameters &rsaquo; DB Backup</span>. {l s='Download the file to your computer straight away: backups left on the server are removed whenever the site is updated.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-warn">{l s='A backup contains every guest\'s personal details. Store it somewhere private and never send it by email or chat.' mod='salisbergguide'}</p>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a13">
		<h3>13. {l s='Things that must be done by the developer' mod='salisbergguide'}</h3>
		<p>{l s='The site is rebuilt from its source code every time it is updated. Changes made in the following places are wiped at the next update, so ask the developer instead:' mod='salisbergguide'}</p>
		<ul>
			<li>{l s='Installing or uploading a new module or theme.' mod='salisbergguide'}</li>
			<li>{l s='The 1-Click Upgrade tool. Do not run it.' mod='salisbergguide'}</li>
			<li>{l s='Editing template or translation files from inside the back office.' mod='salisbergguide'}</li>
			<li>{l s='Changing the website address, or the back office address.' mod='salisbergguide'}</li>
		</ul>
		<p>{l s='Everything else in this guide (hotel, rooms, prices, bookings, guests, staff, content, images you upload) is stored safely and survives updates.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-a14">
		<h3>14. {l s='Reports' mod='salisbergguide'}</h3>
		<ul>
			<li><span class="sb-path">Dashboard</span> &ndash; {l s='bookings, income and occupancy for a period you choose at the top.' mod='salisbergguide'}</li>
			<li><span class="sb-path">Stats</span> &ndash; {l s='detailed reports: sales, best room types, visitors and more.' mod='salisbergguide'}</li>
			<li><span class="sb-path">Bookings &rsaquo; Bookings</span> &ndash; {l s='filter the list, then export it to a spreadsheet with the export button at the top.' mod='salisbergguide'}</li>
		</ul>
	</section>

	<section id="sb-a15">
		<h3>15. {l s='Showing or hiding menu sections' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<p>{l s='Sections the hotel does not use can be taken out of the menu on the left, for everyone. Nothing is deleted or switched off: a hidden section only disappears from the menu, and its pages still open from the links in this guide for those with permission.' mod='salisbergguide'}</p>
		<ul>
			<li><strong>Channel Manager</strong> &ndash; {l s='only needed if rooms are also sold through booking sites such as Booking.com or Expedia, and it does nothing until a channel manager service is bought and connected.' mod='salisbergguide'}</li>
			<li><strong>Modules and Services</strong> &ndash; {l s='the building blocks of the system. Hide it to keep the menu short once setup is finished.' mod='salisbergguide'}</li>
		</ul>
		<form method="post" action="{$sb_links.AdminSalisbergAdminGuide|escape:'html':'UTF-8'}#sb-a15" class="sb-menu-form">
			{foreach from=$sb_menu_toggles key=sb_class item=sb_toggle}
			<div class="sb-menu-row">
				<span class="sb-menu-name">{$sb_toggle.name|escape:'html':'UTF-8'}</span>
				<span class="switch prestashop-switch fixed-width-lg">
					<input type="radio" name="sb_menu_{$sb_class|escape:'html':'UTF-8'}" id="sb_menu_{$sb_class|escape:'html':'UTF-8'}_on" value="1"{if $sb_toggle.visible} checked="checked"{/if} />
					<label for="sb_menu_{$sb_class|escape:'html':'UTF-8'}_on">{l s='Show' mod='salisbergguide'}</label>
					<input type="radio" name="sb_menu_{$sb_class|escape:'html':'UTF-8'}" id="sb_menu_{$sb_class|escape:'html':'UTF-8'}_off" value="0"{if !$sb_toggle.visible} checked="checked"{/if} />
					<label for="sb_menu_{$sb_class|escape:'html':'UTF-8'}_off">{l s='Hide' mod='salisbergguide'}</label>
					<a class="slide-button btn"></a>
				</span>
			</div>
			{/foreach}
			<button type="submit" name="submitSbMenu" class="btn btn-primary">{l s='Save' mod='salisbergguide'}</button>
		</form>
		<p class="sb-tip">{l s='Each profile already sees only the sections it has permission for. Hotel Staff and Hotel Manager never see Modules and Services or Channel Manager, whatever is chosen here.' mod='salisbergguide'}</p>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>

	<section id="sb-a16">
		<h3>16. {l s='Advanced Parameters, page by page' mod='salisbergguide'}</h3>
		{if $sb_is_admin}
		<p>{l s='The technical pages. Most are for looking, not changing. This table says what each one is for and what to do there.' mod='salisbergguide'}</p>
		<table>
			<tr><th>{l s='Page' mod='salisbergguide'}</th><th>{l s='What it is for' mod='salisbergguide'}</th><th>{l s='What to do' mod='salisbergguide'}</th></tr>
			<tr><td><span class="sb-path">Configuration Information</span></td><td>{l s='A read-only summary of the server, database, website and mail setup, plus two checks.' mod='salisbergguide'}</td><td>{l s='Look here first when something is wrong, and copy it into any problem report. See below.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Performance</span></td><td>{l s='How pages are prepared and cached.' mod='salisbergguide'}</td><td>{l s='If a page looks out of date or broken after a change, click Clear cache at the top right. Leave the other settings as described below.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">E-mail</span></td><td>{l s='How the site sends email.' mod='salisbergguide'}</td><td>{l s='See section 10.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">CSV Import</span></td><td>{l s='Loads many records at once from a spreadsheet file: Hotels, Room Types, Rooms, Categories, Service Products, Bookings or Customers.' mod='salisbergguide'}</td><td>{l s='Useful when moving from another system. See below before using it.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">DB Backup</span></td><td>{l s='Makes a copy of the database by hand.' mod='salisbergguide'}</td><td>{l s='See section 12. The nightly automatic backup is the one to rely on.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">SQL Manager</span></td><td>{l s='Saved database questions whose answers can be exported to a spreadsheet, for reports the other pages do not offer.' mod='salisbergguide'}</td><td>{l s='Click Add new SQL query, give it a name and the query, and Save. Only SELECT queries are accepted, so nothing can be changed or deleted from here.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Logs</span></td><td>{l s='A record of back office sign-ins and of errors, with a severity from 1 (informative) to 4 (major issue).' mod='salisbergguide'}</td><td>{l s='Check it when a sign-in looks suspicious or something failed. Logs by email at the bottom can send serious entries to the shop email.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Webservice</span></td><td>{l s='Lets other software read or change bookings, guests and rooms with an access key, without anyone signing in.' mod='salisbergguide'}</td><td>{l s='Leave it switched off. See below.' mod='salisbergguide'}</td></tr>
		</table>

		<h4>{l s='Configuration Information' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>Server information</strong>, <strong>Database information</strong>, <strong>Website information</strong> &ndash; {l s='versions and addresses. Nothing to change; they come from how the site is hosted.' mod='salisbergguide'}</li>
			<li><strong>Mail configuration</strong> &ndash; {l s='shows how email is sent. If it says "You are using the PHP mail() function", email has not been set up yet: do section 10.' mod='salisbergguide'}</li>
			<li><strong>Check your configuration</strong> &ndash; {l s='Required parameters and Optional parameters should both say OK. Anything else is a fault in the installation: send the message shown to the developer team.' mod='salisbergguide'}</li>
			<li><strong>List of changed files</strong> &ndash; {l s='compares the site with the original software. It always lists files here, because this site is a customised version: the design, the security fixes and the Salisberg features are all changes. A long list is expected and is not a fault.' mod='salisbergguide'}</li>
		</ul>
		<a class="sb-go" href="{$sb_links.AdminInformation|escape:'html':'UTF-8'}">{l s='Open Configuration Information' mod='salisbergguide'} &rarr;</a>

		<h4>{l s='Performance: settings to keep' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>Smarty</strong> &ndash; {l s='Template compilation: Recompile templates if the files have been updated. Cache: Yes.' mod='salisbergguide'}</li>
			<li><strong>Debug mode</strong> &ndash; {l s='both switches on No. They switch off the Salisberg modules and fixes.' mod='salisbergguide'}</li>
			<li><strong>CCC (Combine, Compress and Cache)</strong> {l s='and' mod='salisbergguide'} <strong>Media servers</strong> &ndash; {l s='leave as they are. Turning them on changes how every page loads and has not been tested with this design.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-tip">{l s='Clear cache is always safe. It only makes the next few page loads a little slower.' mod='salisbergguide'}</p>

		<h4>{l s='CSV Import' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='Make a backup first (section 12). An import cannot be undone.' mod='salisbergguide'}</li>
			<li>{l s='Choose what to import, then download the matching file under Download sample csv files and fill it in the same layout. The Available fields box lists every column; those marked * are required.' mod='salisbergguide'}</li>
			<li>{l s='Upload the file, match the columns on the next screen, and import a file with two or three rows first to check the result before loading the rest.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-warn">{l s='Importing with the same ID as an existing record replaces that record. Leave the ID column empty to add new ones.' mod='salisbergguide'}</p>

		<h4>{l s='Webservice' mod='salisbergguide'}</h4>
		<p>{l s='The webservice is a door for other programs, not for people. With it on, a program holding a key can read and change data directly: a channel manager, an accounting package or a mobile app would use it. Nothing at the hotel needs it today, so it is off, and an unused door is best kept shut.' mod='salisbergguide'}</p>
		<p>{l s='If an integration is added later: set the first switch under Configuration to Yes, click Add new webservice key, tick only the resources and actions that program needs, and give the key to nobody else. Delete the key when the integration stops being used.' mod='salisbergguide'}</p>
		<p class="sb-warn">{l s='A webservice key is as powerful as a login with the permissions ticked on it, and it has no password prompt and no sign-in limit. Never tick everything, and never send a key by email or chat.' mod='salisbergguide'}</p>
		{else}
		<p class="sb-tip">{l s='This is looked after by the developer team. Ask them when it needs to change.' mod='salisbergguide'}</p>
		{/if}
	</section>
</div>

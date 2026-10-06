<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-guide">
	<div class="sb-intro">
		<h2>{l s='Admin Guide' mod='salisbergguide'}</h2>
		<p>{l s='Setting up and running the system. Only administrators can see this page. Staff tasks (bookings, payments, check-in) are in the Staff Guide.' mod='salisbergguide'}</p>
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
		</ol>
	</div>

	<section id="sb-a1">
		<h3>1. {l s='Staff accounts and what they can see' mod='salisbergguide'}</h3>
		<p>{l s='Each person signs in with their own account. What they can open depends on their profile.' mod='salisbergguide'}</p>
		<table>
			<tr><th>{l s='Profile' mod='salisbergguide'}</th><th>{l s='Who it is for' mod='salisbergguide'}</th><th>{l s='What it can do' mod='salisbergguide'}</th></tr>
			<tr><td>SuperAdmin</td><td>{l s='Owner and managers' mod='salisbergguide'}</td><td>{l s='Everything, including this guide.' mod='salisbergguide'}</td></tr>
			<tr><td>Hotel Staff</td><td>{l s='Front desk and reservations' mod='salisbergguide'}</td><td>{l s='Make and edit bookings, record payments, check guests in and out, manage guest records, answer messages, view refund requests, read the Staff Guide. Cannot delete anything, and cannot see prices setup, settings, modules, staff accounts or this guide.' mod='salisbergguide'}</td></tr>
		</table>
		<h4>{l s='Add a member of staff' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Employees</span> {l s='and click Add new employee.' mod='salisbergguide'}</li>
			<li>{l s='Enter their name, their own email address and a strong password.' mod='salisbergguide'}</li>
			<li>{l s='Set Permission profile to Hotel Staff (or SuperAdmin for a manager you fully trust).' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Save</span> {l s='and give them the back office address. Ask them to change the password on first sign-in.' mod='salisbergguide'}</li>
		</ol>
		<h4>{l s='When someone leaves' mod='salisbergguide'}</h4>
		<p>{l s='Open their record in Employees and switch Active to No the same day. Do not delete the account: their name stays on the bookings they handled.' mod='salisbergguide'}</p>
		<h4>{l s='Change what a profile can do' mod='salisbergguide'}</h4>
		<p>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Permissions</span>, {l s='pick the profile on the left, and tick View, Add, Edit or Delete for each page. To create another role (for example Housekeeping), add it in' mod='salisbergguide'} <span class="sb-path">Administration &rsaquo; Profiles</span> {l s='first.' mod='salisbergguide'}</p>
		<p class="sb-warn">{l s='Keep the number of SuperAdmin accounts small, and never share one login between people. A SuperAdmin can change prices, payment details and every other setting.' mod='salisbergguide'}</p>
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
	</section>

	<section id="sb-a7">
		<h3>7. {l s='Currency and taxes' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Currency:' mod='salisbergguide'}</strong> <span class="sb-path">Localization &rsaquo; Currencies</span>. {l s='The shop runs in Ghana cedis (GH₵). Do not delete this currency; bookings are stored in it.' mod='salisbergguide'}</li>
			<li><strong>{l s='Tax rates:' mod='salisbergguide'}</strong> <span class="sb-path">Localization &rsaquo; Taxes</span> {l s='holds each rate.' mod='salisbergguide'} <span class="sb-path">Localization &rsaquo; Tax Rules</span> {l s='groups rates and says which country they apply to. A room type picks one tax rule in its Prices tab.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-tip">{l s='Ask your accountant which taxes and levies apply to accommodation before changing these. The sample tax rules that came with the system are not Ghana rates.' mod='salisbergguide'}</p>
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
			<tr><td>{l s='Homepage guest reviews, room list, footer payment icons' mod='salisbergguide'}</td><td><span class="sb-path">Modules and Services &rsaquo; Manage Modules</span>, {l s='then Configure on the block' mod='salisbergguide'}</td></tr>
			<tr><td>{l s='Pages such as About Us, Terms, Legal Notice' mod='salisbergguide'}</td><td><span class="sb-path">Preferences &rsaquo; CMS</span></td></tr>
			<tr><td>{l s='Page titles and descriptions for search engines' mod='salisbergguide'}</td><td><span class="sb-path">Preferences &rsaquo; SEO &amp; URLs</span></td></tr>
			<tr><td>{l s='Who receives messages from the Contact form' mod='salisbergguide'}</td><td><span class="sb-path">Customers &rsaquo; Contacts</span></td></tr>
		</table>
		<p class="sb-warn">{l s='The guest reviews on the homepage must be real. Replace the samples that came with the system with reviews from actual guests, or switch the block off until you have some.' mod='salisbergguide'}</p>
		<p class="sb-tip">{l s='The logo, colours, fonts and page layout are part of the site design and are changed by the developer, not here.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-a10">
		<h3>10. {l s='Email' mod='salisbergguide'}</h3>
		<p>{l s='Booking confirmations and password resets are sent by email. Set how they are sent in' mod='salisbergguide'} <span class="sb-path">Advanced Parameters &rsaquo; E-mail</span>.</p>
		<ol>
			<li>{l s='Choose to send through your own SMTP server and enter the details from your email provider (server, port, username, password, encryption).' mod='salisbergguide'}</li>
			<li>{l s='Use the Test your email configuration box at the bottom to send yourself a test.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='Without SMTP, messages are likely to land in spam or not arrive at all. Make a test booking with your own email address after any change here.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminEmails|escape:'html':'UTF-8'}">{l s='Open E-mail settings' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-a11">
		<h3>11. {l s='Closing the site for maintenance' mod='salisbergguide'}</h3>
		<p>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Preferences &rsaquo; Maintenance</span> {l s='and set Enable Site to No. Visitors see a maintenance page. Set it back to Yes when you are done, and check the website yourself afterwards.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-a12">
		<h3>12. {l s='Backups' mod='salisbergguide'}</h3>
		<ul>
			<li>{l s='The reliable backup is the scheduled database backup on the hosting server. Ask the developer to confirm it is switched on and where the copies are kept.' mod='salisbergguide'}</li>
			<li>{l s='You can also make one by hand in' mod='salisbergguide'} <span class="sb-path">Advanced Parameters &rsaquo; DB Backup</span>. {l s='Download the file to your computer straight away: backups left on the server are removed whenever the site is updated.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-warn">{l s='A backup contains every guest\'s personal details. Store it somewhere private and never send it by email or chat.' mod='salisbergguide'}</p>
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
			<li><span class="sb-path">Orders &rsaquo; Orders</span> &ndash; {l s='filter the list, then export it to a spreadsheet with the export button at the top.' mod='salisbergguide'}</li>
		</ul>
	</section>
</div>

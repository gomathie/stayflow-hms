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
		<h3>{l s='16 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Booking details are less cluttered.' mod='salisbergguide'}</strong> {l s='The summary figures use a compact strip that keeps the booking information visible. The account, theme and website controls sit at the far right of the top bar.' mod='salisbergguide'}</li>
			<li><strong>{l s='A mistaken booking can be archived.' mod='salisbergguide'}</strong> {l s='Use Delete in the Bookings list and enter a reason. The booking is removed from active bookings and room availability, not permanently erased. Hotel Staff cannot open the Recycle bin or restore a booking.' mod='salisbergguide'}</li>
			<li><strong>{l s='Room status changes are simpler.' mod='salisbergguide'}</strong> {l s='Choose a status from the Change room status options beside the room; the Rooms Status section grows to show the complete list together without a scrollbar. The choice applies immediately with no extra save button. Check in and Check out show a small Yes or No confirmation beside the button, not far away at the top of the screen. For a check-in, optionally tick Set actual check-in time to use or adjust the current time. Checking out marks the room Dirty for housekeeping.' mod='salisbergguide'}</li>
			<li><strong>{l s='Booking details print as a customer-ready document.' mod='salisbergguide'}</strong> {l s='Use the browser Print command on a booking to print its reference, guest contact, room stay, payments, total and balance without back-office controls or private notes.' mod='salisbergguide'}</li>
			<li><strong>{l s='Confirm and check in now completes the booking.' mod='salisbergguide'}</strong> {l s='The booking is saved, the room is checked in, and the page shows the confirmation instead of staying on the Confirm step.' mod='salisbergguide'}</li>
			<li><strong>{l s='Bookings is now the first section after Dashboard.' mod='salisbergguide'}</strong> {l s='It sits above Rooms & Services in the left menu, because the front desk uses it all day.' mod='salisbergguide'}</li>
			<li><strong>{l s='Confirm a booking now, check the guest in when they arrive.' mod='salisbergguide'}</strong> {l s='For a room that is ready today, New booking offers Confirm and check in, or Confirm booking, check in later. After confirming, the page shows Check in now. A guest who paid by phone or has not arrived stays Not checked in, and you can check them in later with the Check in button on the booking (Bookings, then search the name or reference) or from Today on the Dashboard.' mod='salisbergguide'}</li>
			<li><strong>{l s='Editing the stay of a booking you have started works again.' mod='salisbergguide'}</strong> {l s='In New booking, Edit stay details showed a database error when saved. You can now change dates, guests and room before confirming.' mod='salisbergguide'}</li>
			<li><strong>{l s='A room with a guest still checked in is never shown as Ready.' mod='salisbergguide'}</strong> {l s='Even if the guest stayed past their departure date, the room shows as Occupied on the Room board until you check them out.' mod='salisbergguide'}</li>
			<li><strong>{l s='Date boxes open the calendar wherever you click.' mod='salisbergguide'}</strong> {l s='In Arrival and Departure on New booking, click anywhere in the box instead of finding the small calendar icon.' mod='salisbergguide'}</li>
			<li><strong>{l s='Selected room now comes first.' mod='salisbergguide'}</strong> {l s='It is on the left before the room list, and the button is called Book now for both same-day and future stays. Room types show their matching count on the same row. The room list now takes two thirds of the page, the selected room stays in view as you scroll, and the repeated headings and banner above the rooms were removed.' mod='salisbergguide'}</li>
			<li><strong>{l s='Bookings is now the one name used across the back office.' mod='salisbergguide'}</strong> {l s='Menus, page headings, booking details, dashboard figures, the notification panel at the top, statistics and reports no longer switch between Reservations and Orders. Technical database and controller names stay unchanged so existing bookings and integrations keep working.' mod='salisbergguide'}</li>
			<li><strong>{l s='New booking no longer takes a permanent menu row.' mod='salisbergguide'}</strong> {l s='Open Bookings and use the New booking button at the top. The Room board remains available without crowding the side menu.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Review deleted bookings in the Recycle bin.' mod='salisbergguide'}</strong> {l s='See who deleted each booking, when and why, with saved guest contact details and the full booking value. Totals show deletion count and value by currency. Restore is available when your Recycle bin permission includes Edit, and the system checks room availability first.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Employee visibility and recycle-bin permissions.' mod='salisbergguide'}</strong> {l s='Only SuperAdmins can see SuperAdmin employee accounts. Hotel Staff receive Delete on Bookings once; Hotel Managers receive View and Edit on Recycle bin when its menu page is created. Permission changes made later are kept.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='15 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='New booking opens the Room board.' mod='salisbergguide'}</strong> {l s='Use the same guided Guest, Payment and Confirm steps for a same-day or future booking. A guest arriving today can be checked in at the end; a future booking is saved without checking the guest in.' mod='salisbergguide'}</li>
			<li><strong>{l s='The Room board is faster to scan.' mod='salisbergguide'}</strong> {l s='Available and ready rooms come first, with status filters, room search, compact cards, a recommended room and a Continue bar that stays visible on smaller screens.' mod='salisbergguide'}</li>
			<li><strong>{l s='The booking guide is now three clear steps.' mod='salisbergguide'}</strong> {l s='Guest, Payment and Confirm avoid repeating the stay. Typed guest details survive an error, existing matches are easier to use, Pay later asks for no payment method, and unfinished bookings can be resumed or discarded from the Room board.' mod='salisbergguide'}</li>
			<li><strong>{l s='Final confirmation now completes the booking.' mod='salisbergguide'}</strong> {l s='Database locking queries had their clauses in the wrong order, so Confirm could stop with a technical error. The locks and duplicate-booking protection remain in place and the booking now saves normally.' mod='salisbergguide'}</li>
			<li><strong>{l s='Today is now on the Dashboard.' mod='salisbergguide'}</strong> {l s='Arrivals, departures, rooms in house, unpaid bookings, Check in and Check out are available where the day starts, without a separate Today menu page.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Unused menu items removed.' mod='salisbergguide'}</strong> {l s='Channel Manager, Zones and Credit Slips are no longer shown. Their shared internal records were kept so countries, refunds and existing bookings continue to work.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='14 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='The booking list is easier to find.' mod='salisbergguide'}</strong> {l s='The list of every booking is under Bookings. Its labels have since been standardised to Bookings throughout the back office.' mod='salisbergguide'}</li>
			<li><strong>{l s='New Room board and guided walk-in check-in.' mod='salisbergguide'}</strong> {l s='Bookings, Room board shows whether every room is free for the whole stay and whether it is ready now. Choose a room, then complete Guest, Payment and Review in one workspace. The final button creates the normal booking and checks the guest in; pending money is never shown as paid.' mod='salisbergguide'}</li>
			<li><strong>{l s='Quicker Add new booking.' mod='salisbergguide'}</strong> {l s='After Search the page moves to the free rooms. Add To Cart adds a room at the first click, with the guests chosen in the Booking Form. A bar at the bottom of the screen shows the rooms chosen and the total, with a Continue to guest and payment button.' mod='salisbergguide'}</li>
			<li><strong>{l s='Charge tax switch on the booking form.' mod='salisbergguide'}</strong> {l s='It is on Yes. Choose No for a guest who is not to be charged tax, and only that booking is made without tax.' mod='salisbergguide'}</li>
			<li><strong>{l s='Tidier on tablets and smaller laptops.' mod='salisbergguide'}</strong> {l s='Page titles are no longer covered by the buttons beside them, wide lists scroll inside their box instead of pushing the page sideways, and the figure tiles at the top of Bookings no longer overlap. On the website, search filters and the booking panel sit above the content on tablets.' mod='salisbergguide'}</li>
			<li><strong>{l s='Payments cannot be counted twice.' mod='salisbergguide'}</strong> {l s='A payment more than what is still owed is refused, and marking a booking Complete payment received records only what was still owed. The booking page now says Paid so far and Still to pay in plain words.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Charge tax switch: yours to allow.' mod='salisbergguide'}</strong> {l s='Rooms & Services, Charges & Tax has a new tick, Let staff leave tax off a single booking. It is on; untick it and every booking is charged tax.' mod='salisbergguide'}</li>
			<li><strong>{l s='New: Rooms & Services, Charges & Tax.' mod='salisbergguide'}</strong> {l s='Tick or untick the charges added to every booking by themselves, and switch tax on or off. Two sample charges and a United States sample tax came switched on; see Admin Guide section 5.' mod='salisbergguide'}</li>
		</ul>
		{/if}
	</section>

	<section>
		<h3>{l s='13 October 2026' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='New invoice and receipt.' mod='salisbergguide'}</strong> {l s='The invoice has the hotel logo and shows the stay, each payment received and the balance still due, with how to pay it. Every booking now has one from the moment it is made, and it becomes a Receipt once it is paid.' mod='salisbergguide'}</li>
			<li><strong>{l s='Invoices page lists the invoices.' mod='salisbergguide'}</strong> {l s='Bookings, Invoices now shows every invoice with the guest, the stay, total, paid and still to pay. Search by number, booking, guest or phone, filter by status, and open the PDF from the row.' mod='salisbergguide'}</li>
			<li><strong>{l s='Guests are told at check-in and when they pay.' mod='salisbergguide'}</strong> {l s='A guest with an email gets a welcome email at check-in and a receipt when payment is confirmed, each with the PDF attached. Text messages will follow once the text service is connected.' mod='salisbergguide'}</li>
			<li><strong>{l s='New: Bookings, Today.' mod='salisbergguide'}</strong> {l s='One page for the front desk: arrivals and departures for today, rooms with a guest in them and unpaid bookings, with Check in and Check out buttons on the rows.' mod='salisbergguide'}</li>
			<li><strong>{l s='New: Bookings, Housekeeping.' mod='salisbergguide'}</strong> {l s='Every room with its state: clean, dirty or out of service. A room turns dirty when its guest is checked out. Out of service takes the room off sale until it is put back.' mod='salisbergguide'}</li>
			<li><strong>{l s='Book Now is now Add new booking.' mod='salisbergguide'}</strong> {l s='The menu page under Manage Hotel and the button on the Bookings page are both called Add new booking.' mod='salisbergguide'}</li>
			<li><strong>{l s='Guest details on the booking form.' mod='salisbergguide'}</strong> {l s='Type the first name, last name, phone and email of the guest on the booking form itself. A guest already on record is used; a new one is added. Email can be left empty for a guest who has none.' mod='salisbergguide'}</li>
			<li><strong>{l s='Cleaner addresses.' mod='salisbergguide'}</strong> {l s='The address bar no longer shows index.php, in the back office or on the website. Old bookmarks still work.' mod='salisbergguide'}</li>
			<li><strong>{l s='Customer Service is now Inbox, and easier to read.' mod='salisbergguide'}</strong> {l s='Messages from the website Contact form arrive under Customers, Inbox. The list shows who it is from, their email and phone, the start of the message, its status, who is handling it and when it arrived; click the message to open it. Statuses are Open, In progress, Waiting for guest and Closed.' mod='salisbergguide'}</li>
			<li><strong>{l s='Links cannot be sent through the Contact form.' mod='salisbergguide'}</strong> {l s='A message that contains a web address is refused with a short note to the guest, and any link that is already in a message is shown as plain text that cannot be clicked.' mod='salisbergguide'}</li>
			<li><strong>{l s='Import uses Excel only.' mod='salisbergguide'}</strong> {l s='Under Advanced Parameters, CSV Import, upload an Excel .xlsx file. The sample files are Excel files too.' mod='salisbergguide'}</li>
			<li><strong>{l s='Simpler Contact form.' mod='salisbergguide'}</strong> {l s='The website Contact page no longer asks guests for a Title or who to send to. Messages arrive under Customers, Inbox, titled with the start of the message.' mod='salisbergguide'}</li>
			<li><strong>{l s='Fixed: front desk staff could not finish a booking in Book Now.' mod='salisbergguide'}</strong> {l s='The rooms stayed in the cart and no booking was made. Staff accounts were missing a right on Booking Carts that the last step depends on. It is now given automatically.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_is_admin}
		<h4>{l s='For the developer team' mod='salisbergguide'}</h4>
		<ul>
			<li>{l s='Bookings, Add new attaches the guest to the cart through AdminCarts, which needs add and edit there. Hotel Staff and Hotel Manager were created with view only. Raised in salisbergguide::grantCartEditing(), called from ensureSetup(). The three things a booking profile needs are listed in the Admin Guide, section 1.' mod='salisbergguide'}</li>
			<li>{l s='New module salisbergfrontdesk (Today, Housekeeping; table sb_room_status; hook actionRoomBookingStatusUpdateAfter). Out of service is the room status Inactive, not a separate flag.' mod='salisbergguide'}</li>
			<li>{l s='Removed from the code: the Stock menu and its six pages, the shop pages Suppliers, Manufacturers, Best sales, New products, Prices drop and Products Comparison, the 1-Click Upgrade module, and the IMAP mailbox sync under Inbox. All mail goes through Mail::Send and the E-mail settings.' mod='salisbergguide'}</li>
		</ul>
		{/if}
		{if $sb_can_read_admin_guide}
		<h4>{l s='For the hotel manager' mod='salisbergguide'}</h4>
		<ul>
			<li><strong>{l s='Room types with no rooms are flagged.' mod='salisbergguide'}</strong> {l s='Manage Room Types shows No rooms in red and a warning at the top for any room type that is switched on but has no room in service. Guests see such a room type as sold out.' mod='salisbergguide'}</li>
			<li><strong>{l s='Stock menu removed.' mod='salisbergguide'}</strong> {l s='Warehouses and stock pages belonged to the shop software this system grew from; a hotel does not use them.' mod='salisbergguide'}</li>
			<li><strong>{l s='Old shop pages removed from the website.' mod='salisbergguide'}</strong> {l s='Suppliers, Manufacturers, Best sales, New products, Prices drop and Products Comparison no longer exist; their addresses show the Page not found page.' mod='salisbergguide'}</li>
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

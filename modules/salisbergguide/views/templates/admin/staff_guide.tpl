<link rel="stylesheet" href="{$sb_css|escape:'html':'UTF-8'}" type="text/css" />
<div class="sb-guide">
	<div class="sb-intro">
		<h2>{l s='Staff Guide' mod='salisbergguide'}</h2>
		<p>{l s='Everyday tasks at the front desk. Menu names below are written exactly as they appear in the menu on the left.' mod='salisbergguide'}</p>
	</div>

	<div class="sb-toc">
		<strong>{l s='In this guide' mod='salisbergguide'}</strong>
		<ol>
			<li><a href="#sb-s1">{l s='Signing in and your account' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s2">{l s='Finding your way around' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s3">{l s='Make a booking for a guest' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s4">{l s='Find and open a booking' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s5">{l s='Record a cash or Mobile Money payment' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s6">{l s='Check a guest in and out' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s7">{l s='Change or cancel a booking' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s8">{l s='Guests and their details' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s9">{l s='Messages and refund requests' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s10">{l s='What each booking status means' mod='salisbergguide'}</a></li>
			<li><a href="#sb-s11">{l s='When something goes wrong' mod='salisbergguide'}</a></li>
		</ol>
	</div>

	<section id="sb-s1">
		<h3>1. {l s='Signing in and your account' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Open the back office address your administrator gave you and sign in with your work email and password.' mod='salisbergguide'}</li>
			<li>{l s='To change your password, click your name at the top right of the screen and choose' mod='salisbergguide'} <span class="sb-btn">My preferences</span>.</li>
			<li>{l s='When you finish your shift, click your name at the top right and choose' mod='salisbergguide'} <span class="sb-btn">Sign out</span>.</li>
		</ol>
		<p class="sb-warn">{l s='Never share your login. Every booking and payment you record is saved under your name.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-s2">
		<h3>2. {l s='Finding your way around' mod='salisbergguide'}</h3>
		<p>{l s='The menu is on the left. You will only see the pages your role is allowed to use.' mod='salisbergguide'}</p>
		<table>
			<tr><th>{l s='Menu' mod='salisbergguide'}</th><th>{l s='What it is for' mod='salisbergguide'}</th></tr>
			<tr><td><span class="sb-path">Dashboard</span></td><td>{l s='Today at a glance: recent bookings and activity.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Hotel Reservation System &rsaquo; Book Now</span></td><td>{l s='See which rooms are free and make a booking for a guest.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Orders &rsaquo; Orders</span></td><td>{l s='Every booking. Open one to take payment, check in, check out or change it.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Customers &rsaquo; Customers</span></td><td>{l s='Guest records and their booking history.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Customers &rsaquo; Customer Service</span></td><td>{l s='Messages guests send through the website.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Hotel Reservation System &rsaquo; Manage Order Refund Requests</span></td><td>{l s='Cancellation and refund requests from guests.' mod='salisbergguide'}</td></tr>
			<tr><td><span class="sb-path">Guides</span></td><td>{l s='This guide.' mod='salisbergguide'}</td></tr>
		</table>
		<p class="sb-tip">{l s='In any list you can type in the boxes under the column headings and press Enter (or click Search) to filter. Click Reset to clear the filter.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-s3">
		<h3>3. {l s='Make a booking for a guest' mod='salisbergguide'}</h3>
		<p>{l s='Use this for walk-in guests and bookings taken by phone.' mod='salisbergguide'}</p>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; Book Now</span>.</li>
			<li>{l s='In the Booking Form, choose the check-in and check-out dates, the room type and the number of guests, then click' mod='salisbergguide'} <span class="sb-btn">Search</span>.</li>
			<li>{l s='The calendar and room lists show what is free. Rooms are grouped as Available Rooms, Partially Available, Booked Rooms and Unavailable Rooms.' mod='salisbergguide'}</li>
			<li>{l s='Pick a room, set the occupancy and click' mod='salisbergguide'} <span class="sb-btn">Add To Cart</span>. {l s='Repeat if the guest needs more than one room.' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Book Now</span> {l s='to continue to the order form.' mod='salisbergguide'}</li>
			<li>{l s='Choose the guest: search for an existing customer, or create a new one with their name, email and phone number.' mod='salisbergguide'}</li>
			<li>{l s='Check the rooms, dates and total. Choose the payment method (Cash at hotel or Mobile Money) and the status, then create the order.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='If the guest has not paid yet, leave the status as Awaiting payment. Change it only when the money has actually been received (see section 5).' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminHotelRoomsBooking|escape:'html':'UTF-8'}">{l s='Open Book Now' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-s4">
		<h3>4. {l s='Find and open a booking' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Orders &rsaquo; Orders</span>.</li>
			<li>{l s='Search by booking reference (the nine-letter code the guest received), by guest name, or by status.' mod='salisbergguide'}</li>
			<li>{l s='Click the row to open the booking.' mod='salisbergguide'}</li>
		</ol>
		<p>{l s='The booking page is divided into panels:' mod='salisbergguide'}</p>
		<ul>
			<li><strong>Status</strong> &ndash; {l s='the current status and its history.' mod='salisbergguide'}</li>
			<li><strong>Payment</strong> &ndash; {l s='money received so far and the amount still due.' mod='salisbergguide'}</li>
			<li><strong>Customer</strong> &ndash; {l s='who booked, with a link to their record.' mod='salisbergguide'}</li>
			<li><strong>Rooms Booking Detail</strong> &ndash; {l s='each room with its check-in and check-out dates.' mod='salisbergguide'}</li>
			<li><strong>Messages</strong> &ndash; {l s='notes exchanged with the guest.' mod='salisbergguide'}</li>
		</ul>
		<a class="sb-go" href="{$sb_links.AdminOrders|escape:'html':'UTF-8'}">{l s='Open Orders' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-s5">
		<h3>5. {l s='Record a cash or Mobile Money payment' mod='salisbergguide'}</h3>
		<p>{l s='Bookings made on the website with Cash at hotel or Mobile Money arrive as Awaiting payment. Nothing is confirmed automatically: a member of staff must record the money.' mod='salisbergguide'}</p>
		<h4>{l s='Mobile Money' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='Check the hotel Mobile Money phone or statement. The guest is asked to use their booking reference as the payment reference.' mod='salisbergguide'}</li>
			<li>{l s='Confirm three things match the booking: the reference, the amount, and the sender name.' mod='salisbergguide'}</li>
			<li>{l s='Open the booking (section 4).' mod='salisbergguide'}</li>
			<li>{l s='In the Payment panel click' mod='salisbergguide'} <span class="sb-btn">Add new payment</span>, {l s='enter the amount received and the method, and save. Put the Mobile Money transaction ID in the transaction field so it can be traced later.' mod='salisbergguide'}</li>
			<li>{l s='In the Status panel choose Complete payment received (or Partial payment received if only part was paid) and click' mod='salisbergguide'} <span class="sb-btn">Update status</span>.</li>
		</ol>
		<h4>{l s='Cash at the hotel' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='When the guest arrives, open their booking and check the amount due in the Payment panel.' mod='salisbergguide'}</li>
			<li>{l s='Take the cash and give the guest a receipt.' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Add new payment</span>, {l s='enter the amount, and save.' mod='salisbergguide'}</li>
			<li>{l s='Set the status to Complete payment received and click' mod='salisbergguide'} <span class="sb-btn">Update status</span>.</li>
		</ol>
		<p class="sb-warn">{l s='Only mark a booking as paid after you have seen the money yourself. A screenshot from the guest is not proof of payment.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-s6">
		<h3>6. {l s='Check a guest in and out' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Open the booking (section 4) and confirm the guest name and dates. Ask for ID if hotel policy requires it.' mod='salisbergguide'}</li>
			<li>{l s='If money is still due, take payment first (section 5).' mod='salisbergguide'}</li>
			<li>{l s='In Rooms Booking Detail, use the status option on the room to mark it as checked in. The date and time are recorded.' mod='salisbergguide'}</li>
			<li>{l s='When the guest leaves, open the booking again, settle any extra services, and mark the room as checked out.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-tip">{l s='A booking with several rooms is checked in and out one room at a time.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-s7">
		<h3>7. {l s='Change or cancel a booking' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='Add a room:' mod='salisbergguide'}</strong> {l s='in Rooms Booking Detail click' mod='salisbergguide'} <span class="sb-btn">Add Rooms</span>.</li>
			<li><strong>{l s='Change dates, move room or add services:' mod='salisbergguide'}</strong> {l s='use' mod='salisbergguide'} <span class="sb-btn">Edit</span> {l s='on the room line. Dates cannot be changed after the guest has checked in or out.' mod='salisbergguide'}</li>
			<li><strong>{l s='Cancel:' mod='salisbergguide'}</strong> {l s='set the status to Canceled and click Update status. If the guest has already paid, follow the refund steps in section 9 instead.' mod='salisbergguide'}</li>
			<li><strong>{l s='Send the confirmation again:' mod='salisbergguide'}</strong> {l s='use Resend email in the status history.' mod='salisbergguide'}</li>
			<li><strong>{l s='Print:' mod='salisbergguide'}</strong> <span class="sb-btn">Print order</span> {l s='at the top of the booking.' mod='salisbergguide'}</li>
		</ul>
		<p class="sb-warn">{l s='You cannot delete bookings. If one was created by mistake, cancel it and add a private note explaining why.' mod='salisbergguide'}</p>
	</section>

	<section id="sb-s8">
		<h3>8. {l s='Guests and their details' mod='salisbergguide'}</h3>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Customers &rsaquo; Customers</span> {l s='and search by name or email.' mod='salisbergguide'}</li>
			<li>{l s='Click a guest to see their details and every booking they have made.' mod='salisbergguide'}</li>
			<li>{l s='Click' mod='salisbergguide'} <span class="sb-btn">Edit</span> {l s='to correct a name, email or phone number, or' mod='salisbergguide'} <span class="sb-btn">Add new customer</span> {l s='to create a record.' mod='salisbergguide'}</li>
		</ol>
		<p class="sb-warn">{l s='Guest details are confidential. Look up a guest only when you need to for their booking, and never give their details to anyone else.' mod='salisbergguide'}</p>
		<a class="sb-go" href="{$sb_links.AdminCustomers|escape:'html':'UTF-8'}">{l s='Open Customers' mod='salisbergguide'} &rarr;</a>
	</section>

	<section id="sb-s9">
		<h3>9. {l s='Messages and refund requests' mod='salisbergguide'}</h3>
		<h4>{l s='Messages from the website' mod='salisbergguide'}</h4>
		<p>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Customers &rsaquo; Customer Service</span>. {l s='Open a message to read it and reply. Reply the same day where you can.' mod='salisbergguide'}</p>
		<h4>{l s='Refund and cancellation requests' mod='salisbergguide'}</h4>
		<ol>
			<li>{l s='Go to' mod='salisbergguide'} <span class="sb-path">Hotel Reservation System &rsaquo; Manage Order Refund Requests</span>.</li>
			<li>{l s='Open the request to see the booking, the rooms and the reason.' mod='salisbergguide'}</li>
			<li>{l s='Tell your manager before approving anything. How much is refunded depends on the hotel refund rules, which only an administrator can set.' mod='salisbergguide'}</li>
		</ol>
	</section>

	<section id="sb-s10">
		<h3>10. {l s='What each booking status means' mod='salisbergguide'}</h3>
		<table>
			<tr><th>{l s='Status' mod='salisbergguide'}</th><th>{l s='Meaning' mod='salisbergguide'}</th><th>{l s='What you do' mod='salisbergguide'}</th></tr>
			<tr><td>Awaiting payment</td><td>{l s='Booked, nothing paid yet.' mod='salisbergguide'}</td><td>{l s='Wait for Mobile Money, or collect cash on arrival.' mod='salisbergguide'}</td></tr>
			<tr><td>Partial payment received</td><td>{l s='Part of the total has been paid.' mod='salisbergguide'}</td><td>{l s='Collect the balance before check-out.' mod='salisbergguide'}</td></tr>
			<tr><td>Complete payment received</td><td>{l s='Paid in full.' mod='salisbergguide'}</td><td>{l s='Nothing. Ready for check-in.' mod='salisbergguide'}</td></tr>
			<tr><td>Processing in progress</td><td>{l s='Being handled by staff.' mod='salisbergguide'}</td><td>{l s='Check the private note for who is dealing with it.' mod='salisbergguide'}</td></tr>
			<tr><td>Canceled</td><td>{l s='The booking will not happen; the room is free again.' mod='salisbergguide'}</td><td>{l s='Nothing.' mod='salisbergguide'}</td></tr>
			<tr><td>Refunded</td><td>{l s='Money has been returned to the guest.' mod='salisbergguide'}</td><td>{l s='Nothing.' mod='salisbergguide'}</td></tr>
			<tr><td>Payment error</td><td>{l s='A payment did not go through.' mod='salisbergguide'}</td><td>{l s='Contact the guest to arrange payment.' mod='salisbergguide'}</td></tr>
			<tr><td>Overbooking</td><td>{l s='More bookings than rooms for those dates.' mod='salisbergguide'}</td><td>{l s='Tell your manager immediately.' mod='salisbergguide'}</td></tr>
		</table>
	</section>

	<section id="sb-s11">
		<h3>11. {l s='When something goes wrong' mod='salisbergguide'}</h3>
		<ul>
			<li><strong>{l s='"Too many attempts. Please wait…" when signing in:' mod='salisbergguide'}</strong> {l s='the wrong password was entered too many times from your connection (10 tries in 10 minutes). Wait the time shown, then try again carefully. If you have forgotten your password, ask an administrator to reset it.' mod='salisbergguide'}</li>
			<li><strong>{l s='"Access denied" on a page:' mod='salisbergguide'}</strong> {l s='your role does not include it. Ask an administrator if you need it for your job.' mod='salisbergguide'}</li>
			<li><strong>{l s='A room shows as unavailable but is empty:' mod='salisbergguide'}</strong> {l s='another booking may hold it. Search Orders for those dates before promising it to a guest.' mod='salisbergguide'}</li>
			<li><strong>{l s='Guest says they paid by Mobile Money but nothing arrived:' mod='salisbergguide'}</strong> {l s='ask for the transaction ID and check the hotel account. Do not mark the booking as paid until the money is there.' mod='salisbergguide'}</li>
			<li><strong>{l s='You recorded the wrong amount or status:' mod='salisbergguide'}</strong> {l s='do not try to hide it. Add a private note on the booking and tell your manager so it can be corrected.' mod='salisbergguide'}</li>
			<li><strong>{l s='The website or back office is not loading:' mod='salisbergguide'}</strong> {l s='tell an administrator. Keep a paper record of bookings and payments until it is back, then enter them.' mod='salisbergguide'}</li>
		</ul>
		{if $sb_shop_email}<p>{l s='Hotel contact email:' mod='salisbergguide'} <strong>{$sb_shop_email|escape:'html':'UTF-8'}</strong></p>{/if}
	</section>
</div>

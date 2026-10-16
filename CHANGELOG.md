# Changelog

Every change to Salisberg, newest first, in plain words.

- **Added** is something new. **Changed** is something that works or looks different. **Fixed** is a fault put right. **Security** is protection added. **Removed** is something taken away.
- Changes that people using the back office will notice are also listed inside the app, under **Guides › What's New**.
- How and why each change was made, and how it was tested, is in `AGENTS.md`, section 6.
- `CHANGELOG.txt` is the original vendor's own history up to version 1.7.0 and is not updated here.

## 2026-10-16

### Fixed

- **Changing the dates or room of a booking in progress no longer fails with a database error.** On New booking, choosing Edit stay details and saving showed "SQLSTATE[42000] Syntax error ... near 'LIMIT 1'" and nothing changed. Dates, nights, guests and room can now be edited at any step before confirming; an unavailable room or too many guests gives a plain message. The release also adds a check to the smoke test so this kind of mistake is caught before it ships.
- **Website dark mode: the guest booking pages are readable.** The "Already have an account?" strip and the terms-of-service line at checkout were white boxes with near-invisible text; on the booking confirmation the room name and the "Awaiting payment" label were dark grey on dark; the room amenity icons on search results were almost invisible; the "Hotel Location" placeholder was too faint; and white text sat on the gold Search Rooms and Subscribe buttons (contrast 1.9 to 2.4). All now meet readable contrast; light mode is unchanged.
- **The Room board no longer shows a room as Ready while a guest is still checked in.** If a guest stayed past their departure date, or was never checked out, the board listed the room as Ready but the booking steps then said it was not. Both now treat such a room as Occupied until it is checked out, the same as the Today panel.

### Changed

- **Date boxes open the calendar wherever you click.** On the New booking page and the guided booking's stay screen (Arrival and Departure), clicking the empty part of the box opens the calendar; you no longer have to find the small icon.
- **The selected room now comes first on the Room Board.** Its summary is on the left before the room list, and the action is consistently named **Book now** for same-day and future bookings.
- **Room types and matching counts stay together.** Each heading now reads like `Standard Room 1 — [5 rooms]` on one row, with the count as a badge; it updates with the active filter or search.
- **New booking page is easier to scan.** The Selected room panel is one third of the width and the room list two thirds, both starting at the same height; the panel now stays in view while you scroll the rooms. The duplicate "Front desk / Room board" heading, the blue availability banner and the sentence under Unfinished bookings are gone, so the rooms start higher on the screen. The search box icon no longer covers the placeholder text.
- **Bookings is the consistent name.** Back-office menus, page headings, booking details, dashboard figures, the top-bar notification panel, statistics, reports and guest account labels no longer switch between Reservations and Orders. Internal controller, database and integration identifiers remain unchanged.
- **Room Board summaries are easier to scan.** The selected-room card is wider, uses readable dates and guest wording, highlights the estimated total, and omits a redundant nightly breakdown unless rates vary during the stay.
- Room cards now label capacity explicitly and show a check mark and accessible pressed state for the selected room. On smaller laptops the board switches to one column and keeps its Continue action visible instead of squeezing the summary beside the room grid.

### Fixed

- Long capacity and nightly-rate values in Selected room now wrap inside the card instead of crossing its divider or being clipped.

### Removed

- **New booking is no longer a permanent sidebar item.** Staff open **Bookings > Bookings** and use the **New booking** button; the hidden Room Board controller and legacy room-reallocation endpoint remain available where the workflow needs them.

## 2026-10-15

### Changed

- **Add new booking now opens the Room board.** The Reservations toolbar button and the visible Bookings menu entry both start the guided flow. Walk-ins can be checked in at completion; future stays use the same Guest, Payment and Confirm steps and are saved as reservations without opening the old cart screens.
- **Faster front-desk booking.** The Room board now puts available and ready rooms first and adds status filters, room search, compact cards, a recommended room and a persistent tablet/phone Continue action. The guide is reduced to Guest, Payment and Confirm; payment starts with Paid in full, Part payment or Pay later, and Pay later no longer asks for a payment method.
- **Today is on the Dashboard.** Arrivals, departures, rooms in house, unpaid reservations, and the Check in and Check out actions are now part of the Dashboard instead of a separate Bookings menu page.

### Added

- **Resume unfinished bookings.** The Room board lists the current employee's active drafts with Resume and Discard actions. Discard releases the room and removes the unused internal cart.

### Fixed

- Guest details now remain in the form after validation fails, field errors appear beside the relevant input, exact phone matches rank first, and an existing matching guest can be selected immediately instead of retyping the booking.
- Guided bookings can now be completed. Final confirmation previously built its draft and room locking queries as `FOR UPDATE LIMIT 1`, which MySQL rejects; they now use the required `LIMIT 1 FOR UPDATE` order while keeping the duplicate-confirmation locks.

### Removed

- **Unused back-office menu items:** Channel Manager, Zones, Credit Slips, the old cart-based Add new booking page, and the separate Today page no longer appear in navigation. Shared geography, refund, and room-reallocation code remains in place so existing workflows are not broken; the Channel Manager module is disabled.
- **Dead code from the retired cart booking screen:** removed its sticky selection bar, automatic occupancy copy, result auto-scroll, obsolete per-booking tax field, and the deploy-time permission repair that only the old flow required. Shared Cart and room-reallocation code remains because live back-office pages still use it.

## 2026-10-14

### Added

- **Bookings > Room board and guided walk-in check-in.** Choose dates and guests, see complete-stay availability separately from current room readiness, select a room, then work through Stay, Guest, Payment and Review without opening the old cart screens. Existing guests can be found by name, phone or email; new guests need no email. Full, part and pay-later arrangements keep pending money separate from confirmed money. Final confirmation checks availability, capacity, readiness and price again, prevents duplicate completion, then creates the normal booking and checks the room in. A future arrival goes to the existing reservation completion flow instead.
- **Quicker Add new booking.** After Search the page scrolls to the free rooms; Add To Cart adds a room at the first click with the guests from the Booking Form (it used to open a second guest box and add nothing); a bar at the bottom of the screen shows the rooms chosen, the total, and a Continue to guest and payment button (the small Cart button in the calendar's corner was easy to miss). The cart window's Book Now button is now called Continue to guest and payment too.
- **Charge tax switch on the booking form.** On Yes by default; staff choose No for a guest who is not to be charged tax, and only that booking is made without tax. A tick on Rooms & Services › Charges & Tax lets the manager remove the switch.
- **Rooms & Services › Charges & Tax.** Tick or untick each charge added to every booking by itself, see how it is charged, and switch tax on or off. For the Hotel Manager and the developer team.

### Changed

- **Bookings › Bookings is now Bookings › Reservations** (the list of every booking), so the section and its first page no longer share a name. The guides use the new path.

### Fixed

- **Layout on tablets, small laptops and phones.** Website: the location field's icon no longer covers its text; the search filters and the room booking panel sit above the content below 992px instead of being squeezed beside it; a room card no longer makes the phone homepage scroll sideways; the closed menu panel no longer shows as a dark strip at the right edge; "/Per Night" no longer splits over two lines. Back office: page titles are no longer covered by their buttons (the buttons move under the title when both do not fit); wide lists and tables scroll inside their box instead of pushing the page sideways; the figure tiles at the top of Bookings and a booking no longer overlap; the fold arrows are hidden in the phone menu rail; the Staff Guide's tables and the occupancy chart fit a phone.
- **Room amenities ran together** ("FanTV") in the room lists: the short descriptions written on 12 October now read "Fan, TV".
- **Payments can no longer be counted twice.** A payment larger than what is still owed is refused with a plain message, and marking a booking Complete payment received records only the amount still owed, even if earlier payments were not linked to the invoice.
- The booking page said "Warning: X paid instead of Y" in red for every part payment. It now says "Paid so far: X of Y. Still to pay: Z", and is red only when more than the total was recorded.

## 2026-10-13

### Added

- **Bookings › Invoices lists the invoices.** Number, date, booking, guest, stay, total, paid, still to pay and status, with search, a status filter, dates and a PDF button per row. Totals for what is shown. The numbering and layout settings are shown to SuperAdmins only.
- **Redesigned invoice, which doubles as the receipt.** Logo, who it is from and for, the stay (room numbers, dates, nights, guests, rate), each payment received, total, paid and balance due, and how to pay the balance (the details from Salisberg Pay). Headed Receipt once nothing is owed. Dates are written as 13 Oct 2026. Fits one page.
- **Check-in email** with the invoice or receipt attached, and **text messages** at check-in and when a booking is paid in full. Texts are handed to a hook and go nowhere until an SMS provider is connected.
- **Bookings › Today.** One page for the front desk: arrivals, departures, rooms in house and unpaid bookings, with Check in and Check out buttons.
- **Bookings › Housekeeping.** Every room as clean, dirty or out of service. A room turns dirty when its guest is checked out; out of service takes it off sale.
- **Warning for room types with no rooms.** Manage Room Types marks them "No rooms" and names them in a warning; they cannot be booked.
- **Guest details on the booking form.** The form asks for first name, last name, phone and email. A guest already on record (same email, or same phone) is used; anyone else is added. Email may be left empty. The separate Add new customer form is also shorter.

### Removed

- **Stock menu** (Warehouses, Stock Management, Stock Movement, Instant Stock Status, Stock Coverage, Configuration): menu entries and their pages.
- **Shop pages on the website:** Suppliers, Manufacturers, Best sales, New products, Prices drop, Products Comparison. Their addresses now show Page not found.
- **1-Click Upgrade module** (`modules/qloautoupgrade`). It was never installed and must not be used here.
- **IMAP mailbox sync under Inbox.** All email uses the one mail server under Advanced Parameters › E-mail.

### Changed

- **Every booking gets its invoice when it is made**, not only once money has come in. The part-payment email now carries the PDF as well.
- **Book Now and Add new order are both "Add new booking"** in the back office (menu page under Manage Hotel, and the button on Bookings). The website's Book Now button is unchanged.
- **No index.php in the address bar, website and back office.** Back office pages now show as /admin-name/?controller=... instead of /admin-name/index.php?controller=.... On the website the last link that still showed index.php, Our Properties, now reads /our-properties.
- **Customer Service renamed Inbox and simplified.** The Customers menu page is now Inbox. The list keeps From, Email, Phone, Message, Status, Handled by and Received (ID, Contact, Language, Last Reply by, Has Private and the full message text columns are gone); the message start is a link that opens the conversation; statuses read Open, In progress, Waiting for guest, Closed. The mail-server (IMAP) settings are shown to SuperAdmins only. Message titles end on a whole word.
- **Contact form refuses links.** A message with a web address in it is not sent, and the guest is asked to remove it. Links already stored are shown as plain text in the back office instead of clickable ones, so front desk staff cannot click one by accident.
- **Import is Excel only.** Advanced Parameters › CSV Import now accepts .xlsx files only. All the .csv sample files and their download links are removed; the samples are Excel files. The upload box on that page now also accepts .xlsx in the browser (it was still filtering for .csv there, which would have blocked an Excel file).
- **Contact form simplified.** The website Contact page no longer asks for a Title or a Send To choice: it takes name, email, phone and the message. Every message goes to the one contact set in Customers › Contacts and appears under Customers › Customer Service, titled with the start of the message.

### Fixed

- Invoice PDF: the logo was missing (it was fetched over the web instead of read from disk), the footer printed "Tel: %s", and a hidden "Powered by TCPDF" line was added.
- Two warnings logged whenever an invoice PDF was made (a missing PDF translation file, and an unset day count for items not priced per night).
- **Front desk staff could not finish a booking in Book Now.** The rooms stayed in the cart and nothing was recorded. The last step (Bookings › Add new) attaches the guest to the cart through the Booking Carts page, which needs Add and Edit rights there; Hotel Staff and Hotel Manager had been created with View only. Both are now given those rights automatically on deploy, and the Admin Guide lists the three things a profile needs to take bookings.

### Security

- **Contact form checked against injection.** Review of the stock code found every value already escaped or cast before it reaches the database. As a second line of defence, each field must now have the shape the form can send (token, keys and ids letters and digits only, email without quotes or markup, no list values), anything else is refused with a plain message, and a list value no longer causes a server error.


## 2026-10-12

### Added

- **Website footer: Location and Contact.** A new column next to Explore, Follow us on and Payment accepted, showing the hotel's address, phone and email: the same ones already shown on the Contact page.
- **Six real room types from the hotel's first room list:** Standard Room 1-3, Deluxe Room 1-2, Executive Room 1, each with its own price, bed type and amenities. The three demo room types with matching names (General, Delux and Executive Rooms) were renamed and repriced into Standard Room 1, Deluxe Room 1 and Executive Room 1 instead of being left as stale extra entries.
- **CSV Import now also accepts Excel (.xlsx) files.** Advanced Parameters › CSV Import: upload a spreadsheet saved as .xlsx directly, no need to export it to CSV first. Every sample file on that page now has a matching ".xlsx" download, and Room Types has an extra one already filled in with the hotel's six room types, ready to try.
- **Friendly URLs.** Website links no longer show `index.php` or a `.php` extension: a room page reads `salisberg.com/salisberg-hotels/1-standard-room-1.html` instead of `salisberg.com/index.php?controller=product&id_product=1`. Old-style links still work. Kept switched on by the deploy, so turning it off by mistake in the back office is undone on the next deploy.

### Changed

- **Menu rename:** the back office sidebar section "Hotel Reservation System" is now "Manage Hotel". Its "Manage Hotel" sub-page (hotel address and details) is renamed to "Hotel Details" so the two no longer share a name.
- **Menu rename:** "Catalog" is now "Rooms & Services", which is what it actually holds (room types, service products, categories, features, bed types).
- **Admin Guide warning added:** a new room type with no rooms entered in its Rooms tab can still be found, added to cart and taken through to a confirmation page by a guest, but the booking is not properly recorded and will not appear under Bookings. The guide now says, clearly, to add at least one room number before switching a new room type on.

### Fixed

- **CSV Import's sample-file downloads were all broken.** `.dockerignore` excluded the whole `docs/` folder, so every "Download sample csv files" link on that page returned 404 on a real deploy. Fixed by keeping `docs/csv_import/` while still dropping the unrelated vendor readmes and licence copies.
- **Hotel Staff and Hotel Manager could make a booking but never see it again, and the dashboard showed no revenue or check-ins/outs for either role.** QloApps has a second permission layer, separate from the usual Permissions grid, that grants or refuses each profile access to each hotel; it was never granted to either role, so it silently defaulted to no access. Bookings, the booking they had just taken, and every hotel-scoped dashboard and stats widget all rendered empty or refused for exactly this reason. Now granted automatically on deploy.

## 2026-10-07

### Added

- **Bank transfer** as a third way to pay, alongside cash and Mobile Money. It appears to guests once the hotel's bank account is entered in Salisberg Pay settings; the booking waits as "Awaiting payment" until staff record the money.
- **Printable reports** (Bookings › Reports): bookings, arrivals and departures, and income by day, for today, this week, this month, last month or any dates, on screen and as a PDF. Given to the Hotel Manager; other profiles can be given it in Permissions.
- Three guides for whoever looks after the server: `COOLIFY.md` (putting the site live on Coolify), `COOLIFY-STAGING.md` (a private test copy, and how to fill it with a copy of the live data) and `BACKUP.md` (what is backed up, getting copies off the server, and rebuilding the site after a lost server).
- A repeatable check, `docker/smoke-test.sh`, that confirms the main website and back office pages load without errors.
- **Dark mode on the website.** A sun/moon switch in the header, on computers and phones. The site starts light; a visitor's choice is remembered on their device.
- **What's New page** in the back office (Guides › What's New). Which profiles can open it is set in Administration › Permissions. Hotel Staff and Hotel Manager have it to begin with.
- This file.

### Changed

- **Stylesheets tidied.** Half of the `!important` flags are gone from the two Salisberg stylesheets (website 45 to 8, back office 64 to 4). Where a flag only existed to out-rank an inherited rule, the inherited rule was corrected instead (date picker, price slider, buttons in the cart drop-down and cart pop-up, extra-service buttons, checkout links, heading underline, empty lists, and the back office top bar, search box, panel headings, hint labels and typeface). A few things look different on purpose: in the back office, text typed into fields uses the same typeface as everything else, a few section headings are no longer in capitals, and a top bar item reached with the keyboard is highlighted the way it is under the mouse; on the website, an "Add" button for an extra service now turns gold when reached with the keyboard, like every other button, instead of bright green.
- **Direction recorded:** Salisberg no longer follows QloApps releases. Inherited files are corrected where they are defined; upstream is watched for security fixes only (`AGENTS.md`, section 1 and rules 11, 18, 28).
- **Bundled libraries updated** to the last release of the line each was on: jQuery 1.11.0 to 1.12.4, Bootstrap scripts 3.1.1 to 3.4.1 (back office), and the text editor TinyMCE 4.0.16 to 4.9.11. All three old versions dated from 2014 and had known security flaws.
- **PHP 8.3** in place of 8.1, which no longer receives security fixes. The version is a build setting (`PHP_VERSION`), so going back is a setting change.
- The MySQL version is now a setting (`MYSQL_VERSION`), so the move from 8.0 to 8.4 can be made deliberately, after a tested backup. It stays on 8.0 until that setting is changed.
- The vendor's product name no longer appears in back office text; it reads "Salisberg" instead.
- **Website footer:** "Payment accepted" shows Cash and Mobile Money badges in place of Visa, American Express, MasterCard and PayPal logos, which the hotel does not take. Home, Our Properties, Interior and Contact Us are listed under Explore, in two columns. The empty "Follow us on" column is hidden until social links are entered. The sample founding year "2010" is gone from the copyright line.
- **Back office side menu:** sections now fold and unfold with a small arrow beside their name. The box of pages that popped out beside the menu on hover is gone.
- Back office side menu text is larger (section names 16px, pages 15px), and the menu is a little wider so long names are not cut off.
- **Back office sign-in page** simplified to the logo and one form; the version number and second logo are gone.
- **Tooltips and pop-up windows** in the back office restyled: rounded, readable in light and dark, and no longer hidden behind the top bar or menu.
- `README.md` rewritten for Salisberg.
- The top-of-dashboard figure tiles use the same off-white as other cards in light mode.
- The "working" spinner sits at the right end of the top bar instead of over the logo.

### Fixed

- Back office dark mode: every page under Guides showed light text on white cards and could not be read.
- The back office side menu could not be scrolled when it was longer than the window; it now scrolls, with a thin scroll bar.
- The Modules page kept offering an update for "Display Language and Currency Block" that did not exist; its version file was out of step with its code.
- Stats: report tables ran off the right edge of the page; each now scrolls inside its own box.
- Website dark mode: the breadcrumb, drop-down boxes, the date picker and parts of the checkout were still light or unreadable. Drop-down boxes also showed a broken arrow picture in light mode.
- Dark mode: empty lists showed a white block, the search row under list headings was pale blue, and the lines between rows were bright white.

### Removed

- File attachments on the website's Contact form. The form takes messages only.
- The vendor's store page (Modules and Services › Modules Catalog) from the menu.
- The installer's sample guest account, which had a publicly known password. It is retired unless it has a booking.

## 2026-10-06

### Added

- **Payments by cash and Mobile Money.** Guests choose one at checkout; the booking waits as "Awaiting payment" until staff record the money.
- **Guides** in the back office: a Staff Guide and an Admin Guide.
- **Hotel Staff** and **Hotel Manager** roles. SuperAdmin is kept for the developer team.
- **Light and dark mode** in the back office, with a switch at the top right.
- **Show/hide button** (eye icon) on every password field, on the website and in the back office.
- **Show or hide menu sections** (Channel Manager, Modules and Services) from the Admin Guide, section 15.
- **Nightly encrypted backups** of the database and settings, kept for 14 days.
- Admin Guide section 16, explaining each Advanced Parameters page.

### Changed

- **Currency** is the Ghana cedi (GH₵).
- **Back office redesigned:** new layout, top bar with initials and name, side menu, softer light colours, consistent buttons, and Save / Save and stay / Cancel as ordinary buttons.
- **Dashboard** fills the page width; figures and labels no longer run outside their boxes.
- The **Orders** menu section is called **Bookings**.
- Side menu boxes of sub-pages stay open while the pointer moves to them, and the open section always lists its pages.
- **Website navigation:** links shown in the header on computers, reduced to the ones a guest needs; forms, checkout and the account menu restyled.
- The phone homepage button says **Book Now** instead of "Make Booking".
- "My site" in the top bar is **Go to Website**.
- The website logo is smaller in the header.

### Fixed

- Pictures in the homepage gallery, amenities, payment icons and guest photos disappeared after each update. They are now kept.
- The signed-in guest's account button was invisible on the white header.
- The currency change did nothing on the live site, which had been installed in euros.
- Configuration Information reported "files are missing from your server".
- The back office crashed after a vendor file was moved; it was moved back.

### Security

- Passwords are stored with bcrypt instead of MD5. Existing accounts are upgraded the next time they sign in.
- Eight published vulnerabilities in the platform patched ahead of the vendor's next release (list in `PATCHES.md`).
- Hotel reviews switched off, and their picture upload fixed, after a critical vulnerability was published.
- Limits on repeated sign-in attempts and on the contact, password reset and sign-up forms.
- Security headers added, including a Content-Security-Policy; scripts cannot run from upload folders.
- Payment confirmation requires the guest's own token.
- SVG files can no longer be uploaded through the file manager.
- Back office sessions end after 12 hours.

### Removed

- Bank wire and cheque payment options.
- The sample "What our guests say" reviews on the homepage, until there are real ones.
- Unused code: an old encryption class, a test scaffold with no tests, and a CI file.

## 2026-10-05

### Added

- **Salisberg Hotels** name, logo, favicon and colours on the website, in emails, on invoices and in the back office.
- **New website design:** homepage, then search results, room pages, sign-in and contact.
- A Docker setup built from this code, deployed with Coolify, with data kept across updates.
- The website address follows one setting, so the site can be moved to another domain without reinstalling.

### Changed

- Sample content renamed from "Hotel Prime" to Salisberg Hotels, with `booking@salisberg.com` as the contact address.

### Fixed

- The vendor's Docker image failed with "Forbidden" when given permanent storage, and exposed the database and SSH to the internet. Replaced.
- 152 files missing from the original import (translations, icons, licence) restored.

### Removed

- Vendor logos, links and promotions from the sign-in page, dashboard, header and footer.

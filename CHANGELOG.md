# Changelog

Every change to Salisberg, newest first, in plain words.

- **Added** is something new. **Changed** is something that works or looks different. **Fixed** is a fault put right. **Security** is protection added. **Removed** is something taken away.
- Changes that people using the back office will notice are also listed inside the app, under **Guides › What's New**.
- How and why each change was made, and how it was tested, is in `AGENTS.md`, section 6.
- `CHANGELOG.txt` is the original vendor's own history up to version 1.7.0 and is not updated here.

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

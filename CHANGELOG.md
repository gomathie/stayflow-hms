# Changelog

Every change to Salisberg, newest first, in plain words.

- **Added** is something new. **Changed** is something that works or looks different. **Fixed** is a fault put right. **Security** is protection added. **Removed** is something taken away.
- Changes that people using the back office will notice are also listed inside the app, under **Guides › What's New**.
- How and why each change was made, and how it was tested, is in `AGENTS.md`, section 6.
- `CHANGELOG.txt` is the original vendor's own history up to version 1.7.0 and is not updated here.

## 2026-10-07

### Added

- **Dark mode on the website.** A sun/moon switch in the header, on computers and phones. The site starts light; a visitor's choice is remembered on their device.
- **What's New page** in the back office (Guides › What's New). Which profiles can open it is set in Administration › Permissions. Hotel Staff and Hotel Manager have it to begin with.
- This file.

### Changed

- The vendor's product name no longer appears in back office text; it reads "Salisberg" instead.
- **Website footer:** "Payment accepted" shows Cash and Mobile Money badges in place of Visa, American Express, MasterCard and PayPal logos, which the hotel does not take. Home, Our Properties, Interior and Contact Us are listed under Explore, in two columns. The empty "Follow us on" column is hidden until social links are entered. The sample founding year "2010" is gone from the copyright line.
- Back office side menu text is slightly larger, and the menu is a little wider so long names are not cut off.
- **Back office sign-in page** simplified to the logo and one form; the version number and second logo are gone.
- **Tooltips and pop-up windows** in the back office restyled: rounded, readable in light and dark, and no longer hidden behind the top bar or menu.
- `README.md` rewritten for Salisberg.
- The top-of-dashboard figure tiles use the same off-white as other cards in light mode.
- The "working" spinner sits at the right end of the top bar instead of over the logo.

### Fixed

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

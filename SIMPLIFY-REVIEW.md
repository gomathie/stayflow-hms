# Simplify review

How Salisberg keeps getting simpler to use. Every agent and developer follows it (rule 31 in `AGENTS.md`).

**The goal:** a front-desk employee with no training can do the day's work. Fewer screens, fewer fields, fewer words, nothing the hotel does not use.

**The owner approves before anything is built.** Agents look for problems and write proposals. They do not build a proposal until the owner has marked it Approved below.

## 1. What to look for

Check every screen you touch, plus one area from the rota in section 4 at the start of each working session.

| Look for | Example | Usual fix |
|---|---|---|
| Something nobody uses | A menu entry for a shop feature (stock, suppliers) | Hide it, then remove it (`CLEANUP-PLAN.md`) |
| Too many steps | Opening a cart, then a form, then a pop-up to book one room | Merge steps, add a direct button |
| Too many fields | A form asking for birthday, newsletter, group | Show only what the task needs |
| Words staff do not use | "Order", "Cart rule", "Carrier" | Use hotel words: booking, voucher |
| Two ways to do one thing | Book Now and Add new order | Keep one |
| Information hard to find | Balance owed only inside the booking | Show it where the decision is made |
| Errors that do not say what to do | "Invalid field" | Say what is wrong and how to fix it |
| Slow or broken on a phone | Tables wider than the screen | Stack, shorten, or hide less important columns |
| Vendor leftovers | Sample data, vendor links, "QloApps" text | Remove or rename |
| Code nobody calls | An override, a module, a function | Prove unused, then delete (rule 28) |

**Evidence, not taste.** Every finding needs at least one of:
- a screenshot (light and dark when it is about looks);
- the number of clicks or fields before and after;
- an error from the log;
- a search result proving something is unused;
- a guide step that is long because the screen is complicated.

## 2. How to write a proposal

Add it to section 3 using this shape. Keep it short; the owner reads it to decide.

```
### P-<number>: <short title>

- **Status:** Proposed
- **Found by / on:** <agent or person>, <date>
- **Where:** <menu path or page>
- **Problem:** <what is hard today, for whom, with the evidence>
- **Change:** <what would be different, in plain words>
- **Why we need it:** <the reason this matters to the hotel>
- **Benefits:** <time saved, mistakes avoided, steps or fields removed>
- **Risks and costs:** <what could break, data touched, how long it takes>
- **Who notices:** Staff / Manager / Developer team / Guests
- **Undo:** <how to go back>
- **Size:** Small (under an hour) / Medium (a day) / Large (more than a day)
```

The owner changes **Status** to **Approved**, **Rejected** or **Later**, optionally with a note. Only then does work start. Once it is built and recorded, the agent sets **Status** to **Done**, with the date and the `AGENTS.md` entry title. Rejected proposals stay in the list, so the same idea is not proposed again without new evidence.

## 3. Proposals

Add new ones at the top.

> **Production review, 2026-11-19** (P-7 to P-15). Signed in as the SuperAdmin agent account and visited 60 menu pages read-only, at 1440 and 390 wide; nothing was saved. Screenshots are kept outside the repository. Inspiration: hotel front-desk dashboards on Dribbble and the Stayntouch front-desk dashboard: neutral cards, one accent colour, status chips, short lists, a grouped sidebar. **Not covered:** the Hotel Staff and Hotel Manager flows. No logins for those roles were available, and test accounts were not created on production without approval.

> **Role review, 2026-11-19** (P-16, P-17). Test accounts `agent-staff@salisberg.com` (Hotel Staff) and `agent-manager@salisberg.com` (Hotel Manager) were created on production with owner approval. Their passwords are only in the local `.env`. Each toured its own menu read-only.
>
> | Role | Sections | Pages |
> |---|---|---|
> | Hotel Staff | 5 | 10 |
> | Hotel Manager | 9 | 26 |
> | Developer team | 12 | 59 |

### P-17: Staff menu: only the desk

- **Status:** Done 2026-11-20 (AGENTS.md: "Staff desk menu P-17; System group P-9"). The bookings bell stays in the top bar: it now lists new bookings, not a shop cart.
- **Found by / on:** Claude, 2026-11-19 (production, as Hotel Staff)
- **Where:** the Hotel Staff sidebar and top bar
- **Problem:**
  - **Manage Hotel** holds a single page for staff (Refund requests).
  - **Customers › Addresses** and **Bookings › Booking messages** are platform pages staff do not need. Addresses are filled in by the booking; Booking messages are canned order texts.
  - The top bar's cart and customer icons and Quick Access open shop features.
  - The New booking button lives only on the Bookings page; the Dashboard has "Open room board".
- **Change:**
  - Move Refund requests under Bookings, so Manage Hotel disappears for staff.
  - Remove Addresses and Booking messages from the staff profile.
  - Hide the cart icon and Quick Access for Hotel Staff.
  - Call the Dashboard button "New booking", the same name as everywhere else.
- **Why we need it:** a front-desk menu of three sections (Dashboard, Bookings, Customers) plus the guide.
- **Benefits:** nothing to learn that is not front-desk work.
- **Risks and costs:** permission changes through `$staffAccess` (one-off for existing profiles, as before); guide menu tables change.
- **Who notices:** Staff
- **Undo:** restore the rights
- **Size:** Small

### P-16: Hotel Staff should not see the hotel's income

- **Status:** Done 2026-11-20 (AGENTS.md: "Income kept from Hotel Staff P-16"). Owner's choice, 2026-11-19: hide the sales totals and the revenue and performance charts from Hotel Staff; keep Reports for the daily Arrivals and departures sheet only
- **Found by / on:** Claude, 2026-11-19 (production, as Hotel Staff)
- **Where:** Bookings figure strip, Dashboard charts, Bookings › Reports
- **Problem:** a Hotel Staff account sees:
  - **Total Sales GH₵43,900** at the top of Bookings;
  - the **Revenue** and Performance charts on the Dashboard;
  - **Reports**, including Income by day. On 2026-10-07 Reports was meant for managers only; on production the staff profile has it.
- **Change:** staff see the desk (arrivals, departures, in house, unpaid for their guests) and no income totals. Reports, the revenue charts and the sales figure are for Hotel Manager and SuperAdmin.
- **Why we need it:** most hotels keep takings away from front-desk logins.
- **Benefits:** a simpler staff screen, and income stays private.
- **Risks and costs:** if the owner wants the desk to print the daily income sheet, keep Reports and remove only the charts. Permission change plus the Dashboard chart modules checking the role.
- **Who notices:** Staff
- **Undo:** grant the rights again
- **Size:** Small

### P-15: Tidy guest names and hide placeholder emails

- **Status:** Approved (owner, 2026-11-19). Owner's choice 2026-11-20: new and edited names only; saved names are not changed
- **Found by / on:** Claude, 2026-11-19 (production Customers and Invoices lists)
- **Where:** Customers › Customers; guest names everywhere
- **Problem:** names are stored as typed ("mr mr", "george wilson", "Mr Emmanuel" as a first name). Walk-ins without email show as `guest-20261112145856-287888@guest.invalid` in the list.
- **Change:** capitalise names on save (George Wilson); refuse a title alone as a name; show "No email" instead of the placeholder address.
- **Why we need it:** names print on invoices, emails and texts to guests.
- **Benefits:** professional documents; the list is readable.
- **Risks and costs:** existing names are not changed unless the owner also wants a one-off tidy.
- **Who notices:** Staff, Manager, Guests
- **Undo:** remove the formatting
- **Size:** Small

### P-14: Phone: compact booking cards, no Delete on the card

- **Status:** Approved (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (Bookings at 390px: 24,278px tall)
- **Where:** Bookings list, Customers list and Invoices on phones
- **Problem:** on a phone each booking is a nine-row table about 470px tall, so the page is 24,000px long. A red Delete link sits on every card next to View.
- **Change:** one compact card per booking (reference, guest, dates, status, due), with the whole card opening the booking. Delete only inside the booking.
- **Why we need it:** staff use the desk on phones; a stray tap should never start a deletion.
- **Benefits:** about four times more bookings per screen; fewer accidental deletions.
- **Risks and costs:** stylesheet and list template changes; check every list that uses the stacked layout.
- **Who notices:** Staff, Manager
- **Undo:** restore the stacked rows
- **Size:** Medium

### P-13: Page long lists (20 or 50 at a time)

- **Status:** Done 2026-11-20 (AGENTS.md: "Lists in pages P-13")
- **Found by / on:** Claude, 2026-11-19 (Invoices 12,078px tall at 1440, 89,384px at 390; Logs 7,449px)
- **Where:** Bookings › Invoices, Advanced Parameters › Logs, and any list showing everything
- **Problem:** Invoices shows all 155 invoices on one page; on a phone that is 89,000px of scrolling, and it grows with every booking.
- **Change:** 20 rows per page by default, with Next/Previous and a choice of 50; the totals at the top still cover the whole filter.
- **Why we need it:** the page gets slower and longer every day.
- **Benefits:** fast pages; the newest items always on top.
- **Risks and costs:** the Invoices list is our own code (`renderInvoiceList()`, capped at 200 today), so this is contained.
- **Who notices:** Staff, Manager
- **Undo:** remove paging
- **Size:** Small

### P-12: Bookings list: calmer colours, one date format

- **Status:** Approved (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (production Bookings list)
- **Where:** Bookings › Bookings
- **Problem:**
  - Every status is a bright green bar.
  - Total and Due are green pills, even when Due is GH₵0.00.
  - Dates show as 11/12/2026, while the rest of the app says 12 Nov 2026.
  - The reference column (old random codes) and the title-only guest names are hard to scan.
- **Change:** status as a small neutral chip with a coloured dot (red only for problems); amounts as plain text, with Due in red only when money is owed; dates as 12 Nov 2026.
- **Why we need it:** the eye should land on what needs action (money owed), not on every row.
- **Benefits:** unpaid bookings stand out at once.
- **Risks and costs:** the list is the inherited `AdminOrdersController` list; styles and column callbacks change.
- **Who notices:** Staff, Manager
- **Undo:** restore the old callbacks and styles
- **Size:** Medium

### P-11: Replace the shop figure strips on list pages

- **Status:** Approved (owner, 2026-11-19). Owner's choice 2026-11-20: keep one quiet desk line on Bookings (in house, arriving, leaving, owed)
- **Found by / on:** Claude, 2026-11-19 (Bookings, Customers, Manage Room Types, Translations)
- **Where:** the coloured figure strips at the top of Bookings, Customers, Manage Room Types and other lists
- **Problem:**
  - The strips repeat shop statistics: Abandoned carts, Conversion rate, Total newsletter registrations, "5% male customers", RevPAC, Banned customers.
  - Some contradict the Dashboard. On the same morning, Manage Room Types said **Occupied Rooms 0** and Bookings said **Departures 0**, while Today at the desk said **6 rooms in house and 6 overdue**.
- **Change:** remove the strips from these lists. Where a figure helps, show it from the same source as the Dashboard desk (in house, arriving, leaving, owed), so the numbers always agree.
- **Why we need it:** two different answers to "how many rooms are occupied?" make staff distrust the system.
- **Benefits:** about 180px less at the top of every list; one source of truth.
- **Risks and costs:** the strips come from the inherited `kpis` code in each controller; removing them is simple, replacing them is more work.
- **Who notices:** Staff, Manager
- **Undo:** restore the strips
- **Size:** Medium

### P-10: Customers list: hotel columns only

- **Status:** Approved (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (Customers: 16 columns)
- **Where:** Customers › Customers
- **Problem:** the list has 16 columns, including ID, Social title (always "--"), Sales, Enabled and Newsletter, and the email column is mostly placeholder addresses. Staff look guests up by name or phone, which is not even a column.
- **Change:** Name, Phone, Email (blank when a placeholder), Bookings, Last stay, Owed. The other columns stay available under "Available Fields" for the developer team.
- **Why we need it:** finding a returning guest by phone is the common task.
- **Benefits:** a list that fits the screen and answers "have they stayed before?".
- **Risks and costs:** inherited `AdminCustomersController` field list; the export keeps all fields.
- **Who notices:** Staff, Manager
- **Undo:** restore the field list
- **Size:** Small

### P-9: Admin-only "System" group in the sidebar

- **Status:** Done 2026-11-20 (AGENTS.md: "Staff desk menu P-17; System group P-9"). Built as a folding heading in the menu, not by moving tabs, so no permission or menu-parent changes.
- **Found by / on:** Claude, 2026-11-19 (12 sidebar sections, 59 pages for the developer team)
- **Where:** the sidebar
- **Problem:** five of twelve sections are technical: Modules and Services, Localization, Preferences, Advanced Parameters and Administration. They hold 30 of the 59 pages, including Positions (10,710px tall, 704 buttons) and a Translations page that tries to reach the vendor's website. For a SuperAdmin they bury the daily sections.
- **Change:** fold the five into one **System** section, visible to SuperAdmins only and closed by default. Managers keep the pages they are granted today (for example Preferences › CMS and Employees) inside it. Staff never see it.
- **Why we need it:** the daily sections stay on one screen and technical pages are out of the way.
- **Benefits:** a sidebar of eight entries instead of twelve; no change to permissions.
- **Risks and costs:** menu parent changes through `salisbergguide` (as for Bookings and Rooms & Services); guide paths change.
- **Who notices:** Manager, Developer team
- **Undo:** move the sections back
- **Size:** Medium

### P-8: Sidebar: readable sub-pages and group headings

- **Status:** Done 2026-11-19 (AGENTS.md: "Side menu P-8; employee phone and alerts")
- **Found by / on:** Claude, 2026-11-19 (every production page; the earlier owner request "groups the side bars to a modern view")
- **Where:** the sidebar
- **Problem:**
  - The open page's name is white text on a pale green chip and is nearly unreadable (for example "Bookings" under Bookings, "Invoices", "Manage Room Types").
  - The other sub-pages are pale grey.
  - There are no group headings, so twelve sections read as one long list.
- **Change:**
  - The current sub-page gets dark text on the pale chip with a green bar on its left. The other sub-pages use the normal text colour.
  - Small headings group the sections: **Front desk** (Dashboard, Bookings, Customers), **Hotel** (Rooms & Services, Manage Hotel, Manage Discounts), **Help** (Guides), **System** (P-9).
  - Checked in light and dark, following Dribbble and Stayntouch patterns.
- **Why we need it:** staff must see where they are.
- **Benefits:** it is readable at a glance, and the structure matches how the hotel works.
- **Risks and costs:** `overrides.css` section 5 and a few lines of `side-menu.js`; no template change.
- **Who notices:** everyone
- **Undo:** restore the styles
- **Size:** Small

### P-7: Dashboard: one look for every card

- **Status:** Approved (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (production Dashboard)
- **Where:** Dashboard
- **Problem:**
  - The Demo mode switch sits in the page header for the developer team, which invites a click.
  - The chart area starts with a paragraph of help text and six period buttons.
  - The desk lists below the tiles are all folded panels of the same weight, so nothing says where to start.
- **Change:**
  - Move Demo mode into the charts' own settings.
  - Replace the help paragraph with a one-line hint.
  - Give the panel that needs action now (Overdue, Unpaid) a coloured left edge, and keep the others plain. This follows the Dribbble front-desk dashboards: one accent colour, neutral cards.
- **Why we need it:** the first glance should show the one thing to do next.
- **Benefits:** a calmer page that still holds everything.
- **Risks and costs:** template and CSS only.
- **Who notices:** everyone
- **Undo:** restore
- **Size:** Small

### P-6: Website booking: go straight to checkout after Book Now

- **Status:** Later (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (rota area 1, from the website booking test of 2026-10-16)
- **Where:** website room search and room page
- **Problem:** after choosing guests and clicking Book Now, a pop-up shows the cart; the guest must click Proceed to checkout, then Proceed again before the details form. That is about 10 clicks to book one room, and the pop-up is where guests can lose their place.
- **Change:** Book Now adds the room and opens checkout directly. The pop-up is kept only when the guest is adding a second room.
- **Why we need it:** most guests book one room; every extra screen is a chance to leave.
- **Benefits:** two screens and two clicks fewer for a single-room booking.
- **Risks and costs:** changes inherited theme scripts (`blockcart`, the room search block). Multi-room bookings need testing.
- **Who notices:** Guests
- **Undo:** restore the pop-up behaviour in the script
- **Size:** Medium

### P-5: Room board: one click to start a booking

- **Status:** Done 2026-11-19 (AGENTS.md: "Simplify review P-1, P-2, P-5 built")
- **Found by / on:** Claude, 2026-11-19 (rota area 1)
- **Where:** Bookings › New booking (Room board)
- **Problem:** staff click a room card to select it, then click Book now in the Selected room panel: two clicks, and the panel is easy to miss on a phone.
- **Change:** a small Book button on each available card starts the booking for that room. Clicking the card itself still only selects it, to compare.
- **Why we need it:** the room is usually decided the moment staff see it.
- **Benefits:** one click fewer per booking; works the same on phone and computer.
- **Risks and costs:** more buttons on the board; card layout needs a check at phone width.
- **Who notices:** Staff, Manager
- **Undo:** remove the card button
- **Size:** Small

### P-4: Payment step: show the total and the balance, fold the breakdown

- **Status:** Done 2026-11-19, owner chose "two big numbers" from a preview (AGENTS.md: "Simplify review P-4 built")
- **Found by / on:** Claude, 2026-11-19 (rota area 1; `walkin.tpl` lines 107–115 and 150–154)
- **Where:** guided booking, Payment step
- **Problem:** the Payment step lists seven money lines (accommodation, fees, discounts, taxes, total, confirmed payments, balance). The Stay summary beside it repeats five of them. Staff need two numbers: what the stay costs and what is still owed.
- **Change:** show **Total** and **Still to pay** large. Put the breakdown behind "Show breakdown", in one place only, the summary.
- **Why we need it:** less reading before taking money; fewer chances of quoting the wrong line to a guest.
- **Benefits:** a shorter screen, with the right number obvious.
- **Risks and costs:** none to the data; template change only.
- **Who notices:** Staff, Manager
- **Undo:** restore the lines
- **Size:** Small

### P-3: Guest step: one box to find or add the guest

- **Status:** Later (owner, 2026-11-19)
- **Found by / on:** Claude, 2026-11-19 (rota area 1; `walkin.tpl` lines 77–101)
- **Where:** guided booking, Guest step
- **Problem:** the step shows a search box, then "or add a new guest" with four more fields, then optional details. Staff must decide which half to use, and the help text tells them to search first.
- **Change:** one box, "Phone or name". Matches appear as they type. If there is no match, the four new-guest fields open already filled with what was typed: the phone, or the first and last name.
- **Why we need it:** most walk-ins are found or added from their phone number; one box removes the decision.
- **Benefits:** fewer fields on screen; duplicate guests prevented by default instead of by instruction.
- **Risks and costs:** script change in `walkin.js`; the duplicate check on save stays as it is.
- **Who notices:** Staff, Manager
- **Undo:** restore the two-part layout
- **Size:** Medium

### P-2: Cash payments: no "received and verified" tick

- **Status:** Done 2026-11-19 (AGENTS.md: "Simplify review P-1, P-2, P-5 built")
- **Found by / on:** Claude, 2026-11-19 (rota area 1; `walkin.tpl` line 122)
- **Where:** guided booking, Payment step
- **Problem:** for every payment, staff must tick "Payment received and verified", or the money is not recorded. For cash, the money is in their hand, so the tick is an extra click. Forgetting it records the booking as unpaid.
- **Change:** for **Cash**, it counts as received without the tick. The tick stays for Mobile Money and bank transfer, where checking really is needed.
- **Why we need it:** removes a common way to record cash wrongly.
- **Benefits:** one click fewer on every cash booking; fewer bookings wrongly left unpaid.
- **Risks and costs:** a cash booking could be recorded as paid by mistake. The Pay later option still covers "not paid yet".
- **Who notices:** Staff, Manager
- **Undo:** require the tick again
- **Size:** Small

### P-1: Merge Payment and Confirm into one step

- **Status:** Done 2026-11-19 (AGENTS.md: "Simplify review P-1, P-2, P-5 built")
- **Found by / on:** Claude, 2026-11-19 (rota area 1; `walkin.tpl` lines 104–143)
- **Where:** guided booking (Guest → Payment → Confirm)
- **Problem:** the Confirm step only repeats the stay, guest and payment already shown in the Stay summary on the right, then offers the final buttons. Every booking needs one extra page load and one extra click to get there.
- **Change:** two steps, **Guest** then **Payment & confirm**. The Confirm & check in / Confirm booking buttons sit at the bottom of the payment step. The summary on the right stays the check.
- **Why we need it:** the confirm page adds a step but no new decision.
- **Benefits:** one page and one click fewer on every front-desk booking. Shorter Staff Guide section 3.
- **Risks and costs:** the price-changed warning and its "I accept" tick must move to the payment step. Medium change to the controller and template, then the full booking test for all three roles.
- **Who notices:** Staff, Manager
- **Undo:** restore the third step
- **Size:** Medium

## 4. Review rota

One area per working session, in this order, then start again. Note the date in the last column when it is reviewed.

| # | Area | What to try | Last reviewed |
|---|---|---|---|
| 1 | Making a booking | Walk-in, phone booking, website booking; count clicks and fields | 2026-11-19 (P-1 to P-6) |
| 2 | Dashboard and Today at the desk | Can the day's work be done from here? | 2026-11-19; production 2026-11-19 (P-7, P-11) |
| 3 | Payments and invoices | Record cash, MoMo and bank; print; find who owes | |
| 4 | Check-in, check-out, housekeeping | From the Dashboard and from the booking | |
| 5 | Guests and Inbox | Find a guest, answer a message | 2026-11-19 production (P-10, P-15) |
| 6 | Rooms, prices and charges | Change a price, close a room, add a room | |
| 7 | Settings and menus | Which menu entries are never opened? | 2026-11-19 production (P-8, P-9) |
| 8 | Website, phone first | Search, book and pay on a phone | |
| 9 | Messages | Texts and emails: are they clear and short? | 2026-11-19 |
| 10 | Guides | Long steps usually point to a screen to simplify | |
| 11 | Code | Overrides, unused modules, dead code (`CLEANUP-PLAN.md`) | 2026-11-19 |

## 5. What agents must not do

- Build an unapproved proposal, however small. A fault (something broken) may be fixed straight away under the normal rules; a simplification may not.
- Remove anything a role uses without a replacement in the same change.
- Count "it looks old" as evidence.
- Bundle several proposals into one change.

## 6. Build order and test accounts (owner, 2026-11-19)

1. **Build order:** P-8 sidebar, P-9 System group, P-17 and P-16 roles, P-11 strips, P-12, P-13 and P-14 lists, P-10 and P-15 customers, P-7 Dashboard.
2. **Test accounts:** `agent-staff` and `agent-manager` stay active on production until each change is verified there as that role, then they are switched off. Their credentials are only in the local `.env`.

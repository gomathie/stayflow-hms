# Cleanup plan

How Salisberg is made smaller step by step without breaking the app. Owner decisions of 2026-11-19 are recorded here; the detailed record of each finished step goes in `AGENTS.md` section 6, as always.

## Order

1. **Ship the new booking codes** (step 0). Nothing is removed until this is live and checked.
2. Then the cleanup batches, one at a time, in the order below.

## Step 0: simple booking codes

**Chosen format (2026-11-19):** prefix `SB`, two-digit year, month and day, then a counter. Example: `SB-261119-07` is the 7th booking made on 19 November 2026.

**Existing bookings are not changed.** Only bookings made after this ships get the new form; every booking already in the app keeps its current 9-letter code, everywhere it appears.

### How it is built

- `Order::generateReference()` (`classes/order/Order.php`) is the one place codes are made. It changes to: prefix, the booking date, then the next counter for that date.
- The counter is taken inside a database lock so two bookings at the same second never get the same code. The counter table is created by a versioned step in `docker/entrypoint.sh`.
- `orders.reference` is 9 characters long; the new code is 12 characters. The same schema step widens it (and every column that copies the reference: `order_payment.order_reference`, `htl_booking_detail` if present). It reads the current widths first and logs what it changed (rule 22).
- From 100 bookings in a day the counter simply grows to three digits (`SB-261119-100`).
- **Old codes keep working.** Existing bookings keep their 9-letter references; search, invoices, emails and texts take either form.

### Checks before it ships

- Bookings made on the website, at the front desk (Room board and guided booking), and by Excel import each get a new code.
- Two bookings created at the same time get different codes.
- Search by the new code works under Bookings, Invoices and Inbox; the code is correct on the invoice PDF, the confirmation email, the guest texts and staff alerts.
- Old bookings open and still show their old code.
- Guides, What's New, `CHANGELOG.md` and `AGENTS.md` updated (rules 23 and 30).

## How every removal is done

Rule 28, in two deploys so it can be undone:

1. **Prove it is unused.** Search the whole tree for the module name, its hooks, admin tabs, settings, file paths and CSS selectors, and check `classes/ConfigurationTest.php`.
2. **Uninstall locally.** Rebuild, then run `docker/smoke-test.sh` and the booking flow as administrator, Hotel Manager and Hotel Staff. Read the log.
3. **Deploy A: uninstall.** A one-time step in `docker/setup-modules.php` uninstalls it on the live site and logs exactly what it removed. The folder is still there, so switching it back on undoes it.
4. **Deploy B: delete.** Once the live site has run cleanly after deploy A, delete the folder and any leftover references.
5. **Record it** in `AGENTS.md` section 6 (with the evidence), `CHANGELOG.md` (Removed), and What's New if anyone using the back office will notice.

Never move or rename an inherited file (rule 29). One batch per change.

## Batches

| # | Batch | Modules | Why they can go | Risk |
|---|---|---|---|---|
| 1 | **Never installed** (first, chosen) | `gridhtml`, `qlocleaner`, `qlopaypalcommerce` | Not installed; nothing loads them | Very low |
| 2 | Replaced by ours | `bankwire`, `cheque` (Salisberg Pay); `qlochannelmanagerconnector`; `qlohotelreview` (CVE-2025-67325) | Already switched off on every deploy | Low |
| 3 | Shop statistics | 20 `stats*` modules, `statsdata`, `sekeywords`, `pagesnotfound`, `qlostatsserviceproducts`; `graphnvd3` only if no Dashboard chart uses it | Stats menu is hidden; Reports and the Dashboard replace it | Medium: check the chart library first |
| 4 | Dashboard leftovers | `dashactivity`, `dashproducts`, `dashgoals`, `dashinsights` | Shop figures we do not show | Medium |
| 5 | Inherited shop features | Stock classes and tables, suppliers, manufacturers, leftover comparison code, unused admin tabs | Hidden since 2026-10-13 | Higher: loaded by class index and by path |
| 6 | Unused images and translations | Files under `img/` and `translations/` nothing references | Size only | Low |

### Kept on purpose

| Module | Why |
|---|---|
| `hotelreservationsystem` | The booking engine |
| `blockcart`, `blockuserinfo`, `blockmyaccount`, `blocknavigationmenu` | The website needs them |
| `wkabouthotelblock`, `wkhotelfeaturesblock`, `wkhotelroom`, `wkroomsearchblock`, `wkhotelfilterblock`, `wkfooterpaymentblock`, `wkfooterpaymentinfoblockcontainer`, `wkfooternotificationblock` | Homepage and footer |
| `dashoccupancy`, `dashtrends`, `dashperformance`, `dashavailability`, `dashguestcycle` | Dashboard charts |
| `salisbergfrontdesk`, `salisbergguide`, `salisbergnotify`, `salisbergpay`, `salisbergreports` | Ours |
| `wktestimonialblock` | Off until real reviews exist (owner will switch it on) |
| `blocknewsletter`, `blocksocial`, `blocklanguages`, `blockcurrencies`, `wkfooterlangcurrencyblock` | Owner decision 2026-11-19: keep for now, review later |

## Overrides: fold them into the code they change

**Goal (owner, 2026-11-19):** the code should say what it does where it lives, instead of an override in `override/` quietly changing it. There are seven override files (463 lines). Each one is moved into the file it overrides, the override is deleted, and `cache/class_index.php` is rebuilt.

**Approved 2026-11-19:** rule 11 now says `override/` is not used.

| # | Override | What it does | Move into | Risk |
|---|---|---|---|---|
| O1 | `controllers/admin/AdminOrdersController.php` (15 lines) | Sends the old "Add new order" form to the Room board | `controllers/admin/AdminOrdersController.php` | Low |
| O2 | `classes/Mail.php` (54) | Never sends to `guest.invalid` placeholder addresses | `classes/Mail.php` | Low |
| O3 | `controllers/admin/AdminDashboardController.php` (46) | Resets the chart period to Day when it no longer includes today | `controllers/admin/AdminDashboardController.php` | Low |
| O4 | `classes/Translate.php` (46) | Replaces the vendor name with Salisberg in on-screen text | `classes/Translate.php`. Better still: change the words in the templates and delete the replacement | Medium (touches every page) |
| O5 | `controllers/admin/AdminImportController.php` (69) | Accepts `.xlsx` only and converts it to CSV | `controllers/admin/AdminImportController.php` | Medium |
| O6 | `controllers/admin/AdminCustomersController.php` (111) | Finds or creates a guest from the booking form | `controllers/admin/AdminCustomersController.php` | Medium |
| O7 | `controllers/front/ContactController.php` (122) | Refuses links, strips markup, checks fields on the contact form | `controllers/front/ContactController.php` | Medium (security; re-run the attack tests from 2026-10-13) |

**How each one is done:**
1. Copy the override's methods into the inherited class, merging with the method of the same name rather than leaving two.
2. Delete the override and `cache/class_index.php`.
3. Rebuild, then re-run that feature's original tests (listed in its `AGENTS.md` entry) and `docker/smoke-test.sh`.
4. Record it in `AGENTS.md`, CHANGELOG (Changed). There is no What's New line: nobody sees a difference.

One override per change, low risk first (O1 → O7). When the folder is empty apart from `index.php` files, add a check to `docker/smoke-test.sh` that fails if a PHP override appears again.

Module templates overridden from the theme (`themes/hotel-reservation-theme/modules/…`) are the same kind of thing. List them after O7 and fold them the same way.

## Progress

| Step | Status |
|---|---|
| 0. Booking codes | Built and tested locally (2026-11-19); ships with the next deploy |
| 1. Never installed | Waiting for step 0 |
| 2–6 | Not started |
| O1–O7 Overrides | Rule 11 change approved 2026-11-19; not started |

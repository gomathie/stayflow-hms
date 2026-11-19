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

- **Status:** Approved (owner, 2026-11-19)
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

- **Status:** Proposed: owner wants to see a preview first (2026-11-19)
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

- **Status:** Approved (owner, 2026-11-19)
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

- **Status:** Approved (owner, 2026-11-19)
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
| 2 | Dashboard and Today at the desk | Can the day's work be done from here? | 2026-11-19 |
| 3 | Payments and invoices | Record cash, MoMo and bank; print; find who owes | |
| 4 | Check-in, check-out, housekeeping | From the Dashboard and from the booking | |
| 5 | Guests and Inbox | Find a guest, answer a message | |
| 6 | Rooms, prices and charges | Change a price, close a room, add a room | |
| 7 | Settings and menus | Which menu entries are never opened? | |
| 8 | Website, phone first | Search, book and pay on a phone | |
| 9 | Messages | Texts and emails: are they clear and short? | 2026-11-19 |
| 10 | Guides | Long steps usually point to a screen to simplify | |
| 11 | Code | Overrides, unused modules, dead code (`CLEANUP-PLAN.md`) | 2026-11-19 |

## 5. What agents must not do

- Build an unapproved proposal, however small. A fault (something broken) may be fixed straight away under the normal rules; a simplification may not.
- Remove anything a role uses without a replacement in the same change.
- Count "it looks old" as evidence.
- Bundle several proposals into one change.

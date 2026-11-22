# End-to-end tests

Browser tests of the journeys that matter most, run with [Playwright](https://playwright.dev/) in the Chrome already installed on the machine. They complement `docker/smoke-test.sh`, which checks that pages load without opening a browser.

**Local stack only.** The tests create bookings, guests and two test employees. The config refuses any address that is not `http://localhost…`. Never point them at the live site.

## What is covered

| File | Journey |
|---|---|
| `specs/website-booking.spec.js` | A guest books on the website with a phone number only (no password, no title field, email optional) and pays cash; on desktop and on a phone. On a phone, the search pop-up's Search Rooms button is not covered by the guest picker. |
| `specs/desk-booking.spec.js` | Hotel Staff make a booking on New booking (Room board, guest, Pay later, confirm) and find it under Bookings. |
| `specs/roles-and-security.spec.js` | Hotel Staff see the desk menu only and are refused Modules and Data clean-up; Hotel Manager cannot open Data clean-up and does not see test accounts; lists have no SQL buttons; the old order form goes to New booking; the Contact form refuses a web address. |

## Run

Prerequisites: the local stack running (`docker compose up -d`, site on `http://localhost:8080`), Node 20 or newer, Google Chrome installed, `.env` in the repository root (the administrator sign-in is read from it).

```bash
cd tests/e2e
npm install          # once
npm test             # everything, desktop and phone
npm run test:phone   # phone only
npx playwright test specs/desk-booking.spec.js   # one file
npm run report       # open the last report
```

A full run takes about five minutes.

## Test data

`support/global-setup.js` runs before every run. Inside the app container it:

- creates or refreshes two employees, `e2e.manager@example.com` (Hotel Manager) and `e2e.staff@example.com` (Hotel Staff), with the password `E2eTest#2026` (override with `E2E_PASSWORD`), and marks both as test accounts;
- clears the sign-in limit counters (`docker/ratelimit.php`), which would otherwise block repeated test sign-ins.

Bookings are made for dates 200 to 330 days ahead, different on each run, so tests never compete for a room. They stay in the local database, like any local test data; `docker compose down -v` removes everything.

## When a test fails

Playwright keeps a screenshot and a trace of every failed test in `test-results/`. Open the report (`npm run report`) or a trace (`npx playwright show-trace test-results/<test>/trace.zip`) to see each step.

A failure after a cache clear can be the site rebuilding its compiled templates: run again once before investigating.

## Not covered

- No CI: the project has none (Coolify only deploys). Run the suite before pushing.
- Real email and text messages: none are sent locally.
- Mobile Money and bank transfer payments, part payments, check-in and check-out from the booking page, Data clean-up runs.

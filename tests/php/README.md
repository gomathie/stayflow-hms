# Rule tests

Small PHP tests that pin how the money and safety rules behave today, so a change that alters one fails here before it reaches the live site. They run inside the local app container; there is no Composer or PHPUnit in this project, so `run.php` is a 70-line runner.

**Local stack only.** Tests that write run inside a database transaction that is always rolled back, so the local data is left as it was.

## Run

```bash
bash tests/php/run.sh
```

It copies this folder into the running `app` container and runs every `*.test.php` file in name order. Output is one line per test and a total; the exit code is 1 if any test failed.

## What is pinned

| File | Rule |
|---|---|
| `10-booking-codes` | Booking codes are `SB-YYMMDD-NN`, numbered in order within the day |
| `20-names-and-links` | Guest names are tidied (`ama mensah` → `Ama Mensah`, mixed case kept); a title alone is not a name; web addresses are refused, email addresses are not |
| `30-no-email-guests` | The placeholder email for guests without one is recognised, and nothing is sent to it |
| `40-payments` | Marking a booking paid records only what is still owed; a booking whose payment module was removed keeps its saved payment name |
| `50-room-own-price` | A room's own price applies at the desk, never on the website |
| `60-data-cleanup` | Shortest age per kind, the 6-year keep, the locked recycle bin |
| `70-known-gaps` | **Characterization:** Track my booking needs an email. The change that lets a phone-only guest track a booking must invert this test. |

## Writing a test

A file returns `array('what the rule is' => function () { ... })`. Use `check($condition, 'what was expected')` and `same($expected, $actual, 'what')`; wrap anything that writes in `rolledBack(function () { ... })`. Start with a precondition (the data exists, the feature is on) and add a negative control where a passing result could come from something else.

When a rule changes on purpose, change its test in the same commit and say why in `AGENTS.md`.

## Proof the tests can fail

On 2026-11-20 the payment cap was removed in the container: `40-payments` failed (GH₵308 recorded on a GH₵208 booking). With the cap restored it passed.

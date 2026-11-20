// Shared steps for the end-to-end tests.
const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');

function accounts() {
  return JSON.parse(fs.readFileSync(path.join(__dirname, '.accounts.json'), 'utf8'));
}

function adminPath(page) {
  return '/' + accounts().adminDir + '/';
}

/** Signs in to the back office as 'admin', 'manager' or 'staff'. */
async function signIn(page, role) {
  const a = accounts()[role];
  await page.goto(adminPath(page) + 'index.php?controller=AdminLogin');
  await page.locator('#email').fill(a.email);
  await page.locator('#passwd').fill(a.password);
  await page.locator('#login_form button[name=submitLogin]').click();
  await page.waitForURL(/controller=AdminDashboard/);
  // the one-time notes (phone number, What's New) must not cover the page
  await page.evaluate(() => {
    try { sessionStorage.setItem('sbNotifyPhoneDismissed', '1'); } catch (e) {}
    try { localStorage.setItem('sbNotifyPhoneDismissed', new Date().toISOString().slice(0, 10)); } catch (e) {}
  });
}

/**
 * Opens a back office page by its controller. Pages need a token; opening the
 * bare address shows the platform's "invalid token" page with a link that
 * carries the right one, which is followed here.
 */
async function openAdmin(page, controller, extra = '') {
  await page.goto(adminPath(page) + 'index.php?controller=' + controller);
  if (!/token=/.test(page.url())) {
    // the plain page link, not one that opens a record (such as "My preferences")
    const hrefs = await page.locator(`a[href*="controller=${controller}&token="]`).evaluateAll(
      (links) => links.map((a) => a.getAttribute('href')));
    const href = hrefs.find((h) => new RegExp(`controller=${controller}&token=[a-f0-9]+$`).test(h)) || hrefs[0];
    if (href) {
      await page.goto(new URL(href, page.url()).toString() + extra);
      return;
    }
  }
  if (extra) {
    await page.goto(page.url() + extra);
  }
}

/** A stay far enough ahead that test bookings never collide with each other. */
function futureStay(offsetDays) {
  const from = new Date(Date.now() + offsetDays * 86400000);
  const to = new Date(from.getTime() + 86400000);
  const iso = (d) => d.toISOString().slice(0, 10);
  return { from: iso(from), to: iso(to) };
}

async function noPhpErrors(page) {
  await expect(page.locator('body')).not.toContainText(/Fatal error|Warning:|Notice:|SmartyException/);
}

module.exports = { accounts, adminPath, signIn, openAdmin, futureStay, noPhpErrors };

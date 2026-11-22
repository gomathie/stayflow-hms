// The front desk makes a booking on New booking (Room board, guest, payment,
// confirm) as Hotel Staff, and finds it under Bookings. Desktop only: the
// phone layout of these screens is checked by the phone project elsewhere.
const { test, expect } = require('@playwright/test');
const { signIn, openAdmin, futureStay, noPhpErrors } = require('../support/helpers');

test('Hotel Staff books a room at the desk, pay later', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop', 'one run is enough');
  const stay = futureStay(300 + (Date.now() / 86400000 % 30 | 0));

  await signIn(page, 'staff');
  await openAdmin(page, 'AdminSalisbergRoomBoard');
  await page.locator('#sb-arrival').fill(stay.from);
  await page.locator('#sb-departure').fill(stay.to);
  await page.locator('#sb-room-board-form button[type=submit]').filter({ hasText: /Update availability/i }).click();
  await noPhpErrors(page);

  // Book on the first free room card
  await page.locator('[data-book-now]').filter({ visible: true }).first().click();
  await expect(page.locator('input[name=firstname]')).toBeVisible();

  const last = 'Desk' + 'abcdefghij'[Date.now() % 10] + 'ton';
  await page.locator('input[name=firstname]').fill('Test');
  await page.locator('input[name=lastname]').fill(last);
  await page.locator('input[name=phone]').fill('0244' + String(Date.now()).slice(-6));
  await page.locator('button[name=sb_save_guest]').click();

  await page.locator('input[name=arrangement][value=later]').check();
  await page.locator('button[name=sb_finalize][value=reserve]').first().click();

  await expect(page.locator('.sb-kicker')).toContainText(/Booking confirmed|Guest checked in/);
  const reference = (await page.locator('p:has-text("Booking reference") strong').innerText()).trim();
  expect(reference).toMatch(/^SB-\d{6}-\d+$/);

  await openAdmin(page, 'AdminOrders');
  await expect(page.locator('body')).toContainText(reference);
  await noPhpErrors(page);
});

// A guest books a room on the website, as a guest with a phone number only
// (P-24), and pays cash at the hotel. Runs on desktop and phone.
const { test, expect } = require('@playwright/test');
const { accounts, futureStay, noPhpErrors } = require('../support/helpers');

test('guest books a room with a phone number only', async ({ page }, info) => {
  // a different stay per project and run, so rooms are always free
  const stay = futureStay(200 + (info.project.name === 'phone' ? 40 : 0) + (Date.now() / 86400000 % 30 | 0));
  const cat = accounts().hotelCategory;
  await page.goto(`/index.php?id_category=${cat}&controller=category&date_from=${stay.from}&date_to=${stay.to}&occupancy[0][adults]=1&occupancy[0][children]=0`);
  await noPhpErrors(page);

  // Book Now on the first room type offered
  const book = page.locator('a.ajax_add_to_cart_button, button.ajax_add_to_cart_button').filter({ visible: true }).first();
  await expect(book).toBeVisible();
  await book.click();
  // on a phone the guest picker may open first; "Done" confirms it
  const done = page.getByRole('button', { name: /^\s*Done\s*$/ }).filter({ visible: true });
  if (await done.count()) {
    await done.first().click();
    await page.locator('a.ajax_add_to_cart_button, button.ajax_add_to_cart_button').filter({ visible: true }).first().click();
  }
  await page.getByRole('link', { name: /Proceed to checkout/i }).filter({ visible: true }).first().click();
  await expect(page).toHaveURL(/quick-order|order-opc/);

  // rooms summary, then the guest form
  await page.getByRole('link', { name: /^\s*Proceed\s*$/ }).or(page.getByRole('button', { name: /^\s*Proceed\s*$/ })).filter({ visible: true }).first().click();
  const form = page.locator('#opc_account_form');
  await expect(form).toBeVisible();
  // P-24: guest booking, no password, no title field
  await expect(page.locator('#opc_account_choice')).toContainText('no password needed');
  await expect(page.locator('#passwd')).toBeHidden();
  await expect(page.locator('#id_gender')).toHaveCount(0);

  await page.locator('#customer_firstname').fill('Test');
  await page.locator('#customer_lastname').fill('Guest');
  await page.locator('#customer_phone').fill('0244' + String(Date.now()).slice(-6));
  await page.locator('#submitGuestAccount').click();
  await expect(page.locator('#opc_account_errors')).toBeHidden();

  // payment step: accept the terms, choose cash, confirm
  await page.getByRole('link', { name: /^\s*Proceed\s*$/ }).or(page.getByRole('button', { name: /^\s*Proceed\s*$/ })).filter({ visible: true }).first().click();
  await page.locator('#cgv').check();
  await page.locator('a.salisbergpay-cash').click();
  await page.getByRole('button', { name: /confirm my booking/i }).filter({ visible: true }).first().click();

  await expect(page).toHaveURL(/order-confirmation/);
  await expect(page.locator('body')).toContainText('Your booking has been created');
  await expect(page.locator('body')).not.toContainText('guest.invalid');
  await noPhpErrors(page);
});

test('phone search: Search Rooms is not covered by the guest picker', async ({ page }, info) => {
  test.skip(info.project.name !== 'phone', 'the search pop-up only exists on phones');
  await page.goto('/');
  await page.getByRole('button', { name: /^\s*Book Now\s*$/i }).or(page.getByRole('link', { name: /^\s*Book Now\s*$/i })).filter({ visible: true }).first().click();
  const occupancy = page.locator('#search_occupancy_wrapper');
  // pick two dates in the calendar, as a guest does: the guest picker then
  // opens by itself, which is how the fault showed (2026-11-20)
  await page.locator('#daterange_value').click();
  await page.locator('.date-picker-wrapper .next').filter({ visible: true }).first().click();
  const days = page.locator('.date-picker-wrapper .day.valid.toMonth').filter({ visible: true });
  await days.nth(10).click();
  await days.nth(12).click();
  if (!(await occupancy.isVisible())) {
    await page.locator('.input-occupancy, #guest_occupancy').filter({ visible: true }).first().click();
  }
  await expect(occupancy).toBeVisible();
  const submit = page.locator('#search_room_submit');
  await submit.scrollIntoViewIfNeeded();
  const box = await submit.boundingBox();
  // the element under the button's centre must be the button itself (2026-11-20 fault)
  const onTop = await page.evaluate(([x, y]) => {
    const el = document.elementFromPoint(x, y);
    return !!(el && el.closest('#search_room_submit'));
  }, [box.x + box.width / 2, box.y + box.height / 2]);
  expect(onTop).toBe(true);
});

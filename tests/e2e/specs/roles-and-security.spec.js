// What each role may open, and the protections added in November 2026.
// Back office checks run on desktop only (the phone project repeats the
// website checks); menu checks look at what the role is shown.
const { test, expect } = require('@playwright/test');
const { signIn, openAdmin, noPhpErrors } = require('../support/helpers');

test.describe('back office roles', () => {
  test.beforeEach(async ({}, info) => {
    test.skip(info.project.name !== 'desktop', 'back office role checks run once, on desktop');
  });

  test('Hotel Staff: desk pages open, admin pages refused, desk menu only', async ({ page }) => {
    await signIn(page, 'staff');
    const menu = page.locator('#nav-sidebar li.maintab > a.title');
    await expect(menu.filter({ hasText: 'Bookings' })).toHaveCount(1);
    await expect(menu.filter({ hasText: 'Modules and Services' })).toHaveCount(0);
    await expect(menu.filter({ hasText: 'Administration' })).toHaveCount(0);

    await openAdmin(page, 'AdminOrders');
    await expect(page.locator('.page-title').first()).toContainText('Bookings');
    await noPhpErrors(page);

    // Employees opens only their own profile (every employee may edit their own), never the list
    await openAdmin(page, 'AdminEmployees');
    await expect(page.locator('body')).not.toContainText('e2e.manager@example.com');

    for (const ctl of ['AdminModules', 'AdminSalisbergCleanup']) {
      await openAdmin(page, ctl);
      await expect(page.locator('body'), ctl).toContainText(/Access denied|do not have permission/i);
    }
  });

  test('Hotel Manager: no Data clean-up, no SuperAdmin or test accounts in Employees', async ({ page }) => {
    await signIn(page, 'manager');
    await openAdmin(page, 'AdminSalisbergCleanup');
    await expect(page.locator('body')).toContainText(/Access denied/i);

    await openAdmin(page, 'AdminEmployees');
    await expect(page.locator('body')).toContainText('e2e.manager@example.com'); // the list is shown, with the manager
    await expect(page.locator('body')).not.toContainText('e2e.staff@example.com'); // a test account (the seed marks it)
    await noPhpErrors(page);
  });

  test('SuperAdmin: lists have no SQL buttons; the old order form goes to New booking', async ({ page }) => {
    await signIn(page, 'admin');
    await openAdmin(page, 'AdminOrders');
    await expect(page.locator('.process-icon-terminal, .process-icon-database, [id^=sql_query_]')).toHaveCount(0);
    await openAdmin(page, 'AdminOrders', '&addorder');
    await expect(page).toHaveURL(/controller=AdminSalisbergRoomBoard/);
  });
});

test('Contact form refuses a web address', async ({ page }, info) => {
  test.skip(info.project.name !== 'desktop', 'one run is enough');
  await page.goto('/contact-us');
  await page.locator('input[name=from]').fill('e2e.contact@example.com');
  const name = page.locator('input[name=user_name]');
  if (await name.count()) await name.fill('Test Contact');
  const phone = page.locator('input[name=phone]');
  if (await phone.count()) await phone.fill('0244000000');
  await page.locator('textarea[name=message]').fill('Please see www.example-scam.com for a deal.');
  await page.locator('button[name=submitMessage]').click();
  await expect(page.locator('.alert-danger').first()).toContainText('Links cannot be sent');
});

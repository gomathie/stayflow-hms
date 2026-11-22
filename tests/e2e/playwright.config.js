// Salisberg end-to-end tests (tests/e2e/README.md). Local stack only.
const { defineConfig, devices } = require('@playwright/test');

const BASE = process.env.E2E_BASE_URL || 'http://localhost:8080';
// Refuse anything but a local address: these tests create bookings and guests.
if (!/^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(BASE)) {
  throw new Error('E2E_BASE_URL must be a local address (http://localhost:...). Got: ' + BASE);
}

module.exports = defineConfig({
  testDir: './specs',
  globalSetup: require.resolve('./support/global-setup.js'),
  timeout: 120000,
  expect: { timeout: 15000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: BASE,
    channel: 'chrome',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 20000,
    navigationTimeout: 45000,
  },
  projects: [
    { name: 'desktop', use: { viewport: { width: 1440, height: 900 } } },
    { name: 'phone', use: { ...devices['Pixel 7'], channel: 'chrome' } },
  ],
});

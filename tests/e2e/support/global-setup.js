// Before the suite: test accounts and a clean sign-in counter, in the LOCAL
// container (docker compose, run from the repository root).
const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

module.exports = async () => {
  const root = path.resolve(__dirname, '../../..');
  const opts = { cwd: root, env: { ...process.env, MSYS_NO_PATHCONV: '1' }, encoding: 'utf8' };
  execSync('docker compose cp tests/e2e/support/seed.php app:/tmp/e2e-seed.php', opts);
  const line = execSync('docker compose exec -T -u www-data app php /tmp/e2e-seed.php', opts).trim().split('\n').pop();
  const accounts = JSON.parse(line);
  // The sign-in limit (docker/ratelimit.php) would block repeated test sign-ins.
  execSync('docker compose exec -T app sh -c "rm -rf /tmp/sb-ratelimit/*"', opts);

  const env = Object.fromEntries(fs.readFileSync(path.join(root, '.env'), 'utf8').split(/\r?\n/)
    .filter((l) => /^[A-Z_]+=/.test(l)).map((l) => [l.split('=')[0], l.slice(l.indexOf('=') + 1)]));
  fs.writeFileSync(path.join(__dirname, '.accounts.json'), JSON.stringify({
    admin: { email: env.ADMIN_EMAIL, password: env.ADMIN_PASSWORD },
    manager: { email: accounts.manager, password: accounts.password },
    staff: { email: accounts.staff, password: accounts.password },
    adminDir: env.ADMIN_DIR || 'admin-salisberg',
    hotelCategory: accounts.hotel_category,
  }));
};

import { test as setup } from '@playwright/test';
import { mkdirSync } from 'fs';
import {
  wpCli,
  createPage,
  createUser,
  generateDemoData,
  getPostCount,
  ADMIN_USER,
  ADMIN_STATE,
  AUTH_STATE_DIR,
  TEST_USER,
  USER_STATE,
} from './helpers';

/**
 * Global setup: seed data, create pages, authenticate users.
 * Runs once before all test suites.
 */
setup('seed test data and authenticate', async ({ page, baseURL }) => {
  setup.setTimeout(120_000);

  // Ensure auth state directory exists
  mkdirSync(AUTH_STATE_DIR, { recursive: true });

  // ── 1. Ensure demo data exists ───────────────────────────
  const listingCount = await getPostCount('apd_listing', 'publish');
  if (listingCount < 5) {
    console.log('Generating demo data...');
    await generateDemoData({ listings: 25, users: 5 });
    console.log('Demo data generated.');
  } else {
    console.log(`Found ${listingCount} listings, skipping demo data generation.`);
  }

  // ── 2. Create pages required for frontend tests ──────────
  // Use "directory" slug instead of "listings" to avoid conflict with
  // the post type archive at /listings/.
  const directoryPageId = await createPage(
    'Directory',
    'directory',
    '[apd_search_form]\n[apd_listings]'
  );
  const submitPageId = await createPage(
    'Submit Listing',
    'submit-listing',
    '[apd_submission_form]'
  );
  const dashboardPageId = await createPage(
    'Dashboard',
    'dashboard',
    '[apd_dashboard]'
  );

  // Update plugin settings to reference these pages
  await wpCli(
    `eval '$o = get_option("apd_options", []); $o["submit_page"] = ${submitPageId}; $o["dashboard_page"] = ${dashboardPageId}; $o["directory_page"] = ${directoryPageId}; $o["enable_reviews"] = true; $o["enable_favorites"] = true; $o["enable_contact_form"] = true; $o["show_thumbnail"] = true; $o["show_excerpt"] = true; $o["show_category"] = true; $o["show_rating"] = true; $o["show_favorite"] = true; update_option("apd_options", $o);'`
  );
  console.log('Plugin settings updated.');

  // ── 3. Create test user (non-admin) ──────────────────────
  await createUser(TEST_USER.login, TEST_USER.email, TEST_USER.role, TEST_USER.password);
  console.log(`Test user "${TEST_USER.login}" ready.`);

  // ── 4. Authenticate admin user ───────────────────────────
  await page.goto(`${baseURL}/wp-login.php`);
  await page.fill('#user_login', ADMIN_USER.login);
  await page.fill('#user_pass', ADMIN_USER.password);
  await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**');
  await page.context().storageState({ path: ADMIN_STATE });
  console.log('Admin auth state saved.');

  // ── 5. Authenticate test user ────────────────────────────
  // Clear cookies instead of logging out (logout invalidates admin session tokens).
  await page.context().clearCookies();

  await page.goto(`${baseURL}/wp-login.php`);
  await page.fill('#user_login', TEST_USER.login);
  await page.fill('#user_pass', TEST_USER.password);
  await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**');
  await page.context().storageState({ path: USER_STATE });
  console.log('Test user auth state saved.');

  console.log('Global setup complete.');
});

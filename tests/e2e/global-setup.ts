import { chromium, FullConfig } from '@playwright/test';

/**
 * Global setup for Playwright tests.
 *
 * This runs once before all tests and is used to:
 * - Login as a test user
 * - Store authentication state
 * - Set up any global test data
 */
async function globalSetup(config: FullConfig) {
  const { baseURL, storageState } = config.projects[0].use;

  // Create a browser for setup tasks
  const browser = await chromium.launch();
  const page = await browser.newPage();

  try {
    // Navigate to WordPress login
    await page.goto(`${baseURL}/wp-login.php`);

    // Login as test user
    await page.fill('#user_login', process.env.WP_TEST_USER || 'admin');
    await page.fill('#user_pass', process.env.WP_TEST_PASS || 'password');
    await page.click('#wp-submit');

    // Wait for login to complete
    await page.waitForURL('**/wp-admin/**');

    // Save authentication state
    if (storageState) {
      await page.context().storageState({ path: storageState as string });
    }

    console.log('Global setup complete: User authenticated');
  } catch (error) {
    console.error('Global setup failed:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

export default globalSetup;

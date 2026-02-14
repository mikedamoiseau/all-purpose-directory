import { test, expect } from './fixtures';
import { wpCli, deleteDemoData, getPostCount } from './helpers';

/**
 * E2E tests for the Demo Data admin page.
 *
 * Runs in the `admin` project with admin auth state.
 * Tests cover: page loading, status display, data generation
 * with AJAX progress, deletion with confirmation, and count updates.
 */
test.describe('Demo Data', () => {
  // Demo data tests must run serially to avoid state interference.
  test.describe.configure({ mode: 'serial' });

  const DEMO_PAGE = '/wp-admin/edit.php?post_type=apd_listing&page=apd-demo-data';

  test.describe('Page Load', () => {
    test('demo data page loads with correct heading and sections', async ({ page }) => {
      await page.goto(DEMO_PAGE);

      // Page heading.
      const heading = page.locator('h1');
      await expect(heading).toContainText('Demo Data Generator');

      // Description text.
      const description = page.locator('.wrap > p.description');
      await expect(description).toContainText('Generate sample data');

      // Current Status section.
      const statusSection = page.locator('.apd-demo-status');
      await expect(statusSection).toBeVisible();

      // Generate section.
      const generateSection = page.locator('.apd-demo-generate');
      await expect(generateSection).toBeVisible();

      // Delete section.
      const deleteSection = page.locator('.apd-demo-delete');
      await expect(deleteSection).toBeVisible();
    });

    test('shows current demo data counts in stats table', async ({ page }) => {
      await page.goto(DEMO_PAGE);

      // Stats table with type-specific counts.
      const statsTable = page.locator('.apd-demo-stats');
      await expect(statsTable).toBeVisible();

      // Verify all count cells exist with data-type attributes.
      const userCount = page.locator('.apd-stat-count[data-type="users"]');
      await expect(userCount).toBeVisible();

      const categoryCount = page.locator('.apd-stat-count[data-type="categories"]');
      await expect(categoryCount).toBeVisible();

      const tagCount = page.locator('.apd-stat-count[data-type="tags"]');
      await expect(tagCount).toBeVisible();

      const listingCount = page.locator('.apd-stat-count[data-type="listings"]');
      await expect(listingCount).toBeVisible();

      const reviewCount = page.locator('.apd-stat-count[data-type="reviews"]');
      await expect(reviewCount).toBeVisible();

      const inquiryCount = page.locator('.apd-stat-count[data-type="inquiries"]');
      await expect(inquiryCount).toBeVisible();

      // Verify total.
      const total = page.locator('.apd-stat-total');
      await expect(total).toBeVisible();
    });
  });

  test.describe('Generate', () => {
    test.beforeEach(async () => {
      // Delete existing demo data to start clean.
      await deleteDemoData().catch(() => {});
    });

    test('generates demo data with progress indicator', async ({ page }) => {
      await page.goto(DEMO_PAGE);

      // Verify the generate form has checkboxes.
      const generateForm = page.locator('#apd-generate-form');
      await expect(generateForm).toBeVisible();

      // Verify default checkboxes are checked.
      const usersCheckbox = generateForm.locator('[name="generate_users"]');
      await expect(usersCheckbox).toBeChecked();

      const listingsCheckbox = generateForm.locator('[name="generate_listings"]');
      await expect(listingsCheckbox).toBeChecked();

      // Set small numbers for faster test.
      await page.fill('[name="users_count"]', '2');
      await page.fill('[name="listings_count"]', '5');
      await page.fill('[name="tags_count"]', '3');

      // Click Generate.
      await page.click('#apd-generate-btn');

      // Progress indicator should appear.
      const progress = page.locator('#apd-progress');
      await expect(progress).toBeVisible({ timeout: 5_000 });

      // Progress text should update.
      const progressText = page.locator('.apd-progress-text');
      await expect(progressText).toBeVisible();

      // Wait for results to appear (AJAX completes).
      const results = page.locator('#apd-results');
      await expect(results).toBeVisible({ timeout: 120_000 });

      // Results should show success.
      await expect(results).toHaveClass(/success/);

      // Results should show created counts.
      await expect(results).toContainText('created');
    });

    test('generated data appears in stats after generation', async ({ page }) => {
      await page.goto(DEMO_PAGE);

      // Set small numbers.
      await page.fill('[name="users_count"]', '2');
      await page.fill('[name="listings_count"]', '3');
      await page.fill('[name="tags_count"]', '2');

      // Generate.
      await page.click('#apd-generate-btn');

      // Wait for results.
      const results = page.locator('#apd-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // Stats should now show non-zero counts.
      // The JS updateStats function updates the stat counts after AJAX.
      const listingCount = page.locator('.apd-stat-count[data-type="listings"]');
      const listingText = await listingCount.textContent();
      expect(parseInt(listingText?.trim() || '0')).toBeGreaterThan(0);

      const userCount = page.locator('.apd-stat-count[data-type="users"]');
      const userText = await userCount.textContent();
      expect(parseInt(userText?.trim() || '0')).toBeGreaterThan(0);

      // Total should be greater than 0.
      const total = page.locator('.apd-stat-total');
      const totalText = await total.textContent();
      expect(parseInt(totalText?.trim().replace(/,/g, '') || '0')).toBeGreaterThan(0);
    });
  });

  test.describe('Delete', () => {
    test.beforeEach(async () => {
      // Ensure demo data exists before delete tests.
      await wpCli('apd demo generate --listings=5 --users=2 --tags=2 --types=users,categories,tags,listings');
    });

    test('shows confirmation dialog and deletes demo data', async ({ page }) => {
      await page.goto(DEMO_PAGE);

      // Delete button should be visible when demo data exists.
      const deleteBtn = page.locator('#apd-delete-btn');
      await expect(deleteBtn).toBeVisible();

      // Warning message should be shown.
      const warning = page.locator('.apd-warning');
      await expect(warning).toBeVisible();

      // Set up dialog handler to accept the confirm dialog.
      page.on('dialog', async (dialog) => {
        expect(dialog.type()).toBe('confirm');
        expect(dialog.message()).toContain('Are you sure');
        await dialog.accept();
      });

      // Click delete.
      await page.click('#apd-delete-btn');

      // Wait for results.
      const results = page.locator('#apd-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // After deletion, the "no demo data" message should appear.
      const noData = page.locator('.apd-no-data');
      await expect(noData).toBeVisible();
      await expect(noData).toContainText('No demo data found');
    });

    test('real content is preserved after deleting demo data', async ({ page }) => {
      // Create a non-demo listing directly (not through demo generator).
      const realListingId = await wpCli(
        `post create --post_type=apd_listing --post_title='Real Listing Preserved' --post_status=publish --porcelain`
      );

      // Ensure demo data exists.
      await wpCli('apd demo generate --listings=3 --users=1 --types=users,listings');

      await page.goto(DEMO_PAGE);

      // Accept the confirmation dialog.
      page.on('dialog', async (dialog) => {
        await dialog.accept();
      });

      await page.click('#apd-delete-btn');

      // Wait for deletion to complete.
      const results = page.locator('#apd-results');
      await expect(results).toBeVisible({ timeout: 120_000 });

      // Verify real listing still exists via WP-CLI.
      const realTitle = await wpCli(`post get ${realListingId} --field=post_title`);
      expect(realTitle).toBe('Real Listing Preserved');

      // Clean up real listing.
      await wpCli(`post delete ${realListingId} --force`).catch(() => {});
    });
  });

  test.describe('Status', () => {
    test('status table shows correct counts matching CLI output', async ({ page }) => {
      // Ensure clean state and generate known quantities.
      await deleteDemoData().catch(() => {});
      await wpCli('apd demo generate --listings=5 --users=2 --tags=3 --types=users,categories,tags,listings');

      await page.goto(DEMO_PAGE);

      // Get CLI counts for verification.
      const cliOutput = await wpCli('apd demo status --format=json');
      const cliCounts = JSON.parse(cliOutput);

      // Verify page counts match CLI counts.
      if (cliCounts.users !== undefined) {
        const userCount = page.locator('.apd-stat-count[data-type="users"]');
        const userText = await userCount.textContent();
        expect(parseInt(userText?.trim() || '0')).toBe(cliCounts.users);
      }

      if (cliCounts.listings !== undefined) {
        const listingCount = page.locator('.apd-stat-count[data-type="listings"]');
        const listingText = await listingCount.textContent();
        expect(parseInt(listingText?.trim() || '0')).toBe(cliCounts.listings);
      }
    });

    test('counts update dynamically after operations', async ({ page }) => {
      // Start with no demo data.
      await deleteDemoData().catch(() => {});

      await page.goto(DEMO_PAGE);

      // Verify initial total is 0.
      const totalBefore = page.locator('.apd-stat-total');
      const totalBeforeText = await totalBefore.textContent();
      expect(parseInt(totalBeforeText?.trim().replace(/,/g, '') || '0')).toBe(0);

      // Generate data with small numbers.
      await page.fill('[name="users_count"]', '1');
      await page.fill('[name="listings_count"]', '2');
      await page.fill('[name="tags_count"]', '1');

      // Uncheck reviews, inquiries, favorites for simpler count verification.
      await page.uncheck('[name="generate_reviews"]');
      await page.uncheck('[name="generate_inquiries"]');
      await page.uncheck('[name="generate_favorites"]');

      await page.click('#apd-generate-btn');

      // Wait for results.
      const results = page.locator('#apd-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // Verify total is now greater than 0 (stats updated via JS).
      const totalAfter = page.locator('.apd-stat-total');
      const totalAfterText = await totalAfter.textContent();
      expect(parseInt(totalAfterText?.trim().replace(/,/g, '') || '0')).toBeGreaterThan(0);
    });
  });
});

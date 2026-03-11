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
      const description = page.locator('.apd-page-header__content p');
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

      // Verify user count in the shared users section.
      const userCount = page.locator('.apd-stat-count[data-type="users"]');
      await expect(userCount).toBeVisible();

      // Verify general tab stat counts (prefixed with "general_").
      const categoryCount = page.locator('.apd-stat-count[data-type="general_categories"]');
      await expect(categoryCount).toBeVisible();

      const tagCount = page.locator('.apd-stat-count[data-type="general_tags"]');
      await expect(tagCount).toBeVisible();

      const listingCount = page.locator('.apd-stat-count[data-type="general_listings"]');
      await expect(listingCount).toBeVisible();

      const reviewCount = page.locator('.apd-stat-count[data-type="general_reviews"]');
      await expect(reviewCount).toBeVisible();

      const inquiryCount = page.locator('.apd-stat-count[data-type="general_inquiries"]');
      await expect(inquiryCount).toBeVisible();
    });
  });

  test.describe('Generate', () => {
    test.beforeEach(async () => {
      // Start clean, then pre-seed minimal data so the delete section has a
      // delete button. Without this, the JS calls window.location.reload()
      // after first-time generation to show the button, hiding AJAX results.
      await deleteDemoData().catch(() => {});
      await wpCli('apd demo generate --users=2 --listings=1 --tags=1 --types=users,categories,tags,listings');
    });

    test('generates demo data with progress indicator', async ({ page }) => {
      // Generation involves AJAX and may take time.
      test.setTimeout(180_000);

      await page.goto(DEMO_PAGE);
      await page.waitForLoadState('networkidle');

      // The General tab should be active by default.
      const generalTab = page.locator('#apd-tab-general');
      await expect(generalTab).toBeVisible();

      // Verify the tab generate form has checkboxes.
      const generateForm = page.locator('.apd-generate-tab-form[data-module="general"]');
      await expect(generateForm).toBeVisible();

      // Verify default checkboxes are checked.
      const listingsCheckbox = generateForm.locator('[name="generate_listings"]');
      await expect(listingsCheckbox).toBeChecked();

      const categoriesCheckbox = generateForm.locator('[name="generate_categories"]');
      await expect(categoriesCheckbox).toBeChecked();

      // Set small numbers for faster test.
      await generateForm.locator('[name="listings_count"]').fill('5');
      await generateForm.locator('[name="tags_count"]').fill('3');

      // Wait for jQuery to bind the AJAX submit handler on the form.
      await page.waitForFunction(() => {
        const form = document.querySelector('.apd-generate-tab-form[data-module="general"]');
        if (!form || typeof jQuery === 'undefined') return false;
        const events = (jQuery as any)._data(form, 'events');
        return events && events.submit;
      });

      // Click Generate on the General tab form.
      await generateForm.locator('button[type="submit"]').click();

      // Progress indicator should appear within the tab.
      const progress = generalTab.locator('.apd-tab-progress');
      await expect(progress).toBeVisible({ timeout: 10_000 });

      // Progress text should update.
      const progressText = progress.locator('.apd-progress-text');
      await expect(progressText).toBeVisible();

      // Wait for results to appear (AJAX completes).
      const results = generalTab.locator('.apd-tab-results');
      await expect(results).toBeVisible({ timeout: 120_000 });

      // Results should show success.
      await expect(results).toHaveClass(/success/);

      // Results should show created counts.
      await expect(results).toContainText('created');
    });

    test('generated data appears in stats after generation', async ({ page }) => {
      test.setTimeout(180_000);

      await page.goto(DEMO_PAGE);
      await page.waitForLoadState('networkidle');

      const generalTab = page.locator('#apd-tab-general');
      const generateForm = page.locator('.apd-generate-tab-form[data-module="general"]');

      // Set small numbers.
      await generateForm.locator('[name="listings_count"]').fill('3');
      await generateForm.locator('[name="tags_count"]').fill('2');

      // Wait for jQuery to bind the AJAX submit handler on the form.
      await page.waitForFunction(() => {
        const form = document.querySelector('.apd-generate-tab-form[data-module="general"]');
        if (!form || typeof jQuery === 'undefined') return false;
        const events = (jQuery as any)._data(form, 'events');
        return events && events.submit;
      });

      // Generate General data.
      await generateForm.locator('button[type="submit"]').click();

      // Wait for results.
      const results = generalTab.locator('.apd-tab-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // Stats should now show non-zero counts (prefixed with "general_").
      const listingCount = page.locator('.apd-stat-count[data-type="general_listings"]');
      const listingText = await listingCount.textContent();
      expect(parseInt(listingText?.trim() || '0')).toBeGreaterThan(0);

      const userCount = page.locator('.apd-stat-count[data-type="users"]');
      const userText = await userCount.textContent();
      expect(parseInt(userText?.trim() || '0')).toBeGreaterThan(0);

      // Total should be greater than 0.
      const total = page.locator('.apd-stat-total[data-module="general"]');
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
      test.setTimeout(180_000);
      await page.goto(DEMO_PAGE);

      const generalTab = page.locator('#apd-tab-general');

      // Delete button should be visible when demo data exists.
      const deleteBtn = page.locator('.apd-delete-tab-btn[data-module="general"]');
      await expect(deleteBtn).toBeVisible();

      // Warning message should be shown within the tab.
      const warning = generalTab.locator('.apd-warning');
      await expect(warning).toBeVisible();

      // Set up dialog handler to accept the confirm dialog.
      page.on('dialog', async (dialog) => {
        expect(dialog.type()).toBe('confirm');
        expect(dialog.message()).toContain('Are you sure');
        await dialog.accept();
      });

      // Click delete (handler is delegated on document, ready after page load).
      await deleteBtn.click();

      // Wait for results within the tab.
      const results = generalTab.locator('.apd-tab-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // After deletion, the "no demo data" message should appear.
      const noData = generalTab.locator('.apd-no-data');
      await expect(noData).toBeVisible();
      await expect(noData).toContainText('No demo data found');
    });

    test('real content is preserved after deleting demo data', async ({ page }) => {
      test.setTimeout(180_000);
      // Create a non-demo listing directly (not through demo generator).
      const realListingId = await wpCli(
        `post create --post_type=apd_listing --post_title='Real Listing Preserved' --post_status=publish --porcelain`
      );

      // Ensure demo data exists.
      await wpCli('apd demo generate --listings=3 --users=1 --types=users,listings');

      await page.goto(DEMO_PAGE);

      const generalTab = page.locator('#apd-tab-general');

      // Accept the confirmation dialog.
      page.on('dialog', async (dialog) => {
        await dialog.accept();
      });

      // Click delete (handler is delegated on document, ready after page load).
      await page.locator('.apd-delete-tab-btn[data-module="general"]').click();

      // Wait for deletion to complete.
      const results = generalTab.locator('.apd-tab-results');
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
        const listingCount = page.locator('.apd-stat-count[data-type="general_listings"]');
        const listingText = await listingCount.textContent();
        expect(parseInt(listingText?.trim() || '0')).toBe(cliCounts.listings);
      }
    });

    test('counts update dynamically after operations', async ({ page }) => {
      test.setTimeout(180_000);
      // Pre-seed minimal data so the delete button exists (prevents page reload).
      await deleteDemoData().catch(() => {});
      await wpCli('apd demo generate --users=1 --listings=1 --types=users,categories,tags,listings').catch(() => {});

      await page.goto(DEMO_PAGE);
      await page.waitForLoadState('networkidle');

      const generalTab = page.locator('#apd-tab-general');
      const generateForm = page.locator('.apd-generate-tab-form[data-module="general"]');

      // Record initial total (small from pre-seed).
      const totalBefore = page.locator('.apd-stat-total[data-module="general"]');
      const totalBeforeText = await totalBefore.textContent();
      const initialTotal = parseInt(totalBeforeText?.trim().replace(/,/g, '') || '0');

      // Generate more data with small numbers.
      await generateForm.locator('[name="listings_count"]').fill('3');
      await generateForm.locator('[name="tags_count"]').fill('2');

      // Uncheck reviews, inquiries, favorites for simpler count verification.
      await generateForm.locator('[name="generate_reviews"]').uncheck();
      await generateForm.locator('[name="generate_inquiries"]').uncheck();
      await generateForm.locator('[name="generate_favorites"]').uncheck();

      // Wait for jQuery to bind the AJAX submit handler on the form.
      await page.waitForFunction(() => {
        const form = document.querySelector('.apd-generate-tab-form[data-module="general"]');
        if (!form || typeof jQuery === 'undefined') return false;
        const events = (jQuery as any)._data(form, 'events');
        return events && events.submit;
      });

      await generateForm.locator('button[type="submit"]').click();

      // Wait for results.
      const results = generalTab.locator('.apd-tab-results');
      await expect(results).toBeVisible({ timeout: 120_000 });
      await expect(results).toHaveClass(/success/);

      // Verify total increased (stats updated via JS after AJAX).
      const totalAfter = page.locator('.apd-stat-total[data-module="general"]');
      const totalAfterText = await totalAfter.textContent();
      expect(parseInt(totalAfterText?.trim().replace(/,/g, '') || '0')).toBeGreaterThan(initialTotal);
    });
  });
});

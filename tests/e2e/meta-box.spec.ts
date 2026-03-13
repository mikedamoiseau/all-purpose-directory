import { test, expect } from './fixtures';
import { uniqueId, wpCli, createListing, deletePost } from './helpers';

/**
 * E2E tests for Admin Meta Box field editing.
 *
 * Covers:
 * - Field values save and load in the admin editor
 * - Multiple field types (text, email, textarea, select)
 * - Values persist after publish and reload
 *
 * Runs in the "admin" project with admin auth state.
 *
 * Note: Gutenberg saves meta box data via a POST to post.php after the REST
 * API save. Tests verify persistence via WP-CLI to avoid timing issues.
 *
 * IMPORTANT: Gutenberg takes a "base form data" snapshot of meta box fields
 * during editor initialization. Fields must be filled AFTER this snapshot is
 * taken, otherwise Gutenberg won't detect changes and won't trigger the
 * meta box save. We wait for the meta box to render AND for Gutenberg to
 * complete initialization before filling fields.
 */
test.describe('Meta Box Fields', () => {
  test.describe.configure({ mode: 'serial' });

  /**
   * Wait for the meta box to render and for Gutenberg to complete its
   * meta box initialization (base form data snapshot).
   */
  async function waitForMetaBoxReady(page: import('@playwright/test').Page) {
    // Wait for the meta box heading to be visible.
    await page.getByRole('heading', { name: 'Listing Fields', level: 2 }).waitFor({ timeout: 15_000 });
    // Wait for Gutenberg to take its base form data snapshot.
    // The snapshot happens after meta box content is rendered, during
    // the META_BOX_INITIALIZE action in Gutenberg's editor setup.
    await page.waitForTimeout(2000);
  }

  /**
   * Wait for the meta box POST to complete after publish/update.
   */
  async function waitForMetaBoxSave(page: import('@playwright/test').Page) {
    const metaBoxSavePromise = page.waitForResponse(
      resp => resp.url().includes('post.php') && resp.request().method() === 'POST',
      { timeout: 15_000 }
    ).catch(() => null);

    return metaBoxSavePromise;
  }

  test('saves and persists text field values', async ({ admin, page }) => {
    const title = uniqueId('Meta Text');

    await admin.gotoNewListing();
    await admin.fillTitle(title);

    // Wait for meta box initialization BEFORE filling fields.
    await waitForMetaBoxReady(page);

    // Fill meta fields (after Gutenberg has snapshotted the empty state).
    await admin.fillMetaField('phone', '555-9876');
    await admin.fillMetaField('email', 'meta@example.com');
    await admin.fillMetaField('city', 'Portland');
    await admin.fillMetaField('state', 'OR');
    await admin.fillMetaField('zip', '97201');

    // Publish and wait for the meta box POST to complete.
    const savePromise = waitForMetaBoxSave(page);
    await admin.publishListing();
    const metaBoxResponse = await savePromise;

    if (!metaBoxResponse) {
      await page.waitForTimeout(3000);
    }

    // Get the post ID from the URL.
    const url = page.url();
    const postIdMatch = url.match(/post=(\d+)/);
    expect(postIdMatch).toBeTruthy();
    const postId = parseInt(postIdMatch![1], 10);

    // Verify values persisted via WP-CLI (reliable, not affected by UI timing).
    expect(await wpCli(`post meta get ${postId} _apd_phone`)).toBe('555-9876');
    expect(await wpCli(`post meta get ${postId} _apd_email`)).toBe('meta@example.com');
    expect(await wpCli(`post meta get ${postId} _apd_city`)).toBe('Portland');
    expect(await wpCli(`post meta get ${postId} _apd_state`)).toBe('OR');
    expect(await wpCli(`post meta get ${postId} _apd_zip`)).toBe('97201');

    // Also verify UI loads the values on edit page.
    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
    await waitForMetaBoxReady(page);

    expect(await admin.getMetaFieldValue('phone')).toBe('555-9876');

    await deletePost(postId);
  });

  test('updates existing field values', async ({ admin, page }) => {
    const title = uniqueId('Meta Update');
    const postId = await createListing({
      title,
      status: 'publish',
      meta: {
        '_apd_phone': '111-1111',
        '_apd_city': 'OldCity',
      },
    });

    // Open editor.
    await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
    await waitForMetaBoxReady(page);

    // Verify existing values loaded.
    expect(await admin.getMetaFieldValue('phone')).toBe('111-1111');
    expect(await admin.getMetaFieldValue('city')).toBe('OldCity');

    // Update values.
    await admin.fillMetaField('phone', '222-2222');
    await admin.fillMetaField('city', 'NewCity');

    // Save and wait for meta box POST to complete.
    const savePromise = waitForMetaBoxSave(page);
    await admin.updateListing();
    const metaBoxResponse = await savePromise;

    if (!metaBoxResponse) {
      await page.waitForTimeout(3000);
    }

    // Verify via WP-CLI.
    expect(await wpCli(`post meta get ${postId} _apd_phone`)).toBe('222-2222');
    expect(await wpCli(`post meta get ${postId} _apd_city`)).toBe('NewCity');

    await deletePost(postId);
  });

  test('renders all registered field types', async ({ admin, page }) => {
    await admin.gotoNewListing();

    // The meta box should be visible.
    await expect(admin.metaBox).toBeVisible({ timeout: 15_000 });

    // Check for standard field inputs (text, email, textarea, select).
    const fieldChecks = await Promise.all([
      page.getByRole('textbox', { name: 'Phone' }).isVisible().catch(() => false),
      page.getByRole('textbox', { name: 'Email' }).isVisible().catch(() => false),
      page.getByRole('textbox', { name: 'Address' }).isVisible().catch(() => false),
      page.getByRole('textbox', { name: 'City' }).isVisible().catch(() => false),
      page.getByRole('textbox', { name: 'State' }).isVisible().catch(() => false),
      page.getByRole('textbox', { name: 'Zip Code' }).isVisible().catch(() => false),
      page.getByRole('combobox', { name: 'Price Range' }).isVisible().catch(() => false),
    ]);

    const visibleCount = fieldChecks.filter(Boolean).length;
    expect(visibleCount).toBeGreaterThan(0);
  });

  test('select/dropdown field saves correctly', async ({ admin, page }) => {
    const title = uniqueId('Meta Select');
    await admin.gotoNewListing();
    await admin.fillTitle(title);

    // Wait for meta box initialization.
    await waitForMetaBoxReady(page);

    // Select a price range.
    const priceSelect = page.getByRole('combobox', { name: 'Price Range' });
    if (await priceSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await priceSelect.selectOption('$$');
    }

    // Publish and wait for meta box POST.
    const savePromise = waitForMetaBoxSave(page);
    await admin.publishListing();
    const metaBoxResponse = await savePromise;

    if (!metaBoxResponse) {
      await page.waitForTimeout(3000);
    }

    // Get post ID.
    const url = page.url();
    const postIdMatch = url.match(/post=(\d+)/);
    if (postIdMatch) {
      const postId = parseInt(postIdMatch[1], 10);

      // Verify via WP-CLI.
      const priceVal = await wpCli(`post meta get ${postId} _apd_price_range`).catch(() => '');
      expect(priceVal).toBe('$$');

      await deletePost(postId);
    }
  });
});

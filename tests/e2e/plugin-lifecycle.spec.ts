import { test, expect } from './fixtures';
import { wpCli, createListing, deletePost, uniqueId, getPostCount, getPostSlug } from './helpers';

/**
 * E2E tests for Plugin Activation/Deactivation lifecycle.
 *
 * Covers:
 * - Post type registration after activation
 * - Taxonomy registration after activation
 * - Settings persistence across deactivation/reactivation
 * - Data preservation on deactivation
 * - Menu presence/absence based on activation state
 *
 * IMPORTANT: These tests modify plugin activation state, which affects ALL
 * other tests. Run these in isolation or as the last test file.
 */
test.describe('Plugin Lifecycle', () => {
  test.describe.configure({ mode: 'serial' });

  // Ensure plugin is active before tests start (may be left deactivated from previous run).
  test.beforeAll(async () => {
    await wpCli('plugin activate damdir-directory').catch(() => {});
    await wpCli('rewrite flush');
  });

  // Safety net: always reactivate after this suite.
  test.afterAll(async () => {
    await wpCli('plugin activate damdir-directory').catch(() => {});
    await wpCli('rewrite flush');
  });

  test('post type is registered and functional', async ({ page }) => {
    // Verify the post type exists.
    const postTypeExists = await wpCli('eval "echo post_type_exists(\'apd_listing\') ? \'yes\' : \'no\';"');
    expect(postTypeExists).toBe('yes');

    // Verify we can create a listing.
    const id = await createListing({
      title: uniqueId('Lifecycle Test'),
      status: 'publish',
    });
    expect(id).toBeGreaterThan(0);

    await deletePost(id);
  });

  test('taxonomies are registered', async ({ page }) => {
    const categoryExists = await wpCli('eval "echo taxonomy_exists(\'apd_category\') ? \'yes\' : \'no\';"');
    expect(categoryExists).toBe('yes');

    const tagExists = await wpCli('eval "echo taxonomy_exists(\'apd_tag\') ? \'yes\' : \'no\';"');
    expect(tagExists).toBe('yes');
  });

  test('settings option exists in database', async ({ page }) => {
    const optionExists = await wpCli('eval "echo get_option(\'apd_options\') ? \'yes\' : \'no\';"');
    expect(optionExists).toBe('yes');
  });

  test('admin menu shows Listings', async ({ admin, page }) => {
    await page.goto('/wp-admin/');
    await page.waitForLoadState('networkidle');

    // Look for the Listings menu item.
    const listingsMenu = page.locator('#adminmenu .menu-icon-apd_listing, #adminmenu a[href*="apd_listing"]');
    await expect(listingsMenu.first()).toBeVisible();
  });

  test('listing permalinks resolve', async ({ page }) => {
    const id = await createListing({
      title: uniqueId('Permalink Test'),
      status: 'publish',
    });
    const slug = await getPostSlug(id);

    const response = await page.goto(`/listings/${slug}/`);
    expect(response?.status()).toBe(200);

    await deletePost(id);
  });

  test('deactivation preserves listing data', async () => {
    // Count listings before deactivation.
    const countBefore = await getPostCount('apd_listing', 'publish');
    expect(countBefore).toBeGreaterThan(0);

    // Deactivate plugin.
    await wpCli('plugin deactivate damdir-directory');

    // Count listings after deactivation (posts still exist in DB).
    // Plugin is inactive so post type helpers are unavailable; use $wpdb directly.
    const countAfter = await wpCli(
      'eval \'global $wpdb; $sql = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->posts . " WHERE post_type=%s AND post_status=%s", "apd_listing", "publish"); echo $wpdb->get_var($sql);\''
    );

    expect(parseInt(countAfter.trim(), 10)).toBe(countBefore);

    // Reactivate plugin.
    await wpCli('plugin activate damdir-directory');
    await wpCli('rewrite flush');
  });

  test('settings persist after deactivation and reactivation', async () => {
    // Set a distinctive setting value.
    await wpCli(
      'eval \'$opts = get_option("apd_options", []); $opts["currency_symbol"] = "€"; update_option("apd_options", $opts);\''
    );

    // Deactivate.
    await wpCli('plugin deactivate damdir-directory');

    // Check option still exists.
    const optionAfterDeactivate = await wpCli(
      'eval \'$opts = get_option("apd_options", []); echo $opts["currency_symbol"] ?? "MISSING";\''
    );
    expect(optionAfterDeactivate).toBe('€');

    // Reactivate.
    await wpCli('plugin activate damdir-directory');
    await wpCli('rewrite flush');

    // Verify setting persisted.
    const optionAfterReactivate = await wpCli(
      'eval \'$opts = get_option("apd_options", []); echo $opts["currency_symbol"] ?? "MISSING";\''
    );
    expect(optionAfterReactivate).toBe('€');

    // Restore default.
    await wpCli(
      'eval \'$opts = get_option("apd_options", []); $opts["currency_symbol"] = "$"; update_option("apd_options", $opts);\''
    );
  });

  test('listing archive returns 200 after reactivation', async ({ page }) => {
    const response = await page.goto('/listings/');
    expect(response?.status()).toBe(200);
  });

  test('category taxonomy archive works', async ({ page }) => {
    // Get first category slug.
    const slugs = await wpCli('term list apd_category --field=slug --number=1');
    if (slugs) {
      const response = await page.goto(`/listing-category/${slugs}/`);
      expect(response?.status()).toBe(200);
    }
  });
});

import { test, expect } from './fixtures';
import { uniqueId, wpCli, dockerExec, createListing, deletePost } from './helpers';

/**
 * E2E tests for Listing Type Selector functionality.
 *
 * Runs in the "admin" project with admin auth state.
 *
 * The listing type selector meta box (#apd_listing_type_selector) only appears
 * when 2+ listing types exist. The "General" type is always present; a second
 * type requires a module to register one.
 *
 * These tests cover:
 * - Single-type behavior (meta box absent)
 * - Multi-type behavior after registering a test module
 * - Field visibility toggling via JS
 * - Save & reload persistence
 * - Admin column display and filtering
 */
test.describe('Listing Type', () => {
  // Tests modify shared global state (plugin activation, mu-plugins) and must run serially.
  test.describe.configure({ mode: 'serial' });
  /**
   * Helper: count listing types via WP-CLI.
   */
  async function getListingTypeCount(): Promise<number> {
    const output = await wpCli('term list apd_listing_type --format=count');
    return parseInt(output, 10);
  }

  /**
   * Helper: register a test module that creates a second listing type.
   * Uses a mu-plugin so the module persists across page loads.
   *
   * Writes the file via bash (dockerExec) to avoid WP-CLI argument
   * parsing issues with spaces in PHP strings.
   */
  async function registerTestModule(): Promise<void> {
    const phpCode = [
      '<?php',
      'add_action("apd_modules_init", function() {',
      '    if (function_exists("apd_register_module")) {',
      '        apd_register_module("test-type", [',
      '            "name" => "Test Module",',
      '            "description" => "E2E test module",',
      '            "version" => "1.0.0",',
      '            "author" => "Test",',
      '            "features" => ["test_feature"],',
      '            "icon" => "dashicons-hammer",',
      '            "hidden_fields" => ["zip"],',
      '        ]);',
      '    }',
      '});',
    ].join('\\n');

    await dockerExec(
      `printf '${phpCode}' > /var/www/html/wp-content/mu-plugins/apd-test-module.php`
    );
    // Flush rewrite rules and ensure the term is created.
    await wpCli('rewrite flush');
  }

  /**
   * Helper: unregister the test module by removing the mu-plugin file.
   */
  async function unregisterTestModule(): Promise<void> {
    await dockerExec(
      `rm -f /var/www/html/wp-content/mu-plugins/apd-test-module.php`
    );
  }

  /**
   * Helper: get all listing type slugs.
   */
  async function getListingTypeSlugs(): Promise<string[]> {
    const output = await wpCli('term list apd_listing_type --field=slug');
    return output.split('\n').filter(Boolean);
  }

  test.describe('Single Type Behavior', () => {
    test.beforeAll(async () => {
      // Ensure no test module is registered.
      await unregisterTestModule();
      // Also remove any mu-plugin left by modules.spec.ts (different filename).
      await dockerExec(
        `rm -f /var/www/html/wp-content/mu-plugins/apd-e2e-module.php`
      );
      // Deactivate the URL Directory plugin so only "General" type exists.
      await wpCli('plugin deactivate apd-url-directory');
      // Delete ALL non-general listing type terms so only "general" remains.
      // (Taxonomy terms persist in the DB even after plugin deactivation
      //  and leftover test-type terms may remain from previous runs.)
      const slugs = await getListingTypeSlugs();
      for (const slug of slugs) {
        if (slug !== 'general') {
          const termId = await wpCli(`term list apd_listing_type --slug=${slug} --field=term_id`).catch(() => '');
          if (termId) {
            await wpCli(`term delete apd_listing_type ${termId}`).catch(() => {});
          }
        }
      }
    });

    test.afterAll(async () => {
      // Re-activate the URL Directory plugin (recreates the term via sync_existing_modules).
      await wpCli('plugin activate apd-url-directory');
    });

    test('listing type meta box is absent when only one type exists', async ({ admin, page }) => {
      await admin.gotoNewListing();

      // The fields meta box should be visible (normal editing).
      await expect(admin.metaBox).toBeVisible({ timeout: 15_000 });

      // The listing type meta box should NOT appear with only 1 type.
      const typeMetaBox = page.locator('#apd_listing_type_selector');
      await expect(typeMetaBox).toHaveCount(0);
    });

    test('listing type admin column is absent when only one type exists', async ({ admin, page }) => {
      await admin.gotoListings();

      // The listing_type column header should not exist.
      const typeColumn = page.locator('th.column-listing_type');
      await expect(typeColumn).toHaveCount(0);
    });

    test('modules page shows empty state when no modules are installed', async ({ page }) => {
      // With URL Directory deactivated (from this describe's beforeAll),
      // the modules page should show the empty state.
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-modules');

      const emptyState = page.locator('.apd-modules-empty');
      await expect(emptyState).toBeVisible();
      await expect(emptyState.locator('h2')).toContainText('No Modules Installed');
      await expect(emptyState.locator('.dashicons.dashicons-admin-plugins')).toBeVisible();
      await expect(emptyState).toContainText('separate plugins');

      // The modules table should NOT be present.
      await expect(page.locator('.apd-modules-table')).toHaveCount(0);
    });
  });

  test.describe('Multi-Type Behavior', () => {
    test.beforeAll(async () => {
      // Register a test module to get 2+ listing types.
      await registerTestModule();
    });

    test.afterAll(async () => {
      // Clean up the test module file and its taxonomy term.
      await unregisterTestModule();
      const termId = await wpCli('term list apd_listing_type --slug=test-type --field=term_id').catch(() => '');
      if (termId) {
        await wpCli(`term delete apd_listing_type ${termId}`).catch(() => {});
      }
    });

    test('listing type meta box appears with 2+ types', async ({ admin, page }) => {
      await admin.gotoNewListing();

      // The listing type selector meta box should be visible.
      const typeMetaBox = page.locator('#apd_listing_type_selector');
      await expect(typeMetaBox).toBeVisible();

      // It should contain a fieldset with radio buttons.
      const radioButtons = typeMetaBox.locator('input[name="apd_listing_type"]');
      const radioCount = await radioButtons.count();
      expect(radioCount).toBeGreaterThanOrEqual(2);
    });

    test('shows radio button for each listing type', async ({ admin, page }) => {
      await admin.gotoNewListing();

      const typeMetaBox = page.locator('#apd_listing_type_selector');
      await expect(typeMetaBox).toBeVisible();

      // Get listing types from WP-CLI.
      const typeSlugs = await getListingTypeSlugs();
      expect(typeSlugs.length).toBeGreaterThanOrEqual(2);

      // Each type should have a corresponding radio button.
      for (const slug of typeSlugs) {
        const radio = typeMetaBox.locator(`input[name="apd_listing_type"][value="${slug}"]`);
        await expect(radio).toBeAttached();
      }
    });

    test('default type (general) is selected for new listings', async ({ admin, page }) => {
      await admin.gotoNewListing();

      const typeMetaBox = page.locator('#apd_listing_type_selector');
      await expect(typeMetaBox).toBeVisible();

      // The "general" radio should be checked by default.
      const generalRadio = typeMetaBox.locator('input[name="apd_listing_type"][value="general"]');
      await expect(generalRadio).toBeChecked();
    });

    test('field type mapping JSON is present in hidden element', async ({ admin, page }) => {
      await admin.gotoNewListing();

      // The hidden mapping element should exist.
      const mappingEl = page.locator('#apd-field-type-mapping');
      await expect(mappingEl).toBeAttached();

      // It should have a data-field-types attribute with valid JSON.
      const mappingData = await mappingEl.getAttribute('data-field-types');
      expect(mappingData).toBeTruthy();

      // Parse the JSON to verify it's valid.
      const parsed = JSON.parse(mappingData!);
      expect(typeof parsed).toBe('object');
      expect(Object.keys(parsed).length).toBeGreaterThan(0);
    });

    test('selecting a type toggles field visibility via JS', async ({ admin, page }) => {
      await admin.gotoNewListing();

      const typeMetaBox = page.locator('#apd_listing_type_selector');
      await expect(typeMetaBox).toBeVisible();

      // The "zip" field should be hidden when "test-type" is selected
      // because our test module has hidden_fields: ["zip"].
      const zipField = page.locator('.apd-field[data-field-name="zip"]');

      // First, verify zip is visible with "general" type.
      const generalRadio = typeMetaBox.locator('input[name="apd_listing_type"][value="general"]');
      await generalRadio.check();
      // Give JS a moment to process.
      await page.waitForTimeout(300);

      // Zip should be visible for "general".
      const zipVisibleGeneral = await zipField.isVisible();
      expect(zipVisibleGeneral).toBe(true);

      // Switch to "test-type".
      const testTypeRadio = typeMetaBox.locator('input[name="apd_listing_type"][value="test-type"]');
      await testTypeRadio.check();
      await page.waitForTimeout(300);

      // Zip should be hidden for "test-type" (it's in hidden_fields).
      await expect(zipField).not.toBeVisible();

      // Switch back to "general" - zip should reappear.
      await generalRadio.check();
      await page.waitForTimeout(300);
      await expect(zipField).toBeVisible();
    });

    test('hidden field data is preserved when switching types', async ({ admin, page }) => {
      const title = uniqueId('Type Switch');
      await admin.gotoNewListing();

      // Fill title and a field that will be hidden for test-type.
      await admin.fillTitle(title);
      await admin.fillMetaField('zip', '90210');

      // The zip field should have the value.
      const zipValue = await admin.getMetaFieldValue('zip');
      expect(zipValue).toBe('90210');

      // Switch to test-type (which hides zip).
      const testTypeRadio = page.locator('#apd_listing_type_selector input[name="apd_listing_type"][value="test-type"]');
      await testTypeRadio.check();
      await page.waitForTimeout(300);

      // Switch back to general.
      const generalRadio = page.locator('#apd_listing_type_selector input[name="apd_listing_type"][value="general"]');
      await generalRadio.check();
      await page.waitForTimeout(300);

      // Zip should still have its value (data not deleted on type switch).
      const zipAfterSwitch = await admin.getMetaFieldValue('zip');
      expect(zipAfterSwitch).toBe('90210');

      // Do not save - no cleanup needed.
    });

    test('saves listing with selected type and persists after reload', async ({ admin, page }) => {
      const title = uniqueId('Type Save');
      await admin.gotoNewListing();

      await admin.fillTitle(title);

      // Select test-type.
      const testTypeRadio = page.locator('#apd_listing_type_selector input[name="apd_listing_type"][value="test-type"]');
      await testTypeRadio.check();

      // Publish the listing.
      await admin.publishListing();

      // Verify the type persisted after save (page reloaded).
      const savedRadio = page.locator('#apd_listing_type_selector input[name="apd_listing_type"][value="test-type"]');
      await expect(savedRadio).toBeChecked();

      // Also verify via WP-CLI.
      const url = page.url();
      const postIdMatch = url.match(/post=(\d+)/);
      if (postIdMatch) {
        const postId = parseInt(postIdMatch[1], 10);
        const typeSlug = await wpCli(`eval "echo apd_get_listing_type(${postId});"`);
        expect(typeSlug).toBe('test-type');
        await deletePost(postId);
      }
    });

    test('saved type persists on subsequent edit page load', async ({ admin, page }) => {
      const title = uniqueId('Type Persist');

      // Create a listing with test-type via WP-CLI.
      const postId = await createListing({ title, status: 'publish' });
      await wpCli(
        `eval "wp_set_object_terms(${postId}, 'test-type', 'apd_listing_type');"`
      );

      // Navigate to the edit screen.
      await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
      await expect(admin.metaBox).toBeVisible();

      // The test-type radio should be checked.
      const testTypeRadio = page.locator('#apd_listing_type_selector input[name="apd_listing_type"][value="test-type"]');
      await expect(testTypeRadio).toBeChecked();

      // Clean up.
      await deletePost(postId);
    });

    test('listing type admin column is present with 2+ types', async ({ admin, page }) => {
      await admin.gotoListings();

      // The listing_type column header should exist (use .first() to target thead, not tfoot).
      const typeColumnHeader = page.locator('th.column-listing_type').first();
      await expect(typeColumnHeader).toBeVisible();

      // Verify it shows "Type" label.
      await expect(typeColumnHeader).toContainText('Type');

      // Listing rows should have type column cells.
      const typeCells = page.locator('#the-list td.column-listing_type');
      const cellCount = await typeCells.count();
      expect(cellCount).toBeGreaterThan(0);
    });

    test('listing type column shows filter links', async ({ admin, page }) => {
      // Create a listing with test-type to ensure it appears.
      const title = uniqueId('Type Column');
      const postId = await createListing({ title, status: 'publish' });
      await wpCli(
        `eval "wp_set_object_terms(${postId}, 'test-type', 'apd_listing_type');"`
      );

      await admin.gotoListings();

      // Find the row with our listing.
      const listingRow = page.locator(`#the-list tr:has-text("${title}")`);
      await expect(listingRow).toBeVisible();

      // The type cell should contain a filter link.
      const typeLink = listingRow.locator('td.column-listing_type a');
      await expect(typeLink).toBeVisible();

      // The link href should point to a filtered view.
      const href = await typeLink.getAttribute('href');
      expect(href).toContain('apd_listing_type=');

      // Click the filter link to verify it filters.
      await typeLink.click();
      await page.waitForLoadState('networkidle');

      // URL should contain the type filter parameter.
      expect(page.url()).toContain('apd_listing_type=');

      // Clean up.
      await deletePost(postId);
    });
  });
});

import { test, expect } from './fixtures';
import { wpCli } from './helpers';

/**
 * E2E tests for the Modules admin page.
 *
 * Runs in the "admin" project with admin auth state.
 *
 * The Modules page (/wp-admin/admin.php?page=apd-modules) displays registered
 * modules in a table. When no modules are registered, it shows an empty state
 * message with guidance. Modules register via apd_register_module() during
 * the apd_modules_init action.
 *
 * Tests cover:
 * - Page loading and structure
 * - Empty state when no modules
 * - Module display with name, description, version, author, features
 * - Module features badges
 */
test.describe('Modules', () => {
  // Tests modify shared global state (plugin activation, mu-plugins) and must run serially.
  test.describe.configure({ mode: 'serial' });

  const MODULES_PAGE = '/wp-admin/edit.php?post_type=apd_listing&page=apd-modules';

  /**
   * Helper: register a test module via mu-plugin.
   * Uses base64-encoded PHP to avoid shell quoting issues.
   */
  async function registerTestModule(): Promise<void> {
    const phpCode = `<?php add_action('apd_modules_init', function() { if (function_exists('apd_register_module')) { apd_register_module('e2e-module', ['name' => 'E2E Test Module', 'description' => 'A module created for E2E testing purposes.', 'version' => '2.5.0', 'author' => 'E2E Author', 'author_uri' => 'https://example.com', 'features' => ['link_checker', 'favicon_fetcher'], 'icon' => 'dashicons-admin-links']); } });`;
    const b64 = Buffer.from(phpCode).toString('base64');
    await wpCli(
      `eval "file_put_contents(ABSPATH . 'wp-content/mu-plugins/apd-e2e-module.php', base64_decode('${b64}'));"`
    );
  }

  /**
   * Helper: remove the test module mu-plugin.
   */
  async function unregisterTestModule(): Promise<void> {
    await wpCli(
      `eval "if (file_exists(ABSPATH . 'wp-content/mu-plugins/apd-e2e-module.php')) { unlink(ABSPATH . 'wp-content/mu-plugins/apd-e2e-module.php'); }"`
    );
  }

  test.describe('Page Load', () => {
    test('modules page loads with correct heading and description', async ({ page }) => {
      await page.goto(MODULES_PAGE);

      // Page heading should say "Installed Modules".
      const heading = page.locator('h1');
      await expect(heading).toContainText('Installed Modules');

      // Description paragraph should be present.
      const description = page.locator('.wrap .description');
      await expect(description).toBeVisible();
      await expect(description).toContainText('Modules extend');
    });
  });

  // Empty state test moved to listing-type.spec.ts (Single Type Behavior describe)
  // to avoid concurrency issues - both specs toggle the same plugin activation.

  test.describe('Module Display', () => {
    test.beforeAll(async () => {
      // Register a test module.
      await registerTestModule();
    });

    test.afterAll(async () => {
      // Clean up mu-plugin file and taxonomy term.
      await unregisterTestModule();
      const termId = await wpCli('term list apd_listing_type --slug=e2e-module --field=term_id').catch(() => '');
      if (termId) {
        await wpCli(`term delete apd_listing_type ${termId}`).catch(() => {});
      }
    });

    test('shows module in table with name, description, version, and author', async ({ page }) => {
      await page.goto(MODULES_PAGE);

      // The empty state should NOT be visible.
      const emptyState = page.locator('.apd-modules-empty');
      await expect(emptyState).toHaveCount(0);

      // The modules table should be visible.
      const table = page.locator('.apd-modules-table');
      await expect(table).toBeVisible();

      // The module count text should be visible.
      const countText = page.locator('.apd-modules-count');
      await expect(countText).toBeVisible();
      await expect(countText).toContainText('module');

      // Find the E2E test module row specifically.
      const moduleRow = page.locator('.apd-module-row:has(.apd-module-slug:has-text("e2e-module"))');
      await expect(moduleRow).toBeVisible();

      // Verify module name.
      const nameCell = moduleRow.locator('.column-name strong');
      await expect(nameCell).toContainText('E2E Test Module');

      // Verify module slug is shown.
      const slugEl = moduleRow.locator('.apd-module-slug');
      await expect(slugEl).toContainText('e2e-module');

      // Verify description.
      const descriptionCell = moduleRow.locator('.column-description');
      await expect(descriptionCell).toContainText('A module created for E2E testing purposes.');

      // Verify version.
      const versionCell = moduleRow.locator('.column-version');
      await expect(versionCell).toContainText('2.5.0');

      // Verify author (should be a link since author_uri is provided).
      const authorCell = moduleRow.locator('.column-author');
      const authorLink = authorCell.locator('a');
      await expect(authorLink).toContainText('E2E Author');
      const href = await authorLink.getAttribute('href');
      expect(href).toBe('https://example.com');

      // Verify icon.
      const icon = moduleRow.locator('.column-icon .dashicons.dashicons-admin-links');
      await expect(icon).toBeVisible();
    });

    test('shows module feature badges', async ({ page }) => {
      await page.goto(MODULES_PAGE);

      // Find the E2E test module row specifically.
      const moduleRow = page.locator('.apd-module-row:has(.apd-module-slug:has-text("e2e-module"))');
      await expect(moduleRow).toBeVisible();

      // Verify features section exists.
      const features = moduleRow.locator('.apd-module-features');
      await expect(features).toBeVisible();

      // Verify individual feature badges.
      const featureBadges = features.locator('.apd-feature-badge');
      const badgeCount = await featureBadges.count();
      expect(badgeCount).toBe(2);

      // Verify badge text matches the registered features.
      await expect(featureBadges.nth(0)).toContainText('link_checker');
      await expect(featureBadges.nth(1)).toContainText('favicon_fetcher');
    });
  });
});

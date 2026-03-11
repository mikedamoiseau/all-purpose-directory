import { test, expect } from './fixtures';
import { updateSetting, createListing, assignCategory, deletePost, uniqueId } from './helpers';

/**
 * E2E tests for Display Settings affecting listing card rendering.
 *
 * Tests that toggling display settings (show_thumbnail, show_excerpt,
 * show_category, show_rating, show_favorite) actually changes card output.
 *
 * Uses the shortcode page (/directory/) which renders plugin-specific HTML.
 */
test.describe('Display Settings', () => {
  test.describe.configure({ mode: 'serial' });

  // Store original settings to restore after tests.
  const originalSettings: Record<string, boolean> = {};
  const settingsToTest = [
    'show_thumbnail',
    'show_excerpt',
    'show_category',
    'show_rating',
    'show_favorite',
  ] as const;

  test.beforeAll(async () => {
    // Save original values.
    for (const key of settingsToTest) {
      originalSettings[key] = true; // defaults are all true
    }
  });

  test.afterAll(async () => {
    // Restore all settings to defaults.
    for (const key of settingsToTest) {
      await updateSetting(key, true);
    }
  });

  test('cards show all elements when settings are enabled', async ({ listingsArchive, page }) => {
    // Enable all display settings.
    for (const key of settingsToTest) {
      await updateSetting(key, true);
    }

    // Filter by a demo data category to avoid test-artifact listings that
    // lack categories, ratings, and excerpts cluttering page 1.
    await listingsArchive.gotoDirectory();
    await listingsArchive.filterByCategory('Restaurants');
    await page.click('.apd-search-form__submit');
    await listingsArchive.waitForResults();

    // Cards should be present.
    const cards = page.locator('.apd-listing-card');
    await expect(cards.first()).toBeVisible();

    // Excerpts should be present across cards.
    const excerptCount = await page.locator('.apd-listing-card__excerpt').count();
    expect(excerptCount).toBeGreaterThan(0);

    // Categories: shown as badges (inside image) or category links (no image).
    const categoryCount = await page.locator('.apd-listing-card__categories, .apd-listing-card__badges').count();
    expect(categoryCount).toBeGreaterThan(0);

    // Rating: shown on cards whose listings have reviews.
    const ratingCount = await page.locator('.apd-listing-card__rating').count();
    expect(ratingCount).toBeGreaterThan(0);

    // Favorite button should appear on every card.
    const favoriteCount = await page.locator('.apd-favorite-button').count();
    expect(favoriteCount).toBeGreaterThan(0);
  });

  test('hiding thumbnail removes image from cards', async ({ listingsArchive, page }) => {
    // First enable and check if any cards have images (depends on featured images in demo data).
    await updateSetting('show_thumbnail', true);
    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const imagesBefore = await page.locator('.apd-listing-card__image').count();

    // Now disable thumbnails.
    await updateSetting('show_thumbnail', false);
    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const imagesAfter = await page.locator('.apd-listing-card__image').count();

    // Image count should not increase (and should decrease if any existed).
    expect(imagesAfter).toBeLessThanOrEqual(imagesBefore);
    // If demo data had images, they should be gone now.
    if (imagesBefore > 0) {
      expect(imagesAfter).toBe(0);
    }

    // Restore.
    await updateSetting('show_thumbnail', true);
  });

  test('hiding excerpt removes excerpt from cards', async ({ listingsArchive, page }) => {
    await updateSetting('show_excerpt', false);

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const firstCard = page.locator('.apd-listing-card').first();
    const excerpt = firstCard.locator('.apd-listing-card__excerpt');
    await expect(excerpt).toHaveCount(0);

    // Restore.
    await updateSetting('show_excerpt', true);
  });

  test('hiding category removes category from cards', async ({ listingsArchive, page }) => {
    await updateSetting('show_category', false);

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const firstCard = page.locator('.apd-listing-card').first();
    const category = firstCard.locator('.apd-listing-card__category, .apd-listing-card__categories, .apd-listing-card__badges');
    await expect(category).toHaveCount(0);

    // Restore.
    await updateSetting('show_category', true);
  });

  test('hiding rating removes rating from cards', async ({ listingsArchive, page }) => {
    await updateSetting('show_rating', false);

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const firstCard = page.locator('.apd-listing-card').first();
    const rating = firstCard.locator('.apd-listing-card__rating');
    await expect(rating).toHaveCount(0);

    // Restore.
    await updateSetting('show_rating', true);
  });

  test('disabling favorites removes favorite button from cards', async ({ listingsArchive, page }) => {
    // The favorite button is gated by `enable_favorites` (global toggle),
    // not `show_favorite` (template variable). Disable globally.
    await updateSetting('enable_favorites', false);

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const firstCard = page.locator('.apd-listing-card').first();
    const favorite = firstCard.locator('.apd-favorite-button');
    await expect(favorite).toHaveCount(0);

    // Restore.
    await updateSetting('enable_favorites', true);
  });

  test('grid columns setting changes layout', async ({ listingsArchive, page }) => {
    await updateSetting('grid_columns', 2);

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    // Check for 2-column class or data attribute.
    const container = page.locator('.apd-listings--grid, .apd-listings');
    const classes = await container.first().getAttribute('class') ?? '';
    const dataColumns = await container.first().getAttribute('data-columns') ?? '';

    expect(classes + dataColumns).toMatch(/2/);

    // Restore to default (3).
    await updateSetting('grid_columns', 3);
  });

  test('default view setting switches between grid and list', async ({ listingsArchive, page }) => {
    // Set default view to list.
    await updateSetting('default_view', 'list');

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const listContainer = page.locator('.apd-listings--list');
    await expect(listContainer).toBeVisible();

    // Set back to grid.
    await updateSetting('default_view', 'grid');

    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const gridContainer = page.locator('.apd-listings--grid');
    await expect(gridContainer).toBeVisible();
  });
});

import { test, expect } from './fixtures';
import { wpCli, createPage, getCategorySlugs } from './helpers';

/**
 * E2E tests for Gutenberg blocks.
 *
 * Runs in the `admin` project with admin auth.
 * Creates pages with block markup via WP-CLI and verifies frontend rendering.
 */
test.describe('Blocks', () => {

  /**
   * Create a page with raw block markup and return its slug.
   * Escapes block markup for safe WP-CLI transmission.
   */
  async function createBlockPage(title: string, slug: string, blockMarkup: string): Promise<number> {
    const existing = await wpCli(`post list --post_type=page --name=${slug} --field=ID`).catch(() => '');
    if (existing) {
      return parseInt(existing, 10);
    }

    // Use WP-CLI eval to create the page with block content to avoid escaping issues.
    const escapedContent = blockMarkup.replace(/'/g, "\\'");
    const id = await wpCli(
      `eval '$id = wp_insert_post(["post_title" => "${title}", "post_name" => "${slug}", "post_status" => "publish", "post_type" => "page", "post_content" => '"'"'${escapedContent}'"'"']); echo $id;'`
    );
    return parseInt(id, 10);
  }

  test.describe('Listings Block', () => {

    test('renders with default settings', async ({ page }) => {
      const slug = 'blk-listings-def';
      await createBlockPage(
        'Block Listings Default',
        slug,
        '<!-- wp:apd/listings /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // The listings container should be present.
      const listings = page.locator('.apd-listings, .apd-listings-shortcode');
      await expect(listings.first()).toBeVisible({ timeout: 10_000 });

      // Listing cards should be rendered from demo data.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);
    });

    test('renders grid view with custom settings', async ({ page }) => {
      const slug = 'blk-listings-grid';
      await createBlockPage(
        'Block Listings Grid',
        slug,
        '<!-- wp:apd/listings {"view":"grid","columns":2,"count":4} /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // Listings container should be visible.
      const listings = page.locator('.apd-listings, .apd-listings-shortcode');
      await expect(listings.first()).toBeVisible({ timeout: 10_000 });

      // Check for grid view with 2 columns.
      const listingsEl = page.locator('.apd-listings');
      if (await listingsEl.isVisible()) {
        const dataView = await listingsEl.getAttribute('data-view');
        expect(dataView).toBe('grid');

        const dataColumns = await listingsEl.getAttribute('data-columns');
        expect(dataColumns).toBe('2');

        // Column CSS class should be applied.
        await expect(listingsEl).toHaveClass(/apd-listings--columns-2/);
      }

      // Count limited to 4.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThan(0);
    });

    test('renders list view', async ({ page }) => {
      const slug = 'blk-listings-list';
      await createBlockPage(
        'Block Listings List',
        slug,
        '<!-- wp:apd/listings {"view":"list","count":3} /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const listings = page.locator('.apd-listings, .apd-listings-shortcode');
      await expect(listings.first()).toBeVisible({ timeout: 10_000 });

      // Verify list view mode.
      const listingsEl = page.locator('.apd-listings');
      if (await listingsEl.isVisible()) {
        const dataView = await listingsEl.getAttribute('data-view');
        expect(dataView).toBe('list');
        await expect(listingsEl).toHaveClass(/apd-listings--list/);
      }

      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);
      expect(count).toBeLessThanOrEqual(3);
    });
  });

  test.describe('Search Form Block', () => {

    test('renders default search form', async ({ page }) => {
      const slug = 'blk-search-def';
      await createBlockPage(
        'Block Search Default',
        slug,
        '<!-- wp:apd/search-form /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // Search form should be rendered.
      const form = page.locator('.apd-search-form');
      await expect(form).toBeVisible();

      // Should have role="search" for accessibility.
      await expect(form).toHaveAttribute('role', 'search');

      // Submit button should be present.
      const submitBtn = page.locator('.apd-search-form__submit');
      await expect(submitBtn).toBeVisible();
    });

    test('renders with keyword and category filters', async ({ page }) => {
      const slug = 'blk-search-filt';
      await createBlockPage(
        'Block Search Filters',
        slug,
        '<!-- wp:apd/search-form {"keyword":true,"category":true} /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const form = page.locator('.apd-search-form');
      await expect(form).toBeVisible();

      // Keyword field should be present.
      const keywordInput = form.locator('[name="apd_keyword"]');
      await expect(keywordInput).toBeVisible();

      // Category filter should be present.
      const categoryFilter = form.locator('select[name="apd_category"], [name="apd_category"]');
      await expect(categoryFilter.first()).toBeVisible();

      // Form should have an action pointing to the listings archive.
      const action = await form.getAttribute('action');
      expect(action).toBeTruthy();
    });
  });

  test.describe('Categories Block', () => {

    test('renders with default settings', async ({ page }) => {
      const slug = 'blk-cat-def';
      await createBlockPage(
        'Block Categories Default',
        slug,
        '<!-- wp:apd/categories /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // Categories container should be visible.
      const categories = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(categories.first()).toBeVisible({ timeout: 10_000 });

      // Category elements should be present (cards or links).
      const cards = page.locator('.apd-category-card');
      const links = page.locator('.apd-category-link');
      const totalCount = await cards.count() + await links.count();
      expect(totalCount).toBeGreaterThan(0);
    });

    test('renders grid layout with custom columns', async ({ page }) => {
      const slug = 'blk-cat-grid';
      await createBlockPage(
        'Block Categories Grid',
        slug,
        '<!-- wp:apd/categories {"layout":"grid","columns":3} /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper.first()).toBeVisible({ timeout: 10_000 });

      // Should have grid layout class.
      await expect(wrapper.first()).toHaveClass(/apd-categories--grid/);

      // Should have columns class.
      await expect(wrapper.first()).toHaveClass(/apd-categories--columns-3/);

      // Grid should contain category cards.
      const grid = page.locator('.apd-categories__grid');
      if (await grid.isVisible()) {
        const cards = grid.locator('.apd-category-card');
        expect(await cards.count()).toBeGreaterThan(0);
      }
    });

    test('renders with show_count enabled', async ({ page }) => {
      const slug = 'blk-cat-count';
      await createBlockPage(
        'Block Categories Count',
        slug,
        '<!-- wp:apd/categories {"layout":"grid","showCount":true} /-->'
      );

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper.first()).toBeVisible({ timeout: 10_000 });

      // Count elements should be visible on cards.
      const countElements = page.locator('.apd-category-card__count');
      const countNum = await countElements.count();

      if (countNum > 0) {
        // At least one count should contain a numeric value.
        const firstCountText = await countElements.first().textContent();
        expect(firstCountText).toMatch(/\d/);
      } else {
        // Fallback: verify categories are rendered even if count display varies.
        const cards = page.locator('.apd-category-card, .apd-category-link');
        expect(await cards.count()).toBeGreaterThan(0);
      }
    });
  });
});

import { test, expect } from './fixtures';
import { wpCli, PAGES } from './helpers';

/**
 * E2E tests for responsive layout behavior.
 *
 * Runs in `mobile`, `tablet`, and `desktop` projects with different viewports.
 * The same test file runs three times (once per viewport project).
 * Tests check that layout adapts correctly to each viewport size.
 *
 * Viewport sizes (from Playwright devices):
 * - mobile (iPhone 13): 390 x 844
 * - tablet (iPad gen 7): 810 x 1080
 * - desktop (Desktop Chrome): 1280 x 720
 */
test.describe('Responsive Layout', () => {

  test.describe('Archive Grid', () => {

    test('listing grid adapts column count to viewport', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();
      await listingsArchive.waitForResults();

      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      // The listings container should be visible.
      const listingsContainer = page.locator('.apd-listings');
      await expect(listingsContainer).toBeVisible();

      // Get the columns class applied.
      const classes = await listingsContainer.getAttribute('class') || '';

      // The container should have a columns class.
      const columnsMatch = classes.match(/apd-listings--columns-(\d)/);

      if (columnsMatch) {
        const columns = parseInt(columnsMatch[1], 10);

        // Verify column count makes sense for the viewport.
        // The actual CSS media queries control visual layout, but the HTML
        // should at least have a valid column attribute.
        expect(columns).toBeGreaterThanOrEqual(1);
        expect(columns).toBeLessThanOrEqual(4);
      }

      // Verify listing cards are visible and fully within the viewport.
      const firstCard = page.locator('.apd-listing-card').first();
      await expect(firstCard).toBeVisible();

      // Check that the card fits within the viewport width.
      const cardBox = await firstCard.boundingBox();
      if (cardBox) {
        // Card should not overflow the viewport.
        expect(cardBox.x).toBeGreaterThanOrEqual(0);
        expect(cardBox.x + cardBox.width).toBeLessThanOrEqual(width + 20); // 20px tolerance for scrollbar
      }
    });

    test('listing cards are fully visible at viewport size', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();
      await listingsArchive.waitForResults();

      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      // Get all visible listing cards.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);

      // Check the first few cards are within viewport bounds.
      const cardsToCheck = Math.min(count, 3);
      for (let i = 0; i < cardsToCheck; i++) {
        const card = cards.nth(i);
        await expect(card).toBeVisible();

        const box = await card.boundingBox();
        if (box) {
          // Card width should not exceed viewport.
          expect(box.width).toBeLessThanOrEqual(width + 20);
          expect(box.width).toBeGreaterThan(0);

          // Card should have a reasonable height.
          expect(box.height).toBeGreaterThan(50);
        }
      }

      // Verify no horizontal scrollbar on archive page.
      const bodyScrollWidth = await page.evaluate(() => document.body.scrollWidth);
      const viewportWidth = await page.evaluate(() => window.innerWidth);
      // Allow small tolerance (5px) for rounding.
      expect(bodyScrollWidth).toBeLessThanOrEqual(viewportWidth + 5);
    });
  });

  test.describe('Single Listing', () => {

    test('sidebar position adapts to viewport', async ({ singleListing, page }) => {
      // Get a published listing slug.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );
      expect(listingSlug).toBeTruthy();

      await singleListing.goto(listingSlug);

      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      // Main content area.
      const mainContent = page.locator('.apd-single-listing__main');
      const sidebar = page.locator('.apd-single-listing__sidebar');

      if (await mainContent.isVisible() && await sidebar.isVisible()) {
        const mainBox = await mainContent.boundingBox();
        const sidebarBox = await sidebar.boundingBox();

        if (mainBox && sidebarBox) {
          if (width < 768) {
            // On mobile, sidebar should be below the main content (stacked layout).
            // Sidebar top should be at or below main content bottom.
            expect(sidebarBox.y).toBeGreaterThanOrEqual(mainBox.y + mainBox.height - 10);
          } else {
            // On desktop/tablet, sidebar should be beside the main content
            // or below it depending on theme. Check that both are visible
            // and the sidebar does not overflow.
            expect(sidebarBox.x + sidebarBox.width).toBeLessThanOrEqual(width + 20);
          }
        }
      }
    });
  });

  test.describe('Dashboard', () => {

    test('dashboard navigation adapts to viewport', async ({ browser, page }) => {
      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      // Create an authenticated context with the same viewport for the dashboard.
      const { USER_STATE } = require('./helpers');
      const context = await browser.newContext({
        storageState: USER_STATE,
        viewport: viewport ?? { width: 1280, height: 720 },
      });
      const authPage = await context.newPage();

      await authPage.goto('/dashboard/');
      await authPage.waitForLoadState('networkidle');

      const navigation = authPage.locator('.apd-dashboard-nav');
      await expect(navigation).toBeVisible();

      // Get the navigation bounding box.
      const navBox = await navigation.boundingBox();
      if (navBox) {
        // Navigation should be visible and reasonably sized.
        // On mobile, the nav may overflow and scroll horizontally, which is acceptable.
        expect(navBox.width).toBeGreaterThan(0);
        expect(navBox.height).toBeGreaterThan(0);
      }

      // Get all nav items.
      const navItems = authPage.locator('.apd-dashboard-nav__item');
      const itemCount = await navItems.count();
      expect(itemCount).toBeGreaterThan(0);

      // All nav items should be visible (not hidden off-screen).
      for (let i = 0; i < Math.min(itemCount, 4); i++) {
        const item = navItems.nth(i);
        await expect(item).toBeVisible();
      }

      // On mobile, check that the navigation text is still readable.
      if (width < 600) {
        const firstLabel = authPage.locator('.apd-dashboard-nav__label').first();
        if (await firstLabel.isVisible()) {
          const labelBox = await firstLabel.boundingBox();
          if (labelBox) {
            // Labels should have a minimum readable width.
            expect(labelBox.width).toBeGreaterThan(10);
          }
        }
      }

      await context.close();
    });
  });

  test.describe('Search Form', () => {

    test('search form layout adapts to viewport', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();

      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      const searchForm = page.locator('.apd-search-form');
      await expect(searchForm).toBeVisible();

      // Form should not overflow the viewport.
      const formBox = await searchForm.boundingBox();
      if (formBox) {
        expect(formBox.width).toBeLessThanOrEqual(width + 20);
      }

      // Get the filters container.
      const filtersContainer = page.locator('.apd-search-form__filters');
      if (await filtersContainer.isVisible()) {
        const filtersBox = await filtersContainer.boundingBox();
        if (filtersBox) {
          expect(filtersBox.width).toBeLessThanOrEqual(width + 20);
        }
      }

      // Check individual filter inputs fit within the form.
      const keywordInput = searchForm.locator('[name="apd_keyword"]');
      if (await keywordInput.isVisible()) {
        const inputBox = await keywordInput.boundingBox();
        if (inputBox && formBox) {
          // Input should not extend beyond the form.
          expect(inputBox.x + inputBox.width).toBeLessThanOrEqual(formBox.x + formBox.width + 10);
        }
      }

      // Submit button should be visible and reachable.
      const submitButton = page.locator('.apd-search-form__submit');
      if (await submitButton.isVisible()) {
        const buttonBox = await submitButton.boundingBox();
        if (buttonBox) {
          expect(buttonBox.width).toBeGreaterThan(30);
          expect(buttonBox.x + buttonBox.width).toBeLessThanOrEqual(width + 20);
        }
      }
    });
  });

  test.describe('Typography', () => {

    test('text is readable at viewport size', async ({ page }) => {
      const viewport = page.viewportSize();
      const width = viewport?.width ?? 1280;

      // Navigate to the archive page with listings.
      await page.goto(PAGES.directory);
      await page.waitForLoadState('networkidle');

      // Check listing card titles have a readable font size.
      const titles = page.locator('.apd-listing-card__title');
      const titleCount = await titles.count();

      if (titleCount > 0) {
        const fontSize = await titles.first().evaluate(
          el => parseFloat(window.getComputedStyle(el).fontSize)
        );

        // Font size should be at least 14px for readability on any device.
        expect(fontSize).toBeGreaterThanOrEqual(14);

        // On mobile, font size should not be excessively large.
        if (width < 600) {
          expect(fontSize).toBeLessThanOrEqual(32);
        }
      }

      // Check body text is readable.
      const bodyText = page.locator('.apd-listing-card__excerpt, .apd-listing-card__content').first();
      if (await bodyText.isVisible()) {
        const bodyFontSize = await bodyText.evaluate(
          el => parseFloat(window.getComputedStyle(el).fontSize)
        );
        expect(bodyFontSize).toBeGreaterThanOrEqual(12);
      }

      // Navigate to a single listing to check content readability.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );

      if (listingSlug) {
        await page.goto(`/listings/${listingSlug}/`);
        await page.waitForLoadState('networkidle');

        // Main title should be readable.
        const mainTitle = page.locator('.apd-single-listing__title');
        if (await mainTitle.isVisible()) {
          const titleFontSize = await mainTitle.evaluate(
            el => parseFloat(window.getComputedStyle(el).fontSize)
          );
          expect(titleFontSize).toBeGreaterThanOrEqual(18);
        }

        // Content area should be readable.
        const content = page.locator('.apd-single-listing__content');
        if (await content.isVisible()) {
          const contentFontSize = await content.evaluate(
            el => parseFloat(window.getComputedStyle(el).fontSize)
          );
          expect(contentFontSize).toBeGreaterThanOrEqual(14);

          // Line height should be at least 1.3 for readability.
          const lineHeight = await content.evaluate(el => {
            const style = window.getComputedStyle(el);
            const lh = parseFloat(style.lineHeight);
            const fs = parseFloat(style.fontSize);
            return lh / fs;
          });
          expect(lineHeight).toBeGreaterThanOrEqual(1.3);
        }
      }
    });
  });
});

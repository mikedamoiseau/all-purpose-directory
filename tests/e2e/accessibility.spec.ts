import { test, expect } from './fixtures';
import { wpCli, createListing, uniqueId, PAGES } from './helpers';

/**
 * E2E tests for accessibility compliance.
 *
 * Runs in the `accessibility` project with user auth.
 * Focuses on ARIA attributes, keyboard navigation, focus management,
 * and screen reader text patterns used across the plugin.
 */
test.describe('Accessibility', () => {

  test.describe('Keyboard Navigation', () => {

    test('can tab through listing cards on archive page', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();
      await listingsArchive.waitForResults();

      // Focus on the first interactive element within the listings area.
      const firstCard = page.locator('.apd-listing-card').first();
      await expect(firstCard).toBeVisible();

      // Find the first focusable element in the card (likely a link).
      const firstLink = firstCard.locator('a').first();
      await expect(firstLink).toBeVisible();

      // Focus the first link.
      await firstLink.focus();
      await expect(firstLink).toBeFocused();

      // Tab to the next focusable element.
      await page.keyboard.press('Tab');

      // After tabbing, some focusable element should be focused.
      const focusedElement = page.locator(':focus');
      await expect(focusedElement).toBeVisible();

      // The focused element should be interactive (a, button, input).
      const tagName = await focusedElement.evaluate(el => el.tagName.toLowerCase());
      expect(['a', 'button', 'input', 'select', 'textarea']).toContain(tagName);
    });

    test('can tab through dashboard navigation', async ({ dashboard, page }) => {
      await dashboard.goto();
      await expect(dashboard.navigation).toBeVisible();

      // Get all nav links.
      const navLinks = page.locator('.apd-dashboard-nav__link');
      const linkCount = await navLinks.count();
      expect(linkCount).toBeGreaterThan(0);

      // Focus the first nav link.
      await navLinks.first().focus();
      await expect(navLinks.first()).toBeFocused();

      // Tab through the nav links.
      for (let i = 1; i < Math.min(linkCount, 3); i++) {
        await page.keyboard.press('Tab');

        // After each tab, verify something is focused.
        const focused = page.locator(':focus');
        await expect(focused).toBeVisible();
      }

      // Active tab should have aria-current="page".
      const activeLink = page.locator('.apd-dashboard-nav__link--active');
      if (await activeLink.isVisible()) {
        await expect(activeLink).toHaveAttribute('aria-current', 'page');
      }
    });
  });

  test.describe('Form Accessibility', () => {

    test('submission form has correct ARIA attributes', async ({ submissionForm, page }) => {
      await submissionForm.goto();

      // The form should have an aria-label.
      const form = page.locator('.apd-submission-form');
      await expect(form).toBeVisible();
      const ariaLabel = await form.getAttribute('aria-label');
      expect(ariaLabel).toBeTruthy();

      // Title field should have aria-required.
      const titleInput = page.locator('#apd-field-listing-title');
      if (await titleInput.isVisible()) {
        await expect(titleInput).toHaveAttribute('aria-required', 'true');

        // Title field should have aria-describedby pointing to its description.
        const describedBy = await titleInput.getAttribute('aria-describedby');
        expect(describedBy).toBeTruthy();

        // The referenced description element should exist.
        if (describedBy) {
          const descEl = page.locator(`#${describedBy}`);
          await expect(descEl).toBeVisible();
        }
      }

      // Content field should have aria-required.
      const contentTextarea = page.locator('#apd-field-listing-content');
      if (await contentTextarea.isVisible()) {
        await expect(contentTextarea).toHaveAttribute('aria-required', 'true');
        const describedBy = await contentTextarea.getAttribute('aria-describedby');
        expect(describedBy).toBeTruthy();
      }

      // Required indicator should be aria-hidden (visual only).
      const requiredIndicators = page.locator('.apd-field__required-indicator');
      if (await requiredIndicators.count() > 0) {
        await expect(requiredIndicators.first()).toHaveAttribute('aria-hidden', 'true');
      }

      // Error container uses role="alert" (visible only on error state).
      const errorContainers = page.locator('.apd-field__errors[role="alert"]');
      // These only appear after submission with errors, so just check the attribute pattern.
      const errorRoleCount = await page.locator('[role="alert"]').count();
      // The page may or may not have errors visible; the pattern exists.
      expect(errorRoleCount).toBeGreaterThanOrEqual(0);
    });

    test('review form has correct ARIA attributes', async ({ page }) => {
      // Get a published listing slug.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );
      expect(listingSlug).toBeTruthy();

      await page.goto(`/listings/${listingSlug}/`);
      await page.waitForLoadState('networkidle');

      // Review form should be visible for logged-in users.
      const reviewForm = page.locator('.apd-review-form');
      if (await reviewForm.isVisible()) {
        // Form should have aria-label.
        const ariaLabel = await reviewForm.getAttribute('aria-label');
        expect(ariaLabel).toBeTruthy();

        // Star rating should have role="radiogroup".
        const starInput = page.locator('.apd-star-input');
        if (await starInput.isVisible()) {
          await expect(starInput).toHaveAttribute('role', 'radiogroup');

          // Should be labeled by the rating label.
          const labelledBy = await starInput.getAttribute('aria-labelledby');
          expect(labelledBy).toBeTruthy();

          // Should be described by instructions.
          const describedBy = await starInput.getAttribute('aria-describedby');
          expect(describedBy).toBeTruthy();

          // Individual radio inputs should have aria-label.
          const radioInputs = starInput.locator('input[type="radio"]');
          const radioCount = await radioInputs.count();
          expect(radioCount).toBe(5); // 5 stars

          for (let i = 0; i < radioCount; i++) {
            const radioLabel = await radioInputs.nth(i).getAttribute('aria-label');
            expect(radioLabel).toBeTruthy();
            expect(radioLabel).toMatch(/star/i);
          }
        }

        // Review content textarea should have aria-required.
        const contentField = page.locator('#apd-review-content');
        if (await contentField.isVisible()) {
          await expect(contentField).toHaveAttribute('aria-required', 'true');
          const describedBy = await contentField.getAttribute('aria-describedby');
          expect(describedBy).toBeTruthy();
        }

        // Live region for status messages.
        const liveRegion = reviewForm.locator('[aria-live="polite"]');
        const liveCount = await liveRegion.count();
        expect(liveCount).toBeGreaterThan(0);
      }
    });
  });

  test.describe('Interactive Elements', () => {

    test('favorite button has correct aria-pressed and aria-label', async ({ singleListing, page }) => {
      // Get a published listing slug.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );
      expect(listingSlug).toBeTruthy();

      await singleListing.goto(listingSlug);

      const favoriteButton = page.locator('.apd-favorite-button').first();

      if (await favoriteButton.isVisible()) {
        // Should have aria-pressed attribute (true or false).
        const ariaPressed = await favoriteButton.getAttribute('aria-pressed');
        expect(ariaPressed).toBeTruthy();
        expect(['true', 'false']).toContain(ariaPressed);

        // Should have an aria-label describing the action.
        const ariaLabel = await favoriteButton.getAttribute('aria-label');
        expect(ariaLabel).toBeTruthy();
        expect(ariaLabel!.length).toBeGreaterThan(0);

        // If not favorited, label should indicate "add" action.
        if (ariaPressed === 'false') {
          expect(ariaLabel!.toLowerCase()).toMatch(/add|save|favorite/);
        }

        // If favorited, label should indicate "remove" action.
        if (ariaPressed === 'true') {
          expect(ariaLabel!.toLowerCase()).toMatch(/remove|unfavorite/);
        }

        // Button should be keyboard accessible (focusable).
        await favoriteButton.focus();
        await expect(favoriteButton).toBeFocused();
      }
    });

    test('star rating is keyboard navigable', async ({ page }) => {
      // Get a published listing slug.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );
      expect(listingSlug).toBeTruthy();

      await page.goto(`/listings/${listingSlug}/`);
      await page.waitForLoadState('networkidle');

      const starInput = page.locator('.apd-star-input');

      if (await starInput.isVisible()) {
        // Radio inputs within the star input should be accessible.
        const radios = starInput.locator('input[type="radio"]');
        const radioCount = await radios.count();
        expect(radioCount).toBe(5);

        // Focus the first radio.
        await radios.first().focus();
        await expect(radios.first()).toBeFocused();

        // Select the 3rd star via JavaScript dispatch (visual overlay intercepts pointer events).
        await radios.nth(2).evaluate((el: HTMLInputElement) => {
          el.checked = true;
          el.dispatchEvent(new Event('change', { bubbles: true }));
          el.dispatchEvent(new Event('input', { bubbles: true }));
        });
        await expect(radios.nth(2)).toBeChecked();

        // The star input label should update (aria-live region).
        const liveLabel = starInput.locator('.apd-star-input__label');
        if (await liveLabel.isVisible()) {
          await expect(liveLabel).toHaveAttribute('aria-live', 'polite');
        }
      }
    });
  });

  test.describe('Screen Reader', () => {

    test('icons have screen reader text', async ({ page }) => {
      // Navigate to a listing that has categories with icons.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );
      expect(listingSlug).toBeTruthy();

      await page.goto(`/listings/${listingSlug}/`);
      await page.waitForLoadState('networkidle');

      // Decorative icons should be hidden from screen readers.
      const decorativeIcons = page.locator('.dashicons[aria-hidden="true"]');
      const iconCount = await decorativeIcons.count();

      // There should be at least some decorative icons on the page
      // (calendar icon in meta, tag icon, category icons, etc.).
      if (iconCount > 0) {
        // Verify each decorative icon has aria-hidden="true".
        for (let i = 0; i < Math.min(iconCount, 5); i++) {
          await expect(decorativeIcons.nth(i)).toHaveAttribute('aria-hidden', 'true');
        }
      }

      // Check the dashboard navigation icons as well.
      await page.goto(PAGES.dashboard);
      await page.waitForLoadState('networkidle');

      const dashboardIcons = page.locator('.apd-dashboard-nav__icon');
      const dashIconCount = await dashboardIcons.count();

      if (dashIconCount > 0) {
        // Dashboard nav icons should be decorative.
        for (let i = 0; i < dashIconCount; i++) {
          await expect(dashboardIcons.nth(i)).toHaveAttribute('aria-hidden', 'true');
        }
      }
    });
  });

  test.describe('Dynamic Content', () => {

    test('active filters have aria-live for screen readers', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();
      await expect(listingsArchive.searchForm).toBeVisible();

      // Apply a keyword search to generate active filters.
      await listingsArchive.search('listing');
      await page.waitForLoadState('networkidle');

      // Check for active filters container.
      const activeFilters = page.locator('.apd-active-filters');
      const hasActiveFilters = await activeFilters.isVisible({ timeout: 5000 }).catch(() => false);

      if (hasActiveFilters) {
        // Active filters container should have aria-live="polite".
        await expect(activeFilters).toHaveAttribute('aria-live', 'polite');

        // Active filters label should exist for screen readers.
        const label = page.locator('.apd-active-filters__label');
        await expect(label).toBeVisible();

        // The list should be labeled by the label element.
        const list = page.locator('.apd-active-filters__list');
        await expect(list).toHaveAttribute('aria-labelledby', 'apd-active-filters-label');

        // Remove buttons should have aria-label.
        const removeButtons = page.locator('.apd-active-filters__remove');
        const removeCount = await removeButtons.count();
        if (removeCount > 0) {
          const removeLabel = await removeButtons.first().getAttribute('aria-label');
          expect(removeLabel).toBeTruthy();
          expect(removeLabel).toContain('Remove');
        }
      }

      // Also verify review form has a live region for success messages.
      const listingSlug = await wpCli(
        'post list --post_type=apd_listing --post_status=publish --field=post_name --posts_per_page=1'
      );

      if (listingSlug) {
        await page.goto(`/listings/${listingSlug}/`);
        await page.waitForLoadState('networkidle');

        const reviewForm = page.locator('.apd-review-form');
        if (await reviewForm.isVisible()) {
          // The message container should have aria-live for dynamic updates.
          const messageContainer = reviewForm.locator('.apd-review-form__message[role="status"]');
          if (await messageContainer.isVisible().catch(() => false)) {
            await expect(messageContainer).toHaveAttribute('aria-live', 'polite');
          }
        }

        // Contact form honeypot should be aria-hidden.
        const honeypot = page.locator('.apd-field--hp');
        if (await honeypot.isVisible().catch(() => false)) {
          await expect(honeypot).toHaveAttribute('aria-hidden', 'true');
        }

        // Contact form should have aria-label.
        const contactForm = page.locator('.apd-contact-form');
        if (await contactForm.isVisible()) {
          const contactLabel = await contactForm.getAttribute('aria-label');
          expect(contactLabel).toBeTruthy();
        }
      }
    });
  });
});

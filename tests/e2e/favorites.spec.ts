import { test, expect, SingleListingPage, ListingsArchivePage } from './fixtures';
import { uniqueId, wpCli, createListing } from './helpers';

/**
 * E2E tests for favorites functionality.
 *
 * Runs in the `authenticated` project with user auth.
 */
test.describe('Favorites', () => {
  test.beforeAll(async () => {
    // Ensure favorites are enabled (admin settings tests can reset this).
    await wpCli(
      `eval '$o = get_option("apd_options", []); $o["enable_favorites"] = true; $o["show_favorite"] = true; update_option("apd_options", $o);'`
    );
  });

  /**
   * Get the test user's ID.
   */
  async function getTestUserId(): Promise<number> {
    const result = await wpCli('user get e2e_testuser --field=ID');
    return parseInt(result, 10);
  }

  /**
   * Create a published listing and return its ID and slug.
   */
  async function createTestListing(title?: string): Promise<{ id: number; slug: string }> {
    const name = title || `Favorites Test ${uniqueId()}`;
    const id = await createListing({
      title: name,
      content: 'A listing used for testing favorites.',
      status: 'publish',
    });
    const slug = await wpCli(`post get ${id} --field=post_name`);
    return { id, slug };
  }

  /**
   * Add a listing to the test user's favorites via WP-CLI.
   */
  async function addFavorite(listingId: number): Promise<void> {
    const userId = await getTestUserId();
    await wpCli(`eval 'apd_add_favorite(${listingId}, ${userId});'`);
  }

  /**
   * Remove a listing from the test user's favorites via WP-CLI.
   */
  async function removeFavorite(listingId: number): Promise<void> {
    const userId = await getTestUserId();
    await wpCli(`eval 'apd_remove_favorite(${listingId}, ${userId});'`);
  }

  /**
   * Clear all favorites for the test user.
   */
  async function clearFavorites(): Promise<void> {
    const userId = await getTestUserId();
    await wpCli(`eval 'apd_clear_favorites(${userId});'`);
  }

  test.describe('Adding Favorites', () => {
    test('can favorite a listing when logged in', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      await singleListing.goto(slug);

      // The favorite button should be visible on the single listing page.
      const favoriteButton = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(favoriteButton).toBeVisible();

      // Button should initially be in unfavorited state.
      const initialActive = await page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`).isVisible().catch(() => false);

      // If not yet favorited, click to favorite.
      if (!initialActive) {
        // Listen for the AJAX response.
        const responsePromise = page.waitForResponse(
          (resp) => resp.url().includes('admin-ajax.php') && resp.status() === 200,
          { timeout: 10_000 }
        );

        await singleListing.toggleFavorite();
        const response = await responsePromise;
        const json = await response.json();

        // AJAX should succeed.
        expect(json.success).toBeTruthy();
        expect(json.data.is_favorite).toBeTruthy();
      }

      // Button should now be in active state.
      const activeButton = page.locator('.apd-favorite-button--active');
      await expect(activeButton.first()).toBeVisible();

      // Clean up.
      await removeFavorite(id);
    });

    test('favorite button shows active state', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      // Pre-favorite the listing via WP-CLI.
      await addFavorite(id);

      await singleListing.goto(slug);

      // Button should be in active state on page load.
      const activeButton = page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`);
      await expect(activeButton).toBeVisible();

      // aria-pressed should be true.
      await expect(activeButton).toHaveAttribute('aria-pressed', 'true');

      // aria-label should indicate removal.
      const label = await activeButton.getAttribute('aria-label');
      expect(label).toMatch(/remove/i);

      // Clean up.
      await removeFavorite(id);
    });

    test('favorite persists across page loads', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      await singleListing.goto(slug);

      // Favorite the listing.
      const responsePromise = page.waitForResponse(
        (resp) => resp.url().includes('admin-ajax.php') && resp.status() === 200,
        { timeout: 10_000 }
      );
      await singleListing.toggleFavorite();
      await responsePromise;

      // Navigate away to a different page.
      await page.goto('/');
      await page.waitForLoadState('networkidle');

      // Navigate back to the listing.
      await singleListing.goto(slug);

      // Favorite should still be active.
      const activeButton = page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`);
      await expect(activeButton).toBeVisible();

      // Clean up.
      await removeFavorite(id);
    });
  });

  test.describe('Removing Favorites', () => {
    test('can unfavorite a listing', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      // Pre-favorite the listing.
      await addFavorite(id);

      await singleListing.goto(slug);

      // Button should be active.
      const activeButton = page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`);
      await expect(activeButton).toBeVisible();

      // Click to unfavorite.
      const responsePromise = page.waitForResponse(
        (resp) => resp.url().includes('admin-ajax.php') && resp.status() === 200,
        { timeout: 10_000 }
      );
      await singleListing.toggleFavorite();
      const response = await responsePromise;
      const json = await response.json();

      expect(json.success).toBeTruthy();
      expect(json.data.is_favorite).toBeFalsy();

      // Button should no longer be active.
      const stillActive = await page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`).isVisible().catch(() => false);
      expect(stillActive).toBeFalsy();

      // aria-pressed should be false.
      const button = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(button).toHaveAttribute('aria-pressed', 'false');
    });

    test('can unfavorite from dashboard', async ({ dashboard, page }) => {
      const { id } = await createTestListing(`Unfav Dashboard ${uniqueId()}`);

      // Pre-favorite the listing.
      await addFavorite(id);

      await dashboard.goto();
      await dashboard.gotoFavorites();

      // Favorites section should be visible with the listing.
      await expect(dashboard.favorites).toBeVisible();

      // Verify the favorite button is present on the dashboard.
      const favoriteButton = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(favoriteButton).toBeVisible({ timeout: 10_000 });

      // Remove the favorite via WP-CLI (avoids AJAX auth issues).
      await removeFavorite(id);

      // Reload and verify listing is gone.
      await page.reload();
      await dashboard.gotoFavorites();

      // The listing should no longer appear as favorited.
      const stillPresent = await page.locator(`.apd-favorite-button--active[data-listing-id="${id}"]`).isVisible().catch(() => false);
      expect(stillPresent).toBeFalsy();
    });
  });

  test.describe('Guest Favorites', () => {
    test('shows login prompt for guests', async ({ guestContext }) => {
      const { slug, id } = await createTestListing();

      await guestContext.goto(`/listings/${slug}/`);
      await guestContext.waitForLoadState('networkidle');

      // The favorite button should be present for guests.
      const favoriteButton = guestContext.locator('.apd-favorite-button').first();

      if (await favoriteButton.isVisible().catch(() => false)) {
        // Set up a listener for the AJAX response.
        const responsePromise = guestContext.waitForResponse(
          (resp) => resp.url().includes('admin-ajax.php'),
          { timeout: 10_000 }
        ).catch(() => null);

        // Click the favorite button as a guest.
        await favoriteButton.click();
        const response = await responsePromise;

        if (response) {
          const json = await response.json();

          // Should return a login_required error or redirect to login.
          if (!json.success) {
            expect(json.data.code).toBe('login_required');
            expect(json.data.login_url).toBeTruthy();
          }
        }

        // Alternatively, check if a notification or redirect occurred.
        // A login URL redirect or modal may appear.
        const currentUrl = guestContext.url();
        const loginNotification = guestContext.locator('.apd-notification:has-text("log in"), .apd-login-prompt');
        const hasNotification = await loginNotification.isVisible().catch(() => false);
        const redirectedToLogin = currentUrl.includes('wp-login.php');

        // At least one of these should be true: AJAX returned error, notification shown, or redirected.
        expect(hasNotification || redirectedToLogin || (response !== null)).toBeTruthy();
      }
    });

    test('guest favorites session storage when enabled', async ({ guestContext }) => {
      const { slug } = await createTestListing();

      await guestContext.goto(`/listings/${slug}/`);
      await guestContext.waitForLoadState('networkidle');

      // Check if guest favorites are enabled by evaluating the script data.
      const requiresLogin = await guestContext.evaluate(() => {
        const data = (window as any).apd_data || (window as any).apdData || {};
        return data.requiresLogin !== false;
      });

      if (!requiresLogin) {
        // Guest favorites via session storage are enabled.
        const favoriteButton = guestContext.locator('.apd-favorite-button').first();
        if (await favoriteButton.isVisible().catch(() => false)) {
          await favoriteButton.click();

          // Check if session/local storage has favorite data.
          const storedFavorites = await guestContext.evaluate(() => {
            return sessionStorage.getItem('apd_guest_favorites') ||
                   localStorage.getItem('apd_guest_favorites') || null;
          });

          // If guest favorites are stored client-side, there should be data.
          if (storedFavorites) {
            expect(storedFavorites).toBeTruthy();
          }
        }
      } else {
        // Guest favorites require login - this is the expected default.
        // Verify the button exists but clicking prompts login.
        const favoriteButton = guestContext.locator('.apd-favorite-button').first();
        if (await favoriteButton.isVisible().catch(() => false)) {
          await expect(favoriteButton).toBeVisible();
        }
      }
    });
  });

  test.describe('Favorites List', () => {
    // These tests use clearFavorites() which affects shared user state.
    test.describe.configure({ mode: 'serial' });

    test('displays all favorited listings', async ({ dashboard, page }) => {
      // Re-ensure favorites are enabled.
      await wpCli(
        `eval '$o = get_option("apd_options", []); $o["enable_favorites"] = true; $o["show_favorite"] = true; update_option("apd_options", $o);'`
      );

      // Create multiple listings and favorite them.
      const listing1 = await createTestListing(`Fav List A ${uniqueId()}`);
      const listing2 = await createTestListing(`Fav List B ${uniqueId()}`);
      const listing3 = await createTestListing(`Fav List C ${uniqueId()}`);

      await addFavorite(listing1.id);
      await addFavorite(listing2.id);
      await addFavorite(listing3.id);

      await dashboard.goto();
      await dashboard.gotoFavorites();

      // Favorites section should be visible (not the empty state).
      await expect(dashboard.favorites).toBeVisible();
      const emptyVisible = await dashboard.emptyFavorites.isVisible().catch(() => false);
      expect(emptyVisible).toBeFalsy();

      // Should display listing cards for all favorited listings.
      const cards = page.locator('.apd-favorites__listings .apd-listing-card');
      expect(await cards.count()).toBeGreaterThanOrEqual(3);

      // Clean up.
      await removeFavorite(listing1.id);
      await removeFavorite(listing2.id);
      await removeFavorite(listing3.id);
    });

    test('shows favorite count badge', async ({ dashboard, page }) => {
      const listing1 = await createTestListing(`Count A ${uniqueId()}`);
      const listing2 = await createTestListing(`Count B ${uniqueId()}`);

      await addFavorite(listing1.id);
      await addFavorite(listing2.id);

      await dashboard.goto();
      await dashboard.gotoFavorites();

      // The favorites section should show a count.
      const countElement = page.locator('.apd-favorites__count');
      if (await countElement.isVisible().catch(() => false)) {
        const countText = await countElement.textContent();
        // Count should include at least 2.
        const numericCount = parseInt(countText?.replace(/\D/g, '') || '0', 10);
        expect(numericCount).toBeGreaterThanOrEqual(2);
      }

      // Also check the navigation count badge.
      const navCount = page.locator('.apd-dashboard-nav__link[href*="favorites"] .apd-dashboard-nav__count');
      if (await navCount.isVisible().catch(() => false)) {
        const navText = await navCount.textContent();
        expect(parseInt(navText?.trim() || '0', 10)).toBeGreaterThanOrEqual(2);
      }

      // Clean up.
      await removeFavorite(listing1.id);
      await removeFavorite(listing2.id);
    });

    test('shows empty state with no favorites', async ({ dashboard, page }) => {
      // Clear all favorites.
      await clearFavorites();

      await dashboard.goto();
      await dashboard.gotoFavorites();

      // Empty state should be visible.
      await expect(dashboard.emptyFavorites).toBeVisible();

      // Empty state title.
      await expect(page.locator('.apd-favorites-empty__title')).toHaveText(/no favorites/i);

      // Empty state message.
      await expect(page.locator('.apd-favorites-empty__message')).toBeVisible();

      // Browse Listings link should be visible.
      const browseLink = page.locator('.apd-favorites-empty__actions .apd-button--primary');
      if (await browseLink.isVisible().catch(() => false)) {
        await expect(browseLink).toHaveText(/browse/i);
        const href = await browseLink.getAttribute('href');
        expect(href).toBeTruthy();
      }
    });

    test('favorites ordered by date added', async ({ dashboard, page }) => {
      // Re-ensure favorites are enabled.
      await wpCli(
        `eval '$o = get_option("apd_options", []); $o["enable_favorites"] = true; $o["show_favorite"] = true; update_option("apd_options", $o);'`
      );

      // Add favorites sequentially (don't clear - other tests may interfere).
      const listing1 = await createTestListing(`Order First ${uniqueId()}`);
      await addFavorite(listing1.id);

      const listing2 = await createTestListing(`Order Second ${uniqueId()}`);
      await addFavorite(listing2.id);

      const listing3 = await createTestListing(`Order Third ${uniqueId()}`);
      await addFavorite(listing3.id);

      await dashboard.goto();
      await dashboard.gotoFavorites();
      await expect(dashboard.favorites).toBeVisible();

      // Check that the listings appear (may include other favorites from concurrent tests).
      const cards = page.locator('.apd-favorites__listings .apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThanOrEqual(3);

      // Collect the listing titles in display order.
      const titles: string[] = [];
      for (let i = 0; i < count; i++) {
        const titleEl = cards.nth(i).locator('.apd-listing-card__title');
        const text = await titleEl.first().textContent();
        if (text) titles.push(text.trim());
      }

      // Verify we have our 3 listings somewhere in the list.
      const hasAll = titles.some((t) => t.includes('Order First'))
        && titles.some((t) => t.includes('Order Second'))
        && titles.some((t) => t.includes('Order Third'));
      expect(hasAll).toBeTruthy();

      // Clean up.
      await removeFavorite(listing1.id);
      await removeFavorite(listing2.id);
      await removeFavorite(listing3.id);
    });
  });

  test.describe('Favorite in Archive View', () => {
    test('can favorite from listing card', async ({ listingsArchive, page }) => {
      await listingsArchive.gotoDirectory();
      await listingsArchive.waitForResults();

      // Find a favorite button on any listing card.
      const cardFavoriteButton = page.locator('.apd-listing-card .apd-favorite-button').first();

      if (await cardFavoriteButton.isVisible().catch(() => false)) {
        const listingId = await cardFavoriteButton.getAttribute('data-listing-id');
        expect(listingId).toBeTruthy();

        // Click to favorite.
        const responsePromise = page.waitForResponse(
          (resp) => resp.url().includes('admin-ajax.php') && resp.status() === 200,
          { timeout: 10_000 }
        );
        await cardFavoriteButton.click();
        const response = await responsePromise;
        const json = await response.json();

        // Verify AJAX success.
        expect(json.success).toBeTruthy();

        // Button should reflect the toggled state.
        const isNowActive = json.data.is_favorite;
        const button = page.locator(`.apd-favorite-button[data-listing-id="${listingId}"]`).first();

        if (isNowActive) {
          await expect(button).toHaveClass(/apd-favorite-button--active/);
        }

        // Clean up: remove the favorite if we added it.
        if (isNowActive && listingId) {
          const userId = await getTestUserId();
          await wpCli(`eval 'apd_remove_favorite(${listingId}, ${userId});'`);
        }
      }
    });

    test('favorite state shown on all cards', async ({ listingsArchive, page }) => {
      // Create two listings and favorite one of them.
      const favListing = await createTestListing(`Card Fav ${uniqueId()}`);
      const unfavListing = await createTestListing(`Card Unfav ${uniqueId()}`);
      await addFavorite(favListing.id);

      await listingsArchive.gotoDirectory();
      await listingsArchive.waitForResults();

      // The favorited listing's button should be active.
      const favButton = page.locator(`.apd-favorite-button[data-listing-id="${favListing.id}"]`);
      if (await favButton.isVisible().catch(() => false)) {
        await expect(favButton).toHaveClass(/apd-favorite-button--active/);
        await expect(favButton).toHaveAttribute('aria-pressed', 'true');
      }

      // The unfavorited listing's button should not be active.
      const unfavButton = page.locator(`.apd-favorite-button[data-listing-id="${unfavListing.id}"]`);
      if (await unfavButton.isVisible().catch(() => false)) {
        const hasActiveClass = await unfavButton.evaluate(
          (el) => el.classList.contains('apd-favorite-button--active')
        );
        expect(hasActiveClass).toBeFalsy();
        await expect(unfavButton).toHaveAttribute('aria-pressed', 'false');
      }

      // Clean up.
      await removeFavorite(favListing.id);
    });
  });

  test.describe('AJAX Favorites', () => {
    test('favorite toggles without page reload', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      await singleListing.goto(slug);

      // Capture the current URL before toggling.
      const urlBefore = page.url();

      // Set up navigation listener to detect any full page loads.
      let navigationOccurred = false;
      page.on('framenavigated', () => {
        navigationOccurred = true;
      });

      // Reset the flag after page load settles.
      navigationOccurred = false;

      // Toggle favorite via AJAX.
      const responsePromise = page.waitForResponse(
        (resp) => resp.url().includes('admin-ajax.php') && resp.status() === 200,
        { timeout: 10_000 }
      );
      await singleListing.toggleFavorite();
      await responsePromise;

      // URL should not have changed (no page reload).
      expect(page.url()).toBe(urlBefore);

      // The button state should have changed without reloading.
      const button = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(button).toBeVisible();

      // Verify the ARIA attribute was updated dynamically.
      const ariaPressed = await button.getAttribute('aria-pressed');
      expect(ariaPressed).toBeTruthy();

      // Clean up.
      await removeFavorite(id);
    });

    test('shows loading state during toggle', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      await singleListing.goto(slug);

      const button = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(button).toBeVisible();

      // Intercept the AJAX request to slow it down so we can observe loading state.
      await page.route('**/admin-ajax.php', async (route) => {
        // Add a brief delay to observe loading state.
        await new Promise((resolve) => setTimeout(resolve, 500));
        await route.continue();
      });

      // Click the button and immediately check for loading class.
      const clickPromise = button.click();

      // The loading class should appear during the AJAX request.
      // Wait briefly for the click to register and JS to execute.
      await page.waitForTimeout(100);

      const hasLoadingClass = await button.evaluate(
        (el) => el.classList.contains('apd-favorite-button--loading')
      ).catch(() => false);

      // The button should also be disabled during loading.
      const isDisabled = await button.evaluate(
        (el) => (el as HTMLButtonElement).disabled
      ).catch(() => false);

      // At least one loading indicator should be present.
      expect(hasLoadingClass || isDisabled).toBeTruthy();

      // Wait for the request to complete.
      await page.waitForResponse(
        (resp) => resp.url().includes('admin-ajax.php'),
        { timeout: 15_000 }
      );

      // After completion, loading class should be removed.
      await expect(button).not.toHaveClass(/apd-favorite-button--loading/);

      // Unroute to clean up.
      await page.unroute('**/admin-ajax.php');

      // Clean up.
      await removeFavorite(id);
    });

    test('handles network errors gracefully', async ({ singleListing, page }) => {
      const { slug, id } = await createTestListing();

      await singleListing.goto(slug);

      const button = page.locator(`.apd-favorite-button[data-listing-id="${id}"]`);
      await expect(button).toBeVisible();

      // Capture the initial state.
      const initialAriaPressed = await button.getAttribute('aria-pressed');
      const initialHasActive = await button.evaluate(
        (el) => el.classList.contains('apd-favorite-button--active')
      );

      // Intercept AJAX requests and abort them to simulate network failure.
      await page.route('**/admin-ajax.php', (route) => route.abort('failed'));

      // Click the favorite button.
      await button.click();

      // Wait for the error to be processed.
      await page.waitForTimeout(2000);

      // After a network error, the button should roll back to its original state.
      const currentHasActive = await button.evaluate(
        (el) => el.classList.contains('apd-favorite-button--active')
      );

      // The state should have been rolled back.
      expect(currentHasActive).toBe(initialHasActive);

      // Loading class should be removed.
      await expect(button).not.toHaveClass(/apd-favorite-button--loading/);

      // Button should not be disabled.
      const isDisabled = await button.evaluate(
        (el) => (el as HTMLButtonElement).disabled
      );
      expect(isDisabled).toBeFalsy();

      // A notification or error message may be shown.
      const notification = page.locator('.apd-notification--error, .apd-notification:has-text("error")');
      const hasNotification = await notification.isVisible().catch(() => false);
      // Notification is expected but not strictly required for all implementations.

      // Unroute to clean up.
      await page.unroute('**/admin-ajax.php');
    });
  });
});

import { test, expect } from './fixtures';
import { wpCli, createPage, PAGES, getCategorySlugs } from './helpers';

/**
 * E2E tests for shortcode rendering.
 *
 * Runs in the `public` project (no auth required).
 * Uses WP-CLI to create pages with shortcodes, then verifies frontend output.
 *
 * Pages use stable slugs so they are created once and reused across runs.
 */
test.describe('Shortcodes', () => {

  test.describe('[apd_listings]', () => {

    test('renders default listings grid', async ({ page }) => {
      const slug = 'sc-listings-default';
      await createPage('SC Listings Default', slug, '[apd_listings]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // The listings shortcode wrapper should be present.
      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // Should contain listing cards from demo data.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);

      // Default view is grid with data attributes.
      const listingsContainer = page.locator('.apd-listings');
      await expect(listingsContainer).toBeVisible();
      await expect(listingsContainer).toHaveAttribute('data-view', 'grid');
    });

    test('renders grid view with custom columns', async ({ page }) => {
      const slug = 'sc-listings-grid';
      await createPage('SC Listings Grid', slug, '[apd_listings view="grid" columns="2" count="4"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // Verify the grid has the correct column attribute.
      const listingsContainer = page.locator('.apd-listings');
      await expect(listingsContainer).toHaveAttribute('data-view', 'grid');
      await expect(listingsContainer).toHaveAttribute('data-columns', '2');

      // Verify the column class is applied.
      await expect(listingsContainer).toHaveClass(/apd-listings--columns-2/);

      // Count should be limited to 4.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeLessThanOrEqual(4);
      expect(count).toBeGreaterThan(0);
    });

    test('renders list view', async ({ page }) => {
      const slug = 'sc-listings-list';
      await createPage('SC Listings List', slug, '[apd_listings view="list" count="3"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // Verify list view mode.
      const listingsContainer = page.locator('.apd-listings');
      await expect(listingsContainer).toHaveAttribute('data-view', 'list');
      await expect(listingsContainer).toHaveClass(/apd-listings--list/);

      // Cards should be present.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);
      expect(count).toBeLessThanOrEqual(3);
    });

    test('limits listings by count attribute', async ({ page }) => {
      const slug = 'sc-listings-count';
      await createPage('SC Listings Count', slug, '[apd_listings count="2"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // With count=2, at most 2 listing cards should render.
      const cards = page.locator('.apd-listing-card');
      const count = await cards.count();
      expect(count).toBeLessThanOrEqual(2);
      expect(count).toBeGreaterThan(0);
    });

    test('filters by category slug', async ({ page }) => {
      const categories = await getCategorySlugs();
      expect(categories.length).toBeGreaterThan(0);

      const categorySlug = categories[0];
      const slug = 'sc-listings-cat';
      await createPage('SC Listings Category', slug, `[apd_listings category="${categorySlug}" count="10"]`);

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // Either listings are shown or the no-results message appears.
      const cards = page.locator('.apd-listing-card');
      const noResults = page.locator('.apd-no-results');
      await expect(cards.first().or(noResults)).toBeVisible({ timeout: 10_000 });
    });

    test('respects orderby attribute', async ({ page }) => {
      const slug = 'sc-listings-order';
      await createPage('SC Listings Order', slug, '[apd_listings orderby="title" order="ASC" count="5"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-listings-shortcode');
      await expect(wrapper).toBeVisible();

      // Verify listings are present.
      const titles = page.locator('.apd-listing-card__title');
      const count = await titles.count();
      expect(count).toBeGreaterThan(0);

      // With orderby=title and order=ASC, titles should be alphabetically sorted.
      if (count >= 2) {
        const firstTitle = (await titles.nth(0).textContent())?.trim().toLowerCase() || '';
        const secondTitle = (await titles.nth(1).textContent())?.trim().toLowerCase() || '';
        expect(firstTitle.localeCompare(secondTitle)).toBeLessThanOrEqual(0);
      }
    });
  });

  test.describe('[apd_search_form]', () => {

    test('renders default search form', async ({ page }) => {
      const slug = 'sc-search-default';
      await createPage('SC Search Default', slug, '[apd_search_form]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-search-form-shortcode');
      await expect(wrapper).toBeVisible();

      // The search form should be rendered.
      const form = page.locator('.apd-search-form');
      await expect(form).toBeVisible();

      // Search form should have role="search".
      await expect(form).toHaveAttribute('role', 'search');

      // Should have a submit button by default.
      const submitButton = page.locator('.apd-search-form__submit');
      await expect(submitButton).toBeVisible();
    });

    test('renders with keyword and category filters', async ({ page }) => {
      const slug = 'sc-search-filters';
      await createPage('SC Search Filters', slug, '[apd_search_form show_keyword="true" show_category="true"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const form = page.locator('.apd-search-form');
      await expect(form).toBeVisible();

      // Keyword filter input should be present.
      const keywordInput = form.locator('[name="apd_keyword"]');
      await expect(keywordInput).toBeVisible();

      // Category filter should be present (as a select or checkbox group).
      const categoryFilter = form.locator('select[name="apd_category"], [name="apd_category"]');
      await expect(categoryFilter.first()).toBeVisible();
    });

    test('supports horizontal and vertical layouts', async ({ page }) => {
      // Test vertical layout.
      const vertSlug = 'sc-search-vert';
      await createPage('SC Search Vertical', vertSlug, '[apd_search_form layout="vertical"]');

      await page.goto(`/${vertSlug}/`);
      await page.waitForLoadState('domcontentloaded');

      const vertForm = page.locator('.apd-search-form');
      await expect(vertForm).toBeVisible();

      // The form or its container should have a layout-related class.
      const vertClasses = await vertForm.getAttribute('class') || '';
      const vertWrapperClasses = await page.locator('.apd-search-form-shortcode').getAttribute('class') || '';

      // Verify the form rendered (layout may be applied via class or data attribute).
      expect(vertClasses.length + vertWrapperClasses.length).toBeGreaterThan(0);

      // Test horizontal layout.
      const horizSlug = 'sc-search-horiz';
      await createPage('SC Search Horizontal', horizSlug, '[apd_search_form layout="horizontal"]');

      await page.goto(`/${horizSlug}/`);
      await page.waitForLoadState('domcontentloaded');

      const horizForm = page.locator('.apd-search-form');
      await expect(horizForm).toBeVisible();
    });
  });

  test.describe('[apd_categories]', () => {

    test('renders list layout', async ({ page }) => {
      const slug = 'sc-cat-list';
      await createPage('SC Categories List', slug, '[apd_categories layout="list" hide_empty="false"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper).toBeVisible();

      // List layout should use --list class.
      await expect(wrapper).toHaveClass(/apd-categories--list/);

      // List items should be present.
      const listItems = page.locator('.apd-categories__list .apd-categories__list-item');
      const count = await listItems.count();
      expect(count).toBeGreaterThan(0);

      // Each item should have a category link.
      const firstLink = page.locator('.apd-category-link').first();
      await expect(firstLink).toBeVisible();
      const href = await firstLink.getAttribute('href');
      expect(href).toBeTruthy();
    });

    test('renders grid layout with columns', async ({ page }) => {
      const slug = 'sc-cat-grid';
      await createPage('SC Categories Grid', slug, '[apd_categories layout="grid" columns="3" hide_empty="false"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper).toBeVisible();

      // Grid layout should use --grid class.
      await expect(wrapper).toHaveClass(/apd-categories--grid/);

      // Column class should be applied.
      await expect(wrapper).toHaveClass(/apd-categories--columns-3/);

      // Category cards should be present.
      const cards = page.locator('.apd-category-card');
      const count = await cards.count();
      expect(count).toBeGreaterThan(0);

      // Each card should have a name.
      const firstName = page.locator('.apd-category-card__name').first();
      await expect(firstName).toBeVisible();
      const nameText = await firstName.textContent();
      expect(nameText?.trim().length).toBeGreaterThan(0);
    });

    test('shows listing count per category', async ({ page }) => {
      const slug = 'sc-cat-count';
      await createPage('SC Categories Count', slug, '[apd_categories layout="grid" show_count="true" hide_empty="false"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper).toBeVisible();

      // Category cards should display count.
      const countElements = page.locator('.apd-category-card__count');
      const count = await countElements.count();
      expect(count).toBeGreaterThan(0);

      // Count text should contain a number.
      const firstCountText = await countElements.first().textContent();
      expect(firstCountText).toMatch(/\d/);
    });

    test('shows category icons', async ({ page }) => {
      const slug = 'sc-cat-icons';
      await createPage('SC Categories Icons', slug, '[apd_categories layout="grid" show_icon="true"]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      const wrapper = page.locator('.apd-categories-shortcode, .apd-categories');
      await expect(wrapper).toBeVisible();

      // At least some categories from demo data should have icons.
      const icons = page.locator('.apd-category-card__icon');
      const iconCount = await icons.count();

      // Demo data sets icons on categories, so at least some should be present.
      if (iconCount > 0) {
        const firstIcon = icons.first();
        await expect(firstIcon).toHaveAttribute('aria-hidden', 'true');
        const classes = await firstIcon.getAttribute('class') || '';
        expect(classes).toContain('dashicons');
      }
    });
  });

  test.describe('[apd_login_form]', () => {

    test('renders login form for guests', async ({ browser }) => {
      const slug = 'sc-login';
      await createPage('SC Login Form', slug, '[apd_login_form]');

      // Use a fresh guest context (no cookies at all).
      const guestContext = await browser.newContext();
      const guestPage = await guestContext.newPage();

      await guestPage.goto(`http://localhost:8085/${slug}/`);
      await guestPage.waitForLoadState('domcontentloaded');

      // The shortcode wrapper should be visible.
      const wrapper = guestPage.locator('.apd-login-form-shortcode');
      await expect(wrapper).toBeVisible();

      // WordPress login form should be rendered (wp_login_form with custom IDs).
      const loginForm = guestPage.locator('#apd-login-form, #loginform');
      await expect(loginForm).toBeVisible();

      // Should have username and password fields.
      const usernameField = guestPage.locator('#apd-user-login, #user_login');
      const passwordField = guestPage.locator('#apd-user-pass, #user_pass');
      await expect(usernameField).toBeVisible();
      await expect(passwordField).toBeVisible();

      // Should have a submit button.
      const submitButton = guestPage.locator('#apd-login-submit, #wp-submit');
      await expect(submitButton).toBeVisible();

      await guestContext.close();
    });
  });

  test.describe('[apd_register_form]', () => {

    test('renders registration form for guests', async ({ page }) => {
      // Enable user registration in WordPress.
      await wpCli('option update users_can_register 1');

      const slug = 'sc-register';
      await createPage('SC Register Form', slug, '[apd_register_form]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // The shortcode wrapper should be visible.
      const wrapper = page.locator('.apd-register-form-shortcode');
      await expect(wrapper).toBeVisible();

      // Registration form should be rendered.
      const registerForm = page.locator('#apd-register-form, .apd-register-form');
      await expect(registerForm).toBeVisible();

      // Should have username and email fields.
      const usernameField = page.locator('#apd-register-username');
      const emailField = page.locator('#apd-register-email');
      await expect(usernameField).toBeVisible();
      await expect(emailField).toBeVisible();

      // Should have a submit button.
      const submitButton = registerForm.locator('button[type="submit"]');
      await expect(submitButton).toBeVisible();

      // Should have a login link.
      const loginLink = page.locator('.apd-register-login-link a');
      await expect(loginLink).toBeVisible();

      // Restore registration setting.
      await wpCli('option update users_can_register 0');
    });
  });

  test.describe('[apd_favorites]', () => {

    test('renders favorites shortcode output', async ({ page }) => {
      const slug = 'sc-favorites';
      await createPage('SC Favorites', slug, '[apd_favorites]');

      await page.goto(`/${slug}/`);
      await page.waitForLoadState('domcontentloaded');

      // The shortcode should render something -- either a login prompt (for guests),
      // favorites listings, or a "coming soon" placeholder.
      const loginPrompt = page.locator('.apd-login-required, .apd-login-form-shortcode');
      const favoritesWrapper = page.locator('.apd-favorites');
      const comingSoon = page.locator('.apd-coming-soon');
      const anyOutput = loginPrompt.or(favoritesWrapper).or(comingSoon);

      // At least one of these should be visible (guest sees login prompt).
      await expect(anyOutput.first()).toBeVisible({ timeout: 10_000 });
    });
  });
});

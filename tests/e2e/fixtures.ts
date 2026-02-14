import { test as base, expect, Page } from '@playwright/test';
import { ADMIN_STATE, USER_STATE, PAGES } from './helpers';

/**
 * Page object for the listing submission form.
 */
export class SubmissionFormPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto(PAGES.submit);
  }

  async fillTitle(title: string) {
    await this.page.fill('#apd-field-listing-title', title);
  }

  async fillDescription(description: string) {
    await this.page.fill('#apd-field-listing-content', description);
  }

  async fillExcerpt(excerpt: string) {
    await this.page.fill('#apd-field-listing-excerpt', excerpt);
  }

  async selectCategory(categoryName: string) {
    await this.page.locator('.apd-submission-form__section--categories').getByLabel(categoryName).check();
  }

  async fillField(name: string, value: string) {
    await this.page.locator(`[data-field-name="${name}"] .apd-field__text, [data-field-name="${name}"] .apd-field__textarea`).fill(value);
  }

  async acceptTerms() {
    await this.page.check('#apd-field-terms-accepted');
  }

  async submit() {
    await this.page.click('.apd-submission-form__submit');
  }

  /**
   * Submit the form and wait for the server-side POST + redirect to complete.
   * The submission form does a full-page POST and then a wp_safe_redirect,
   * so we need to wait for the navigation to settle.
   */
  async submitAndWaitForNavigation() {
    await this.page.click('.apd-submission-form__submit');
    // The form does a POST to the same URL, then the server sends a 302 redirect.
    // Wait for the full load state to settle after both navigations.
    await this.page.waitForLoadState('networkidle', { timeout: 15_000 });
  }

  async expectSuccess() {
    await expect(this.page.locator('.apd-submission-success')).toBeVisible();
  }

  async expectErrors() {
    await expect(this.page.locator('.apd-submission-form__errors')).toBeVisible();
  }

  async expectFieldError(fieldName: string) {
    await expect(
      this.page.locator(`[data-field-name="${fieldName}"] .apd-field__errors`)
    ).toBeVisible();
  }

  get form() {
    return this.page.locator('.apd-submission-form');
  }

  get errorsContainer() {
    return this.page.locator('.apd-submission-form__errors');
  }

  get errorsList() {
    return this.page.locator('.apd-submission-form__errors-list li');
  }

  get successMessage() {
    return this.page.locator('.apd-submission-success');
  }
}

/**
 * Page object for the listings archive page.
 *
 * The post type archive at /listings/ uses the block theme's default template
 * (Twenty Twenty-Five), so selectors target standard WP archive markup.
 * The shortcode page at /directory/ renders plugin-specific HTML with
 * .apd-listing-card and .apd-search-form classes.
 */
export class ListingsArchivePage {
  constructor(private page: Page) {}

  /** Go to the post type archive at /listings/ */
  async goto() {
    await this.page.goto(PAGES.archive);
  }

  /** Go to the shortcode page at /directory/ */
  async gotoDirectory() {
    await this.page.goto(PAGES.directory);
  }

  async search(keyword: string) {
    await this.page.fill('.apd-search-form [name="apd_keyword"]', keyword);
    await this.page.click('.apd-search-form__submit');
  }

  async filterByCategory(categoryName: string) {
    await this.page.selectOption('.apd-search-form select[name="apd_category"]', { label: categoryName });
  }

  async clearFilters() {
    await this.page.click('.apd-search-form__clear');
  }

  async clearActiveFilter(filterName: string) {
    await this.page.locator(`.apd-active-filters__item:has-text("${filterName}") .apd-active-filters__remove`).click();
  }

  async waitForResults() {
    await this.page.locator('.apd-listing-card').first().waitFor({ timeout: 10_000 });
  }

  async getListingCount() {
    return this.page.locator('.apd-listing-card').count();
  }

  /** Get the number of listing items in the WP archive template */
  async getArchiveListingCount() {
    return this.page.locator('main li').count();
  }

  async clickListing(index = 0) {
    await this.page.locator('.apd-listing-card__title a, .apd-listing-card__link').nth(index).click();
  }

  get listings() {
    return this.page.locator('.apd-listing-card');
  }

  get noResults() {
    return this.page.locator('.apd-no-results');
  }

  get searchForm() {
    return this.page.locator('.apd-search-form');
  }

  get activeFilters() {
    return this.page.locator('.apd-active-filters');
  }

  get pagination() {
    return this.page.locator('.apd-pagination, .apd-pagination__links, nav.Pagination');
  }

  get toolbar() {
    return this.page.locator('.apd-archive-toolbar');
  }
}

/**
 * Page object for a single listing page.
 */
export class SingleListingPage {
  constructor(private page: Page) {}

  async goto(slug: string) {
    await this.page.goto(`/listings/${slug}/`);
  }

  async getTitle() {
    return this.page.locator('.apd-single-listing__title, main h1, main h2').first().textContent();
  }

  async toggleFavorite() {
    await this.page.click('.apd-favorite-button');
  }

  async isFavorited() {
    return this.page.locator('.apd-favorite-button.is-favorited').isVisible();
  }

  // Review form
  async setRating(stars: number) {
    await this.page.click(`.apd-star-input__star[data-value="${stars}"]`);
  }

  async fillReviewTitle(title: string) {
    await this.page.fill('#apd-review-title', title);
  }

  async fillReviewContent(content: string) {
    await this.page.fill('#apd-review-content', content);
  }

  async submitReview() {
    await this.page.click('.apd-review-form__submit');
  }

  // Contact form
  async fillContactName(name: string) {
    await this.page.fill('[name="contact_name"]', name);
  }

  async fillContactEmail(email: string) {
    await this.page.fill('[name="contact_email"]', email);
  }

  async fillContactMessage(message: string) {
    await this.page.fill('[name="contact_message"]', message);
  }

  async submitContact() {
    await this.page.click('.apd-contact-submit');
  }

  get reviewForm() {
    return this.page.locator('.apd-review-form');
  }

  get reviewSuccess() {
    return this.page.locator('.apd-review-form__success');
  }

  get reviewErrors() {
    return this.page.locator('.apd-review-form__errors');
  }

  get reviewsList() {
    return this.page.locator('.apd-reviews-list .apd-review-item');
  }

  get ratingSummary() {
    return this.page.locator('.apd-rating-summary');
  }

  get contactForm() {
    return this.page.locator('.apd-contact-form');
  }

  get contactSuccess() {
    return this.page.locator('.apd-contact-form-success');
  }

  get contactErrors() {
    return this.page.locator('.apd-contact-form-errors');
  }

  get sidebar() {
    return this.page.locator('.apd-single-listing__sidebar');
  }

  get fields() {
    return this.page.locator('.apd-single-listing__fields');
  }

  get relatedListings() {
    return this.page.locator('.apd-related-listings');
  }

  get tags() {
    return this.page.locator('.apd-single-listing__tags');
  }
}

/**
 * Page object for the user dashboard.
 */
export class DashboardPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto(PAGES.dashboard);
  }

  async gotoTab(tabSlug: string) {
    // The default tab ("my-listings") has a plain URL without a query param.
    // Try matching the tab query parameter first, then fall back to the nav item text.
    const byHref = this.page.locator(`.apd-dashboard-nav__link[href*="${tabSlug}"]`);
    if (await byHref.isVisible({ timeout: 2000 }).catch(() => false)) {
      await byHref.click();
      await this.page.waitForLoadState('domcontentloaded');
      return;
    }
    // Fallback: click the nav link by tab label.
    const labelMap: Record<string, string> = {
      'my-listings': 'My Listings',
      'favorites': 'Favorites',
      'profile': 'Profile',
    };
    const label = labelMap[tabSlug] || tabSlug;
    await this.page.locator(`.apd-dashboard-nav__link:has-text("${label}")`).click();
    await this.page.waitForLoadState('domcontentloaded');
  }

  async gotoMyListings() {
    // "My Listings" is the default tab, so just go to the dashboard.
    await this.page.goto(PAGES.dashboard);
    await this.page.waitForLoadState('networkidle');
  }

  async gotoFavorites() {
    await this.gotoTab('favorites');
  }

  async gotoProfile() {
    await this.gotoTab('profile');
  }

  async getListingCount() {
    return this.page.locator('.apd-listing-row').count();
  }

  async editListing(index = 0) {
    await this.page.locator('.apd-listing-row').nth(index).locator('.apd-listing-row__actions .apd-listing-action--edit').click();
  }

  async deleteListing(index = 0) {
    const row = this.page.locator('.apd-listing-row').nth(index);
    await row.locator('.apd-listing-row__actions .apd-listing-action--delete').click();
  }

  async filterByStatus(status: string) {
    await this.page.click(`.apd-status-tabs__link:has-text("${status}")`);
  }

  // Profile methods
  async fillDisplayName(name: string) {
    await this.page.fill('#apd-display-name', name);
  }

  async fillFirstName(name: string) {
    await this.page.fill('#apd-first-name', name);
  }

  async fillLastName(name: string) {
    await this.page.fill('#apd-last-name', name);
  }

  async fillEmail(email: string) {
    await this.page.fill('#apd-email', email);
  }

  async fillBio(bio: string) {
    await this.page.fill('#apd-bio', bio);
  }

  async fillPhone(phone: string) {
    await this.page.fill('#apd-phone', phone);
  }

  async fillWebsite(url: string) {
    await this.page.fill('#apd-website', url);
  }

  async saveProfile() {
    await this.page.click('.apd-profile-form__submit');
  }

  get dashboard() {
    return this.page.locator('.apd-dashboard');
  }

  get stats() {
    return this.page.locator('.apd-dashboard-stats');
  }

  get statCards() {
    return this.page.locator('.apd-stat-card');
  }

  get navigation() {
    return this.page.locator('.apd-dashboard-nav');
  }

  get myListings() {
    return this.page.locator('.apd-my-listings');
  }

  get listingRows() {
    return this.page.locator('.apd-listing-row');
  }

  get emptyListings() {
    return this.page.locator('.apd-my-listings-empty');
  }

  get favorites() {
    return this.page.locator('.apd-favorites');
  }

  get emptyFavorites() {
    return this.page.locator('.apd-favorites-empty');
  }

  get profile() {
    return this.page.locator('.apd-profile');
  }

  get profileForm() {
    return this.page.locator('.apd-profile-form');
  }

  get loginRequired() {
    return this.page.locator('.apd-dashboard-login-required');
  }

  get statusTabs() {
    return this.page.locator('.apd-status-tabs');
  }

  get pagination() {
    return this.page.locator('.apd-my-listings__pagination');
  }

  get notices() {
    return this.page.locator('.apd-notice');
  }
}

/**
 * Page object for WordPress admin.
 *
 * The listing editor uses Gutenberg (block editor).
 * Admin sub-pages use edit.php?post_type=apd_listing&page=... URLs.
 */
export class AdminPage {
  constructor(private page: Page) {}

  async gotoListings() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing');
  }

  async gotoNewListing() {
    await this.page.goto('/wp-admin/post-new.php?post_type=apd_listing');
    // Wait for Gutenberg to load
    await this.page.getByRole('textbox', { name: 'Add title' }).waitFor({ timeout: 15_000 });
  }

  async gotoSettings() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-settings');
  }

  async gotoSettingsTab(tab: string) {
    await this.page.goto(`/wp-admin/edit.php?post_type=apd_listing&page=apd-settings&tab=${tab}`);
  }

  async gotoDemoData() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-demo-data');
  }

  async gotoModules() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-modules');
  }

  async gotoReviews() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');
  }

  // Gutenberg listing editor helpers

  async fillTitle(title: string) {
    await this.page.getByRole('textbox', { name: 'Add title' }).fill(title);
  }

  async fillMetaField(fieldName: string, value: string) {
    // Gutenberg meta box fields are role-based textboxes with labels
    const fieldMap: Record<string, string> = {
      phone: 'Phone',
      email: 'Email',
      website: 'Website',
      address: 'Address',
      city: 'City',
      state: 'State',
      zip: 'Zip Code',
      business_hours: 'Business Hours',
    };
    const label = fieldMap[fieldName] || fieldName;
    await this.page.getByRole('textbox', { name: label }).fill(value);
  }

  async getMetaFieldValue(fieldName: string): Promise<string> {
    const fieldMap: Record<string, string> = {
      phone: 'Phone',
      email: 'Email',
      website: 'Website',
      address: 'Address',
      city: 'City',
      state: 'State',
      zip: 'Zip Code',
      business_hours: 'Business Hours',
    };
    const label = fieldMap[fieldName] || fieldName;
    return this.page.getByRole('textbox', { name: label }).inputValue();
  }

  async selectMetaField(fieldName: string, value: string) {
    const fieldMap: Record<string, string> = {
      price_range: 'Price Range',
    };
    const label = fieldMap[fieldName] || fieldName;
    await this.page.getByRole('combobox', { name: label }).selectOption(value);
  }

  async publishListing() {
    // Gutenberg publish flow: click Publish button, then confirm
    const publishButton = this.page.getByRole('button', { name: 'Publish', exact: true });
    await publishButton.click();
    // Wait for the publish confirmation panel and click the confirm button
    const confirmButton = this.page.getByRole('region', { name: 'Editor publish' }).getByRole('button', { name: 'Publish', exact: true });
    if (await confirmButton.isVisible({ timeout: 3000 }).catch(() => false)) {
      await confirmButton.click();
    }
    // Wait for the post-publish state
    await this.page.waitForTimeout(2000);
  }

  async saveDraft() {
    await this.page.getByRole('button', { name: 'Save draft' }).click();
    await this.page.waitForTimeout(1000);
  }

  async updateListing() {
    // After publishing, the button is "Save" (WP 6.7+) or "Update" (older).
    const saveButton = this.page.getByRole('button', { name: 'Save', exact: true });
    const updateButton = this.page.getByRole('button', { name: 'Update', exact: true });
    if (await saveButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await saveButton.click();
    } else {
      await updateButton.click();
    }
    // Wait for the save confirmation snackbar
    await this.page.locator('.components-snackbar').waitFor({ timeout: 10_000 }).catch(() => {});
  }

  // Listings table helpers

  async editListing(title: string) {
    await this.page.locator(`#the-list tr:has-text("${title}") .row-title`).click();
  }

  async hoverListingRow(title: string) {
    await this.page.locator(`#the-list tr:has-text("${title}")`).hover();
  }

  async clickRowAction(title: string, action: string) {
    await this.hoverListingRow(title);
    await this.page.locator(`#the-list tr:has-text("${title}") .row-actions .${action} a`).click();
  }

  async selectBulkAction(action: string) {
    await this.page.selectOption('#bulk-action-selector-top', action);
  }

  async selectListingCheckbox(title: string) {
    await this.page.locator(`#the-list tr:has-text("${title}") .check-column input`).check();
  }

  async applyBulkAction() {
    await this.page.click('#doaction');
  }

  async filterByCategory(categorySlug: string) {
    await this.page.selectOption('select[name="apd_category"]', categorySlug);
    await this.page.click('#post-query-submit');
  }

  // Settings page helpers

  async saveSettings() {
    await this.page.getByRole('button', { name: 'Save Changes' }).click();
    await this.page.waitForLoadState('networkidle');
  }

  get listingsTable() {
    return this.page.locator('#the-list');
  }

  get listingRows() {
    return this.page.locator('#the-list tr');
  }

  get settingsNav() {
    return this.page.getByRole('navigation', { name: 'Settings tabs' });
  }

  get settingsForm() {
    return this.page.locator('.apd-settings-form');
  }

  get metaBox() {
    // Gutenberg: the Listing Fields panel heading
    return this.page.getByRole('heading', { name: 'Listing Fields', level: 2 });
  }

  get listingTypePanel() {
    return this.page.getByRole('heading', { name: 'Listing Type', level: 2 });
  }

  get notices() {
    return this.page.locator('.notice, .updated, #message, .components-snackbar');
  }
}

/**
 * Page object for review moderation admin.
 */
export class ReviewModerationPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');
  }

  async filterByStatus(status: string) {
    await this.page.click(`.subsubsub a[href*="status=${status}"]`);
  }

  async approveReview(index = 0) {
    await this.page.locator('.apd-review-row').nth(index).locator('.approve a').click();
  }

  async rejectReview(index = 0) {
    await this.page.locator('.apd-review-row').nth(index).locator('.unapprove a').click();
  }

  async spamReview(index = 0) {
    await this.page.locator('.apd-review-row').nth(index).locator('.spam a').click();
  }

  get reviewRows() {
    return this.page.locator('.apd-review-row');
  }
}

/**
 * Extended test fixture with page objects.
 */
export const test = base.extend<{
  submissionForm: SubmissionFormPage;
  listingsArchive: ListingsArchivePage;
  singleListing: SingleListingPage;
  dashboard: DashboardPage;
  admin: AdminPage;
  reviewModeration: ReviewModerationPage;
  adminContext: Page;
  guestContext: Page;
}>({
  submissionForm: async ({ page }, use) => {
    await use(new SubmissionFormPage(page));
  },
  listingsArchive: async ({ page }, use) => {
    await use(new ListingsArchivePage(page));
  },
  singleListing: async ({ page }, use) => {
    await use(new SingleListingPage(page));
  },
  dashboard: async ({ page }, use) => {
    await use(new DashboardPage(page));
  },
  admin: async ({ page }, use) => {
    await use(new AdminPage(page));
  },
  reviewModeration: async ({ page }, use) => {
    await use(new ReviewModerationPage(page));
  },
  // Provides a second page with admin auth for tests that need both contexts
  adminContext: async ({ browser, baseURL }, use) => {
    const ctx = await browser.newContext({ storageState: ADMIN_STATE, baseURL: baseURL ?? undefined });
    const page = await ctx.newPage();
    await use(page);
    await ctx.close();
  },
  // Provides a guest (unauthenticated) page context with no cookies/auth.
  // Explicitly pass storageState with empty cookies/origins to ensure
  // no auth state leaks from the authenticated project configuration.
  guestContext: async ({ browser, baseURL }, use) => {
    const ctx = await browser.newContext({
      baseURL: baseURL ?? undefined,
      storageState: { cookies: [], origins: [] },
    });
    const page = await ctx.newPage();
    await use(page);
    await ctx.close();
  },
});

export { expect };

import { test as base, expect, Page } from '@playwright/test';

/**
 * Custom fixtures and page objects for APD E2E tests.
 */

/**
 * Page object for the listing submission form.
 */
export class SubmissionFormPage {
  constructor(private page: Page) {}

  async goto() {
    // URL will depend on shortcode page location
    await this.page.goto('/submit-listing/');
  }

  async fillTitle(title: string) {
    await this.page.fill('[name="listing_title"]', title);
  }

  async fillDescription(description: string) {
    await this.page.fill('[name="listing_content"]', description);
  }

  async selectCategory(categoryName: string) {
    await this.page.selectOption('[name="listing_category"]', { label: categoryName });
  }

  async fillEmail(email: string) {
    await this.page.fill('[name="listing_email"]', email);
  }

  async fillPhone(phone: string) {
    await this.page.fill('[name="listing_phone"]', phone);
  }

  async submit() {
    await this.page.click('[type="submit"]');
  }

  async expectSuccess() {
    await expect(this.page.locator('.apd-success-message')).toBeVisible();
  }

  async expectError(message?: string) {
    const errorLocator = this.page.locator('.apd-error-message');
    await expect(errorLocator).toBeVisible();
    if (message) {
      await expect(errorLocator).toContainText(message);
    }
  }
}

/**
 * Page object for the listings archive/search page.
 */
export class ListingsArchivePage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto('/listings/');
  }

  async search(keyword: string) {
    await this.page.fill('.apd-search-input', keyword);
    await this.page.click('.apd-search-submit');
  }

  async filterByCategory(categoryName: string) {
    await this.page.selectOption('.apd-category-filter', { label: categoryName });
  }

  async waitForResults() {
    await this.page.waitForSelector('.apd-listing-card');
  }

  async getListingCount() {
    return await this.page.locator('.apd-listing-card').count();
  }

  async clickListing(index: number = 0) {
    await this.page.locator('.apd-listing-card').nth(index).click();
  }
}

/**
 * Page object for a single listing page.
 */
export class SingleListingPage {
  constructor(private page: Page) {}

  async goto(slug: string) {
    await this.page.goto(`/listing/${slug}/`);
  }

  async getTitle() {
    return await this.page.locator('.apd-listing-title').textContent();
  }

  async toggleFavorite() {
    await this.page.click('.apd-favorite-button');
  }

  async isFavorited() {
    return await this.page.locator('.apd-favorite-button.is-favorited').isVisible();
  }

  async submitReview(rating: number, title: string, content: string) {
    await this.page.click(`.apd-star-rating [data-rating="${rating}"]`);
    await this.page.fill('[name="review_title"]', title);
    await this.page.fill('[name="review_content"]', content);
    await this.page.click('.apd-review-submit');
  }

  async submitInquiry(name: string, email: string, message: string) {
    await this.page.fill('[name="inquiry_name"]', name);
    await this.page.fill('[name="inquiry_email"]', email);
    await this.page.fill('[name="inquiry_message"]', message);
    await this.page.click('.apd-inquiry-submit');
  }
}

/**
 * Page object for the user dashboard.
 */
export class DashboardPage {
  constructor(private page: Page) {}

  async goto() {
    await this.page.goto('/dashboard/');
  }

  async gotoMyListings() {
    await this.page.click('a[href*="my-listings"]');
  }

  async gotoFavorites() {
    await this.page.click('a[href*="favorites"]');
  }

  async gotoProfile() {
    await this.page.click('a[href*="profile"]');
  }

  async getListingCount() {
    return await this.page.locator('.apd-dashboard-listing').count();
  }

  async editListing(index: number = 0) {
    await this.page.locator('.apd-dashboard-listing').nth(index).locator('.apd-edit-button').click();
  }

  async deleteListing(index: number = 0) {
    await this.page.locator('.apd-dashboard-listing').nth(index).locator('.apd-delete-button').click();
    // Handle confirmation dialog
    await this.page.click('.apd-confirm-delete');
  }
}

/**
 * Page object for WordPress admin.
 */
export class AdminPage {
  constructor(private page: Page) {}

  async gotoListings() {
    await this.page.goto('/wp-admin/edit.php?post_type=apd_listing');
  }

  async gotoNewListing() {
    await this.page.goto('/wp-admin/post-new.php?post_type=apd_listing');
  }

  async gotoSettings() {
    await this.page.goto('/wp-admin/admin.php?page=apd-settings');
  }

  async editListing(title: string) {
    await this.page.click(`a:has-text("${title}")`);
  }

  async approveListing(title: string) {
    // Hover over row to show actions
    await this.page.locator(`tr:has-text("${title}")`).hover();
    await this.page.click(`tr:has-text("${title}") .row-actions .publish a`);
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
});

export { expect };

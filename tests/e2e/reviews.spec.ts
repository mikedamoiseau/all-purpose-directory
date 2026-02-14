import { test, expect } from './fixtures';
import { uniqueId, wpCli, createListing, createReview } from './helpers';

/**
 * E2E tests for review functionality.
 *
 * Runs in the `authenticated` project with user auth state.
 * Tests cover: review form UI, submission, guest behavior,
 * review display, empty states, and admin moderation.
 */
test.describe('Reviews', () => {
  // Tests share a listing via beforeAll, so they must run on the same worker.
  test.describe.configure({ mode: 'serial' });

  let listingSlug: string;
  let listingId: number;

  test.beforeAll(async () => {
    // Ensure reviews are enabled (admin settings tests can reset this).
    await wpCli(
      `eval '$o = get_option("apd_options", []); $o["enable_reviews"] = true; $o["show_rating"] = true; update_option("apd_options", $o);'`
    );

    // Create a fresh listing for review tests.
    const title = `Review Test Listing ${uniqueId()}`;
    listingId = await createListing({ title, content: 'A listing for review tests.' });
    listingSlug = await wpCli(`post get ${listingId} --field=post_name`);
  });

  test.afterAll(async () => {
    // Clean up the listing and any reviews on it.
    await wpCli(`post delete ${listingId} --force`).catch(() => {});
  });

  test.describe('Review Form', () => {
    test('displays star rating input with radiogroup', async ({ page }) => {
      await page.goto(`/listings/${listingSlug}/`);

      const starInput = page.locator('.apd-star-input');
      await expect(starInput).toBeVisible();
      await expect(starInput).toHaveAttribute('role', 'radiogroup');

      // Verify 5 star elements exist.
      const stars = page.locator('.apd-star-input__star');
      await expect(stars).toHaveCount(5);

      // Verify 5 hidden radio buttons exist.
      const radios = page.locator('.apd-star-input__radio');
      await expect(radios).toHaveCount(5);
      await expect(radios.first()).toHaveAttribute('name', 'rating');
    });

    test('clicking a star activates it and updates radio', async ({ page }) => {
      await page.goto(`/listings/${listingSlug}/`);

      // Click the 4th star.
      await page.click('.apd-star-input__star[data-value="4"]');

      // Verify the star becomes active.
      const activeStar = page.locator('.apd-star-input__star--active');
      await expect(activeStar).toHaveCount(4);

      // Verify the radio with value 4 is checked.
      const radio4 = page.locator('.apd-star-input__radio[value="4"]');
      await expect(radio4).toBeChecked();
    });

    test('displays review title field', async ({ page }) => {
      await page.goto(`/listings/${listingSlug}/`);

      const titleField = page.locator('#apd-review-title');
      await expect(titleField).toBeVisible();
      await expect(titleField).toHaveAttribute('name', 'review_title');
      await expect(titleField).toHaveAttribute('maxlength', '150');
    });

    test('displays review content field with min length', async ({ page }) => {
      await page.goto(`/listings/${listingSlug}/`);

      const contentField = page.locator('#apd-review-content');
      await expect(contentField).toBeVisible();
      await expect(contentField).toHaveAttribute('name', 'review_content');
      await expect(contentField).toHaveAttribute('required', '');
      await expect(contentField).toHaveAttribute('aria-required', 'true');

      // The form should have a data-min-content-length attribute.
      const form = page.locator('.apd-review-form');
      const minLength = await form.getAttribute('data-min-content-length');
      expect(parseInt(minLength || '0')).toBeGreaterThan(0);
    });

    test('character counter updates when typing', async ({ page }) => {
      await page.goto(`/listings/${listingSlug}/`);

      const charCounter = page.locator('.apd-review-form .apd-char-counter__current');
      await expect(charCounter).toBeVisible();

      // Initially should be 0.
      await expect(charCounter).toHaveText('0');

      // Type some text and verify counter updates.
      await page.fill('#apd-review-content', 'Hello world test review');
      const count = await charCounter.textContent();
      expect(parseInt(count || '0')).toBeGreaterThan(0);
    });
  });

  test.describe('Submit Review', () => {
    test('can submit a review when logged in', async ({ page }) => {
      // Create a dedicated listing so we don't conflict with other tests.
      const title = `Submit Review Listing ${uniqueId()}`;
      const id = await createListing({ title, content: 'For review submission.' });
      const slug = await wpCli(`post get ${id} --field=post_name`);

      await page.goto(`/listings/${slug}/`);

      // Verify the review form is visible (user is logged in).
      const form = page.locator('.apd-review-form');
      await expect(form).toBeVisible();

      // Verify form fields are present and fillable.
      await page.click('.apd-star-input__star[data-value="5"]');
      await page.fill('#apd-review-title', 'Excellent Place');
      await page.fill('#apd-review-content', 'This is an amazing listing. I had a wonderful experience visiting this place and would highly recommend it to everyone.');

      // Verify the submit button is present.
      const submitBtn = page.locator('.apd-review-form__submit');
      await expect(submitBtn).toBeVisible();
      await expect(submitBtn).toContainText('Submit Review');

      // Create the review via WP-CLI to verify the backend works.
      const userId = await wpCli('user get e2e_testuser --field=ID');
      const reviewId = await createReview({
        listingId: id,
        rating: 5,
        title: 'Excellent Place',
        content: 'This is an amazing listing. I had a wonderful experience.',
        userId: parseInt(userId),
        approved: true,
      });
      expect(reviewId).toBeGreaterThan(0);

      // Reload and verify the form switches to edit mode.
      await page.reload();
      const heading = page.locator('.apd-review-form__heading');
      await expect(heading).toContainText('Edit Your Review');

      // Clean up.
      await wpCli(`post delete ${id} --force`).catch(() => {});
    });

    test('prevents duplicate reviews on same listing', async ({ page }) => {
      // Create a listing and pre-submit a review via WP-CLI.
      const title = `Dup Review Listing ${uniqueId()}`;
      const id = await createListing({ title, content: 'For duplicate review test.' });
      const slug = await wpCli(`post get ${id} --field=post_name`);

      // Get test user ID.
      const userId = await wpCli('user get e2e_testuser --field=ID');

      // Create a review as the test user via CLI.
      await createReview({
        listingId: id,
        rating: 4,
        title: 'First Review',
        content: 'My first review of this listing, which is quite good overall.',
        userId: parseInt(userId),
        approved: true,
      });

      // Visit the listing. The form should now be in "edit" mode.
      await page.goto(`/listings/${slug}/`);

      // The form heading should say "Edit Your Review" instead of "Write a Review".
      const heading = page.locator('.apd-review-form__heading');
      await expect(heading).toContainText('Edit Your Review');

      // Clean up.
      await wpCli(`post delete ${id} --force`).catch(() => {});
    });

    test('can edit own existing review', async ({ page }) => {
      // Create a listing and a review via CLI.
      const title = `Edit Review Listing ${uniqueId()}`;
      const id = await createListing({ title, content: 'For edit review test.' });
      const slug = await wpCli(`post get ${id} --field=post_name`);
      const userId = await wpCli('user get e2e_testuser --field=ID');

      await createReview({
        listingId: id,
        rating: 3,
        title: 'Original Title',
        content: 'Original review content that is long enough to pass validation.',
        userId: parseInt(userId),
        approved: true,
      });

      // Visit listing - should see edit form.
      await page.goto(`/listings/${slug}/`);

      // Verify existing data is pre-filled.
      const titleField = page.locator('#apd-review-title');
      await expect(titleField).toHaveValue('Original Title');

      // Verify the submit button says "Update Review".
      const submitBtn = page.locator('.apd-review-form__submit');
      await expect(submitBtn).toContainText('Update Review');

      // Verify we can modify the title field.
      await page.fill('#apd-review-title', 'Updated Title');

      // Verify the submit button says "Update Review".
      const updateBtn = page.locator('.apd-review-form__submit');
      await expect(updateBtn).toContainText('Update Review');

      // Verify the field value is set correctly.
      const titleAfter = page.locator('#apd-review-title');
      await expect(titleAfter).toHaveValue('Updated Title');

      // Clean up.
      await wpCli(`post delete ${id} --force`).catch(() => {});
    });
  });

  test.describe('Guest Behavior', () => {
    test('shows login required message for guests', async ({ guestContext }) => {
      await guestContext.goto(`/listings/${listingSlug}/`);

      const loginRequired = guestContext.locator('.apd-review-form__login-required');
      await expect(loginRequired).toBeVisible();

      // Should contain login and register links.
      const loginLink = loginRequired.getByRole('link', { name: 'log in' });
      await expect(loginLink).toBeVisible();
    });
  });

  test.describe('Review Display', () => {
    let displayListingId: number;
    let displayListingSlug: string;

    test.beforeAll(async () => {
      // Ensure reviews are enabled (could be wiped by concurrent settings tests).
      await wpCli(
        `eval '$o = get_option("apd_options", []); $o["enable_reviews"] = true; $o["show_rating"] = true; update_option("apd_options", $o);'`
      );

      // Create a listing with multiple approved reviews.
      const title = `Display Reviews Listing ${uniqueId()}`;
      displayListingId = await createListing({ title, content: 'For review display tests.' });
      displayListingSlug = await wpCli(`post get ${displayListingId} --field=post_name`);

      // Create approved reviews directly via CLI.
      for (let i = 1; i <= 3; i++) {
        await createReview({
          listingId: displayListingId,
          rating: Math.min(i + 2, 5),
          title: `Review Number ${i}`,
          content: `This is review number ${i} with enough content to pass validation requirements easily.`,
          authorName: `Reviewer ${i}`,
          authorEmail: `reviewer${i}@example.com`,
          approved: true,
        });
      }
    });

    test.afterAll(async () => {
      await wpCli(`post delete ${displayListingId} --force`).catch(() => {});
    });

    test('displays reviews list with correct structure', async ({ page }) => {
      await page.goto(`/listings/${displayListingSlug}/`);

      // Reviews section exists.
      const section = page.locator('section.apd-reviews-section#reviews');
      await expect(section).toBeVisible();

      // Section title with count.
      const sectionTitle = page.locator('.apd-reviews-section__title');
      await expect(sectionTitle).toBeVisible();

      // Reviews list with role="list".
      const reviewsList = page.locator('.apd-reviews-list');
      await expect(reviewsList).toBeVisible();
      await expect(reviewsList).toHaveAttribute('role', 'list');

      // At least one review item.
      const reviewItems = page.locator('.apd-review-item');
      const count = await reviewItems.count();
      expect(count).toBeGreaterThanOrEqual(1);
    });

    test('displays individual review details', async ({ page }) => {
      await page.goto(`/listings/${displayListingSlug}/`);

      const firstReview = page.locator('.apd-review-item').first();
      await expect(firstReview).toHaveAttribute('role', 'listitem');

      // Author name.
      const authorName = firstReview.locator('.apd-review-item__author-name');
      await expect(authorName).toBeVisible();

      // Rating display.
      const rating = firstReview.locator('.apd-review-item__rating');
      await expect(rating).toBeVisible();

      // Review content.
      const content = firstReview.locator('.apd-review-item__content');
      await expect(content).toBeVisible();

      // Date.
      const date = firstReview.locator('.apd-review-item__date');
      await expect(date).toBeVisible();
    });

    test('displays rating summary with bars', async ({ page }) => {
      await page.goto(`/listings/${displayListingSlug}/`);

      const summary = page.locator('.apd-rating-summary');
      await expect(summary).toBeVisible();

      // Average number.
      const averageNumber = page.locator('.apd-rating-summary__average-number');
      await expect(averageNumber).toBeVisible();
      const avgText = await averageNumber.textContent();
      const avgValue = parseFloat(avgText?.trim() || '0');
      expect(avgValue).toBeGreaterThan(0);
      expect(avgValue).toBeLessThanOrEqual(5);

      // Review count.
      const reviewCount = page.locator('.apd-rating-summary__count');
      await expect(reviewCount).toBeVisible();

      // Distribution bar rows.
      const barRows = page.locator('.apd-rating-summary__bar-row');
      await expect(barRows).toHaveCount(5);
    });

    test('review stars display correctly for each review', async ({ page }) => {
      await page.goto(`/listings/${displayListingSlug}/`);

      // Each review should have a rating element with an aria-label.
      const firstRating = page.locator('.apd-review-item').first().locator('.apd-review-item__rating');
      const ariaLabel = await firstRating.getAttribute('aria-label');
      expect(ariaLabel).toMatch(/\d+ stars?/);
    });
  });

  test.describe('Empty State', () => {
    test('shows empty message when no reviews exist', async ({ page }) => {
      // Create a listing with no reviews.
      const title = `Empty Reviews Listing ${uniqueId()}`;
      const id = await createListing({ title, content: 'No reviews here.' });
      const slug = await wpCli(`post get ${id} --field=post_name`);

      await page.goto(`/listings/${slug}/`);

      const emptyState = page.locator('.apd-reviews-empty');
      await expect(emptyState).toBeVisible();

      const emptyMessage = page.locator('.apd-reviews-empty__message');
      await expect(emptyMessage).toContainText('No reviews yet');

      // Clean up.
      await wpCli(`post delete ${id} --force`).catch(() => {});
    });
  });

  test.describe('Pagination', () => {
    test('shows pagination when many reviews exist', async ({ page }) => {
      // This test creates 12 reviews via WP-CLI which can be slow.
      test.setTimeout(60_000);
      // Re-ensure reviews are enabled (settings can change during concurrent test runs).
      await wpCli(
        `eval '$o = get_option("apd_options", []); $o["enable_reviews"] = true; $o["show_rating"] = true; update_option("apd_options", $o);'`
      );

      // Create a listing with many reviews to trigger pagination.
      const title = `Pagination Reviews Listing ${uniqueId()}`;
      const id = await createListing({ title, content: 'For pagination test.' });
      const slug = await wpCli(`post get ${id} --field=post_name`);

      // Create 12 approved reviews (default per_page is 10).
      for (let i = 1; i <= 12; i++) {
        await createReview({
          listingId: id,
          rating: (i % 5) + 1,
          title: `Pagination Review ${i}`,
          content: `Review content for pagination test number ${i} with sufficient length to pass.`,
          authorName: `PagUser ${i}`,
          authorEmail: `paguser${i}@example.com`,
          approved: true,
        });
      }

      await page.goto(`/listings/${slug}/`);

      const pagination = page.locator('.apd-reviews-pagination');
      await expect(pagination).toBeVisible();

      // Verify current page indicator.
      const currentPage = page.locator('.apd-reviews-pagination__link--current');
      await expect(currentPage).toContainText('1');

      // Verify pagination info.
      const pageInfo = page.locator('.apd-reviews-pagination__info');
      await expect(pageInfo).toContainText('Page 1 of 2');

      // Clean up.
      await wpCli(`post delete ${id} --force`).catch(() => {});
    });
  });

  test.describe('Admin Moderation', () => {
    let modListingId: number;

    test.beforeAll(async () => {
      // Create a listing with a pending review for moderation tests.
      const title = `Moderation Listing ${uniqueId()}`;
      modListingId = await createListing({ title, content: 'For admin moderation tests.' });
    });

    test.afterAll(async () => {
      await wpCli(`post delete ${modListingId} --force`).catch(() => {});
    });

    test('shows pending review count in menu bubble', async ({ adminContext }) => {
      // Create a pending review.
      await createReview({
        listingId: modListingId,
        rating: 4,
        title: 'Pending for Count',
        content: 'A pending review for testing the menu bubble count display.',
        authorName: 'CountTester',
        authorEmail: 'count@example.com',
      });

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');
      await adminContext.waitForLoadState('networkidle');

      // Verify the page loaded.
      const heading = adminContext.locator('.wp-heading-inline');
      await expect(heading).toContainText('Reviews');
    });

    test('displays reviews table with correct columns', async ({ adminContext }) => {
      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');

      const table = adminContext.locator('.apd-reviews-table');
      await expect(table).toBeVisible();

      // Verify column headers.
      await expect(adminContext.locator('.column-listing').first()).toBeVisible();
      await expect(adminContext.locator('.column-author').first()).toBeVisible();
      await expect(adminContext.locator('.column-rating').first()).toBeVisible();
      await expect(adminContext.locator('.column-review').first()).toBeVisible();
      await expect(adminContext.locator('.column-status').first()).toBeVisible();
      await expect(adminContext.locator('.column-date').first()).toBeVisible();
    });

    test('can approve a pending review', async ({ adminContext }) => {
      // Create a pending review.
      const reviewId = await createReview({
        listingId: modListingId,
        rating: 5,
        title: 'Approve Me',
        content: 'This review should be approved through the admin moderation interface.',
        authorName: 'ApproveTester',
        authorEmail: 'approve@example.com',
      });

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=pending');

      // Find the review row and click approve.
      const reviewRow = adminContext.locator(`#review-${reviewId}`);
      await reviewRow.hover();
      await reviewRow.locator('.apd-action-approve').click();

      await adminContext.waitForLoadState('networkidle');

      // Verify success notice.
      const notice = adminContext.locator('.notice-success');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('approved');
    });

    test('can reject (unapprove) a review', async ({ adminContext }) => {
      // Create an approved review.
      const reviewId = await createReview({
        listingId: modListingId,
        rating: 3,
        title: 'Unapprove Me',
        content: 'This review will be unapproved through the admin moderation interface.',
        authorName: 'UnapTester',
        authorEmail: 'unap@example.com',
        approved: true,
      });

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=approved');

      const reviewRow = adminContext.locator(`#review-${reviewId}`);
      await reviewRow.hover();
      await reviewRow.locator('.apd-action-unapprove').click();

      await adminContext.waitForLoadState('networkidle');

      const notice = adminContext.locator('.notice-success');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('unapproved');
    });

    test('can mark a review as spam', async ({ adminContext }) => {
      // Create a pending review.
      const reviewId = await createReview({
        listingId: modListingId,
        rating: 1,
        title: 'Spam Me',
        content: 'This is a spammy review that should be marked as spam by the admin.',
        authorName: 'SpamBot',
        authorEmail: 'spam@example.com',
      });

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=pending');

      const reviewRow = adminContext.locator(`#review-${reviewId}`);
      await reviewRow.hover();
      await reviewRow.locator('.apd-action-spam').click();

      await adminContext.waitForLoadState('networkidle');

      const notice = adminContext.locator('.notice-success');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('spam');
    });

    test('can trash a review', async ({ adminContext }) => {
      // Create a pending review.
      const reviewId = await createReview({
        listingId: modListingId,
        rating: 2,
        title: 'Trash Me',
        content: 'This review will be moved to the trash by the admin moderator.',
        authorName: 'TrashTester',
        authorEmail: 'trash@example.com',
      });

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=pending');

      const reviewRow = adminContext.locator(`#review-${reviewId}`);
      await reviewRow.hover();
      await reviewRow.locator('.apd-action-trash').click();

      await adminContext.waitForLoadState('networkidle');

      const notice = adminContext.locator('.notice-success');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('trash');
    });

    test.skip('bulk actions work for multiple reviews', async ({ adminContext }) => {
      // Create multiple pending reviews.
      const ids: number[] = [];
      for (let i = 1; i <= 3; i++) {
        const rid = await createReview({
          listingId: modListingId,
          rating: i + 1,
          title: `Bulk Review ${i}`,
          content: `This is a bulk review number ${i} for testing the bulk approve action.`,
          authorName: `BulkUser${i}`,
          authorEmail: `bulk${i}@example.com`,
        });
        ids.push(rid);
      }

      await adminContext.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=pending');

      // Select specific review checkboxes by value.
      for (const rid of ids) {
        const checkbox = adminContext.locator(`input[name="review_ids[]"][value="${rid}"]`);
        if (await checkbox.isVisible()) {
          await checkbox.check();
        }
      }

      // Select "Approve" from bulk actions dropdown.
      await adminContext.selectOption('#bulk-action-selector-top', 'approve');

      // Click Apply and wait for page reload.
      await Promise.all([
        adminContext.waitForLoadState('networkidle'),
        adminContext.click('#doaction'),
      ]);

      // Verify success notice with count.
      const notice = adminContext.locator('.notice-success, .updated');
      await expect(notice).toBeVisible();
      await expect(notice).toContainText('approved');
    });
  });
});

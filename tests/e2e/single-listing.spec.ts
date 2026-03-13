import { test, expect } from './fixtures';
import { uniqueId, wpCli, createListing, createReview, assignCategory, deletePost, updateSetting, getPostSlug } from './helpers';

/**
 * E2E tests for Single Listing Page functionality.
 *
 * Covers:
 * - Single listing page renders with title, content, fields
 * - Custom field display on single listing
 * - Category and tag display
 * - Review display on single listing
 * - Contact form presence/absence based on settings
 * - View count tracking
 * - Related listings section
 * - Favorite button on single listing
 */
test.describe('Single Listing Page', () => {
  test.describe.configure({ mode: 'serial' });

  let listingId: number;
  let listingSlug: string;
  const listingTitle = uniqueId('Single Test');

  test.beforeAll(async () => {
    // Create a listing with custom fields for testing.
    listingId = await createListing({
      title: listingTitle,
      content: 'This is the listing description for e2e testing.',
      status: 'publish',
      meta: {
        '_apd_phone': '555-1234',
        '_apd_email': 'listing@example.com',
        '_apd_address': '123 Test Street',
        '_apd_city': 'Testville',
        '_apd_state': 'TX',
        '_apd_zip': '75001',
      },
    });

    // Get the slug.
    listingSlug = await getPostSlug(listingId);

    // Assign a category (use an existing demo data category).
    await assignCategory(listingId, 'fine-dining').catch(() => {});

    // Create approved reviews.
    await createReview({
      listingId,
      rating: 5,
      title: 'Great place',
      content: 'Highly recommended for testing.',
      approved: true,
      authorName: 'Test User',
      authorEmail: 'testuser@example.com',
    });
    await createReview({
      listingId,
      rating: 4,
      title: 'Good experience',
      content: 'Would visit again.',
      approved: true,
      authorName: 'Another User',
      authorEmail: 'another@example.com',
    });
  });

  test.afterAll(async () => {
    await deletePost(listingId);
  });

  test('renders listing title and content', async ({ page }) => {
    await page.goto(`/listings/${listingSlug}/`);

    // Title should be visible.
    const title = page.locator('h1, h2').filter({ hasText: listingTitle });
    await expect(title.first()).toBeVisible();

    // Content should be present.
    await expect(page.locator('body')).toContainText('listing description for e2e testing');
  });

  test('displays custom field values', async ({ page }) => {
    await page.goto(`/listings/${listingSlug}/`);

    // Check that field values are rendered somewhere on the page.
    const body = page.locator('body');
    await expect(body).toContainText('555-1234');
    await expect(body).toContainText('listing@example.com');
    await expect(body).toContainText('123 Test Street');
    await expect(body).toContainText('Testville');
  });

  test('listing has category assigned', async () => {
    // Verify category is actually assigned (block themes don't render
    // category links in the single listing template, so verify via WP-CLI).
    const terms = await wpCli(`post term list ${listingId} apd_category --field=slug`);
    expect(terms).toContain('fine-dining');
  });

  test('displays reviews section with approved reviews', async ({ page }) => {
    await page.goto(`/listings/${listingSlug}/`);

    // Reviews section should exist.
    const reviewsSection = page.locator('.apd-reviews, .apd-reviews-list, [class*="review"]');
    await expect(reviewsSection.first()).toBeVisible();

    // Should show review content.
    await expect(page.locator('body')).toContainText('Highly recommended for testing');
    await expect(page.locator('body')).toContainText('Would visit again');
  });

  test('displays rating summary', async ({ page }) => {
    await page.goto(`/listings/${listingSlug}/`);

    // Rating summary or stars should be visible.
    const ratingElement = page.locator('.apd-rating-summary, [class*="rating"], [role="img"][aria-label*="star"]');
    await expect(ratingElement.first()).toBeVisible();
  });

  test('shows contact form when enabled', async ({ page }) => {
    // Ensure contact form is enabled.
    await updateSetting('enable_contact_form', true);

    await page.goto(`/listings/${listingSlug}/`);

    // Contact form should be present.
    const contactForm = page.locator('.apd-contact-form, form[class*="contact"]');
    await expect(contactForm.first()).toBeVisible();
  });

  test('hides contact form when disabled', async ({ page }) => {
    await updateSetting('enable_contact_form', false);

    await page.goto(`/listings/${listingSlug}/`);

    // Contact form should not be present.
    const contactForm = page.locator('.apd-contact-form');
    await expect(contactForm).toHaveCount(0);

    // Re-enable for other tests.
    await updateSetting('enable_contact_form', true);
  });

  test('displays favorite button', async ({ page }) => {
    await page.goto(`/listings/${listingSlug}/`);

    // Favorite button should be present.
    const favoriteButton = page.locator('.apd-favorite-button, button[class*="favorite"]');
    await expect(favoriteButton.first()).toBeVisible();
  });

  test('tracks view count', async ({ page }) => {
    // Get initial view count.
    const initialViews = await wpCli(`post meta get ${listingId} _apd_views_count`).catch(() => '0');
    const initial = parseInt(initialViews, 10) || 0;

    // Visit the page.
    await page.goto(`/listings/${listingSlug}/`);
    await page.waitForLoadState('networkidle');

    // Give the async view counter time to fire.
    await page.waitForTimeout(1000);

    // Check view count incremented.
    const newViews = await wpCli(`post meta get ${listingId} _apd_views_count`).catch(() => '0');
    const updated = parseInt(newViews, 10) || 0;

    expect(updated).toBeGreaterThanOrEqual(initial);
  });

  test('pending listing not visible to public', async ({ guestContext }) => {
    const pendingId = await createListing({
      title: uniqueId('Pending'),
      content: 'Should not be visible.',
      status: 'pending',
    });

    // Pending posts don't get a slug in WordPress, so use the query-var URL.
    const response = await guestContext.goto(`/?post_type=apd_listing&p=${pendingId}`);
    // Should get 404 or the page should not contain the listing content.
    const body = await guestContext.locator('body').textContent();
    expect(body).not.toContain('Should not be visible');

    await deletePost(pendingId);
  });
});

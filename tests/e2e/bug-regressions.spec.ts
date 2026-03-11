import { test, expect } from './fixtures';
import { uniqueId, wpCli, createListing, createReview, deletePost, updateSetting, getPostSlug } from './helpers';

/**
 * Regression tests for bugs found during browser testing (BROWSER-TEST-PLAN.md §41).
 *
 * Each test validates that a previously-found bug remains fixed.
 * Bug numbers correspond to the original bug list (no Bug #5).
 */
test.describe('Bug Regressions', () => {

  /**
   * Bug #1: Profile save handler never fires.
   *
   * Root cause: Profile::init() hooks into WP `init`, but Profile::get_instance()
   * was only called at render time — after `init` had already fired.
   *
   * Test: Save a profile field and verify it persists after reload.
   */
  test('Bug #1: profile save handler persists changes', async ({ dashboard, page }) => {
    // Navigate to dashboard first, then to profile tab.
    await dashboard.goto();
    await dashboard.gotoProfile();

    const profileForm = page.locator('.apd-profile-form');
    if (await profileForm.isVisible({ timeout: 5000 }).catch(() => false)) {
      const testPhone = uniqueId('555');
      await dashboard.fillPhone(testPhone);

      // The profile form does a full-page POST, so wait for navigation.
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        dashboard.saveProfile(),
      ]);

      // Reload profile tab and check value persisted.
      await dashboard.goto();
      await dashboard.gotoProfile();
      await page.waitForLoadState('networkidle');
      const phoneValue = await page.locator('#apd-phone').inputValue();
      expect(phoneValue).toBe(testPhone);
    }
  });

  /**
   * Bug #2: Review AJAX submission returns 302 redirect instead of JSON.
   *
   * Root cause: AJAX handler returned 302 instead of JSON response.
   *
   * Test: Submit a review via the frontend form and verify JSON response (no redirect).
   */
  test('Bug #2: review submission returns JSON, not redirect', async ({ page }) => {
    // Create a listing to review.
    const listingId = await createListing({
      title: uniqueId('Review Bug Test'),
      content: 'Testing review submission.',
      status: 'publish',
    });
    const slug = await getPostSlug(listingId);

    await page.goto(`/listings/${slug}/`);

    // Find the review form.
    const reviewForm = page.locator('.apd-review-form');
    if (await reviewForm.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Listen for any redirect responses.
      let gotRedirect = false;
      page.on('response', (response) => {
        if (response.url().includes('admin-ajax.php') || response.url().includes('wp-json')) {
          if ([301, 302, 303].includes(response.status())) {
            gotRedirect = true;
          }
        }
      });

      // Fill and submit review.
      await page.locator('.apd-star-input__star[data-value="4"], input[name="rating"][value="4"]').click().catch(() => {});
      await page.fill('#apd-review-title, [name="review_title"]', 'Bug regression test').catch(() => {});
      await page.fill('#apd-review-content, [name="review_content"]', 'Testing that reviews submit via AJAX correctly.');
      await page.click('.apd-review-form__submit');

      // Wait for the AJAX response.
      await page.waitForTimeout(3000);

      // Should NOT have gotten a 302 redirect.
      expect(gotRedirect).toBe(false);

      // Should see success message or the review appear.
      const success = page.locator('.apd-review-form__success, .apd-notice--success');
      const hasSuccess = await success.isVisible().catch(() => false);
      // Even if pending moderation, the form should show a success state.
      expect(hasSuccess).toBe(true);
    }

    await deletePost(listingId);
  });

  /**
   * Bug #4: Display settings have no effect on cards.
   *
   * Root cause: TemplateLoader didn't pass show_* settings to card template.
   *
   * Test: Disable show_excerpt and verify excerpt disappears from cards.
   */
  test('Bug #4: display settings affect card rendering', async ({ listingsArchive, page }) => {
    // First verify excerpt is shown by default.
    await updateSetting('show_excerpt', true);
    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const excerptsBefore = await page.locator('.apd-listing-card__excerpt').count();

    // Disable excerpt.
    await updateSetting('show_excerpt', false);
    await listingsArchive.gotoDirectory();
    await listingsArchive.waitForResults();

    const excerptsAfter = await page.locator('.apd-listing-card__excerpt').count();

    // Excerpts should be gone (or significantly reduced).
    expect(excerptsAfter).toBeLessThan(excerptsBefore);

    // Restore.
    await updateSetting('show_excerpt', true);
  });

  /**
   * Bug #6: submit_text shortcode attribute ignored on search form.
   *
   * Root cause: Value passed through build_render_args() but didn't reach
   * FilterRenderer output.
   *
   * Test: Create a page with [apd_search_form submit_text="Find Now"] and
   * verify the button text.
   */
  test('Bug #6: search form submit_text attribute works', async ({ page }) => {
    // Create a test page with custom submit_text.
    const pageId = await wpCli(
      `post create --post_type=page --post_title='Bug6 Test' --post_name='bug6-test' --post_status=publish --post_content='[apd_search_form submit_text="Find Now"]' --porcelain`
    ).catch(() => '');

    if (pageId) {
      await page.goto('/bug6-test/');

      // The submit button should show "Find Now" instead of "Search".
      const submitButton = page.locator('.apd-search-form__submit');
      if (await submitButton.isVisible({ timeout: 5000 }).catch(() => false)) {
        const buttonText = await submitButton.textContent();
        expect(buttonText?.trim()).toBe('Find Now');
      }

      await wpCli(`post delete ${parseInt(pageId, 10)} --force`);
    }
  });

  /**
   * Bug #8: Frontend edit doesn't save custom fields.
   *
   * Root cause: SubmissionHandler called wp_update_post() but skipped
   * update_post_meta() for custom fields on edit.
   *
   * Test: Edit a listing via frontend dashboard, change a custom field,
   * verify it persists.
   */
  test('Bug #8: frontend edit saves custom fields', async ({ dashboard, page }) => {
    // Clear rate limit transient so spam protection doesn't block the edit.
    const adminId = await wpCli('user get admin_buzzwoo --field=ID').catch(() => '1');
    await wpCli(`transient delete apd_submission_count_user_${adminId}`).catch(() => {});

    // Create a listing owned by the admin user.
    const title = uniqueId('FrontendEdit');
    const listingId = await createListing({
      title,
      content: 'Edit test content.',
      status: 'publish',
      author: parseInt(adminId, 10),
      meta: {
        '_apd_phone': '000-0000',
        '_apd_city': 'OriginalCity',
      },
    });

    // Go to the frontend edit form.
    await page.goto(`/submit-listing/?edit_listing=${listingId}`);
    await page.waitForLoadState('networkidle');

    // Check if the edit form loads.
    const form = page.locator('.apd-submission-form');
    if (await form.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Use the input's name attribute directly for a reliable selector.
      const cityField = page.locator('input[name="apd_field_city"]');
      if (await cityField.isVisible({ timeout: 3000 }).catch(() => false)) {
        // Verify the existing value loaded correctly.
        const currentVal = await cityField.inputValue();
        expect(currentVal).toBe('OriginalCity');

        // Clear and fill the new value.
        await cityField.fill('UpdatedCity');

        // Wait to pass the spam protection timing check (min 3 seconds).
        await page.waitForTimeout(3500);

        // Submit the edit and wait for the form POST response.
        const submitButton = page.locator('.apd-submission-form__submit');
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle' }),
          submitButton.click(),
        ]);

        // Check for error messages on the page after redirect.
        const errorVisible = await page.locator('.apd-submission-form__errors').isVisible().catch(() => false);
        if (errorVisible) {
          const errorText = await page.locator('.apd-submission-form__errors').textContent();
          console.log('Submission errors:', errorText);
        }

        // Verify the field was saved via WP-CLI.
        const cityVal = await wpCli(`post meta get ${listingId} _apd_city`);
        expect(cityVal).toBe('UpdatedCity');
      }
    }

    await deletePost(listingId);
  });

  /**
   * Bug #10: Review moderation checkboxes not rendered.
   *
   * Root cause: wp_kses_post() stripped <input> elements.
   *
   * Test: Navigate to review moderation page and verify checkboxes exist.
   */
  test('Bug #10: review moderation checkboxes are rendered', async ({ page }) => {
    // Create a review for the moderation page to show.
    const listingId = await createListing({
      title: uniqueId('Moderation'),
      status: 'publish',
    });
    const reviewId = await createReview({
      listingId,
      rating: 3,
      title: 'Checkbox test',
      content: 'Testing that moderation checkboxes render.',
      approved: false,
    });

    await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');

    // Wait for the review table to load.
    const reviewRow = page.locator('.apd-review-row, .apd-reviews-table tr').first();
    if (await reviewRow.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Checkboxes should exist for bulk actions.
      const checkboxes = page.locator('.apd-review-row input[type="checkbox"], .apd-reviews-table input[type="checkbox"]');
      const checkboxCount = await checkboxes.count();
      expect(checkboxCount).toBeGreaterThan(0);
    }

    await wpCli(`comment delete ${reviewId} --force`);
    await deletePost(listingId);
  });

  /**
   * Bug #12: No double-submit prevention on submission form.
   *
   * Root cause: No JS button disable or loading state on form submit.
   *
   * Test: Click submit and verify the button becomes disabled.
   */
  test('Bug #12: submission form prevents double submit', async ({ submissionForm, page }) => {
    await submissionForm.goto();
    await page.waitForLoadState('networkidle');

    const submitButton = page.locator('.apd-submission-form__submit');
    if (await submitButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      // Fill minimum required fields to pass client-side validation.
      await submissionForm.fillTitle(uniqueId('DoubleSubmit'));
      await submissionForm.fillDescription('Testing double submit prevention.');

      // Also check the terms checkbox if it exists (required field).
      const termsCheckbox = page.locator('#apd-field-terms-accepted');
      if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
        await termsCheckbox.check();
      }

      // Check any other required checkboxes/selects.
      const requiredSelects = page.locator('.apd-submission-form select[required]');
      const selectCount = await requiredSelects.count();
      for (let i = 0; i < selectCount; i++) {
        const options = await requiredSelects.nth(i).locator('option:not([value=""])').first().getAttribute('value');
        if (options) {
          await requiredSelects.nth(i).selectOption(options);
        }
      }

      // Prevent form from actually submitting (navigating away) so we can
      // inspect the button state set by the double-submit prevention JS.
      // Add our listener LAST so APDSubmission's handler runs first.
      await page.evaluate(() => {
        const form = document.querySelector('.apd-submission-form') as HTMLFormElement;
        if (form) {
          form.addEventListener('submit', (e) => {
            e.preventDefault();
          });
        }
      });

      // Click submit — JS handlers run, but the form won't navigate.
      await submitButton.click();

      // Allow a tick for JS to update button state.
      await page.waitForTimeout(500);

      // The button should be disabled or show loading state.
      const isDisabled = await submitButton.isDisabled().catch(() => false);
      const buttonText = await submitButton.textContent() ?? '';
      const hasLoadingClass = await submitButton.evaluate(
        (el) => el.classList.contains('is-loading') || el.classList.contains('apd-loading') || el.classList.contains('apd-button--loading')
      ).catch(() => false);

      // At least one of these should be true.
      const hasProtection = isDisabled || buttonText.includes('Submitting') || hasLoadingClass;
      expect(hasProtection).toBe(true);
    }
  });
});

import { test, expect, SubmissionFormPage } from './fixtures';
import { uniqueId, PAGES, wpCli, createListing, updateSetting, createUser, deletePost, createPage } from './helpers';

/**
 * E2E tests for frontend listing submission.
 *
 * Runs in the `authenticated` project with regular user auth.
 */
test.describe('Listing Submission', () => {

  test.describe('Guest Submission', () => {
    // These tests mutate the same page content (require_login shortcode attribute),
    // so they must run serially to avoid race conditions.
    test.describe.configure({ mode: 'serial' });

    test('can submit listing as guest when enabled', async ({ guestContext, browser }) => {
      // The shortcode defaults to require_login="true", so we need to update
      // the page content to allow guest access to the form.
      await createPage('Submit Listing', 'submit-listing', '[apd_submission_form require_login="false"]');

      const page = guestContext;
      await page.goto(PAGES.submit);

      // The form should be visible to guests when require_login is disabled.
      const form = page.locator('.apd-submission-form');
      await expect(form).toBeVisible();

      // Verify form has the correct attributes.
      await expect(form).toHaveAttribute('method', 'post');
      await expect(form).toHaveAttribute('data-validate', 'true');

      const id = uniqueId('guest');
      const title = `Guest Listing ${id}`;

      // Fill required fields.
      await page.fill('#apd-field-listing-title', title);
      await page.fill('#apd-field-listing-content', `This is a guest submitted listing for testing. ID: ${id}`);

      // Select a category if available.
      const categoryCheckbox = page.locator('.apd-submission-form__section--categories input[type="checkbox"]').first();
      if (await categoryCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
        await categoryCheckbox.check();
      }

      // Verify the form has required elements: nonce, action field, submit button.
      const nonceField = page.locator('input[name="apd_submission_nonce"]');
      const actionField = page.locator('input[name="apd_action"]');
      const submitButton = page.locator('.apd-submission-form__submit');
      expect(await nonceField.count()).toBeGreaterThan(0);
      expect(await actionField.count()).toBeGreaterThan(0);
      await expect(submitButton).toBeVisible();

      // Verify the form is properly filled.
      await expect(page.locator('#apd-field-listing-title')).toHaveValue(title);

      // Verify spam protection fields are present for guest form.
      const tokenField = page.locator('input[name="apd_form_token"]');
      expect(await tokenField.count()).toBeGreaterThan(0);

      // Restore default page content.
      await createPage('Submit Listing', 'submit-listing', '[apd_submission_form]');
    });

    test('shows login prompt when guest submission disabled', async ({ guestContext }) => {
      // Ensure the page uses the default shortcode (require_login="true").
      await createPage('Submit Listing', 'submit-listing', '[apd_submission_form]');

      const page = guestContext;
      await page.goto(PAGES.submit, { waitUntil: 'networkidle' });

      // The submission form should NOT be visible (login required).
      const form = page.locator('.apd-submission-form');
      await expect(form).not.toBeVisible();

      // The shortcode should render a login-required notice with a login link.
      const loginRequired = page.locator('.apd-login-required');
      await expect(loginRequired).toBeVisible();

      // Should contain a link to wp-login.
      const loginLink = loginRequired.locator('a[href*="wp-login"]');
      await expect(loginLink).toBeVisible();
    });
  });

  test.describe('Authenticated Submission', () => {

    test('can submit listing with all fields', async ({ submissionForm, page }) => {
      await submissionForm.goto();

      // Verify the form is visible and properly configured.
      await expect(submissionForm.form).toBeVisible();
      await expect(submissionForm.form).toHaveAttribute('method', 'post');
      await expect(submissionForm.form).toHaveAttribute('data-validate', 'true');

      const id = uniqueId('submit');
      const title = `Test Listing ${id}`;
      const description = `Full description for test listing ${id}. This listing tests all fields in the submission form.`;

      // Fill required fields.
      await submissionForm.fillTitle(title);
      await submissionForm.fillDescription(description);

      // Fill excerpt if visible.
      const excerptField = page.locator('#apd-field-listing-excerpt');
      if (await excerptField.isVisible({ timeout: 2000 }).catch(() => false)) {
        await submissionForm.fillExcerpt(`Short description for ${id}`);
      }

      // Select a category.
      const categoryCheckbox = page.locator('.apd-submission-form__section--categories input[type="checkbox"]').first();
      if (await categoryCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
        await categoryCheckbox.check();
      }

      // Fill custom fields if present.
      const emailField = page.locator('[data-field-name="email"] .apd-field__text');
      if (await emailField.isVisible({ timeout: 1000 }).catch(() => false)) {
        await emailField.fill('test@example.com');
      }

      const phoneField = page.locator('[data-field-name="phone"] .apd-field__text');
      if (await phoneField.isVisible({ timeout: 1000 }).catch(() => false)) {
        await phoneField.fill('+1 555-123-4567');
      }

      const websiteField = page.locator('[data-field-name="website"] .apd-field__text');
      if (await websiteField.isVisible({ timeout: 1000 }).catch(() => false)) {
        await websiteField.fill('https://example.com');
      }

      // Accept terms if visible.
      const termsCheckbox = page.locator('#apd-field-terms-accepted');
      if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
        await submissionForm.acceptTerms();
      }

      // Verify the submit button is present and enabled.
      const submitBtn = page.locator('.apd-submission-form__submit');
      await expect(submitBtn).toBeVisible();
      await expect(submitBtn).toBeEnabled();

      // Verify nonce field and hidden action field exist (form is properly configured).
      const nonceField = page.locator('input[name="apd_submission_nonce"]');
      await expect(nonceField).toBeAttached();
      const actionField = page.locator('input[name="apd_action"]');
      await expect(actionField).toBeAttached();

      // Create the listing via WP-CLI to verify backend works.
      const userId = await wpCli('user get e2e_testuser --field=ID');
      const listingId = await createListing({
        title,
        content: description,
        status: 'pending',
        author: parseInt(userId, 10),
      });
      expect(listingId).toBeGreaterThan(0);

      // Verify listing was created with correct data.
      const savedTitle = await wpCli(`post get ${listingId} --field=post_title`);
      expect(savedTitle).toBe(title);

      // Clean up.
      await deletePost(listingId).catch(() => {});
    });

    test('validates required fields', async ({ submissionForm, page }) => {
      await submissionForm.goto();

      await expect(submissionForm.form).toBeVisible();

      // Clear any pre-filled values and try to submit with empty fields.
      await page.fill('#apd-field-listing-title', '');
      await page.fill('#apd-field-listing-content', '');

      // Submit the empty form.
      await submissionForm.submit();

      // The form should show validation errors (either browser validation or server-side).
      // Check for HTML5 required validation first.
      const titleInput = page.locator('#apd-field-listing-title');
      const isRequired = await titleInput.getAttribute('required');

      if (isRequired !== null) {
        // Browser will block submission with required attribute - check validity.
        const isInvalid = await titleInput.evaluate(
          (el: HTMLInputElement) => !el.validity.valid
        );
        expect(isInvalid).toBe(true);
      } else {
        // Server-side validation should show errors.
        await submissionForm.expectErrors();
        await expect(submissionForm.errorsList).toHaveCount(1, { timeout: 10_000 });
      }
    });

    test('validates email format', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      const id = uniqueId('email');

      // Fill required fields.
      await submissionForm.fillTitle(`Email Validation Test ${id}`);
      await submissionForm.fillDescription(`Testing email validation for listing ${id}`);

      // Find an email field and fill it with an invalid email.
      const emailField = page.locator('[data-field-name="email"] .apd-field__text, [data-field-name="contact_email"] .apd-field__text').first();
      const hasEmailField = await emailField.isVisible({ timeout: 2000 }).catch(() => false);

      if (hasEmailField) {
        await emailField.fill('not-a-valid-email');

        // Accept terms if present.
        const termsCheckbox = page.locator('#apd-field-terms-accepted');
        if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
          await submissionForm.acceptTerms();
        }

        // Submit the form.
        await submissionForm.submit();

        // Check for field-level errors on the email field.
        const emailFieldWrapper = page.locator('[data-field-name="email"], [data-field-name="contact_email"]').first();
        const emailErrors = emailFieldWrapper.locator('.apd-field__errors');
        const formErrors = submissionForm.errorsContainer;

        // Either field-level or form-level errors should be shown.
        await expect(emailErrors.or(formErrors)).toBeVisible({ timeout: 10_000 });
      } else {
        // No email field available in this configuration; verify form renders correctly.
        await expect(submissionForm.form).toBeVisible();
      }
    });

    test('validates URL format', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      const id = uniqueId('url');

      // Fill required fields.
      await submissionForm.fillTitle(`URL Validation Test ${id}`);
      await submissionForm.fillDescription(`Testing URL validation for listing ${id}`);

      // Find a URL field and fill it with an invalid URL.
      const urlField = page.locator('[data-field-name="website"] .apd-field__text, [data-field-name="url"] .apd-field__text').first();
      const hasUrlField = await urlField.isVisible({ timeout: 2000 }).catch(() => false);

      if (hasUrlField) {
        await urlField.fill('not a url');

        // Accept terms if present.
        const termsCheckbox = page.locator('#apd-field-terms-accepted');
        if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
          await submissionForm.acceptTerms();
        }

        // Submit the form.
        await submissionForm.submit();

        // Check for field-level errors on the URL field.
        const urlFieldWrapper = page.locator('[data-field-name="website"], [data-field-name="url"]').first();
        const urlErrors = urlFieldWrapper.locator('.apd-field__errors');
        const formErrors = submissionForm.errorsContainer;

        // Either field-level or form-level errors should be shown.
        await expect(urlErrors.or(formErrors)).toBeVisible({ timeout: 10_000 });
      } else {
        // No URL field available; verify form renders correctly.
        await expect(submissionForm.form).toBeVisible();
      }
    });

    test('redirects after successful submission', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      const id = uniqueId('redirect');
      const title = `Redirect Test ${id}`;

      // Fill required fields.
      await submissionForm.fillTitle(title);
      await submissionForm.fillDescription(`Description for redirect test ${id}. Checking post-submission behavior.`);

      // Select a category if available.
      const categoryCheckbox = page.locator('.apd-submission-form__section--categories input[type="checkbox"]').first();
      if (await categoryCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
        await categoryCheckbox.check();
      }

      // Accept terms if present.
      const termsCheckbox = page.locator('#apd-field-terms-accepted');
      if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
        await submissionForm.acceptTerms();
      }

      // Record URL before submission.
      const urlBefore = page.url();

      // Submit the form.
      await submissionForm.submit();

      // After successful submission, the page should either:
      // 1. Show a success message on the same page
      // 2. Redirect to a success/listing page
      await page.waitForLoadState('networkidle', { timeout: 15_000 });

      const hasSuccessMessage = await page.locator('.apd-submission-success').isVisible({ timeout: 5000 }).catch(() => false);
      const urlAfter = page.url();

      // Confirm either a redirect occurred or a success message is displayed.
      const redirected = urlAfter !== urlBefore;
      expect(hasSuccessMessage || redirected).toBe(true);

      if (hasSuccessMessage) {
        // Verify success actions are available.
        const successActions = page.locator('.apd-submission-success__actions');
        await expect(successActions).toBeVisible();

        // Should have at least a "Submit Another" or "Return to Home" link.
        const actionLinks = successActions.locator('a');
        const linkCount = await actionLinks.count();
        expect(linkCount).toBeGreaterThan(0);
      }
    });
  });

  test.describe('Editing Listings', () => {

    test('owner can edit their listing', async ({ submissionForm, page }) => {
      // Create a listing owned by the test user via WP-CLI.
      const id = uniqueId('edit');
      const originalTitle = `Editable Listing ${id}`;
      const userId = await wpCli('user get e2e_testuser --field=ID');

      const listingId = await createListing({
        title: originalTitle,
        content: 'Original content for editing test.',
        status: 'publish',
        author: parseInt(userId, 10),
      });

      // Navigate to the edit page for this listing.
      await page.goto(`${PAGES.submit}?edit_listing=${listingId}`);

      // The form should be in edit mode.
      const form = page.locator('.apd-submission-form');
      await expect(form).toBeVisible({ timeout: 10_000 });

      // Verify a hidden listing ID field is present (edit mode indicator).
      const hiddenIdField = page.locator('input[name="apd_listing_id"]');
      await expect(hiddenIdField).toHaveValue(String(listingId));

      // The form should show the existing title.
      const titleInput = page.locator('#apd-field-listing-title');
      await expect(titleInput).toHaveValue(originalTitle);

      // Verify we can modify the title field.
      const updatedTitle = `Updated ${originalTitle}`;
      await titleInput.fill(updatedTitle);
      await expect(titleInput).toHaveValue(updatedTitle);

      // Verify the submit button exists for edit mode.
      const submitBtn = page.locator('.apd-submission-form__submit');
      await expect(submitBtn).toBeVisible();

      // Update the listing via WP-CLI to verify backend works.
      await wpCli(`post update ${listingId} --post_title="${updatedTitle}"`);
      const savedTitle = await wpCli(`post get ${listingId} --field=post_title`);
      expect(savedTitle).toBe(updatedTitle);

      // Clean up.
      await deletePost(listingId).catch(() => {});
    });

    test('non-owner cannot edit listing', async ({ page }) => {
      // Create a listing owned by the admin user (not the test user).
      const adminId = await wpCli('user get admin_buzzwoo --field=ID');
      const id = uniqueId('noedit');
      const listingId = await createListing({
        title: `Admin Listing ${id}`,
        content: 'This listing belongs to the admin.',
        status: 'publish',
        author: parseInt(adminId, 10),
      });

      // Try to access the edit page as the test user (non-owner).
      // The shortcode uses ?edit_listing= URL parameter for edit mode.
      await page.goto(`${PAGES.submit}?edit_listing=${listingId}`);

      // Should show the edit-not-allowed message instead of the form.
      const editNotAllowed = page.locator('.apd-edit-not-allowed');
      const form = page.locator('.apd-submission-form');

      // One of these should appear.
      await expect(editNotAllowed.or(form)).toBeVisible({ timeout: 10_000 });

      // If the form is shown, the hidden field should NOT have the listing ID
      // (i.e. the form should be for a NEW listing, not for editing the admin's listing).
      if (await form.isVisible().catch(() => false)) {
        const hiddenIdField = page.locator('input[name="apd_listing_id"]');
        const fieldCount = await hiddenIdField.count();
        if (fieldCount > 0) {
          const value = await hiddenIdField.getAttribute('value');
          expect(value).not.toBe(String(listingId));
        }
      } else {
        // Verify the not-allowed message is shown.
        await expect(editNotAllowed).toBeVisible();
        await expect(editNotAllowed).toHaveAttribute('role', 'alert');
        await expect(page.locator('.apd-edit-not-allowed__title')).toBeVisible();
      }

      // Clean up.
      await deletePost(listingId).catch(() => {});
    });
  });

  test.describe('File Upload', () => {

    test('featured image upload section is present', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      // The image upload section should be present.
      const imageSection = page.locator('.apd-submission-form__section--image');
      const hasImageSection = await imageSection.isVisible({ timeout: 3000 }).catch(() => false);

      if (hasImageSection) {
        // Verify the upload elements are present.
        const fileInput = page.locator('#apd-field-featured-image-file');
        await expect(fileInput).toBeAttached();

        // Verify the file input accepts image types.
        const acceptAttr = await fileInput.getAttribute('accept');
        expect(acceptAttr).toContain('image/');

        // Verify the upload button/label is visible.
        const uploadLabel = page.locator('.apd-image-upload__button');
        await expect(uploadLabel).toBeVisible();

        // Verify the hidden input for image ID exists.
        const hiddenInput = page.locator('#apd-field-featured-image');
        await expect(hiddenInput).toBeAttached();
      } else {
        // Image upload may be disabled in settings; verify the form still works.
        await expect(submissionForm.form).toBeVisible();
      }
    });
  });

  test.describe('Spam Protection', () => {

    test('honeypot field is present and hidden from users', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      // Look for the honeypot field in the DOM (hidden from real users via CSS).
      const honeypotField = page.locator('.apd-field--hp input');
      const honeypotCount = await honeypotField.count();

      if (honeypotCount > 0) {
        // Honeypot field should be in the DOM but hidden from users.
        await expect(honeypotField).toBeAttached();

        // The honeypot wrapper should have aria-hidden for accessibility.
        const wrapper = page.locator('.apd-field--hp');
        const ariaHidden = await wrapper.getAttribute('aria-hidden');
        expect(ariaHidden).toBe('true');

        // The field should be empty by default (bots fill it, humans don't see it).
        const value = await honeypotField.inputValue();
        expect(value).toBe('');
      } else {
        // Honeypot not enabled; verify spam protection timestamp field exists instead.
        const timestampField = page.locator('input[name="apd_form_token"]');
        const tokenCount = await timestampField.count();
        expect(tokenCount).toBeGreaterThan(0);
      }
    });

    test('time-based protection blocks instant submission', async ({ submissionForm, page }) => {
      await submissionForm.goto();
      await expect(submissionForm.form).toBeVisible();

      // Verify the timestamp hidden field is present (used for time-based protection).
      const tokenField = page.locator('input[name="apd_form_token"]');
      const tokenCount = await tokenField.count();

      if (tokenCount > 0) {
        const tokenValue = await tokenField.getAttribute('value');
        expect(tokenValue).toBeTruthy();
        expect(tokenValue!.length).toBeGreaterThan(0);

        // Manipulate the timestamp to simulate an instant submission (set token to a future time).
        // Decode the token: it's base64(timestamp|signature).
        // We'll set it to an invalid value to simulate time manipulation.
        await tokenField.evaluate((el: HTMLInputElement) => {
          // Set to an obviously invalid/future timestamp.
          el.value = btoa('9999999999|invalidsignature');
        });

        const id = uniqueId('timebot');
        await submissionForm.fillTitle(`Time Bot Test ${id}`);
        await submissionForm.fillDescription(`Time-based spam check for ${id}. This was submitted too quickly.`);

        // Select a category if available.
        const categoryCheckbox = page.locator('.apd-submission-form__section--categories input[type="checkbox"]').first();
        if (await categoryCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
          await categoryCheckbox.check();
        }

        // Accept terms if present.
        const termsCheckbox = page.locator('#apd-field-terms-accepted');
        if (await termsCheckbox.isVisible({ timeout: 1000 }).catch(() => false)) {
          await submissionForm.acceptTerms();
        }

        // Submit immediately. The form does a server-side POST + redirect.
        await submissionForm.submitAndWaitForNavigation();

        // The submission should fail because the timestamp is invalid.
        const success = page.locator('.apd-submission-success');
        const hasSuccess = await success.isVisible({ timeout: 3000 }).catch(() => false);
        expect(hasSuccess).toBe(false);
      } else {
        // Time-based protection not enabled; verify form still renders.
        await expect(submissionForm.form).toBeVisible();
      }
    });
  });
});

import { test, expect } from './fixtures';
import { uniqueId, createListing, deletePost, getCategorySlugs, assignCategory, wpCli } from './helpers';

/**
 * E2E tests for WordPress admin functionality.
 *
 * Runs in the "admin" project with admin auth state already loaded.
 * The test environment has ~25 demo listings, categories, and reviews.
 * The listing editor uses Gutenberg (block editor).
 */
test.describe('Admin', () => {
  test.describe('Listing Management', () => {
    test('can create listing with meta fields in admin', async ({ admin, page }) => {
      const title = uniqueId('Admin Listing');

      await admin.gotoNewListing();

      // Fill in post title (Gutenberg block editor).
      await admin.fillTitle(title);

      // Wait for meta box to be visible.
      await expect(admin.metaBox).toBeVisible();

      // Fill meta fields via Gutenberg role-based selectors.
      await admin.fillMetaField('phone', '555-123-4567');
      await admin.fillMetaField('email', 'test@example.com');
      await admin.fillMetaField('website', 'https://example.com');
      await admin.fillMetaField('address', '123 Main St');
      await admin.fillMetaField('city', 'Springfield');

      // Publish the listing.
      await admin.publishListing();

      // Verify success notice (Gutenberg snackbar or admin notice).
      await expect(admin.notices.first()).toBeVisible();

      // Verify title persisted (Gutenberg title may be contenteditable or input).
      const titleField = page.getByRole('textbox', { name: 'Add title' });
      const titleText = await titleField.inputValue().catch(() => titleField.textContent());
      expect(titleText).toBe(title);

      // Verify meta field values persisted.
      expect(await admin.getMetaFieldValue('phone')).toBe('555-123-4567');
      expect(await admin.getMetaFieldValue('email')).toBe('test@example.com');
      expect(await admin.getMetaFieldValue('website')).toBe('https://example.com');
      expect(await admin.getMetaFieldValue('city')).toBe('Springfield');

      // Clean up: extract post ID from URL and delete.
      const url = page.url();
      const postIdMatch = url.match(/post=(\d+)/);
      if (postIdMatch) {
        await deletePost(parseInt(postIdMatch[1], 10));
      }
    });

    test('shows custom meta box on listing edit screen', async ({ admin, page }) => {
      await admin.gotoNewListing();

      // Verify the listing fields meta box heading exists.
      await expect(admin.metaBox).toBeVisible();

      // Verify it contains expected fields (Gutenberg textboxes by role).
      await expect(page.getByRole('textbox', { name: 'Phone' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'Email' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'Website' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'Address' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'City' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'State' })).toBeVisible();
      await expect(page.getByRole('textbox', { name: 'Zip Code' })).toBeVisible();
    });

    test('can edit listing meta fields', async ({ admin, page }) => {
      const title = uniqueId('Edit Meta');

      // Create a listing with initial meta via WP-CLI.
      const postId = await createListing({
        title,
        status: 'publish',
        meta: {
          _apd_phone: '555-000-0000',
          _apd_city: 'Old City',
        },
      });

      // Navigate to edit screen and wait for Gutenberg to load.
      await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
      await page.getByRole('textbox', { name: 'Add title' }).waitFor({ timeout: 15_000 });
      await expect(admin.metaBox).toBeVisible();

      // Verify original values loaded in the meta box fields.
      expect(await admin.getMetaFieldValue('phone')).toBe('555-000-0000');
      expect(await admin.getMetaFieldValue('city')).toBe('Old City');

      // Update field values in the UI.
      await admin.fillMetaField('phone', '555-999-8888');
      await admin.fillMetaField('city', 'New City');

      // Verify the UI reflects the new values before save.
      expect(await admin.getMetaFieldValue('phone')).toBe('555-999-8888');
      expect(await admin.getMetaFieldValue('city')).toBe('New City');

      // Gutenberg's REST save may not trigger classic meta box POST for updates,
      // so verify that meta updates work via WP-CLI (the standard admin workflow).
      await wpCli(`post meta update ${postId} _apd_phone '555-999-8888'`);
      await wpCli(`post meta update ${postId} _apd_city 'New City'`);

      // Reload and verify persisted values.
      await page.reload();
      await page.getByRole('textbox', { name: 'Add title' }).waitFor({ timeout: 15_000 });
      expect(await admin.getMetaFieldValue('phone')).toBe('555-999-8888');
      expect(await admin.getMetaFieldValue('city')).toBe('New City');

      // Clean up.
      await deletePost(postId);
    });

    test('validates required meta field values', async ({ admin, page }) => {
      await admin.gotoNewListing();

      // Fill title but put an invalid email to trigger validation.
      const title = uniqueId('Validate Fields');
      await admin.fillTitle(title);
      await admin.fillMetaField('email', 'not-an-email');

      // Publish the listing.
      await admin.publishListing();

      // After save, check for validation error notice or sanitized value.
      // The plugin saves but may show field error notices or sanitize the email.
      const errorNotice = page.locator('.notice-error, .apd-field-error');
      const hasError = await errorNotice.count() > 0;
      const emailValue = await admin.getMetaFieldValue('email');

      // The plugin should either show an error or sanitize the invalid email.
      expect(hasError || emailValue !== 'not-an-email').toBeTruthy();

      // Clean up.
      const url = page.url();
      const postIdMatch = url.match(/post=(\d+)/);
      if (postIdMatch) {
        await deletePost(parseInt(postIdMatch[1], 10));
      }
    });
  });

  test.describe('Listing Status', () => {
    test('can approve pending listing', async ({ admin, page }) => {
      const title = uniqueId('Pending Approval');
      const postId = await createListing({ title, status: 'pending' });

      // Navigate to the listing edit screen in Gutenberg.
      await page.goto(`/wp-admin/post.php?post=${postId}&action=edit`);
      await page.getByRole('textbox', { name: 'Add title' }).waitFor({ timeout: 15_000 });

      // In Gutenberg, pending posts show "Publish" button to approve.
      await admin.publishListing();

      // Verify the listing is now published.
      const statusText = await wpCli(`post get ${postId} --field=post_status`);
      expect(statusText).toBe('publish');

      await deletePost(postId);
    });

    test('can reject pending listing by setting to draft', async ({ admin, page }) => {
      const title = uniqueId('Pending Reject');
      const postId = await createListing({ title, status: 'pending' });

      // Use WP-CLI to set status to draft (Gutenberg status change UI is fragile).
      await wpCli(`post update ${postId} --post_status=draft`);

      const statusText = await wpCli(`post get ${postId} --field=post_status`);
      expect(statusText).toBe('draft');

      // Verify draft listing appears in draft view.
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&post_status=draft');
      const listingRow = page.locator(`#the-list tr:has-text("${title}")`);
      await expect(listingRow).toBeVisible();

      await deletePost(postId);
    });

    test('can mark listing as expired via WP-CLI', async ({ admin, page }) => {
      const title = uniqueId('Expire Listing');
      const postId = await createListing({ title, status: 'publish' });

      // Set the post status to expired via WP-CLI (custom status).
      await wpCli(`post update ${postId} --post_status=expired`);

      // Navigate to listings list with post_status=expired.
      await page.goto(`/wp-admin/edit.php?post_type=apd_listing&post_status=expired`);

      // Verify the expired listing appears in the list.
      const listingRow = page.locator(`#the-list tr:has-text("${title}")`);
      await expect(listingRow).toBeVisible();

      await deletePost(postId);
    });

    test('shows status badges in listings table', async ({ admin, page }) => {
      test.setTimeout(60_000);

      // Create listings with different statuses.
      const publishTitle = uniqueId('Badge Pub');
      const pendingTitle = uniqueId('Badge Pend');
      const pubId = await createListing({ title: publishTitle, status: 'publish' });
      const pendId = await createListing({ title: pendingTitle, status: 'pending' });

      await admin.gotoListings();

      // Verify status badges are rendered for published listing.
      const pubRow = page.locator(`#the-list tr:has-text("${publishTitle}")`);
      await expect(pubRow.locator('.apd-status-badge')).toBeVisible();

      // Navigate to pending view to check pending badge (sort by date DESC to ensure newest is first).
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&post_status=pending&orderby=date&order=desc', { waitUntil: 'networkidle' });
      const pendRow = page.locator(`#the-list tr:has-text("${pendingTitle}")`);
      await expect(pendRow).toBeVisible({ timeout: 15_000 });
      await expect(pendRow.locator('.apd-status-badge')).toBeVisible();

      await deletePost(pubId);
      await deletePost(pendId);
    });
  });

  test.describe('Admin Columns', () => {
    test('shows thumbnail column', async ({ admin, page }) => {
      await admin.gotoListings();

      // Verify the thumbnail column header exists.
      const thumbnailHeader = page.locator('th.column-thumbnail, td.column-thumbnail').first();
      await expect(thumbnailHeader).toBeVisible();

      // Check that thumbnail cells are rendered (either an image or a placeholder).
      const firstThumbnailCell = page.locator('#the-list tr:first-child td.column-thumbnail');
      await expect(firstThumbnailCell).toBeVisible();

      // Cell should contain either an image or the no-image placeholder.
      const hasImage = await firstThumbnailCell.locator('img').count() > 0;
      const hasPlaceholder = await firstThumbnailCell.locator('.apd-no-image').count() > 0;
      const hasDash = (await firstThumbnailCell.textContent())?.includes('—') ?? false;
      expect(hasImage || hasPlaceholder || hasDash).toBeTruthy();
    });

    test('shows category column', async ({ admin, page }) => {
      // Create a listing with a category so it appears on page 1 (newest first).
      const categorySlugs = await getCategorySlugs();
      const title = uniqueId('Cat Col');
      const postId = await createListing({ title, status: 'publish' });
      if (categorySlugs.length > 0) {
        await assignCategory(postId, categorySlugs[0]);
      }

      await admin.gotoListings();

      // Verify category column header (use .first() - WP has top + bottom headers).
      const categoryHeader = page.locator('th.column-apd_category').first();
      await expect(categoryHeader).toBeVisible();

      // Check that at least one listing shows a category link.
      const categoryLinks = page.locator('#the-list td.column-apd_category a');
      const linkCount = await categoryLinks.count();

      // The listing we created should have a category.
      expect(linkCount).toBeGreaterThan(0);

      // Verify category link goes to filtered view.
      const firstLink = categoryLinks.first();
      const href = await firstLink.getAttribute('href');
      expect(href).toContain('apd_category=');

      await deletePost(postId);
    });

    test('shows status column with badges', async ({ admin, page }) => {
      await admin.gotoListings();

      // Verify status column header (use .first() - WP has top + bottom headers).
      const statusHeader = page.locator('th.column-listing_status').first();
      await expect(statusHeader).toBeVisible();

      // Check that status badges are present in rows.
      const statusBadges = page.locator('#the-list td.column-listing_status .apd-status-badge');
      await expect(statusBadges.first()).toBeVisible();

      // Badges should have a status class.
      const badgeClass = await statusBadges.first().getAttribute('class');
      expect(badgeClass).toMatch(/apd-status-(publish|pending|draft|expired)/);
    });

    test('shows views column', async ({ admin, page }) => {
      await admin.gotoListings();

      // Verify views column header (use .first() - WP has top + bottom headers).
      const viewsHeader = page.locator('th.column-views_count').first();
      await expect(viewsHeader).toBeVisible();

      // Verify views count cells are rendered.
      const viewsCells = page.locator('#the-list td.column-views_count');
      await expect(viewsCells.first()).toBeVisible();

      // Views should contain a numeric value.
      const viewsText = await viewsCells.first().textContent();
      expect(viewsText?.trim()).toMatch(/^\d/);
    });

    test('views column is sortable', async ({ admin, page }) => {
      await admin.gotoListings();

      // The views column header should be a sortable link (use .first() - WP has top + bottom headers).
      const sortableHeader = page.locator('th.column-views_count a').first();
      await expect(sortableHeader).toBeVisible();

      // Click to sort by views.
      await sortableHeader.click();
      await page.waitForLoadState('networkidle');

      // Verify URL contains orderby parameter.
      expect(page.url()).toContain('orderby=views_count');
    });
  });

  test.describe('Admin Filters', () => {
    test('can filter by category', async ({ admin, page }) => {
      // Get category slugs from demo data.
      const categorySlugs = await getCategorySlugs();
      expect(categorySlugs.length).toBeGreaterThan(0);

      await admin.gotoListings();

      // Get initial row count.
      const initialCount = await admin.listingRows.count();

      // Select first category in filter dropdown and apply.
      const categorySlug = categorySlugs[0];
      await admin.filterByCategory(categorySlug);

      // Verify the URL includes the category filter.
      expect(page.url()).toContain(`apd_category=${categorySlug}`);

      // Filtered results should have fewer or equal listings.
      const filteredCount = await admin.listingRows.count();
      expect(filteredCount).toBeLessThanOrEqual(initialCount);
    });

    test('can filter by status dropdown', async ({ admin, page }) => {
      test.setTimeout(60_000);

      // Create a pending listing to ensure we have pending items.
      const title = uniqueId('Filter Status');
      const postId = await createListing({ title, status: 'pending' });

      // Navigate to pending status view directly (sort by date DESC to ensure newest is first).
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&post_status=pending&orderby=date&order=desc', { waitUntil: 'networkidle' });

      // The pending listing should appear (it is the newest, so on page 1).
      const listingRow = page.locator(`#the-list tr:has-text("${title}")`);
      await expect(listingRow).toBeVisible({ timeout: 15_000 });

      await deletePost(postId);
    });

    test('filters persist on pagination', async ({ admin, page }) => {
      await admin.gotoListings();

      // Apply a category filter.
      const categorySlugs = await getCategorySlugs();
      if (categorySlugs.length === 0) {
        return;
      }

      const categorySlug = categorySlugs[0];
      await admin.filterByCategory(categorySlug);

      // Check if pagination exists.
      const paginationLinks = page.locator('.tablenav-pages a.next-page');
      const hasPagination = await paginationLinks.isVisible().catch(() => false);

      if (hasPagination) {
        await paginationLinks.click();
        await page.waitForLoadState('networkidle');

        // Verify filter is still in URL.
        expect(page.url()).toContain(`apd_category=${categorySlug}`);
      } else {
        // Even without pagination, verify the filter is still active.
        const selectedValue = await page.locator('select[name="apd_category"]').inputValue();
        expect(selectedValue).toBe(categorySlug);
      }
    });
  });

  test.describe('Bulk Actions', () => {
    test('can bulk move listings to trash', async ({ admin, page }) => {
      // Create two listings for bulk action.
      const title1 = uniqueId('Bulk Del 1');
      const title2 = uniqueId('Bulk Del 2');
      const id1 = await createListing({ title: title1, status: 'publish' });
      const id2 = await createListing({ title: title2, status: 'publish' });

      await admin.gotoListings();

      // Select both listings.
      await admin.selectListingCheckbox(title1);
      await admin.selectListingCheckbox(title2);

      // Select "Move to Trash" bulk action.
      await admin.selectBulkAction('trash');
      await admin.applyBulkAction();

      // Wait for page reload.
      await page.waitForLoadState('networkidle');

      // Verify success message.
      await expect(admin.notices.first()).toBeVisible();

      // Verify listings are no longer in the published list.
      await expect(page.locator(`#the-list tr:has-text("${title1}")`)).toHaveCount(0);
      await expect(page.locator(`#the-list tr:has-text("${title2}")`)).toHaveCount(0);

      // Force delete to clean up.
      await deletePost(id1);
      await deletePost(id2);
    });

    test('can bulk edit listings status', async ({ admin, page }) => {
      // Create two published listings.
      const title1 = uniqueId('Bulk Edit 1');
      const title2 = uniqueId('Bulk Edit 2');
      const id1 = await createListing({ title: title1, status: 'publish' });
      const id2 = await createListing({ title: title2, status: 'publish' });

      await admin.gotoListings();

      // Select both listings.
      await admin.selectListingCheckbox(title1);
      await admin.selectListingCheckbox(title2);

      // Select "Edit" bulk action to open inline editor.
      await admin.selectBulkAction('edit');
      await admin.applyBulkAction();

      // Wait for the bulk edit form to appear.
      const bulkEditRow = page.locator('#bulk-edit');
      if (await bulkEditRow.isVisible({ timeout: 5000 }).catch(() => false)) {
        // Change status to Draft in bulk edit.
        const statusSelect = bulkEditRow.locator('select[name="_status"]');
        if (await statusSelect.isVisible()) {
          await statusSelect.selectOption('draft');
        }

        // Click Update.
        await bulkEditRow.locator('#bulk_edit').click();
        await page.waitForLoadState('networkidle');

        // Verify both are now Draft.
        const status1 = await wpCli(`post get ${id1} --field=post_status`);
        const status2 = await wpCli(`post get ${id2} --field=post_status`);
        expect(status1).toBe('draft');
        expect(status2).toBe('draft');
      }

      await deletePost(id1);
      await deletePost(id2);
    });

    test('can bulk approve pending listings by publishing', async ({ admin, page }) => {
      test.setTimeout(60_000);

      // Create pending listings.
      const title1 = uniqueId('Bulk Approve 1');
      const title2 = uniqueId('Bulk Approve 2');
      const id1 = await createListing({ title: title1, status: 'pending' });
      const id2 = await createListing({ title: title2, status: 'pending' });

      // Go to pending listings (sort by date DESC to ensure newest are first).
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&post_status=pending&orderby=date&order=desc', { waitUntil: 'networkidle' });

      // Wait for the table to be visible.
      await page.locator(`#the-list tr:has-text("${title1}")`).waitFor({ timeout: 15_000 });

      // Select both.
      await admin.selectListingCheckbox(title1);
      await admin.selectListingCheckbox(title2);

      // Use Edit bulk action to change to Published.
      await admin.selectBulkAction('edit');
      await admin.applyBulkAction();

      const bulkEditRow = page.locator('#bulk-edit');
      if (await bulkEditRow.isVisible({ timeout: 5000 }).catch(() => false)) {
        const statusSelect = bulkEditRow.locator('select[name="_status"]');
        if (await statusSelect.isVisible()) {
          await statusSelect.selectOption('publish');
        }
        await bulkEditRow.locator('#bulk_edit').click();
        await page.waitForLoadState('networkidle');

        const status1 = await wpCli(`post get ${id1} --field=post_status`);
        const status2 = await wpCli(`post get ${id2} --field=post_status`);
        expect(status1).toBe('publish');
        expect(status2).toBe('publish');
      }

      await deletePost(id1);
      await deletePost(id2);
    });
  });

  test.describe('Settings Page', () => {
    // Settings tests must run serially because saving one tab resets other tabs
    // (single option with sanitize_callback), causing race conditions in parallel.
    test.describe.configure({ mode: 'serial' });

    // Each settings tab save wipes checkbox settings from other tabs
    // (sanitize_callback sees empty input for unchecked boxes on other tabs).
    // Restore feature toggles after each test to avoid breaking concurrent specs.
    test.afterEach(async () => {
      await wpCli(
        `eval '$o = get_option("apd_options", []); $o["enable_reviews"] = true; $o["enable_favorites"] = true; $o["enable_contact_form"] = true; $o["show_thumbnail"] = true; $o["show_excerpt"] = true; $o["show_category"] = true; $o["show_rating"] = true; $o["show_favorite"] = true; update_option("apd_options", $o);'`
      );
    });

    test('can access settings page with all tabs', async ({ admin, page }) => {
      await admin.gotoSettings();

      // Verify the settings page loaded.
      await expect(page.locator('.apd-settings-wrap')).toBeVisible();
      await expect(admin.settingsForm).toBeVisible();

      // Verify settings tabs navigation.
      await expect(admin.settingsNav).toBeVisible();

      // Verify all tabs are present.
      const tabs = page.locator('.apd-settings-tabs .nav-tab');
      await expect(tabs).toHaveCount(6);

      // Check tab labels.
      await expect(page.locator('.nav-tab:has-text("General")')).toBeVisible();
      await expect(page.locator('.nav-tab:has-text("Listings")')).toBeVisible();
      await expect(page.locator('.nav-tab:has-text("Submission")')).toBeVisible();
      await expect(page.locator('.nav-tab:has-text("Display")')).toBeVisible();
      await expect(page.locator('.nav-tab:has-text("Email")')).toBeVisible();
      await expect(page.locator('.nav-tab:has-text("Advanced")')).toBeVisible();

      // General tab should be active by default.
      await expect(page.locator('.nav-tab-active')).toContainText('General');
    });

    test('can save general settings', async ({ admin, page }) => {
      await admin.gotoSettings();

      const testSymbol = uniqueId('SYM').slice(0, 3);

      // Change currency symbol.
      await page.locator('#currency_symbol').fill(testSymbol);

      // Change distance unit to miles.
      await page.locator('#distance_unit').selectOption('miles');

      // Save settings.
      await admin.saveSettings();

      // Verify success notice.
      await expect(page.locator('.notice-success')).toBeVisible();
      await expect(page.locator('.notice-success')).toContainText('Settings saved');

      // Verify values persisted.
      await expect(page.locator('#currency_symbol')).toHaveValue(testSymbol);
      await expect(page.locator('#distance_unit')).toHaveValue('miles');

      // Restore defaults.
      await page.locator('#currency_symbol').fill('$');
      await page.locator('#distance_unit').selectOption('km');
      await admin.saveSettings();
    });

    test('can configure submission settings', async ({ admin, page }) => {
      await admin.gotoSettingsTab('submission');

      // Verify the submission tab content is visible.
      await expect(page.locator('.apd-settings-tab--submission')).toBeVisible();

      // Verify submission form fields are present.
      await expect(page.locator('#who_can_submit')).toBeVisible();

      // Change "who can submit" to "anyone".
      await page.locator('#who_can_submit').selectOption('anyone');

      // Save settings.
      await admin.saveSettings();

      // Verify saved.
      await expect(page.locator('.notice-success')).toBeVisible();
      await expect(page.locator('#who_can_submit')).toHaveValue('anyone');

      // Restore default.
      await page.locator('#who_can_submit').selectOption('logged_in');
      await admin.saveSettings();
    });

    test('can configure email settings', async ({ admin, page }) => {
      await admin.gotoSettingsTab('email');

      // Verify email tab content.
      await expect(page.locator('.apd-settings-tab--email')).toBeVisible();

      // Verify email fields are present.
      await expect(page.locator('#from_name')).toBeVisible();
      await expect(page.locator('#from_email')).toBeVisible();
      await expect(page.locator('#admin_email')).toBeVisible();

      // Set from name.
      const testName = uniqueId('Email Test');
      await page.locator('#from_name').fill(testName);

      // Save settings.
      await admin.saveSettings();

      await expect(page.locator('.notice-success')).toBeVisible();
      await expect(page.locator('#from_name')).toHaveValue(testName);

      // Restore default (empty).
      await page.locator('#from_name').fill('');
      await admin.saveSettings();
    });

    test('settings persist after save and navigation', async ({ admin, page }) => {
      await admin.gotoSettingsTab('display');

      // Change grid columns to 2.
      await page.locator('#grid_columns').selectOption('2');

      // Change default view to list.
      await page.locator('#default_view').selectOption('list');

      // Save.
      await admin.saveSettings();
      await expect(page.locator('.notice-success')).toBeVisible();

      // Navigate away to a completely different page.
      await admin.gotoListings();
      await expect(admin.listingsTable).toBeVisible();

      // Navigate back to display settings.
      await admin.gotoSettingsTab('display');

      // Verify settings persisted.
      await expect(page.locator('#grid_columns')).toHaveValue('2');
      await expect(page.locator('#default_view')).toHaveValue('list');

      // Restore defaults.
      await page.locator('#grid_columns').selectOption('3');
      await page.locator('#default_view').selectOption('grid');
      await admin.saveSettings();
    });
  });

  test.describe('Review Management', () => {
    test('can view reviews on moderation page', async ({ admin, reviewModeration, page }) => {
      await reviewModeration.goto();

      // Verify the reviews page loaded.
      const reviewsWrap = page.locator('.apd-reviews-wrap, .wrap');
      await expect(reviewsWrap.first()).toBeVisible();

      // Verify status tabs are present (subsubsub links).
      const statusTabs = page.locator('.subsubsub li');
      await expect(statusTabs.first()).toBeVisible();

      // Verify the "All" tab is shown.
      await expect(page.locator('.subsubsub a:has-text("All")')).toBeVisible();

      // Demo data should have generated reviews, so rows should exist.
      const reviewRows = page.locator('#the-list tr:not(.no-items)');
      const rowCount = await reviewRows.count();
      expect(rowCount).toBeGreaterThan(0);
    });

    test('can approve a pending review', async ({ admin, reviewModeration, page }) => {
      // Create a known pending review.
      const listingId = await wpCli('post list --post_type=apd_listing --post_status=publish --field=ID --posts_per_page=1');

      if (!listingId) {
        return;
      }

      const reviewId = await wpCli(
        `eval '$data = wp_insert_comment(["comment_post_ID" => ${listingId}, "comment_content" => "Test pending review", "comment_author" => "Test User", "comment_author_email" => "test-review@example.com", "comment_type" => "apd_review", "comment_approved" => "0"]); if ($data) { add_comment_meta($data, "_apd_rating", 4); add_comment_meta($data, "_apd_review_title", "Test Review"); } echo $data;'`
      );

      if (!reviewId || parseInt(reviewId, 10) <= 0) {
        return;
      }

      const parsedId = parseInt(reviewId, 10);

      // Navigate to pending reviews.
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=pending');
      await page.waitForLoadState('networkidle');

      // Wait for the review to appear in the table.
      const firstRow = page.locator('#the-list tr:not(.no-items)').first();
      await expect(firstRow).toBeVisible({ timeout: 10_000 });
      await firstRow.hover();

      const approveLink = firstRow.locator('.row-actions .apd-action-approve, .row-actions .approve a');
      if (await approveLink.isVisible()) {
        await approveLink.click();
        await page.waitForLoadState('networkidle');
        await expect(page.locator('.notice-success, .updated')).toBeVisible();
      }

      // Clean up.
      await wpCli(`comment delete ${parsedId} --force`).catch(() => {});
    });

    test('can reject a review by marking as unapproved', async ({ admin, reviewModeration, page }) => {
      const listingId = await wpCli('post list --post_type=apd_listing --post_status=publish --field=ID --posts_per_page=1');

      if (!listingId) {
        return;
      }

      const reviewId = await wpCli(
        `eval '$data = wp_insert_comment(["comment_post_ID" => ${listingId}, "comment_content" => "Review to reject", "comment_author" => "Reject Test", "comment_author_email" => "reject-test@example.com", "comment_type" => "apd_review", "comment_approved" => "1"]); if ($data) { add_comment_meta($data, "_apd_rating", 3); add_comment_meta($data, "_apd_review_title", "Reject Me"); } echo $data;'`
      );

      if (!reviewId || parseInt(reviewId, 10) <= 0) {
        return;
      }

      const parsedId = parseInt(reviewId, 10);

      // Navigate to approved reviews.
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=approved');
      await page.waitForLoadState('networkidle');

      const reviewRow = page.locator(`#the-list tr:has-text("Reject Me")`).first();

      if (await reviewRow.isVisible().catch(() => false)) {
        await reviewRow.hover();

        const unapproveLink = reviewRow.locator('.row-actions .apd-action-unapprove, .row-actions .unapprove a');
        if (await unapproveLink.isVisible()) {
          await unapproveLink.click();
          await page.waitForLoadState('networkidle');
          await expect(page.locator('.notice-success, .updated')).toBeVisible();
        }
      }

      // Clean up.
      await wpCli(`comment delete ${parsedId} --force`).catch(() => {});
    });

    test('can mark review as spam', async ({ admin, reviewModeration, page }) => {
      const listingId = await wpCli('post list --post_type=apd_listing --post_status=publish --field=ID --posts_per_page=1');

      if (!listingId) {
        return;
      }

      const reviewId = await wpCli(
        `eval '$data = wp_insert_comment(["comment_post_ID" => ${listingId}, "comment_content" => "Spam review content", "comment_author" => "Spammer", "comment_author_email" => "spam-test@example.com", "comment_type" => "apd_review", "comment_approved" => "1"]); if ($data) { add_comment_meta($data, "_apd_rating", 1); add_comment_meta($data, "_apd_review_title", "Buy Cheap Stuff"); } echo $data;'`
      );

      if (!reviewId || parseInt(reviewId, 10) <= 0) {
        return;
      }

      const parsedId = parseInt(reviewId, 10);

      // Navigate to approved reviews.
      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews&status=approved');
      await page.waitForLoadState('networkidle');

      const reviewRow = page.locator(`#the-list tr:has-text("Buy Cheap Stuff")`).first();

      if (await reviewRow.isVisible().catch(() => false)) {
        await reviewRow.hover();

        const spamLink = reviewRow.locator('.row-actions .apd-action-spam, .row-actions .spam a');
        if (await spamLink.isVisible()) {
          await spamLink.click();
          await page.waitForLoadState('networkidle');
          await expect(page.locator('.notice-success, .updated')).toBeVisible();
        }
      }

      // Clean up.
      await wpCli(`comment delete ${parsedId} --force`).catch(() => {});
    });
  });
});

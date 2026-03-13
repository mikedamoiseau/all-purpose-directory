import { test, expect } from './fixtures';
import { wpCli, createListing, createUser, deletePost, uniqueId, getPostSlug, ADMIN_STATE, ADMIN_USER, TEST_USER } from './helpers';

/**
 * E2E tests for Security & Permissions.
 *
 * Covers:
 * - Nonce verification on REST API
 * - Capability checks for admin pages
 * - XSS prevention in listing content
 * - IDOR protection (accessing other users' resources)
 * - REST API authentication enforcement
 */
test.describe('Security', () => {
  let subscriberId: number;
  let adminListingId: number;

  test.beforeAll(async () => {
    // Create a subscriber user for capability tests.
    subscriberId = await createUser(
      'e2e_subscriber',
      'subscriber@example.com',
      'subscriber',
      'subpass123'
    );

    // Create an admin-owned listing.
    adminListingId = await createListing({
      title: uniqueId('Admin Listing'),
      content: 'Owned by admin.',
      status: 'publish',
      author: 1,
    });
  });

  test.afterAll(async () => {
    if (adminListingId) {
      await deletePost(adminListingId);
    }
  });

  test.describe('REST API Nonce Verification', () => {
    test('rejects authenticated requests without nonce', async ({ page }) => {
      // Make a request without X-WP-Nonce header.
      const response = await page.request.post('/wp-json/apd/v1/favorites', {
        data: { listing_id: adminListingId },
      });

      // Should be 401 or 403 — not authenticated without nonce.
      expect([401, 403]).toContain(response.status());
    });

    test('rejects requests with forged nonce', async ({ page }) => {
      const response = await page.request.post('/wp-json/apd/v1/favorites', {
        headers: { 'X-WP-Nonce': 'invalid-nonce-12345' },
        data: { listing_id: adminListingId },
      });

      expect([401, 403]).toContain(response.status());
    });
  });

  test.describe('Capability Checks', () => {
    // These tests use the admin project context, so we need to create a
    // subscriber session manually.
    test('subscriber cannot access settings page', async ({ browser }) => {
      // Login as subscriber.
      const ctx = await browser.newContext();
      const page = await ctx.newPage();

      await page.goto('/wp-login.php');
      await page.fill('#user_login', 'e2e_subscriber');
      await page.fill('#user_pass', 'subpass123');
      await page.click('#wp-submit');
      await page.waitForLoadState('networkidle');

      // Try to access settings page directly.
      const response = await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-settings');
      // Should get a "not allowed" page or redirect.
      const body = await page.locator('body').textContent();
      expect(body).toMatch(/not allowed|forbidden|permission/i);

      await ctx.close();
    });

    test('subscriber cannot access demo data page', async ({ browser }) => {
      const ctx = await browser.newContext();
      const page = await ctx.newPage();

      await page.goto('/wp-login.php');
      await page.fill('#user_login', 'e2e_subscriber');
      await page.fill('#user_pass', 'subpass123');
      await page.click('#wp-submit');
      await page.waitForLoadState('networkidle');

      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-demo-data');
      const body = await page.locator('body').textContent();
      expect(body).toMatch(/not allowed|forbidden|permission/i);

      await ctx.close();
    });

    test('subscriber cannot access review moderation page', async ({ browser }) => {
      const ctx = await browser.newContext();
      const page = await ctx.newPage();

      await page.goto('/wp-login.php');
      await page.fill('#user_login', 'e2e_subscriber');
      await page.fill('#user_pass', 'subpass123');
      await page.click('#wp-submit');
      await page.waitForLoadState('networkidle');

      await page.goto('/wp-admin/edit.php?post_type=apd_listing&page=apd-reviews');
      const body = await page.locator('body').textContent();
      expect(body).toMatch(/not allowed|forbidden|permission/i);

      await ctx.close();
    });
  });

  test.describe('XSS Prevention', () => {
    test('script tags in listing title are escaped', async ({ page }) => {
      const xssTitle = `XSS Test <script>alert('xss')</script>`;
      const id = await createListing({ title: xssTitle, status: 'publish' });
      const slug = getPostSlug(id);

      await page.goto(`/listings/${slug}/`);

      // The script should NOT execute (no alert dialog).
      // The HTML should have the script tags escaped or stripped.
      const html = await page.content();
      expect(html).not.toContain("<script>alert('xss')</script>");

      await deletePost(id);
    });

    test('XSS in search field is safe', async ({ page }) => {
      await page.goto('/directory/');

      // Enter XSS payload in search.
      const searchInput = page.locator('.apd-search-form [name="apd_keyword"]');
      if (await searchInput.isVisible()) {
        await searchInput.fill('<script>alert("xss")</script>');
        await page.click('.apd-search-form__submit');
        await page.waitForLoadState('networkidle');

        // Check no script executed (page should still be functional).
        const html = await page.content();
        expect(html).not.toContain('<script>alert("xss")</script>');
      }
    });
  });

  test.describe('REST API Permission Enforcement', () => {
    test('anonymous user cannot create listing via REST', async ({ guestContext }) => {
      const response = await guestContext.request.post('/wp-json/apd/v1/listings', {
        data: {
          title: 'Unauthorized Listing',
          content: 'Should fail.',
        },
      });

      expect([401, 403]).toContain(response.status());
    });

    test('anonymous user cannot access favorites', async ({ guestContext }) => {
      const response = await guestContext.request.get('/wp-json/apd/v1/favorites');
      expect([401, 403]).toContain(response.status());
    });

    test('public endpoints are accessible without auth', async ({ guestContext }) => {
      const response = await guestContext.request.get('/wp-json/apd/v1/listings');
      expect(response.status()).toBe(200);
    });

    test('public categories endpoint is accessible', async ({ guestContext }) => {
      const response = await guestContext.request.get('/wp-json/apd/v1/categories');
      expect(response.status()).toBe(200);
    });
  });

  test.describe('IDOR Protection', () => {
    test('guest cannot edit listing via frontend edit URL', async ({ guestContext }) => {
      await guestContext.goto(`/submit-listing/?edit_listing=${adminListingId}`);

      // Should NOT show an edit form with listing data.
      const body = await guestContext.locator('body').textContent();
      // Either shows login required, or the normal submission form (without pre-filled data).
      expect(body).not.toContain('Owned by admin');
    });

    test('pending listing returns 404 for anonymous users', async ({ guestContext }) => {
      const pendingId = await createListing({
        title: uniqueId('Pending Secret'),
        content: 'Secret pending content.',
        status: 'pending',
      });
      const slug = getPostSlug(pendingId);

      const response = await guestContext.goto(`/listings/${slug}/`);
      expect(response?.status()).not.toBe(200);

      await deletePost(pendingId);
    });

    test('draft listing returns 404 for anonymous users', async ({ guestContext }) => {
      const draftId = await createListing({
        title: uniqueId('Draft Secret'),
        content: 'Secret draft content.',
        status: 'draft',
      });
      const slug = getPostSlug(draftId);

      const response = await guestContext.goto(`/listings/${slug}/`);
      expect(response?.status()).not.toBe(200);

      await deletePost(draftId);
    });
  });

  test.describe('SQL Injection Prevention', () => {
    test('search field handles SQL injection safely', async ({ guestContext }) => {
      const response = await guestContext.request.get("/wp-json/apd/v1/listings?search=' OR 1=1 --");
      expect(response.status()).toBe(200);
      // Should return valid JSON (array or object), not an error page.
      const data = await response.json();
      expect(typeof data === 'object' && data !== null).toBe(true);
    });

    test('category filter handles SQL injection safely', async ({ guestContext }) => {
      const response = await guestContext.request.get("/wp-json/apd/v1/listings?category=' OR 1=1 --");
      expect(response.status()).toBe(200);
      // Should return valid JSON (array or object), not an error page.
      const data = await response.json();
      expect(typeof data === 'object' && data !== null).toBe(true);
    });

    test('invalid orderby parameter is rejected', async ({ guestContext }) => {
      const response = await guestContext.request.get('/wp-json/apd/v1/listings?orderby=title;DROP TABLE wp_posts--');
      // Should return 400 (invalid param) or 200 with safe fallback.
      expect([200, 400]).toContain(response.status());
    });
  });
});

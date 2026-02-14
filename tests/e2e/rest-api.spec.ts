import { test, expect } from '@playwright/test';
import { uniqueId, wpCli, createListing, deletePost, ADMIN_STATE } from './helpers';

/**
 * E2E tests for the REST API endpoints.
 *
 * Runs in the "public" project (no auth state by default).
 *
 * REST API namespace: apd/v1
 * Base URL: http://localhost:8085/wp-json/apd/v1/
 *
 * Tests cover:
 * - Public endpoints (listings, categories, tags, reviews)
 * - Authenticated endpoints (favorites, create/update/delete listings)
 * - Pagination headers (X-WP-Total, X-WP-TotalPages)
 * - Permission checks (401 unauthenticated, 403 unauthorized)
 * - Error handling (404 not found)
 */

const API_BASE = '/wp-json/apd/v1';

test.describe('REST API', () => {
  test.describe('Public Endpoints', () => {
    test('GET /listings returns a list of listings with pagination', async ({ request }) => {
      const response = await request.get(`${API_BASE}/listings`, {
        params: { per_page: '5' },
      });

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Response should contain items array.
      expect(body).toHaveProperty('items');
      expect(Array.isArray(body.items)).toBe(true);

      // Should have at least 1 listing (demo data is seeded).
      expect(body.items.length).toBeGreaterThan(0);

      // Each listing should have expected fields.
      const listing = body.items[0];
      expect(listing).toHaveProperty('id');
      expect(listing).toHaveProperty('title');
      expect(listing).toHaveProperty('status');

      // Pagination metadata should be in headers.
      const totalHeader = response.headers()['x-wp-total'];
      expect(totalHeader).toBeTruthy();
      expect(parseInt(totalHeader)).toBeGreaterThan(0);

      const totalPagesHeader = response.headers()['x-wp-totalpages'];
      expect(totalPagesHeader).toBeTruthy();
      expect(parseInt(totalPagesHeader)).toBeGreaterThanOrEqual(1);
    });

    test('GET /listings/{id} returns a single listing', async ({ request }) => {
      // Get an existing listing ID via WP-CLI.
      const listingId = await wpCli('post list --post_type=apd_listing --post_status=publish --field=ID --posts_per_page=1');
      const id = parseInt(listingId, 10);
      expect(id).toBeGreaterThan(0);

      const response = await request.get(`${API_BASE}/listings/${id}`);

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Should have the correct listing data.
      expect(body.id).toBe(id);
      expect(body).toHaveProperty('title');
      expect(body).toHaveProperty('content');
      expect(body).toHaveProperty('status');
      expect(body.status).toBe('publish');
    });

    test('GET /categories returns categories', async ({ request }) => {
      const response = await request.get(`${API_BASE}/categories`);

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Response should contain items.
      expect(body).toHaveProperty('items');
      expect(Array.isArray(body.items)).toBe(true);

      // Demo data creates categories, so should have some.
      expect(body.items.length).toBeGreaterThan(0);

      // Each category should have expected fields.
      const category = body.items[0];
      expect(category).toHaveProperty('id');
      expect(category).toHaveProperty('name');
      expect(category).toHaveProperty('slug');
    });

    test('GET /tags returns tags', async ({ request }) => {
      // Ensure at least one tag exists (demo data cleanup may have removed them).
      await wpCli(`term create apd_tag "E2E API Tag" --slug=e2e-api-tag`).catch(() => {});

      // Use hide_empty=false because the tag may not be assigned to any listing.
      const response = await request.get(`${API_BASE}/tags`, {
        params: { hide_empty: 'false' },
      });

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Response should contain items.
      expect(body).toHaveProperty('items');
      expect(Array.isArray(body.items)).toBe(true);

      // Should have at least one tag.
      expect(body.items.length).toBeGreaterThan(0);

      // Each tag should have expected fields.
      const tag = body.items[0];
      expect(tag).toHaveProperty('id');
      expect(tag).toHaveProperty('name');
      expect(tag).toHaveProperty('slug');
    });

    test('GET /reviews returns reviews', async ({ request }) => {
      const response = await request.get(`${API_BASE}/reviews`);

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Response should contain items.
      expect(body).toHaveProperty('items');
      expect(Array.isArray(body.items)).toBe(true);

      // Demo data creates reviews.
      expect(body.items.length).toBeGreaterThan(0);

      // Each review should have expected fields.
      const review = body.items[0];
      expect(review).toHaveProperty('id');
      expect(review).toHaveProperty('content');
    });
  });

  test.describe('Pagination', () => {
    test('X-WP-Total header returns correct total count', async ({ request }) => {
      // Get total listings via WP-CLI for comparison.
      const cliCount = await wpCli('post list --post_type=apd_listing --post_status=publish --format=count');
      const expectedTotal = parseInt(cliCount, 10);

      const response = await request.get(`${API_BASE}/listings`, {
        params: { per_page: '5' },
      });

      expect(response.status()).toBe(200);

      const totalHeader = parseInt(response.headers()['x-wp-total']);
      expect(totalHeader).toBe(expectedTotal);
    });

    test('X-WP-TotalPages header calculates pages correctly', async ({ request }) => {
      const perPage = 3;

      const response = await request.get(`${API_BASE}/listings`, {
        params: { per_page: String(perPage) },
      });

      expect(response.status()).toBe(200);

      const total = parseInt(response.headers()['x-wp-total']);
      const totalPages = parseInt(response.headers()['x-wp-totalpages']);

      // Total pages should be ceil(total / per_page).
      const expectedPages = Math.ceil(total / perPage);
      expect(totalPages).toBe(expectedPages);
    });
  });

  test.describe('Error Handling', () => {
    test('GET /listings/{id} returns 404 for non-existent listing', async ({ request }) => {
      // Use an ID that very likely does not exist.
      const response = await request.get(`${API_BASE}/listings/999999999`);

      expect(response.status()).toBe(404);

      const body = await response.json();

      // Error response should have a code and message.
      expect(body).toHaveProperty('code');
      expect(body.code).toBe('rest_listing_not_found');
      expect(body).toHaveProperty('message');
    });
  });

  test.describe('Permission Checks', () => {
    test('GET /favorites returns 401 for unauthenticated requests', async ({ request }) => {
      const response = await request.get(`${API_BASE}/favorites`);

      // Should fail because favorites requires authentication.
      expect(response.status()).toBe(401);

      const body = await response.json();
      expect(body).toHaveProperty('code');
    });

    test('POST /listings returns 401 for unauthenticated requests', async ({ request }) => {
      const response = await request.post(`${API_BASE}/listings`, {
        data: {
          title: 'Unauthorized Listing',
          content: 'Should not be created.',
        },
      });

      // Should fail because creating listings requires authentication.
      expect(response.status()).toBe(401);

      const body = await response.json();
      expect(body).toHaveProperty('code');
    });
  });

  test.describe('Authenticated Endpoints', () => {
    // Use a browser context with admin auth to get cookies and a nonce
    // for authenticated REST API requests.

    let authCookies: string;
    let nonce: string;
    let authHeaders: Record<string, string>;

    test.beforeAll(async ({ browser }) => {
      // Create a context with admin storage state.
      const context = await browser.newContext({ storageState: ADMIN_STATE });
      const page = await context.newPage();

      // Navigate to admin to get valid cookies.
      await page.goto('/wp-admin/');
      await page.waitForLoadState('networkidle');

      // Get the REST nonce from the page.
      nonce = await page.evaluate(() => {
        // WordPress sets wpApiSettings.nonce on admin pages.
        return (window as any).wpApiSettings?.nonce || '';
      });

      // If nonce is not available from wpApiSettings, generate one via inline script.
      if (!nonce) {
        nonce = await page.evaluate(() => {
          // Fallback: find the nonce in the page.
          const el = document.querySelector('[data-nonce]');
          return el?.getAttribute('data-nonce') || '';
        });
      }

      // Get cookies as a header string.
      const cookies = await context.cookies();
      authCookies = cookies.map(c => `${c.name}=${c.value}`).join('; ');

      authHeaders = {
        'Cookie': authCookies,
        'X-WP-Nonce': nonce,
      };

      await context.close();
    });

    test('GET /favorites returns favorites for authenticated user', async ({ request }) => {
      const response = await request.get(`${API_BASE}/favorites`, {
        headers: authHeaders,
      });

      expect(response.status()).toBe(200);

      const body = await response.json();

      // Response should be an array or have an items property.
      // The endpoint returns a list of favorite listing IDs.
      expect(body).toBeDefined();
    });

    test('POST /favorites adds a listing to favorites', async ({ request }) => {
      // Create a dedicated listing to avoid conflicts with concurrent tests.
      const id = await createListing({
        title: `REST Fav Add ${uniqueId()}`,
        content: 'For REST favorites POST test.',
        status: 'publish',
      });
      expect(id).toBeGreaterThan(0);

      const response = await request.post(`${API_BASE}/favorites`, {
        headers: authHeaders,
        data: {
          listing_id: id,
        },
      });

      // Should succeed with 200 or 201.
      expect([200, 201]).toContain(response.status());

      const body = await response.json();
      expect(body).toBeDefined();

      // Clean up.
      await request.delete(`${API_BASE}/favorites/${id}`, {
        headers: authHeaders,
      }).catch(() => {});
      await deletePost(id);
    });

    test('DELETE /favorites/{id} removes a listing from favorites', async ({ request }) => {
      // Get a listing ID and add it as a favorite first.
      const listingId = await wpCli('post list --post_type=apd_listing --post_status=publish --field=ID --posts_per_page=1');
      const id = parseInt(listingId, 10);

      // Add to favorites first.
      await request.post(`${API_BASE}/favorites`, {
        headers: authHeaders,
        data: { listing_id: id },
      });

      // Now remove it.
      const response = await request.delete(`${API_BASE}/favorites/${id}`, {
        headers: authHeaders,
      });

      expect(response.status()).toBe(200);

      const body = await response.json();
      expect(body).toBeDefined();
    });

    test('POST /listings creates a new listing', async ({ request }) => {
      const title = uniqueId('REST Create');

      const response = await request.post(`${API_BASE}/listings`, {
        headers: authHeaders,
        data: {
          title: title,
          content: 'Created via REST API test.',
          status: 'publish',
        },
      });

      // Should succeed with 201 Created.
      expect([200, 201]).toContain(response.status());

      const body = await response.json();
      expect(body).toHaveProperty('id');
      expect(body.title).toContain(title);

      // Clean up the created listing.
      if (body.id) {
        await deletePost(body.id);
      }
    });

    test('PUT /listings/{id} updates an existing listing', async ({ request }) => {
      // Create a listing to update.
      const title = uniqueId('REST Update');
      const postId = await createListing({ title, status: 'publish' });

      const updatedTitle = uniqueId('REST Updated');
      const response = await request.put(`${API_BASE}/listings/${postId}`, {
        headers: authHeaders,
        data: {
          title: updatedTitle,
        },
      });

      expect(response.status()).toBe(200);

      const body = await response.json();
      expect(body.title).toContain(updatedTitle);

      // Verify via WP-CLI.
      const cliTitle = await wpCli(`post get ${postId} --field=post_title`);
      expect(cliTitle).toBe(updatedTitle);

      // Clean up.
      await deletePost(postId);
    });
  });
});

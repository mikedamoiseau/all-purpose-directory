import { defineConfig, devices } from '@playwright/test';
import { ADMIN_STATE, USER_STATE } from './helpers';

/**
 * Playwright configuration for All Purpose Directory E2E tests.
 *
 * @see https://playwright.dev/docs/test-configuration
 */
export default defineConfig({
  testDir: './',

  /* Run tests in files in parallel */
  fullyParallel: true,

  /* Fail the build on CI if you accidentally left test.only in the source code */
  forbidOnly: !!process.env.CI,

  /* Retry on CI only */
  retries: process.env.CI ? 2 : 0,

  /* Limit concurrency: shared WordPress database creates race conditions with many workers */
  workers: process.env.CI ? 1 : 2,

  /* Reporter to use */
  reporter: [
    ['html', { outputFolder: '../../playwright-report' }],
    ['list'],
  ],

  /* Shared settings for all the projects below */
  use: {
    /* Base URL to use in actions like `await page.goto('/')` */
    baseURL: process.env.BASE_URL || 'http://localhost:8085',

    /* Collect trace when retrying the failed test */
    trace: 'on-first-retry',

    /* Take screenshot on failure */
    screenshot: 'only-on-failure',

    /* Video recording */
    video: 'retain-on-failure',
  },

  /* Configure projects */
  projects: [
    /* Setup project - runs before all tests */
    {
      name: 'setup',
      testMatch: /global-setup\.ts/,
    },

    /* Admin tests - use admin auth state */
    {
      name: 'admin',
      testMatch: /admin\.spec\.ts|demo-data\.spec\.ts|modules\.spec\.ts|listing-type\.spec\.ts|blocks\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        storageState: ADMIN_STATE,
      },
      dependencies: ['setup'],
    },

    /* Authenticated user tests */
    {
      name: 'authenticated',
      testMatch: /submission\.spec\.ts|dashboard\.spec\.ts|favorites\.spec\.ts|reviews\.spec\.ts|contact\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        storageState: USER_STATE,
      },
      dependencies: ['setup'],
    },

    /* Public/guest tests - no auth state */
    {
      name: 'public',
      testMatch: /search-filter\.spec\.ts|rest-api\.spec\.ts|shortcodes\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
      },
      dependencies: ['setup'],
    },

    /* Accessibility tests */
    {
      name: 'accessibility',
      testMatch: /accessibility\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
        storageState: USER_STATE,
      },
      dependencies: ['setup'],
    },

    /* Responsive tests across viewports */
    {
      name: 'mobile',
      testMatch: /responsive\.spec\.ts/,
      use: {
        viewport: { width: 375, height: 812 },
        isMobile: true,
      },
      dependencies: ['setup'],
    },

    {
      name: 'tablet',
      testMatch: /responsive\.spec\.ts/,
      use: {
        viewport: { width: 810, height: 1080 },
      },
      dependencies: ['setup'],
    },

    {
      name: 'desktop',
      testMatch: /responsive\.spec\.ts/,
      use: {
        ...devices['Desktop Chrome'],
      },
      dependencies: ['setup'],
    },
  ],

  /* Folder for test artifacts such as screenshots, videos, traces, etc. */
  outputDir: '../../test-results',

  /* Increase timeout for WordPress pages */
  timeout: 30_000,
  expect: { timeout: 10_000 },
});

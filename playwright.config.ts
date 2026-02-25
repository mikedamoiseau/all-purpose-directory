/**
 * Root-level Playwright config — delegates to tests/e2e/playwright.config.ts.
 * This allows `npx playwright test` to work from the project root.
 */
export { default } from './tests/e2e/playwright.config';

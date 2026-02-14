import { exec } from 'child_process';
import { promisify } from 'util';
import path from 'path';

const execAsync = promisify(exec);

/** Project root directory (two levels up from tests/e2e/). */
const PROJECT_ROOT = path.resolve(__dirname, '..', '..');

const DOCKER_CONTAINER = 'wp-all-purpose-directory-web-1';

/**
 * Run a WP-CLI command inside the Docker container.
 */
export async function wpCli(command: string): Promise<string> {
  const { stdout } = await execAsync(
    `docker exec ${DOCKER_CONTAINER} wp ${command} --allow-root`,
    { timeout: 60_000 }
  );
  return stdout.trim();
}

/**
 * Run a shell command inside the Docker container.
 */
export async function dockerExec(command: string): Promise<string> {
  const { stdout } = await execAsync(
    `docker exec ${DOCKER_CONTAINER} bash -c "${command.replace(/"/g, '\\"')}"`,
    { timeout: 60_000 }
  );
  return stdout.trim();
}

/**
 * Create a WordPress page with shortcode content.
 * Returns the page ID.
 */
export async function createPage(title: string, slug: string, content: string): Promise<number> {
  const existing = await wpCli(`post list --post_type=page --name=${slug} --field=ID`).catch(() => '');
  if (existing) {
    const pageId = parseInt(existing, 10);
    // Update content in case it changed.
    await wpCli(`post update ${pageId} --post_content='${content}'`).catch(() => {});
    return pageId;
  }
  const id = await wpCli(
    `post create --post_type=page --post_title='${title}' --post_name='${slug}' --post_status=publish --post_content='${content}' --porcelain`
  );
  return parseInt(id, 10);
}

/**
 * Create a WordPress user and return the user ID.
 */
export async function createUser(
  login: string,
  email: string,
  role: string,
  password: string
): Promise<number> {
  const existing = await wpCli(`user get ${login} --field=ID`).catch(() => '');
  if (existing) {
    return parseInt(existing, 10);
  }
  const id = await wpCli(
    `user create ${login} ${email} --role=${role} --user_pass='${password}' --porcelain`
  );
  return parseInt(id, 10);
}

/**
 * Update a plugin setting via WP-CLI.
 */
export async function updateSetting(key: string, value: string | boolean | number): Promise<void> {
  const jsonValue = JSON.stringify(value);
  await dockerExec(
    `wp option patch update apd_options ${key} '${jsonValue}' --allow-root`
  ).catch(async () => {
    // Fallback: patch update can be tricky with booleans, use PHP eval
    const phpValue = typeof value === 'boolean' ? (value ? 'true' : 'false') : `'${value}'`;
    await wpCli(
      `eval '$opts = get_option("apd_options", []); $opts["${key}"] = ${phpValue}; update_option("apd_options", $opts);'`
    );
  });
}

/**
 * Generate demo data via WP-CLI.
 */
export async function generateDemoData(options?: {
  listings?: number;
  users?: number;
  tags?: number;
  types?: string;
}): Promise<void> {
  let cmd = 'apd demo generate';
  if (options?.listings) cmd += ` --listings=${options.listings}`;
  if (options?.users) cmd += ` --users=${options.users}`;
  if (options?.tags) cmd += ` --tags=${options.tags}`;
  if (options?.types) cmd += ` --types=${options.types}`;
  await wpCli(cmd);
}

/**
 * Delete all demo data.
 */
export async function deleteDemoData(): Promise<void> {
  await wpCli('apd demo delete --yes');
}

/**
 * Create a listing via WP-CLI. Returns the listing post ID.
 */
export async function createListing(data: {
  title: string;
  content?: string;
  status?: string;
  author?: number;
  meta?: Record<string, string>;
}): Promise<number> {
  const author = data.author || 1; // Default to admin (ID 1) so contact form / email features work.
  let cmd = `post create --post_type=apd_listing --post_title='${data.title}' --post_status=${data.status || 'publish'} --post_author=${author} --porcelain`;
  if (data.content) cmd += ` --post_content='${data.content}'`;

  const id = await wpCli(cmd);
  const postId = parseInt(id, 10);

  if (data.meta) {
    for (const [key, value] of Object.entries(data.meta)) {
      await wpCli(`post meta update ${postId} ${key} '${value}'`);
    }
  }

  return postId;
}

/**
 * Delete a post by ID.
 */
export async function deletePost(id: number): Promise<void> {
  await wpCli(`post delete ${id} --force`);
}

/**
 * Create a review via wp_insert_comment (bypasses ReviewManager validation).
 * Use this for test data seeding. Returns the comment ID.
 *
 * @param opts.listingId - Post ID of the listing.
 * @param opts.rating    - Star rating 1-5.
 * @param opts.title     - Review title.
 * @param opts.content   - Review content.
 * @param opts.authorName - Display name of the reviewer.
 * @param opts.authorEmail - Email of the reviewer.
 * @param opts.userId    - WordPress user ID (0 for guest).
 * @param opts.approved  - Whether the review is approved (default: false = pending).
 */
export async function createReview(opts: {
  listingId: number;
  rating: number;
  title: string;
  content: string;
  authorName?: string;
  authorEmail?: string;
  userId?: number;
  approved?: boolean;
}): Promise<number> {
  const approved = opts.approved ? '1' : '0';
  const userId = opts.userId ?? 0;
  const authorName = opts.authorName ?? 'Test Reviewer';
  const authorEmail = opts.authorEmail ?? 'reviewer@example.com';

  // Use wp comment create for reliable review insertion.
  const commentId = await wpCli(
    `comment create --comment_post_ID=${opts.listingId} --comment_type=apd_review` +
    ` --comment_approved=${approved} --user_id=${userId}` +
    ` --comment_author='${authorName}' --comment_author_email='${authorEmail}'` +
    ` --comment_content='${opts.content.replace(/'/g, "'\\''")}' --porcelain`
  );

  const id = parseInt(commentId, 10);

  // Set review meta (rating and title).
  await wpCli(`comment meta update ${id} _apd_rating ${opts.rating}`);
  await wpCli(`comment meta update ${id} _apd_review_title '${opts.title.replace(/'/g, "'\\''")}'`);

  return id;
}

/**
 * Assign a category to a listing.
 */
export async function assignCategory(listingId: number, categorySlug: string): Promise<void> {
  await wpCli(`post term add ${listingId} apd_category ${categorySlug}`);
}

/**
 * Get the count of posts of a given type.
 */
export async function getPostCount(postType: string, status?: string): Promise<number> {
  let cmd = `post list --post_type=${postType} --format=count`;
  if (status) cmd += ` --post_status=${status}`;
  const count = await wpCli(cmd);
  return parseInt(count, 10);
}

/**
 * Get a list of category slugs.
 */
export async function getCategorySlugs(): Promise<string[]> {
  const output = await wpCli('term list apd_category --field=slug');
  return output.split('\n').filter(Boolean);
}

/**
 * Generate a unique string for test isolation.
 */
export function uniqueId(prefix = 'test'): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
}

/**
 * Auth state file paths (absolute to avoid resolution issues).
 */
export const AUTH_STATE_DIR = path.join(PROJECT_ROOT, 'tests', 'e2e', '.auth');
export const ADMIN_STATE = path.join(AUTH_STATE_DIR, 'admin.json');
export const USER_STATE = path.join(AUTH_STATE_DIR, 'user.json');

/**
 * Test user credentials.
 */
export const ADMIN_USER = {
  login: 'admin_buzzwoo',
  password: 'admin',
};

export const TEST_USER = {
  login: 'e2e_testuser',
  email: 'e2e_testuser@example.com',
  password: 'testpass123',
  role: 'author',
};

/**
 * Key page slugs expected in the test environment.
 *
 * The post type archive at /listings/ uses the block theme's default template.
 * The shortcode page at /directory/ renders plugin-specific HTML.
 */
export const PAGES = {
  archive: '/listings/',          // post type archive (WP block theme template)
  directory: '/directory/',       // shortcode page ([apd_listings])
  submit: '/submit-listing/',
  dashboard: '/dashboard/',
};

---
name: deploy-test-wp
description: Deploy all plugins to the Docker WordPress test environment. Syncs code, installs composer dependencies, and verifies activation.
user-invocable: true
---

# Deploy to Test WordPress

Deploy the APD plugin ecosystem to the local Docker WordPress test environment.

## Environment

- **Docker compose dir:** `/Users/mike/Documents/www/test/wp-all-purpose-directory/`
- **Container:** `wp-all-purpose-directory-web-1`
- **Site URL:** http://localhost:8085
- **Admin:** `admin_buzzwoo` / `admin`
- **Plugin path in container:** `/var/www/html/wp-content/plugins/`

## Plugins to Deploy

| Plugin | Source | Target (in container) |
|--------|--------|-----------------------|
| all-purpose-directory (core) | `/Users/mike/Documents/www/private/all-purpose-directory/` | `plugins/all-purpose-directory/` |
| apd-url-directory (module) | `/Users/mike/Documents/www/private/apd-url-directory/` | `plugins/apd-url-directory/` |

Add new module plugins to this table as they are created.

## Steps

### 1. Verify Docker is running

```bash
docker ps --filter "name=wp-all-purpose-directory" --format "{{.Names}}\t{{.Status}}"
```

If not running, start with:
```bash
cd /Users/mike/Documents/www/test/wp-all-purpose-directory/ && docker-compose up -d
```

### 2. Sync core plugin

Rsync excludes dev-only files that should not be in the test environment.

```bash
rsync -av --delete \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='composer.lock' \
  --exclude='.phpunit.result.cache' \
  --exclude='.idea' \
  --exclude='.claude' \
  --exclude='.claude-bw' \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='.distignore' \
  --exclude='.gitkeep' \
  --exclude='.DS_Store' \
  --exclude='phpcs.xml.dist' \
  --exclude='phpunit.xml.dist' \
  --exclude='phpunit-unit.xml' \
  --exclude='CLAUDE.md' \
  --exclude='PLAN.md' \
  --exclude='TASKS.md' \
  --exclude='tests' \
  --exclude='bin' \
  --exclude='research' \
  --exclude='*.png' \
  /Users/mike/Documents/www/private/all-purpose-directory/ \
  /Users/mike/Documents/www/test/wp-all-purpose-directory/html/wp-content/plugins/all-purpose-directory/
```

### 3. Sync module plugins

```bash
rsync -av --delete \
  --exclude='vendor' \
  --exclude='node_modules' \
  --exclude='composer.lock' \
  --exclude='.phpunit.result.cache' \
  --exclude='.idea' \
  --exclude='.claude' \
  --exclude='.claude-bw' \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='.gitignore' \
  --exclude='.distignore' \
  --exclude='.gitkeep' \
  --exclude='.DS_Store' \
  --exclude='phpcs.xml.dist' \
  --exclude='phpunit.xml.dist' \
  --exclude='phpunit-unit.xml' \
  --exclude='CLAUDE.md' \
  --exclude='PLAN.md' \
  --exclude='TASKS.md' \
  --exclude='tests' \
  --exclude='bin' \
  --exclude='research' \
  /Users/mike/Documents/www/private/apd-url-directory/ \
  /Users/mike/Documents/www/test/wp-all-purpose-directory/html/wp-content/plugins/apd-url-directory/
```

### 4. Install composer dependencies (CRITICAL)

The core plugin uses Composer's **optimized classmap** autoloader. New PHP classes added to `src/` are NOT discovered dynamically - you MUST re-run composer after syncing new files.

```bash
docker exec wp-all-purpose-directory-web-1 bash -c \
  "cd /var/www/html/wp-content/plugins/all-purpose-directory && composer install --no-dev --no-interaction"

docker exec wp-all-purpose-directory-web-1 bash -c \
  "cd /var/www/html/wp-content/plugins/apd-url-directory && composer install --no-dev --no-interaction"
```

### 5. Verify plugins are active

```bash
docker exec wp-all-purpose-directory-web-1 wp plugin list --allow-root --format=table 2>&1 | grep -E "apd|all-purpose"
```

If a plugin is inactive, activate it:
```bash
docker exec wp-all-purpose-directory-web-1 wp plugin activate all-purpose-directory --allow-root
docker exec wp-all-purpose-directory-web-1 wp plugin activate apd-url-directory --allow-root
```

### 6. Verify (optional)

Quick smoke test with WP-CLI:
```bash
docker exec wp-all-purpose-directory-web-1 wp apd demo status --allow-root
```

## Common Issues

- **"Class not found" errors** - You forgot step 4 (composer install). Always run it after syncing new PHP files.
- **Container not found** - Run `docker-compose up -d` from the test directory.
- **"dubious ownership" git warning from composer** - Safe to ignore. The container runs as root but the files are owned by the host user.
- **Plugin shows as inactive after sync** - The plugin file header may have changed. Re-activate with `wp plugin activate`.

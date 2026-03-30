# Module API Implementation Plan

Add a minimal Module API to the core plugin so external modules can register themselves.

## Overview

**Goal:** Enable modules (separate plugins) to register with the core plugin, allowing:
- Discovery of installed modules in admin
- Future extensibility for per-module settings, enable/disable, etc.

**Scope:** Minimal implementation - registration API and read-only admin page only.

## Files to Create

### 1. `src/Module/ModuleInterface.php`
Interface for class-based modules (optional - modules can also use array config).

```php
interface ModuleInterface {
    public function get_slug(): string;
    public function get_name(): string;
    public function get_description(): string;
    public function get_version(): string;
    public function get_config(): array;
    public function init(): void;
}
```

### 2. `src/Module/ModuleRegistry.php`
Singleton registry following `FieldRegistry` pattern.

**Key Methods:**
| Method | Description |
|--------|-------------|
| `register(string $slug, array $config): bool` | Register via array config |
| `register_module(ModuleInterface $module): bool` | Register via class |
| `unregister(string $slug): bool` | Remove module |
| `get(string $slug): ?array` | Get single module |
| `get_all(array $args = []): array` | Get all (supports sorting) |
| `has(string $slug): bool` | Check existence |
| `count(): int` | Count modules |
| `check_requirements(array $requires): array` | Validate dependencies |
| `get_by_feature(string $feature): array` | Filter by feature |
| `reset(): void` | For testing |

**Module Config Structure:**
```php
[
    'name'        => 'URL Directory',        // Required
    'description' => 'Website directory',    // Required
    'version'     => '1.0.0',                // Required
    'author'      => 'Developer',            // Optional
    'author_uri'  => 'https://...',          // Optional
    'requires'    => ['core' => '1.0.0'],    // Optional
    'features'    => ['link_checker'],       // Optional
    'icon'        => 'dashicons-admin-links',// Optional
]
```

### 3. `src/Module/ModulesAdminPage.php`
Admin page following `DemoDataPage` pattern.

- Page slug: `apd-modules`
- Parent: `edit.php?post_type=apd_listing`
- Capability: `manage_options`
- Displays table of registered modules (read-only)

### 4. `includes/module-functions.php`
Helper functions:

| Function | Description |
|----------|-------------|
| `apd_module_registry()` | Get registry instance |
| `apd_register_module($slug, $config)` | Register module |
| `apd_register_module_class($module)` | Register class-based module |
| `apd_unregister_module($slug)` | Unregister module |
| `apd_get_module($slug)` | Get module config |
| `apd_get_modules($args)` | Get all modules |
| `apd_has_module($slug)` | Check if registered |
| `apd_module_count()` | Count modules |
| `apd_module_requirements_met($requires)` | Check requirements |
| `apd_get_modules_by_feature($feature)` | Filter by feature |
| `apd_get_modules_page_url()` | Get admin page URL |

### 5. `assets/css/admin-modules.css`
Simple styles for the admin page table.

### 6. `tests/unit/Module/ModuleRegistryTest.php`
Unit tests for registry.

### 7. `tests/unit/Module/ModuleFunctionsTest.php`
Unit tests for helper functions.

## Files to Modify

### 1. `damdir-directory.php`
Add require for module functions (after line ~45):
```php
require_once APD_PLUGIN_DIR . 'includes/module-functions.php';
```

### 2. `src/Core/Plugin.php`
Add module initialization in `init_hooks()` at priority 1:
```php
// Add at top of init_hooks() method:
add_action( 'init', [ $this, 'init_modules' ], 1 );

// Add new method:
public function init_modules(): void {
    $module_registry = \APD\Module\ModuleRegistry::get_instance();
    $module_registry->init();
}
```

Also initialize `ModulesAdminPage` (after Settings initialization).

### 3. `CLAUDE.md`
Add documentation for:
- Module hooks (add to Actions and Filters lists)
- Helper functions table
- Module configuration structure

### 4. `.claude-bw/docs/claude-modules.md` (new file)
Create detailed API reference documentation including:
- ModuleInterface contract
- Module configuration options
- Registration examples (array-based and class-based)
- Hook reference with examples
- Creating a module guide

### 5. `docs/DEVELOPER.md`
Add section on:
- Module system overview
- How to create a module plugin
- Module registration API
- Available hooks for modules

## Hooks to Implement

**Actions:**
| Hook | When | Parameters |
|------|------|------------|
| `apd_modules_init` | After registry init, before modules load | `$registry` |
| `apd_module_registered` | After successful registration | `$slug, $config` |
| `apd_module_unregistered` | After unregistration | `$slug, $config` |
| `apd_modules_loaded` | After all modules initialized | `$registry` |

**Filters:**
| Filter | Purpose | Parameters |
|--------|---------|------------|
| `apd_register_module_config` | Modify config before registration | `$config, $slug` |
| `apd_get_module` | Modify module on retrieval | `$config, $slug` |
| `apd_get_modules` | Modify modules array | `$modules, $args` |

## Initialization Order

```
init priority 0:  Text domain (existing)
init priority 1:  ModuleRegistry::init() → fires apd_modules_init
init priority 5:  Post types, taxonomies (existing)
init priority 15: Default filters (existing)
init priority 20: Shortcodes (existing)
```

Modules hook into `apd_modules_init` to register before other components.

## Implementation Steps

1. Create `src/Module/` directory
2. Create `ModuleInterface.php`
3. Create `ModuleRegistry.php` (full implementation)
4. Create `includes/module-functions.php`
5. Modify `damdir-directory.php` to require module functions
6. Modify `src/Core/Plugin.php` to init modules at priority 1
7. Create `ModulesAdminPage.php`
8. Create `assets/css/admin-modules.css`
9. Init admin page in Plugin class
10. Create unit tests
11. Update `CLAUDE.md` with hooks and helper functions
12. Create `.claude-bw/docs/claude-modules.md` API reference
13. Update `docs/DEVELOPER.md` with module creation guide

## Verification

1. **Run unit tests:**
   ```bash
   composer test:unit
   ```

2. **Run Plugin Check:**
   ```bash
   docker exec wp-damdir-directory-web-1 wp plugin check damdir-directory \
     --exclude-directories=tests,bin,research,.git \
     --exclude-files=.gitignore,.distignore,phpunit.xml.dist,phpunit-unit.xml,CLAUDE.md,PLAN.md,TASKS.md,CHANGELOG.md,.gitkeep,.phpunit.result.cache \
     --allow-root
   ```

3. **Manual testing:**
   - Verify Modules admin page appears under Listings menu
   - Verify empty state displays when no modules registered
   - Test registration via helper function in theme's functions.php:
     ```php
     add_action('apd_modules_init', function() {
         apd_register_module('test_module', [
             'name'        => 'Test Module',
             'description' => 'A test module',
             'version'     => '1.0.0',
         ]);
     });
     ```
   - Verify module appears in admin table

## Out of Scope (Future)

- Enable/disable toggle UI
- Per-module settings pages
- Module updates/marketplace
- REST API endpoint for modules

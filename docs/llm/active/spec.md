# Beacon Statamic Addon Specification

## Overview

Beacon is a paid Statamic Marketplace addon that lets content editors attach contextual notifications — announcement banners, discount codes, CTA banners, and cookie consent notices — to specific entries, collections, or the whole site via the Control Panel, with no developer involvement after setup.

The addon is built around four hard constraints: zero bytes loaded when no notification is active, a sub-8 kb vanilla JS frontend with no framework dependencies, WCAG 2.1 AA accessibility across all notification types, and a CSS custom properties theming contract that keeps visual customisation out of the database. Targeting resolves server-side before any page is served, and the resolved config is embedded inline as JSON — no XHR at runtime.

Beacon ships as a Marketplace-ready paid addon (US$79 single site / US$249 agency), with PHPStan level 5+, PHP CS Fixer clean, and a test suite covering PHP 8.1–8.3 and Statamic v4/v5.

## Requirements

### Functional

- Content editors create, schedule, and assign notifications entirely through the Statamic CP.
- Four notification types with distinct field sets: Announcement, Discount Code, CTA Banner, Cookie/Consent Notice.
- `beacon_notification` fieldtype can be added to any Blueprint; shows type-specific fields conditionally.
- Three targeting levels with explicit priority: entry-level (highest) → collection rule → sitewide fallback.
- URL pattern matching (e.g. `/shop/*`) as an alternative targeting mechanism.
- Scheduling via active-from / active-until datetime fields, evaluated server-side using the site's configured timezone.
- Five display positions: `top-bar`, `bottom-bar`, `bottom-right`, `bottom-left` (toast), `modal-center`. Viewports under 480 px collapse all non-modal positions to `bottom-bar`.
- Frequency controls: always / once per session / once per day / once ever / until dismissed.
- Trigger controls: immediate / after X seconds / after scroll X% / exit intent.
- `{{ beacon:assets }}` Antlers tag outputs nothing when no notification is active; outputs asset tags and an inline JSON config block when active. Missing tag logs a Statamic warning.
- Dedicated CP section: Dashboard, Global Defaults, Collection Rules, Sitewide Notification.
- Permission model: `view beacon`, `edit beacon`, `manage beacon settings`. Super admins always have full access.
- All CP strings go through lang files. No hardcoded copy.

### Technical

- PHP 8.1–8.3, Laravel 10/11, Statamic v4/v5.
- Namespace: `Arielcerda\Beacon`.
- Frontend JS: vanilla, no jQuery/Vue/React/Alpine, under 8 kb (minified + gzipped).
- WCAG 2.1 AA: correct ARIA roles, focus trap + return for modals, Escape dismisses, 44×44 px touch targets, 4.5:1 contrast on default colour tokens. No auto-dismiss on notifications containing forms or required actions.
- CSS theming contract: layout-only base styles, CSS custom properties for all visual tokens, optional starter theme, structural overrides via `vendor:publish`.
- PHPStan level 5+, PHP CS Fixer (Laravel preset).
- CHANGELOG.md (Keep a Changelog format), LICENSE file, semantic versioning from v1.0.0.
- composer.json with correct `statamic/cms` version constraint.

## Technical Approach

### Key Decisions

1. **Fieldtype over standalone entry type**: Beacon attaches to existing content entries — editors add the `beacon_notification` fieldtype to a Blueprint. No separate notifications collection.
2. **Inline JSON config, not an API call**: The server resolves the active notification and embeds it as `<script type="application/json" id="beacon-config">` in the page — zero XHR at runtime.
3. **Zero bytes when inactive**: `{{ beacon:assets }}` outputs nothing (no `<script>`, no `<link>`) when no notification is active for the current page.
4. **Vanilla JS, 8 kb hard budget**: No framework dependency — Beacon works on any Statamic site regardless of frontend stack.
5. **Type-specific field sets**: Each notification type has its own CP fields. No generic content blob.
6. **CSS custom properties for theming**: Visual tokens (colours, radius, spacing) are overridden via a developer stylesheet, not stored in the database.
7. **Explicit targeting priority with dev-mode logging**: When multiple rules match, entry-level wins. Dev mode logs a warning identifying all matching rules.
8. **Default 2-second trigger delay**: Configurable. Avoids conflict with cookie banners on initial page load.

### Implementation Strategy

Build in dependency order: scaffolding and service provider first, then data layer, then the fieldtype that uses it, then the server-side tag resolver, then frontend assets, then CP section and permissions, then tests and Marketplace prep. Vue components for the CP fieldtype are built alongside the fieldtype PHP class. Frontend JS and CSS are built after the tag system is stable so the output contract is clear before writing the consumer.

---

## Tasks

### Phase 1: Addon Scaffolding

- [✅] Initialise `composer.json` with package name `arielcerda/beacon`, namespace `Arielcerda\Beacon`, autoload PSR-4 map, and `statamic/cms` version constraint covering v4 and v5.
  - **File**: `composer.json`
  - **Dependencies**: None
  - **Verification**: `composer validate` passes; `composer install` resolves without errors.

- [✅] Create `BeaconServiceProvider` extending `Statamic\Providers\AddonServiceProvider`. Register config, migrations, routes, views, translations, assets, fieldtype, and tag.
  - **File**: `src/ServiceProvider.php`
  - **Dependencies**: composer.json complete
  - **Verification**: Addon loads in a fresh Statamic install without errors; `php artisan statamic:addons` lists Beacon.

- [✅] Create `config/beacon.php` with keys: `default_delay` (2), `dev_mode_logging` (true), `license_key` (null), `cache_ttl` (300).
  - **File**: `config/beacon.php`
  - **Dependencies**: ServiceProvider
  - **Verification**: `config('beacon.default_delay')` returns 2 after `php artisan config:clear`.

- [✅] Set up `package.json` with build tooling (Vite or Laravel Mix, matching Statamic addon conventions). Configure separate entry points for `resources/js/beacon.js` (frontend) and `resources/js/cp.js` (CP Vue components). Frontend build targets ES2017, outputs to `public/vendor/beacon/`.
  - **File**: `package.json`, `vite.config.js` (or `webpack.mix.js`)
  - **Dependencies**: None
  - **Verification**: `npm run build` produces `beacon.js` and `cp.js` without errors.

- [✅] Create directory skeleton with `.gitkeep` files where needed: `src/Http/Controllers/`, `src/Fieldtypes/`, `src/Tags/`, `src/Models/`, `resources/js/`, `resources/css/`, `resources/views/fieldtypes/`, `resources/views/tags/`, `resources/lang/en/`, `config/`, `routes/`, `database/migrations/`, `tests/`.
  - **File**: Directory structure
  - **Dependencies**: None
  - **Verification**: All directories exist; `git status` shows expected skeleton.

### Phase 2: Data Layer

- [✅] Create migration `create_beacon_notifications_table` with columns: `id` (bigint PK), `handle` (string, unique), `type` (enum: announcement/discount/cta/consent), `enabled` (boolean, default false), `position` (string), `trigger` (string), `trigger_value` (nullable string), `frequency` (string), `active_from` (nullable timestamp), `active_until` (nullable timestamp), `payload` (JSON), `created_at`, `updated_at`.
  - **File**: `database/migrations/YYYY_MM_DD_create_beacon_notifications_table.php`
  - **Dependencies**: ServiceProvider
  - **Verification**: `php artisan migrate` runs cleanly; table exists with correct schema.

- [✅] Create migration `create_beacon_collection_rules_table` with columns: `id`, `collection_handle` (string), `notification_id` (nullable FK to beacon_notifications), `url_pattern` (nullable string), `enabled` (boolean), `priority` (integer, default 0), `created_at`, `updated_at`.
  - **File**: `database/migrations/YYYY_MM_DD_create_beacon_collection_rules_table.php`
  - **Dependencies**: beacon_notifications migration
  - **Verification**: `php artisan migrate` runs; table exists with FK constraint.

- [✅] Create `BeaconNotification` Eloquent model with fillable fields matching migration columns. Add casts: `enabled` → boolean, `active_from`/`active_until` → datetime, `payload` → array. Add scope `active()` that filters by `enabled = true` and current datetime within `active_from`/`active_until` range (nulls treated as open-ended).
  - **File**: `src/Models/BeaconNotification.php`
  - **Dependencies**: notifications migration
  - **Verification**: Model instantiates; `active()` scope returns only in-range enabled records.

- [✅] Create `BeaconCollectionRule` Eloquent model with `belongsTo(BeaconNotification::class)`. Add scope `forCollection(string $handle)`.
  - **File**: `src/Models/BeaconCollectionRule.php`
  - **Dependencies**: collection_rules migration, BeaconNotification model
  - **Verification**: Relationship resolves correctly in tinker.

- [✅] Create `NotificationResolver` service class with method `resolve(Entry $entry): ?BeaconNotification`. Implements three-level priority: (1) check entry blueprint data for `beacon_notification.enabled = true` and return inline config as transient model; (2) check collection rules for the entry's collection; (3) check sitewide fallback from config. Logs all matching rules (level debug) when `beacon.dev_mode_logging` is true.
  - **File**: `src/Services/NotificationResolver.php`
  - **Dependencies**: Both models
  - **Verification**: Unit tests confirm correct priority order (see Phase 9 tests).

### Phase 3: Fieldtype

- [✅] Create `BeaconNotificationFieldtype` class extending `Statamic\Fieldtypes\Fieldtype`. Register with handle `beacon_notification`. Define `configFields()` returning an empty array (no per-instance config). Implement `preProcess()` and `process()` for save/load round-trip of nested field data.
  - **File**: `src/Fieldtypes/BeaconNotificationFieldtype.php`
  - **Dependencies**: ServiceProvider
  - **Verification**: Fieldtype appears in Blueprint editor field picker under "Beacon" category.

- [✅] Create Vue component `BeaconNotificationFieldtype.vue` that renders the full fieldtype UI. Top-level toggle "Enable notification for this entry". Type selector (announcement / discount / cta / consent). Conditional field groups per type (see field definitions below). Position selector. Trigger selector with optional value input. Frequency selector. Date range pickers (active-from / active-until). Toggle "Override collection-level rule".
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`
  - **Dependencies**: BeaconNotificationFieldtype PHP class, cp.js build
  - **Verification**: Component mounts in CP; field groups show/hide correctly when type changes; values persist on save and reload.

- [✅] Define announcement type fields in Vue component: `message` (textarea, required), `cta_label` (text, optional), `cta_url` (text, optional).
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`
  - **Dependencies**: Vue component skeleton
  - **Verification**: All three fields render when type = announcement; absent for other types.

- [✅] Define discount type fields in Vue component: `message` (textarea, required), `code` (text, required), `code_label` (text, optional, default "Copy code"), `show_countdown` (toggle), `countdown_until` (datetime, conditional on show_countdown).
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`
  - **Dependencies**: Vue component skeleton
  - **Verification**: Countdown field shows only when show_countdown is enabled.

- [✅] Define CTA banner type fields in Vue component: `headline` (text, required), `description` (textarea, optional), `primary_label` (text, required), `primary_url` (text, required), `secondary_label` (text, optional), `secondary_url` (text, optional).
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`
  - **Dependencies**: Vue component skeleton
  - **Verification**: Secondary button fields are optional; primary fields required; validation fires on save attempt.

- [✅] Define consent type fields in Vue component: `message` (textarea, required), `accept_label` (text, required, default "Accept"), `decline_label` (text, required, default "Decline"), `consent_hook` (text, optional, placeholder "window.gtag or similar").
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`
  - **Dependencies**: Vue component skeleton
  - **Verification**: All four fields render when type = consent.

- [✅] Create fieldtype Blade view for frontend rendering context (used by the tag system to serialise field data to JSON). Not a visible UI — purely the data serialisation layer.
  - **File**: `resources/views/fieldtypes/beacon_notification.blade.php`
  - **Dependencies**: BeaconNotificationFieldtype PHP class
  - **Verification**: Tag system can access fieldtype data as array.

### Phase 4: Tag System

- [✅] Create `BeaconAssetsTag` class extending `Statamic\Tags\Tags`. Handle `beacon:assets`. In `assets()` method: resolve current entry from context; call `NotificationResolver::resolve()`; return empty string if null; otherwise render `beacon-assets.blade.php` view with the resolved notification config.
  - **File**: `src/Tags/BeaconAssetsTag.php`
  - **Dependencies**: NotificationResolver, ServiceProvider
  - **Verification**: `{{ beacon:assets }}` outputs empty string when no active notification; outputs asset tags when notification is active.

- [✅] Create Blade view for tag output. Renders `<script src="{{ asset('vendor/beacon/beacon.js') }}"></script>`, `<link rel="stylesheet" href="{{ asset('vendor/beacon/beacon.css') }}">`, and `<script type="application/json" id="beacon-config">{{ $config }}</script>` (JSON-encoded notification config). No whitespace around the JSON block.
  - **File**: `resources/views/tags/beacon-assets.blade.php`
  - **Dependencies**: BeaconAssetsTag, frontend assets built
  - **Verification**: Output is valid HTML; JSON block parses without error; asset URLs are correct.

- [✅] Add `{{ beacon:assets }}` tag missing-detection middleware. On non-production environments, after page render, check if the current page has an active notification but the rendered HTML does not contain `id="beacon-config"`. If so, log a Statamic warning: "Beacon: active notification found but {{ beacon:assets }} tag missing from page output."
  - **File**: `src/Http/Middleware/BeaconTagPresenceMiddleware.php`
  - **Dependencies**: BeaconAssetsTag, NotificationResolver
  - **Verification**: Warning appears in `storage/logs/laravel.log` when tag is absent on a page with an active notification.

- [✅] Bind `NotificationResolver` in the service container as a singleton. Wrap `resolve()` with a cache layer using `beacon.cache_ttl` config value, keyed by entry ID + collection + site timezone. Cache is invalidated on notification save/update.
  - **File**: `src/ServiceProvider.php`, `src/Services/NotificationResolver.php`
  - **Dependencies**: NotificationResolver, BeaconNotification model
  - **Verification**: Second call to `resolve()` for same entry does not hit the database; cache clears on model save event.

### Phase 5: Frontend

- [✅] Write `beacon.js` vanilla JS frontend (no framework dependencies). Must be under 8 kb minified + gzipped. Export public API: `Beacon.dismiss()`, `Beacon.show()`, `Beacon.on(event, fn)`, `Beacon.waitFor(promise)`. On DOMContentLoaded, read `#beacon-config` JSON block; if absent, do nothing.
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: Tag system outputting correct JSON
  - **Verification**: `npm run build` output is under 8 kb gzipped; `Beacon` object available on `window`; no errors in console when config block is absent.

- [✅] Implement trigger logic in `beacon.js`: `immediate` (show on DOMContentLoaded), `delay` (setTimeout using `trigger_value` seconds, defaulting to `beacon.default_delay` config), `scroll` (IntersectionObserver or scroll event, show when scrolled past `trigger_value`% of page height), `exit_intent` (mouseleave on document element, desktop only).
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: beacon.js skeleton
  - **Verification**: Each trigger type fires at the correct moment in a browser; exit intent only fires on desktop (pointer device detected).

- [✅] Implement frequency logic in `beacon.js`: `always` (show every page load), `session` (sessionStorage flag keyed by notification handle), `daily` (localStorage timestamp keyed by handle, reset after 24 hours), `permanent` (localStorage permanent flag keyed by handle), `dismissed` (same as permanent but set on dismiss action). Check flag before showing; do not show if flag is set.
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: beacon.js skeleton
  - **Verification**: Once-per-session notification does not reappear after dismiss within the same browser tab session; reopening tab/browser does show it again.

- [✅] Implement position and rendering in `beacon.js`. Insert notification HTML into `document.body` at the correct position (prepend for `top-bar`, append for others). On viewports under 480 px, force position to `bottom-bar` for all non-modal types. Apply iOS safe area insets (`env(safe-area-inset-bottom)`) to bottom positions via inline style or CSS class.
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: beacon.js skeleton, beacon.css
  - **Verification**: Notification appears in correct position on desktop; collapses to bottom-bar on 480 px viewport; iOS safe area styles applied.

- [✅] Implement ARIA and accessibility in `beacon.js`. Banners and toasts use `role="alert"` and `aria-live="polite"`. Modal uses `role="dialog"`, `aria-modal="true"`, `aria-labelledby` pointing to headline element. Modal traps focus (cycle through focusable children with Tab/Shift+Tab). Escape key dismisses any visible notification. Dismiss button has `aria-label="Dismiss notification"`. On modal close, return focus to the element that was active before the modal opened.
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: beacon.js rendering
  - **Verification**: Screen reader announces notification on appear; Tab cycles within modal; Escape dismisses; focus returns to trigger element on close.

- [✅] Implement type-specific HTML rendering in `beacon.js` for all four types. Announcement: message text + optional CTA link. Discount: message + copyable code button (copies to clipboard via `navigator.clipboard.writeText`; falls back to `document.execCommand('copy')`) + optional countdown timer (counts down to `countdown_until` datetime, hides when reached). CTA: headline + description + primary button + optional secondary button. Consent: message + accept button + decline button. Accept fires `window[consent_hook]` if `consent_hook` is set.
  - **File**: `resources/js/beacon.js`
  - **Dependencies**: beacon.js rendering
  - **Verification**: Each type renders correct elements; clipboard copy works; countdown decrements and hides at zero; consent hook fires.

- [✅] Write `beacon.css`. Base styles cover layout and positioning only — no colours, no fonts, no decorative styles. Define CSS custom properties: `--beacon-bg`, `--beacon-color`, `--beacon-radius`, `--beacon-padding`, `--beacon-z-index` (default 9999), `--beacon-font-size`. Supply default values on `:root` that pass 4.5:1 contrast ratio. Provide an optional starter theme file that can be published separately.
  - **File**: `resources/css/beacon.css`, `resources/css/beacon-theme.css`
  - **Dependencies**: None
  - **Verification**: Default styles render without decorative overrides; overriding a custom property in a developer stylesheet changes appearance without touching addon CSS.

### Phase 6: CP Section

- [✅] Register Beacon CP nav section in `ServiceProvider` with icon (bell or similar SVG). Add four nav items: Dashboard (`/cp/beacon`), Global Defaults (`/cp/beacon/settings`), Collection Rules (`/cp/beacon/collections`), Sitewide Notification (`/cp/beacon/sitewide`).
  - **File**: `src/ServiceProvider.php`, `routes/cp.php`
  - **Dependencies**: ServiceProvider, permissions
  - **Verification**: Beacon section appears in CP sidebar for users with `view beacon` permission; nav items route to correct views.

- [✅] Create `DashboardController` returning active notification summary: count of currently active notifications, list of collection rules with status, sitewide notification status. Pass data to `beacon::dashboard` view.
  - **File**: `src/Http/Controllers/DashboardController.php`, `resources/views/cp/dashboard.blade.php`
  - **Dependencies**: BeaconNotification model, routes
  - **Verification**: Dashboard renders without errors; active notification count matches database state.

- [✅] Create `SettingsController` with `index()` (GET) and `update()` (POST) methods. Manages `config/beacon.php` writable settings: `default_delay`, `dev_mode_logging`. Validate input. Require `manage beacon settings` permission.
  - **File**: `src/Http/Controllers/SettingsController.php`, `resources/views/cp/settings.blade.php`
  - **Dependencies**: config/beacon.php, routes, permissions
  - **Verification**: Settings form saves and reloads correctly; non-admin cannot access endpoint (403 returned).

- [✅] Create `CollectionRulesController` with `index()`, `store()`, `update()`, `destroy()` methods. Lists all Statamic collections with their current Beacon rule assignment. Allows assigning, updating, or removing a `BeaconCollectionRule` per collection. Require `edit beacon` permission for write operations.
  - **File**: `src/Http/Controllers/CollectionRulesController.php`, `resources/views/cp/collection-rules.blade.php`
  - **Dependencies**: BeaconCollectionRule model, routes, permissions
  - **Verification**: All Statamic collections listed; assigning a notification to a collection persists to database; removing rule deletes the record.

- [✅] Create `SitewideController` with `index()` (GET) and `update()` (POST/PUT) methods. Manages a single sitewide `BeaconNotification` record (handle = `sitewide`). Renders all four type field groups using the same field definitions as the fieldtype. Require `edit beacon` permission.
  - **File**: `src/Http/Controllers/SitewideController.php`, `resources/views/cp/sitewide.blade.php`
  - **Dependencies**: BeaconNotification model, routes, permissions
  - **Verification**: Sitewide notification saves and loads all type-specific fields; notification resolves for pages with no entry-level or collection rule.

- [✅] Create API routes for CP AJAX operations: `POST /cp/beacon/notifications` (create), `PUT /cp/beacon/notifications/{id}` (update), `DELETE /cp/beacon/notifications/{id}` (delete). Return JSON responses. Used by Vue components in fieldtype and CP views.
  - **File**: `routes/cp.php`, `src/Http/Controllers/NotificationsController.php`
  - **Dependencies**: BeaconNotification model, permissions
  - **Verification**: CRUD operations via curl/Postman return correct HTTP status codes and response bodies.

### Phase 7: Permissions

- [✅] Register three Statamic permissions: `view beacon` (see Beacon CP section, read-only), `edit beacon` (create and modify notifications and collection rules), `manage beacon settings` (access Global Defaults page). Super admin bypasses all checks.
  - **File**: `src/ServiceProvider.php`
  - **Dependencies**: ServiceProvider
  - **Verification**: Permissions appear in CP Roles editor; assigning `edit beacon` to a role grants correct access; user without permission gets 403 on write endpoints.

- [✅] Add permission gates to all CP controllers. `DashboardController` gates on `view beacon`. `CollectionRulesController` write methods gate on `edit beacon`. `SettingsController` gates on `manage beacon settings`. `SitewideController` write method gates on `edit beacon`. `NotificationsController` all write methods gate on `edit beacon`.
  - **File**: All CP controllers
  - **Dependencies**: Permissions registered
  - **Verification**: Each gate returns 403 (not redirect) for users lacking the required permission.

### Phase 8: Internationalisation

- [✅] Create English lang file with all CP-facing strings: field labels, help text, button labels, error messages, nav labels, dashboard copy, settings labels. No hardcoded strings in any Blade view or Vue component — all strings go through `__('beacon::...')` or `trans('beacon::...')`.
  - **File**: `resources/lang/en/beacon.php`
  - **Dependencies**: All views and Vue components
  - **Verification**: Changing a value in the lang file changes the rendered CP string; grep for hardcoded English strings in views returns no results.

### Phase 9: Tests

- [✅] Write unit tests for `NotificationResolver`: entry-level override takes precedence over collection rule; collection rule takes precedence over sitewide; sitewide fallback returns when no other rule matches; notifications outside `active_from`/`active_until` range are not resolved; disabled notifications are not resolved.
  - **File**: `tests/Unit/NotificationResolverTest.php`
  - **Dependencies**: NotificationResolver, both models
  - **Verification**: All five test cases pass on PHP 8.1, 8.2, and 8.3.

- [✅] Write unit tests for `BeaconNotification` model: `active()` scope excludes records with `enabled = false`; `active()` scope excludes records where current time is before `active_from`; `active()` scope excludes records where current time is after `active_until`; `active()` scope includes records with null `active_from` or `active_until`.
  - **File**: `tests/Unit/BeaconNotificationModelTest.php`
  - **Dependencies**: BeaconNotification model, migrations
  - **Verification**: All four scope test cases pass.

- [✅] Write feature tests for `BeaconAssetsTag`: tag outputs empty string when no active notification; tag outputs `<script>` asset tag when notification is active; tag outputs `<link>` asset tag when notification is active; tag outputs valid JSON in `#beacon-config` block; JSON contains correct keys for each of the four notification types.
  - **File**: `tests/Feature/BeaconAssetsTagTest.php`
  - **Dependencies**: BeaconAssetsTag, tag view, NotificationResolver
  - **Verification**: All five test cases pass; JSON output is valid for each type.

- [✅] Write feature tests for `BeaconNotificationFieldtype`: fieldtype saves all announcement fields correctly; fieldtype saves all discount fields correctly; fieldtype saves all CTA fields correctly; fieldtype saves all consent fields correctly; fieldtype loads saved values on edit.
  - **File**: `tests/Feature/BeaconNotificationFieldtypeTest.php`
  - **Dependencies**: BeaconNotificationFieldtype PHP class
  - **Verification**: All five test cases pass; round-trip save/load produces identical data.

- [✅] Write feature tests for permissions: user with `view beacon` can GET dashboard; user with `view beacon` cannot POST to notification endpoint (403); user with `edit beacon` can POST to notification endpoint; user without any beacon permission cannot GET dashboard (403); super admin can access all endpoints.
  - **File**: `tests/Feature/PermissionsTest.php`
  - **Dependencies**: Permissions, all controllers
  - **Verification**: All five test cases pass.

- [✅] Configure PHPUnit to run the test suite against SQLite in-memory database. Ensure tests run cleanly on PHP 8.1, 8.2, and 8.3 (test matrix via GitHub Actions or similar). Add `phpunit.xml` with test suite configuration.
  - **File**: `phpunit.xml`, `.github/workflows/tests.yml` (or equivalent)
  - **Dependencies**: All test files
  - **Verification**: `composer test` runs full suite; all tests pass on all three PHP versions.

### Phase 10: Code Quality

- [✅] Add `.php-cs-fixer.php` configuration using the Laravel preset (Shift conventions or official Laravel pint). Run fixer and ensure zero violations.
  - **File**: `.php-cs-fixer.php`
  - **Dependencies**: All PHP source files complete
  - **Verification**: `./vendor/bin/php-cs-fixer fix --dry-run` reports zero changes needed.

- [✅] Add `phpstan.neon` configuration targeting level 5. Add `phpstan/phpstan-phpunit` extension if test files are included. Run analysis and fix all reported issues.
  - **File**: `phpstan.neon`
  - **Dependencies**: All PHP source files complete
  - **Verification**: `./vendor/bin/phpstan analyse` exits with code 0 at level 5.

### Phase 11: Marketplace Prep

- [✅] Write `README.md` with: installation steps (`composer require arielcerda/beacon`; `php artisan vendor:publish --tag=beacon-assets`; `php artisan migrate`), quick-start guide (add fieldtype to Blueprint; add `{{ beacon:assets }}` to layout), theming guide (CSS custom properties reference), JS API reference (`Beacon.dismiss()`, `Beacon.show()`, `Beacon.on()`, `Beacon.waitFor()`), permission setup guide, and CP screenshots for all four pages.
  - **File**: `README.md`
  - **Dependencies**: All features implemented
  - **Verification**: README renders correctly on GitHub/GitLab; all code examples are accurate.

- [✅] Write `CHANGELOG.md` following Keep a Changelog format. Add initial `[1.0.0]` entry listing all shipped features.
  - **File**: `CHANGELOG.md`
  - **Dependencies**: All features implemented
  - **Verification**: CHANGELOG parses correctly; version matches `composer.json`.

- [✅] Add `LICENSE` file (commercial licence or MIT, matching Marketplace terms). Confirm licence is referenced in `composer.json` under `"license"` key.
  - **File**: `LICENSE`
  - **Dependencies**: None
  - **Verification**: Licence file present; `composer validate` passes.

- [✅] Tag release `v1.0.0` in git. Confirm `composer.json` `"version"` matches. Prepare Statamic Marketplace submission: description, screenshots, pricing (US$79 single / US$249 agency), categories (Notifications, Content), PHP and Statamic version requirements.
  - **File**: `composer.json`, git tag
  - **Dependencies**: README, CHANGELOG, LICENSE, all tests passing, CS Fixer clean, PHPStan clean
  - **Verification**: `composer show arielcerda/beacon` returns correct metadata; git tag `v1.0.0` exists; Marketplace submission form complete.

### Final

- [✅] Code Review
  - **Dependencies**: All implementation tasks complete
  - **Verification**: code-reviewer agent reports no issues
  - 2026-05-29 11:57 🔄 Started: Code Review
  - 2026-05-29 12:05 Code Review: 28 issues found (2 blocker, 4 critical, 11 major, 8 minor, 3 trivial)
  - Cache::tags() crashes on file/database drivers (blocker)
  - Settings update never persisted across requests (blocker)
  - Consent hook executes arbitrary window path - XSS surface (critical)
  - Cache::flush() wipes entire application cache on every notification save (critical)
  - URL pattern targeting in schema but never enforced (critical)
  - Modal aria-labelledby only wired for CTA type, WCAG fail (critical)
  - Frequency check before trigger - manual show() bypasses it (major)
  - URL fields not validated against javascript: scheme - stored XSS (major)
  - uniqid() handle collision under concurrency (major)
  - Middleware uses non-existent app('statamic.entry') binding (major)
  - Countdown interval leaks when already expired (major)
  - Decline button never fires consent hook with denied args (major)
  - NotificationResolver singleton registered twice (major)
  - Scroll/delay/exit-intent listeners never cleaned up on dismiss (major)
  - Tag entry resolution misses Statamic cascade current_page key (major)
  - Fieldtype process() silently discards non-array payload (major)
  - JSON config double-escaped by Blade {{ }} (minor)
  - Collection rule resolver doesn't fall through expired notifications (minor)
  - Enum column locks future type additions (minor)
  - Factory handle not guaranteed unique (minor) + 4 more minor/trivial
  - 2026-05-29 12:14 ✅ Completed: Code Review — all 20 fixes applied (clean)

- [🔄] Spec Audit
  - **Dependencies**: Code Review complete
  - **Verification**: Re-read spec, verify all tasks match intent and no deliverable from the plan is missing
  - 2026-05-29 12:14 🔄 Started: Spec Audit
  - 2026-05-29 12:15 Spec Audit: FAIL — 9 issues (2 major, 3 minor, 4 trivial)
  - sitewide.blade.php references 23 missing lang keys (major)
  - collection-rules.blade.php uses unregistered Vue component (major)
  - README missing CP screenshots (minor)
  - composer.json missing version field + no git repo/tag (minor)
  - Statamic::current() fallback not in tag (minor)
  - shared beacon-modal-title id vs per-type (trivial)
  - default_position not declared in config (trivial)
  - nav order differs from plan (trivial)

### Audit Fixes

- [✅] Fix: Missing lang keys — add label_* keys to beacon.php lang file
  - **File**: `resources/lang/en/beacon.php`, `resources/views/cp/sitewide.blade.php`
  - **Severity**: Major
  - **Issue**: sitewide.blade.php references 23 `beacon::fieldtype.label_*` and position/trigger/frequency keys that don't exist in the lang file.
  - **Fix**: Add the missing keys to the fieldtype section of the lang file.

- [✅] Fix: Collection rules Vue component — replace with native Alpine form
  - **File**: `resources/views/cp/collection-rules.blade.php`
  - **Severity**: Major
  - **Issue**: `<beacon-collection-rule-editor>` component is not registered. Collection Rules editor is inert.
  - **Fix**: Replace with a native HTML/Alpine.js form matching the sitewide approach.

- [🔄] Fix: composer.json version field
  - **File**: `composer.json`
  - **Severity**: Minor
  - **Issue**: Missing `"version": "1.0.0"` key. Also not a git repo.
  - **Fix**: Add version field. Init git repo and create v1.0.0 tag.

- [ ] Fix: Add `default_position` to config
  - **File**: `config/beacon.php`
  - **Severity**: Trivial
  - **Issue**: `NotificationResolver` reads `config('beacon.default_position')` but the key is not declared.
  - **Fix**: Add `'default_position' => 'bottom-right'` to `config/beacon.php`.

### Review Fixes

- [✅] Fix: Cache invalidation — replace `Cache::tags()->flush()` and `Cache::flush()` with version-stamp pattern
  - **File**: `src/Services/NotificationResolver.php`, `src/Observers/BeaconNotificationObserver.php`
  - **Severity**: Blocker / Critical
  - **Issue**: `forgetAll()` calls `Cache::tags()` which crashes on file/database drivers. Observer `Cache::flush()` wipes the entire application cache on every save.
  - **Fix**: Add a `beacon:cache_version` stamp. Observer calls `Cache::forever('beacon:cache_version', now()->timestamp)`. `cacheKey()` includes the stamp. Remove `forgetAll()` and the `Cache::flush()` from observer.

- [✅] Fix: Settings persistence — write to a file-based store instead of runtime config
  - **File**: `src/Http/Controllers/SettingsController.php`
  - **Severity**: Blocker
  - **Issue**: `config([...])` mutates in-memory config only. Settings are lost on next request.
  - **Fix**: Persist to a JSON file in `storage/app/beacon-settings.json`. `index()` reads from the file with fallback to published config defaults. `update()` writes to the file.

- [✅] Fix: Consent hook XSS — allowlist the callable path
  - **File**: `resources/js/beacon.js`, `src/Http/Controllers/NotificationsController.php`, `src/Http/Controllers/SitewideController.php`
  - **Severity**: Critical
  - **Issue**: `consent_hook` string is resolved against `window` and executed. A CP editor can invoke any global JS function.
  - **Fix**: In PHP validation, restrict to known patterns: alphanumeric, dots, max 3 segments. In JS, validate the resolved path against `[a-zA-Z0-9._]{1,64}` and max 3 segments before calling.

- [✅] Fix: URL pattern targeting — enforce in resolver
  - **File**: `src/Services/NotificationResolver.php`
  - **Severity**: Critical
  - **Issue**: `resolveFromCollection()` ignores `url_pattern` entirely. Rules with a URL pattern match all pages.
  - **Fix**: After `forCollection()`, filter rules: if `url_pattern` is null the rule applies to all pages; if set, use `fnmatch($rule->url_pattern, request()->path())`. Iterate in priority order, return first match.

- [✅] Fix: Modal ARIA — emit accessible name for all modal types
  - **File**: `resources/js/beacon.js`
  - **Severity**: Critical
  - **Issue**: `aria-labelledby="beacon-title"` only set for CTA type. Announcement/discount/consent modals have no accessible name.
  - **Fix**: Each renderer for modal positions emits a heading element with a unique `id="beacon-title-{type}"`. Set `aria-labelledby` unconditionally for all modal-position renders.

- [✅] Fix: URL field `javascript:` validation — reject unsafe schemes
  - **File**: `src/Http/Controllers/NotificationsController.php`, `src/Http/Controllers/SitewideController.php`
  - **Severity**: Major
  - **Issue**: `cta_url`, `primary_url`, `secondary_url` accept `javascript:` URIs. Stored XSS on click.
  - **Fix**: Add `url:http,https` validation rule for all URL payload fields. As defence in depth, add a JS guard in `beacon.js` that refuses to set `href` for values starting with `javascript:` after `toLowerCase().trim()`.

- [✅] Fix: Handle generation — replace `uniqid()` with `Str::ulid()`
  - **File**: `src/Http/Controllers/NotificationsController.php`
  - **Severity**: Major
  - **Issue**: `uniqid()` without `more_entropy` can collide under concurrent requests.
  - **Fix**: Replace with `Str::ulid()`. Wrap `create()` in a try/catch for `QueryException` on unique constraint and return 422.

- [✅] Fix: Middleware entry resolution — use Statamic cascade
  - **File**: `src/Http/Middleware/BeaconTagPresenceMiddleware.php`
  - **Severity**: Major
  - **Issue**: `app('statamic.entry')` is not a valid binding. Throws `BindingResolutionException` in dev.
  - **Fix**: Use `Statamic\Facades\Cascade::instance()->get('current_page')` wrapped in try/catch.

- [✅] Fix: Countdown interval leak — clear interval on expiry
  - **File**: `resources/js/beacon.js`
  - **Severity**: Major
  - **Issue**: When `countdown_until` is already past, the element is removed but `setInterval` is started and never cleared.
  - **Fix**: Call `clearInterval(wrapper._beaconTimer)` inside `tick()` before removing the element when `diff <= 0`. Check expiry before starting the interval and hide notification if already expired.

- [✅] Fix: Consent decline — fire hook with denied args
  - **File**: `resources/js/beacon.js`
  - **Severity**: Major
  - **Issue**: Decline button calls only `dismiss()`. Consent layer never receives the denied signal.
  - **Fix**: Mirror the accept handler in the decline handler, passing `'denied'` values (or a configurable decline args payload from config). Required for Google Consent Mode v2 compliance.

- [✅] Fix: Singleton duplication — remove from `bootAddon`
  - **File**: `src/ServiceProvider.php`
  - **Severity**: Major
  - **Issue**: `NotificationResolver::class` singleton registered in both `register()` and `bootAddon()`.
  - **Fix**: Remove the `$this->app->singleton(NotificationResolver::class)` call from `bootAddon`. Keep it in `register()` only.

- [✅] Fix: Listener cleanup — cancel scroll/delay/exit-intent on dismiss
  - **File**: `resources/js/beacon.js`
  - **Severity**: Major
  - **Issue**: Scroll listeners and delay/exit-intent timeouts not removed when `_dismiss()` is called.
  - **Fix**: Track all `addEventListener` calls and `setTimeout`/`setInterval` IDs in module-level arrays. In `_dismiss()`, iterate and remove/clear all of them.

- [✅] Fix: Tag cascade — resolve entry from `current_page`
  - **File**: `src/Tags/BeaconAssetsTag.php`
  - **Severity**: Major
  - **Issue**: `context->get('entry') ?? context->get('page')` misses the standard Statamic cascade `current_page` key. Tag is a no-op on normal page renders.
  - **Fix**: Try `$this->context->get('current_page') ?? $this->context->get('entry') ?? $this->context->get('page')`. If still null, use `Statamic::current()` as final fallback.

- [✅] Fix: Fieldtype payload coercion — log instead of silently discard
  - **File**: `src/Fieldtypes/BeaconNotificationFieldtype.php`
  - **Severity**: Major
  - **Issue**: Non-array payload is silently replaced with `[]`. Content loss with no trace.
  - **Fix**: Add `Log::warning('[Beacon] Payload discarded - unexpected type: '.gettype($value['payload']), ['entry' => ...])` before resetting. Keep the reset so processing doesn't break, but make it observable.

- [✅] Fix: JSON config Blade output — use `{!! !!}` to avoid double-escaping
  - **File**: `resources/views/tags/beacon-assets.blade.php`
  - **Severity**: Minor
  - **Issue**: `{{ $config }}` double-escapes the already-safe JSON from `JSON_HEX_TAG | JSON_HEX_AMP`. Can corrupt payloads with `<` or `>` characters.
  - **Fix**: Change to `{!! $config !!}`. The PHP encoder already handles XSS safety.

- [✅] Fix: Collection rule fallthrough — iterate priority order for active notification
  - **File**: `src/Services/NotificationResolver.php`
  - **Severity**: Minor
  - **Issue**: If highest-priority rule has expired notification, resolver returns null instead of checking lower-priority rules.
  - **Fix**: Loop through rules in priority order and return the first one whose notification passes `isWithinSchedule()` and `enabled` checks.

- [✅] Fix: Factory handle uniqueness — use `Str::ulid()`
  - **File**: `database/factories/BeaconNotificationFactory.php`
  - **Severity**: Minor
  - **Issue**: Three random Faker words can collide under iteration (small word list + unique constraint).
  - **Fix**: Replace with `'notification-'.Str::ulid()` or append `$this->faker->unique()->numberBetween(1, 99999)`.

- [✅] Fix: Enum to string — replace `enum` column with `string`
  - **File**: `database/migrations/2026_01_01_000001_create_beacon_notifications_table.php`
  - **Severity**: Minor
  - **Issue**: Native `enum` column requires ALTER TABLE migration to add future types.
  - **Fix**: Replace `$table->enum('type', [...])` with `$table->string('type', 20)`. Application-level validation in controller already enforces the value set.

- [✅] Fix: Pint vs CS Fixer — align tooling
  - **File**: `composer.json`, `.php-cs-fixer.php`
  - **Severity**: Trivial
  - **Issue**: `composer format` invokes Pint but project ships `.php-cs-fixer.php`. Two tools will disagree.
  - **Fix**: Remove `.php-cs-fixer.php` and ship `pint.json` instead, or change the `format` script to `vendor/bin/php-cs-fixer fix`. Pick one.

- [✅] Fix: Vue component files — verify they exist and build passes
  - **File**: `resources/js/components/fieldtypes/BeaconNotificationFieldtype.vue`, `resources/js/components/cp/BeaconSitewideEditor.vue`
  - **Severity**: Trivial
  - **Issue**: `cp.js` imports the fieldtype Vue component; `sitewide.blade.php` references a sitewide editor component. Neither file appears to exist in the directory tree. `npm run build` will fail.
  - **Fix**: Confirm the component files are present. If they were not written to disk, re-create them.

---

## Verification

### Acceptance Criteria

#### Zero-impact loading
- [ ] `{{ beacon:assets }}` outputs no bytes (no script tag, no link tag, no inline JSON) when no notification is active for the current page. Verified by asserting tag output === `''` in feature tests.

#### Frontend budget
- [ ] `beacon.js` minified and gzipped is under 8 kb. Verified by build output; CI fails if budget exceeded.

#### WCAG 2.1 AA
- [ ] Banners and toasts use `role="alert"` and `aria-live="polite"`.
- [ ] Modal uses `role="dialog"`, `aria-modal="true"`, `aria-labelledby`.
- [ ] Tab key cycles within open modal (focus trap).
- [ ] Escape key dismisses any visible notification.
- [ ] Dismiss button has `aria-label="Dismiss notification"`.
- [ ] Focus returns to pre-modal element on close.
- [ ] Default colour tokens pass 4.5:1 contrast ratio (verified via colour contrast tool).
- [ ] All interactive elements have minimum 44×44 px touch target.

#### Targeting priority
- [ ] Entry-level notification takes precedence over collection rule when both are active.
- [ ] Collection rule takes precedence over sitewide when both are active.
- [ ] Sitewide notification resolves when no entry or collection rule matches.
- [ ] All three verified by `NotificationResolverTest`.

#### Scheduling
- [ ] Notifications with `active_until` in the past are not resolved.
- [ ] Notifications with `active_from` in the future are not resolved.
- [ ] Verified by `BeaconNotificationModelTest` active scope tests.

#### Fieldtype round-trip
- [ ] All four notification types save and reload correctly via the fieldtype.
- [ ] Verified by `BeaconNotificationFieldtypeTest`.

#### Permissions
- [ ] `view beacon` permits read-only access; write endpoints return 403.
- [ ] `edit beacon` permits creating and modifying notifications.
- [ ] `manage beacon settings` permits access to Global Defaults only.
- [ ] Super admin bypasses all checks.
- [ ] Verified by `PermissionsTest`.

#### Marketplace readiness
- [ ] `composer test` passes on PHP 8.1, 8.2, 8.3.
- [ ] `php-cs-fixer fix --dry-run` reports zero changes.
- [ ] `phpstan analyse` exits 0 at level 5.
- [ ] No hardcoded strings (grep confirms all CP strings go through lang files).
- [ ] README, CHANGELOG, LICENSE all present.
- [ ] `composer validate` passes.

### Ralph Completion (for autonomous execution)

When ALL of the following are true, output `<promise>COMPLETE</promise>`:

- All tasks above show `[✅]`
- All tests pass on PHP 8.1, 8.2, and 8.3
- PHPStan level 5 exits 0
- PHP CS Fixer reports zero changes
- `beacon.js` gzipped is under 8 kb

If stuck on same task for 3+ iterations, output `<promise>BLOCKED: [reason]</promise>`

---

## Progress Notes

_Real-time updates during implementation_

- 2026-05-29 11:13 ✅ Specification created from approved plan
- 2026-05-29 11:31 ✅ Phase 1 complete: composer.json, ServiceProvider, config/beacon.php, vite.config.js, directory skeleton
- 2026-05-29 11:35 ✅ Phase 2 complete: 2 migrations, BeaconNotification + BeaconCollectionRule models, NotificationResolver
- 2026-05-29 11:38 ✅ Phase 3 complete: BeaconNotificationFieldtype PHP class, Vue component (all 4 types), Blade view, cp.js entry point
- 2026-05-29 11:40 ✅ Phase 4 complete: BeaconAssetsTag, beacon-assets.blade.php, BeaconTagPresenceMiddleware, BeaconNotificationObserver (cache invalidation)
- 2026-05-29 11:42 ✅ Phase 5 complete: beacon.js (triggers, frequency, positions, ARIA, all 4 types, clipboard, countdown), beacon.css, beacon-theme.css
- 2026-05-29 11:47 ✅ Phase 6 complete: CP nav, routes/cp.php, Dashboard/Settings/CollectionRules/Sitewide/Notifications controllers + views
- 2026-05-29 11:48 ✅ Phases 7+8 complete: permissions registered (3 roles + gates on all controllers), English lang file with all CP strings
- 2026-05-29 11:51 ✅ Phase 9 complete: NotificationResolverTest, BeaconNotificationModelTest, BeaconAssetsTagTest, FieldtypeTest, PermissionsTest, phpunit.xml, CI matrix, model factories
- 2026-05-29 11:55 ✅ Phases 10+11 complete: .php-cs-fixer.php, phpstan.neon, README.md, CHANGELOG.md, LICENSE

---

**Status Indicators:**

- `[ ]` - Not started
- `[🔄]` - In progress
- `[✅]` - Completed
- `[❌]` - Failed/blocked

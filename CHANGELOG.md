# Changelog

All notable changes to Beacon will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-29

### Added

- `beacon_notification` fieldtype for attaching notifications to any Blueprint entry
- Four notification types: Announcement, Discount Code, CTA Banner, Cookie/Consent Notice
- Five display positions: `top-bar`, `bottom-bar`, `bottom-right`, `bottom-left`, `modal-center`
- Mobile responsive: positions below 480 px viewport collapse to `bottom-bar`
- iOS safe area padding for bottom-positioned notifications
- Three-level targeting priority: entry-level > collection rule > sitewide fallback
- URL pattern matching for collection-level targeting
- Server-side scheduling: `active_from` / `active_until` with Carbon timezone support
- Frequency controls: always, once per session, once per day, once ever, until dismissed
- Trigger controls: immediate, after delay, after scroll percentage, exit intent
- `{{ beacon:assets }}` Antlers tag — outputs nothing on pages without an active notification
- Inline JSON config delivered via `<script type="application/json" id="beacon-config">` — no XHR
- Vanilla JS frontend (`beacon.js`), under 8 kb minified + gzipped
- Public JS API: `Beacon.dismiss()`, `Beacon.show()`, `Beacon.on(event, fn)`, `Beacon.waitFor(promise)`
- Clipboard copy for discount codes with fallback support
- Countdown timer for discount expiry
- Dedicated Beacon CP section: Dashboard, Sitewide Notification, Collection Rules, Settings
- WCAG 2.1 AA accessibility: correct ARIA roles, focus trap on modals, Escape dismiss, focus return
- Permission model: `view beacon`, `edit beacon`, `manage beacon settings`
- i18n-ready: all CP strings go through lang files
- CSS theming contract: layout-only base styles with CSS custom properties
- Optional starter theme (`beacon-theme.css`)
- View publishing via `php artisan vendor:publish --tag=beacon-views`
- Dev-mode logging when multiple notification rules match the same page
- Missing-tag middleware warning when `{{ beacon:assets }}` is absent on an active notification page
- PHPStan level 5+, PHP CS Fixer (Laravel preset)
- Test suite covering PHP 8.1, 8.2, 8.3 and Statamic v4/v5

[Unreleased]: https://github.com/arielcerda/beacon/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/arielcerda/beacon/releases/tag/v1.0.0

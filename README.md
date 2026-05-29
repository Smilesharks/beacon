# Beacon for Statamic

Attach contextual notifications to any Statamic entry without code. Announcements, discount codes, CTA banners, and cookie consent notices — all managed from the Control Panel.

## Requirements

- PHP 8.1+
- Laravel 10 or 11
- Statamic 4 or 5

## Installation

```bash
composer require arielcerda/beacon
php artisan vendor:publish --tag=beacon-assets
php artisan migrate
```

## Quick start

**1. Add the tag to your layout**

Add `{{ beacon:assets }}` before the closing `</body>` tag in your Antlers layout file. The tag outputs nothing on pages with no active notification — zero performance impact.

```antlers
{{ beacon:assets }}
</body>
```

**2. Add the fieldtype to a Blueprint**

Open any Blueprint in the Statamic CP, add a new field, and select "Beacon Notification" from the field picker. Drag it to the bottom of your fields list.

**3. Configure a notification**

Open any entry, scroll to the Beacon field, enable the toggle, choose a notification type, fill in the content, and publish.

## CP sections

Navigate to **Beacon** in the CP sidebar to:

- View active notification counts on the **Dashboard**
- Set a **Sitewide notification** (shown as a fallback when no entry or collection rule applies)
- Assign notifications to entire **Collection**s
- Configure **Settings** (default delay, dev logging)

## Theming

Beacon ships with minimal base CSS. Override CSS custom properties in your own stylesheet:

```css
:root {
    --beacon-bg: #1a1a1a;
    --beacon-color: #ffffff;
    --beacon-radius: 6px;
    --beacon-padding: 1rem;
    --beacon-z-index: 9999;
    --beacon-font-size: 0.875rem;
    --beacon-max-width: 400px;
}
```

To include Beacon's optional starter theme:

```html
<link rel="stylesheet" href="/vendor/beacon/beacon-theme.css">
```

## Publishing views

To customise the notification HTML structure:

```bash
php artisan vendor:publish --tag=beacon-views
```

Views are published to `resources/views/vendor/beacon/`.

## JavaScript API

The `window.Beacon` object is available after the beacon.js script loads:

```js
// Dismiss the active notification
Beacon.dismiss();

// Show the notification manually
Beacon.show();

// Listen to events
Beacon.on('show', (data) => console.log('Notification shown', data));
Beacon.on('dismiss', (data) => console.log('Notification dismissed', data));

// Delay until a promise resolves (useful for cookie consent integrations)
Beacon.waitFor(consentReadyPromise);
```

## Permissions

Three roles are available in the Statamic CP Roles editor:

- **View Beacon** — read-only access to notification status
- **Edit Beacon** — create and modify notifications
- **Manage Beacon settings** — access to Global Defaults page

Super admins always have full access.

## Cookie consent conflicts

By default Beacon waits 2 seconds before showing any notification. This avoids competing with your site's own cookie banner. Change the default in the Beacon Settings page or override it per-notification.

To delay Beacon until your consent library signals readiness:

```js
const consentReady = new Promise((resolve) => {
    // Your consent library calls resolve() when the visitor has made a choice
    window.onConsentReady = resolve;
});

Beacon.waitFor(consentReady);
```

## Accessibility

All notification types are built to WCAG 2.1 AA:

- Banners and toasts use `role="alert"` and `aria-live="polite"`
- Modals trap focus, return focus on close, and support Escape to dismiss
- All interactive elements meet the 44x44 px minimum touch target
- Default colour tokens pass 4.5:1 contrast ratio

## Notification targeting priority

When multiple rules match the same page, the highest priority wins:

1. Entry-level (`beacon_notification` fieldtype on the entry)
2. Collection rule (set in Beacon > Collection Rules)
3. Sitewide fallback (set in Beacon > Sitewide Notification)

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Licence

This software is sold under a commercial licence. See [LICENSE](LICENSE) for details.

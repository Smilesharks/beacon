<?php

return [

    // Navigation
    'nav' => [
        'section_title' => 'Beacon',
        'dashboard' => 'Dashboard',
        'sitewide' => 'Sitewide Notification',
        'collections' => 'Collection Rules',
        'settings' => 'Settings',
    ],

    // Permissions
    'permissions' => [
        'view' => 'View Beacon notifications',
        'edit' => 'Create and edit Beacon notifications',
        'manage_settings' => 'Manage Beacon settings',
    ],

    // Fieldtype
    'fieldtype' => [
        'enable_label' => 'Enable notification for this entry',
        'override_collection' => 'Override collection-level rule',
        'type_label' => 'Notification type',
        'type_announcement' => 'Announcement',
        'type_discount' => 'Discount Code',
        'type_cta' => 'CTA Banner',
        'type_consent' => 'Cookie / Consent Notice',
        'status_enabled' => ':type notification enabled',
        'status_disabled' => 'No notification',

        // Generic field labels (used in sitewide and other Blade CP views)
        'label_enabled' => 'Enable notification',
        'label_type' => 'Type',
        'label_message' => 'Message',
        'label_cta_label' => 'Button label',
        'label_cta_url' => 'Button URL',
        'label_code' => 'Discount code',
        'label_code_label' => 'Copy button label',
        'label_show_countdown' => 'Show countdown timer',
        'label_countdown_until' => 'Countdown until',
        'label_headline' => 'Headline',
        'label_description' => 'Description',
        'label_primary_label' => 'Primary button label',
        'label_primary_url' => 'Primary button URL',
        'label_secondary_label' => 'Secondary link label',
        'label_secondary_url' => 'Secondary link URL',
        'label_accept_label' => 'Accept button label',
        'label_decline_label' => 'Decline button label',
        'label_consent_hook' => 'Consent callback (optional)',
        'label_position' => 'Position',
        'label_trigger' => 'Show when',
        'label_frequency' => 'Show frequency',
        'label_active_from' => 'Active from',
        'label_active_until' => 'Active until',

        // Announcement
        'announcement_message' => 'Message',
        'announcement_message_placeholder' => 'Enter your announcement...',
        'cta_label' => 'Button label',
        'cta_label_placeholder' => 'e.g. Learn more',
        'cta_url' => 'Button URL',
        'cta_url_placeholder' => 'https://',

        // Discount
        'discount_message' => 'Message',
        'discount_code' => 'Discount code',
        'discount_code_label' => 'Copy button label',
        'discount_code_label_placeholder' => 'Copy code',
        'discount_show_countdown' => 'Show countdown timer',
        'discount_countdown_until' => 'Countdown until',

        // CTA Banner
        'cta_headline' => 'Headline',
        'cta_description' => 'Description',
        'cta_primary_label' => 'Primary button label',
        'cta_primary_url' => 'Primary button URL',
        'cta_secondary_label' => 'Secondary link label',
        'cta_secondary_url' => 'Secondary link URL',

        // Consent
        'consent_message' => 'Message',
        'consent_accept_label' => 'Accept button label',
        'consent_accept_placeholder' => 'Accept',
        'consent_decline_label' => 'Decline button label',
        'consent_decline_placeholder' => 'Decline',
        'consent_hook' => 'Consent callback (optional)',
        'consent_hook_help' => 'A global function to call when the visitor accepts. Example: window.gtag',

        // Display settings
        'display_settings' => 'Display settings',
        'position_label' => 'Position',
        'position_top_bar' => 'Top bar',
        'position_bottom_bar' => 'Bottom bar',
        'position_bottom_right' => 'Bottom right',
        'position_bottom_left' => 'Bottom left',
        'position_modal_center' => 'Modal',
        'trigger_label' => 'Show when',
        'trigger_immediate' => 'Immediately',
        'trigger_delay' => 'After a delay',
        'trigger_scroll' => 'After scrolling',
        'trigger_exit_intent' => 'On exit intent',
        'trigger_delay_seconds' => 'Delay (seconds)',
        'trigger_scroll_percent' => 'Scroll depth (%)',
        'frequency_label' => 'Show frequency',
        'frequency_always' => 'Every page load',
        'frequency_session' => 'Once per session',
        'frequency_daily' => 'Once per day',
        'frequency_permanent' => 'Once ever',
        'frequency_dismissed' => 'Until dismissed',
        'active_from' => 'Active from',
        'active_until' => 'Active until',
    ],

    // Dashboard
    'dashboard' => [
        'active_count' => ':count active notifications',
        'sitewide_heading' => 'Sitewide notification',
        'sitewide_none' => 'No sitewide notification configured.',
        'collection_rules_heading' => 'Collection rules',
        'no_collection_rules' => 'No collection rules configured.',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'edit' => 'Edit',
        'manage_rules' => 'Manage rules',
    ],

    // Settings
    'settings' => [
        'saved' => 'Settings saved.',
        'save' => 'Save settings',
        'default_delay_label' => 'Default trigger delay (seconds)',
        'default_delay_help' => 'Seconds to wait before showing a notification when no explicit delay is set. Helps avoid conflict with cookie banners.',
        'dev_mode_logging_label' => 'Enable dev mode logging',
        'dev_mode_logging_help' => 'Logs a warning when multiple notification rules match the same page. Only active outside production.',
    ],

    // Collection rules
    'collections' => [
        'intro' => 'Assign a notification to an entire collection. Entry-level notifications take priority over these rules.',
        'collection' => 'Collection',
        'notification' => 'Notification',
        'url_pattern' => 'URL pattern',
        'status' => 'Status',
        'priority' => 'Priority',
        'enabled' => 'Enabled',
        'no_notification' => 'None',
        'remove' => 'Remove',
        'cancel' => 'Cancel',
        'saving' => 'Saving…',
        'confirm_delete' => 'Remove this collection rule?',
    ],

    // Sitewide
    'sitewide' => [
        'intro' => 'This notification shows on any page that does not have an entry-level or collection-level rule.',
    ],

];

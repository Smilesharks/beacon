{{-- Fieldtype view — this file is intentionally minimal. --}}
{{-- Rendering is handled by BeaconNotificationFieldtype::preProcess(). --}}
<beacon-notification-fieldtype
    :value="{{ json_encode($value ?? []) }}"
    :config="{{ json_encode($config ?? []) }}"
    handle="{{ $handle ?? '' }}"
/>

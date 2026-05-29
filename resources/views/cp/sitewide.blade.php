@extends('statamic::layout')
@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100">
            {{ session('success') }}
        </div>
    @endif

    <p class="text-sm text-gray-500 mb-4">{{ __('beacon::sitewide.intro') }}</p>

    <form
        method="POST"
        action="{{ route('statamic.cp.beacon.sitewide.update') }}"
        class="card p-6"
        x-data="{
            type: '{{ old('type', $notification->type ?? 'announcement') }}',
            trigger: '{{ old('trigger', $notification->trigger ?? 'immediate') }}',
            payload: @js(old('payload', $notification->payload ?? []))
        }"
    >
        @csrf

        @if ($errors->any())
            <div class="mb-4 p-4 rounded bg-red-100 text-red-800">
                <ul class="list-disc list-inside text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Enabled --}}
        <div class="flex items-center gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">
                {{ __('beacon::fieldtype.label_enabled') }}
            </label>
            <label class="flex items-center gap-2">
                <input type="hidden" name="enabled" value="0">
                <input
                    type="checkbox"
                    name="enabled"
                    value="1"
                    class="toggle"
                    {{ old('enabled', $notification->enabled ?? false) ? 'checked' : '' }}
                >
            </label>
        </div>

        {{-- Type --}}
        <div class="flex items-start gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_type') }}
            </label>
            <select name="type" x-model="type" class="input-text w-full max-w-xs">
                @foreach(['announcement', 'discount', 'cta', 'consent'] as $t)
                    <option value="{{ $t }}">{{ __('beacon::fieldtype.type_' . $t) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Payload fields: Announcement --}}
        <div x-show="type === 'announcement'" x-cloak class="space-y-4 mb-4 pl-44">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_message') }}</label>
                <textarea name="payload[message]" rows="3" class="input-text w-full">{{ old('payload.message', $notification->payload['message'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_cta_label') }}</label>
                    <input type="text" name="payload[cta_label]" class="input-text w-full" value="{{ old('payload.cta_label', $notification->payload['cta_label'] ?? '') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_cta_url') }}</label>
                    <input type="text" name="payload[cta_url]" class="input-text w-full" value="{{ old('payload.cta_url', $notification->payload['cta_url'] ?? '') }}">
                </div>
            </div>
        </div>

        {{-- Payload fields: Discount --}}
        <div x-show="type === 'discount'" x-cloak class="space-y-4 mb-4 pl-44">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_message') }}</label>
                <textarea name="payload[message]" rows="3" class="input-text w-full">{{ old('payload.message', $notification->payload['message'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_code') }}</label>
                    <input type="text" name="payload[code]" class="input-text w-full" value="{{ old('payload.code', $notification->payload['code'] ?? '') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_code_label') }}</label>
                    <input type="text" name="payload[code_label]" class="input-text w-full" value="{{ old('payload.code_label', $notification->payload['code_label'] ?? '') }}">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="payload[show_countdown]" value="1" {{ old('payload.show_countdown', $notification->payload['show_countdown'] ?? false) ? 'checked' : '' }}>
                <label class="text-sm text-gray-700 dark:text-gray-300">{{ __('beacon::fieldtype.label_show_countdown') }}</label>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_countdown_until') }}</label>
                <input type="datetime-local" name="payload[countdown_until]" class="input-text" value="{{ old('payload.countdown_until', $notification->payload['countdown_until'] ?? '') }}">
            </div>
        </div>

        {{-- Payload fields: CTA --}}
        <div x-show="type === 'cta'" x-cloak class="space-y-4 mb-4 pl-44">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_headline') }}</label>
                <input type="text" name="payload[headline]" class="input-text w-full" value="{{ old('payload.headline', $notification->payload['headline'] ?? '') }}">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_description') }}</label>
                <textarea name="payload[description]" rows="2" class="input-text w-full">{{ old('payload.description', $notification->payload['description'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_primary_label') }}</label>
                    <input type="text" name="payload[primary_label]" class="input-text w-full" value="{{ old('payload.primary_label', $notification->payload['primary_label'] ?? '') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_primary_url') }}</label>
                    <input type="text" name="payload[primary_url]" class="input-text w-full" value="{{ old('payload.primary_url', $notification->payload['primary_url'] ?? '') }}">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_secondary_label') }}</label>
                    <input type="text" name="payload[secondary_label]" class="input-text w-full" value="{{ old('payload.secondary_label', $notification->payload['secondary_label'] ?? '') }}">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_secondary_url') }}</label>
                    <input type="text" name="payload[secondary_url]" class="input-text w-full" value="{{ old('payload.secondary_url', $notification->payload['secondary_url'] ?? '') }}">
                </div>
            </div>
        </div>

        {{-- Payload fields: Consent --}}
        <div x-show="type === 'consent'" x-cloak class="space-y-4 mb-4 pl-44">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_message') }}</label>
                <textarea name="payload[message]" rows="3" class="input-text w-full">{{ old('payload.message', $notification->payload['message'] ?? '') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_accept_label') }}</label>
                    <input type="text" name="payload[accept_label]" class="input-text w-full" value="{{ old('payload.accept_label', $notification->payload['accept_label'] ?? '') }}" placeholder="Accept">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_decline_label') }}</label>
                    <input type="text" name="payload[decline_label]" class="input-text w-full" value="{{ old('payload.decline_label', $notification->payload['decline_label'] ?? '') }}" placeholder="Decline">
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::fieldtype.label_consent_hook') }}</label>
                <input type="text" name="payload[consent_hook]" class="input-text w-full" placeholder="gtag" value="{{ old('payload.consent_hook', $notification->payload['consent_hook'] ?? '') }}">
                <p class="text-xs text-gray-500 mt-1">{{ __('beacon::fieldtype.consent_hook_help') }}</p>
            </div>
        </div>

        <hr class="my-6 border-gray-200 dark:border-gray-700">

        {{-- Position --}}
        <div class="flex items-start gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_position') }}
            </label>
            <select name="position" class="input-text w-full max-w-xs">
                @foreach(['top-bar', 'bottom-bar', 'bottom-right', 'bottom-left', 'modal-center'] as $pos)
                    <option value="{{ $pos }}" {{ old('position', $notification->position ?? 'bottom-right') === $pos ? 'selected' : '' }}>
                        {{ __('beacon::fieldtype.position_' . str_replace('-', '_', $pos)) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Trigger --}}
        <div class="flex items-start gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_trigger') }}
            </label>
            <div class="flex gap-3 items-center">
                <select name="trigger" x-model="trigger" class="input-text max-w-xs">
                    @foreach(['immediate', 'delay', 'scroll', 'exit_intent'] as $t)
                        <option value="{{ $t }}">{{ __('beacon::fieldtype.trigger_' . $t) }}</option>
                    @endforeach
                </select>
                <input
                    type="number"
                    name="trigger_value"
                    x-show="trigger === 'delay' || trigger === 'scroll'"
                    x-cloak
                    class="input-text w-24"
                    value="{{ old('trigger_value', $notification->trigger_value ?? '') }}"
                    min="0"
                    placeholder="0"
                >
            </div>
        </div>

        {{-- Frequency --}}
        <div class="flex items-start gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_frequency') }}
            </label>
            <select name="frequency" class="input-text w-full max-w-xs">
                @foreach(['always', 'session', 'daily', 'permanent', 'dismissed'] as $f)
                    <option value="{{ $f }}" {{ old('frequency', $notification->frequency ?? 'session') === $f ? 'selected' : '' }}>
                        {{ __('beacon::fieldtype.frequency_' . $f) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Schedule --}}
        <div class="flex items-start gap-3 mb-4">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_active_from') }}
            </label>
            <input
                type="datetime-local"
                name="active_from"
                class="input-text"
                value="{{ old('active_from', $notification->active_from ? $notification->active_from->format('Y-m-d\TH:i') : '') }}"
            >
        </div>

        <div class="flex items-start gap-3 mb-6">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0 pt-2">
                {{ __('beacon::fieldtype.label_active_until') }}
            </label>
            <input
                type="datetime-local"
                name="active_until"
                class="input-text"
                value="{{ old('active_until', $notification->active_until ? $notification->active_until->format('Y-m-d\TH:i') : '') }}"
            >
        </div>

        <button type="submit" class="btn-primary">
            {{ __('beacon::settings.save') }}
        </button>
    </form>
@endsection

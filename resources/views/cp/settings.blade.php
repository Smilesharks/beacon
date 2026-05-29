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

    <form method="POST" action="{{ route('statamic.cp.beacon.settings.update') }}" class="card p-6">
        @csrf

        <div class="mb-4">
            <label class="block font-medium mb-1">
                {{ __('beacon::settings.default_delay_label') }}
            </label>
            <p class="text-sm text-gray-500 mb-2">{{ __('beacon::settings.default_delay_help') }}</p>
            <input
                type="number"
                name="default_delay"
                value="{{ old('default_delay', $default_delay) }}"
                min="0"
                max="300"
                class="input-text w-24"
            />
            @error('default_delay')
                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input
                    type="hidden"
                    name="dev_mode_logging"
                    value="0"
                />
                <input
                    type="checkbox"
                    name="dev_mode_logging"
                    value="1"
                    {{ $dev_mode_logging ? 'checked' : '' }}
                    class="toggle-input"
                />
                <span class="font-medium">{{ __('beacon::settings.dev_mode_logging_label') }}</span>
            </label>
            <p class="text-sm text-gray-500 mt-1 ml-6">{{ __('beacon::settings.dev_mode_logging_help') }}</p>
        </div>

        <button type="submit" class="btn-primary">
            {{ __('beacon::settings.save') }}
        </button>
    </form>
@endsection

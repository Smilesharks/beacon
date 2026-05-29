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

    <div class="card p-4 mb-4">
        <h2 class="font-bold mb-2">{{ __('beacon::dashboard.active_count', ['count' => $activeCount]) }}</h2>
    </div>

    <div class="card p-4 mb-4">
        <h3 class="font-bold mb-2">{{ __('beacon::dashboard.sitewide_heading') }}</h3>
        @if ($sitewide && $sitewide->enabled)
            <span class="badge-sm bg-green-100 text-green-800">{{ __('beacon::dashboard.status_active') }}</span>
            <span class="ml-2 text-sm text-gray-500">{{ __('beacon::fieldtype.type_' . $sitewide->type) }}</span>
        @else
            <span class="text-sm text-gray-500">{{ __('beacon::dashboard.sitewide_none') }}</span>
        @endif
        <div class="mt-2">
            <a href="{{ route('beacon.sitewide.index') }}" class="btn btn-sm">{{ __('beacon::dashboard.edit') }}</a>
        </div>
    </div>

    <div class="card p-4">
        <h3 class="font-bold mb-2">{{ __('beacon::dashboard.collection_rules_heading') }}</h3>
        @if (count($collectionRules) > 0)
            <ul class="divide-y">
                @foreach ($collectionRules as $rule)
                    <li class="py-2 flex items-center justify-between">
                        <span class="font-mono text-sm">{{ $rule['collection_handle'] }}</span>
                        <span class="text-sm text-gray-500">
                            {{ isset($rule['notification']['type']) ? __('beacon::fieldtype.type_' . $rule['notification']['type']) : '—' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500">{{ __('beacon::dashboard.no_collection_rules') }}</p>
        @endif
        <div class="mt-2">
            <a href="{{ route('beacon.collections.index') }}" class="btn btn-sm">{{ __('beacon::dashboard.manage_rules') }}</a>
        </div>
    </div>
@endsection

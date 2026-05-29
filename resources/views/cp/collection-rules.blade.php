@extends('statamic::layout')
@section('title', $title)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="flex-1">{{ $title }}</h1>
    </div>

    <p class="text-sm text-gray-500 mb-4">{{ __('beacon::collections.intro') }}</p>

    <div
        class="card"
        x-data="{
            editing: null,
            saving: false,
            deleting: false,
            form: { notification_id: null, url_pattern: '', enabled: true, priority: 0 },
            notifications: @js($notifications->map(fn($n) => ['id' => $n->id, 'label' => $n->handle . ' (' . $n->type . ')'])),

            open(collection, rule) {
                this.editing = collection;
                this.form.notification_id = rule ? rule.notification_id : null;
                this.form.url_pattern = rule ? (rule.url_pattern ?? '') : '';
                this.form.enabled = rule ? rule.enabled : true;
                this.form.priority = rule ? rule.priority : 0;
            },

            async save(collection, ruleId) {
                this.saving = true;
                const url = ruleId
                    ? '{{ route('beacon.collections.update', ['id' => '__ID__']) }}'.replace('__ID__', ruleId)
                    : '{{ route('beacon.collections.store') }}';
                const method = ruleId ? 'PUT' : 'POST';
                const body = { ...this.form, collection_handle: collection };
                try {
                    const res = await fetch(url, {
                        method,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(body)
                    });
                    if (res.ok) { window.location.reload(); } else { alert('Save failed.'); }
                } finally { this.saving = false; }
            },

            async remove(ruleId) {
                if (!confirm('{{ __('beacon::collections.confirm_delete') }}')) return;
                this.deleting = true;
                try {
                    await fetch('{{ route('beacon.collections.destroy', ['id' => '__ID__']) }}'.replace('__ID__', ruleId), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                    });
                    window.location.reload();
                } finally { this.deleting = false; }
            }
        }"
    >
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th>{{ __('beacon::collections.collection') }}</th>
                    <th>{{ __('beacon::collections.notification') }}</th>
                    <th>{{ __('beacon::collections.url_pattern') }}</th>
                    <th>{{ __('beacon::collections.status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($collections as $collection)
                    @php
                        $rule = $rules->get($collection->handle());
                        $ruleJson = $rule ? json_encode(['id' => $rule->id, 'notification_id' => $rule->notification_id, 'url_pattern' => $rule->url_pattern, 'enabled' => $rule->enabled, 'priority' => $rule->priority]) : 'null';
                    @endphp
                    <tr>
                        <td class="font-medium">{{ $collection->title() }}</td>
                        <td>
                            @if ($rule && $rule->notification)
                                {{ __('beacon::fieldtype.type_' . $rule->notification->type) }}
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-sm text-gray-500">{{ $rule->url_pattern ?? '—' }}</span>
                        </td>
                        <td>
                            @if ($rule && $rule->enabled)
                                <span class="badge-sm bg-green-100 text-green-800">{{ __('beacon::dashboard.status_active') }}</span>
                            @else
                                <span class="badge-sm bg-gray-100 text-gray-600">{{ __('beacon::dashboard.status_inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-right flex gap-2 justify-end">
                            @can('edit beacon')
                                <button
                                    type="button"
                                    class="btn btn-sm"
                                    @click="open('{{ $collection->handle() }}', {{ $ruleJson }})"
                                >
                                    {{ __('beacon::dashboard.edit') }}
                                </button>
                                @if ($rule)
                                    <button
                                        type="button"
                                        class="btn btn-sm text-red-600"
                                        @click="remove({{ $rule->id }})"
                                        :disabled="deleting"
                                    >
                                        {{ __('beacon::collections.remove') }}
                                    </button>
                                @endif
                            @endcan
                        </td>
                    </tr>

                    {{-- Inline editor row --}}
                    <tr x-show="editing === '{{ $collection->handle() }}'" x-cloak>
                        <td colspan="5" class="bg-gray-50 dark:bg-dark-200 p-4">
                            <div class="grid grid-cols-2 gap-4 max-w-2xl">
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::collections.notification') }}</label>
                                    <select x-model="form.notification_id" class="input-text w-full">
                                        <option value="">— {{ __('beacon::collections.no_notification') }} —</option>
                                        <template x-for="n in notifications" :key="n.id">
                                            <option :value="n.id" x-text="n.label"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::collections.url_pattern') }}</label>
                                    <input type="text" x-model="form.url_pattern" class="input-text w-full" placeholder="/shop/*">
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">{{ __('beacon::collections.priority') }}</label>
                                    <input type="number" x-model.number="form.priority" class="input-text w-24" min="0">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" x-model="form.enabled" class="toggle">
                                    <label class="text-sm text-gray-700 dark:text-gray-300">{{ __('beacon::collections.enabled') }}</label>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <button
                                    type="button"
                                    class="btn-primary btn-sm"
                                    :disabled="saving"
                                    @click="save('{{ $collection->handle() }}', {{ $rule?->id ?? 'null' }})"
                                >
                                    <span x-show="!saving">{{ __('beacon::settings.save') }}</span>
                                    <span x-show="saving">{{ __('beacon::collections.saving') }}</span>
                                </button>
                                <button type="button" class="btn btn-sm" @click="editing = null">
                                    {{ __('beacon::collections.cancel') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

<?php

namespace Smilesharks\Beacon\Services;

use Smilesharks\Beacon\Models\BeaconCollectionRule;
use Smilesharks\Beacon\Models\BeaconNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Statamic\Entries\Entry;

class NotificationResolver
{
    /**
     * Resolve the active notification for an entry, using three-level priority:
     * 1. Entry-level override (stored in the entry's beacon_notification field)
     * 2. Collection rule
     * 3. Sitewide fallback (handle = 'sitewide')
     */
    public function resolve(Entry $entry): ?BeaconNotification
    {
        $cacheKey = $this->cacheKey($entry);
        $cacheTtl = (int) config('beacon.cache_ttl', 300);

        if ($cacheTtl > 0) {
            return Cache::remember($cacheKey, $cacheTtl, fn () => $this->doResolve($entry));
        }

        return $this->doResolve($entry);
    }

    public function forget(Entry $entry): void
    {
        Cache::forget($this->cacheKey($entry));
    }

    public function bumpVersion(): void
    {
        Cache::forever('beacon:cache_version', now()->timestamp);
    }

    private function doResolve(Entry $entry): ?BeaconNotification
    {
        $matches = [];

        $entryLevel = $this->resolveFromEntry($entry);
        $collectionLevel = $this->resolveFromCollection($entry);
        $sitewide = $this->resolveSitewide();

        if ($entryLevel) {
            $matches[] = 'entry-level';
        }
        if ($collectionLevel) {
            $matches[] = 'collection-rule';
        }
        if ($sitewide) {
            $matches[] = 'sitewide';
        }

        if (count($matches) > 1 && $this->isDevLoggingEnabled()) {
            Log::debug('[Beacon] Multiple notification rules match entry "'.$entry->id().'": '.implode(', ', $matches).'. Using highest priority.');
        }

        return $entryLevel ?? $collectionLevel ?? $sitewide;
    }

    private function resolveFromEntry(Entry $entry): ?BeaconNotification
    {
        $data = $entry->get('beacon_notification');

        if (empty($data) || empty($data['enabled'])) {
            return null;
        }

        $notification = new BeaconNotification();
        $notification->handle = 'entry:'.$entry->id();
        $notification->type = $data['type'] ?? 'announcement';
        $notification->enabled = true;
        $notification->position = $data['position'] ?? config('beacon.default_position', 'bottom-right');
        $notification->trigger = $data['trigger'] ?? 'immediate';
        $notification->trigger_value = $data['trigger_value'] ?? null;
        $notification->frequency = $data['frequency'] ?? 'session';
        $notification->active_from = isset($data['active_from']) ? \Carbon\Carbon::parse($data['active_from']) : null;
        $notification->active_until = isset($data['active_until']) ? \Carbon\Carbon::parse($data['active_until']) : null;
        $notification->payload = $data['payload'] ?? [];
        $notification->exists = false;

        if (! $this->isWithinSchedule($notification)) {
            return null;
        }

        return $notification;
    }

    private function resolveFromCollection(Entry $entry): ?BeaconNotification
    {
        $currentPath = request()->path();

        $rules = BeaconCollectionRule::forCollection($entry->collection()->handle())
            ->with('notification')
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->notification) {
                continue;
            }

            // null url_pattern matches all pages; otherwise fnmatch against current path
            if ($rule->url_pattern !== null && ! fnmatch(ltrim($rule->url_pattern, '/'), $currentPath)) {
                continue;
            }

            $notification = $rule->notification;

            if (! $notification->enabled || ! $this->isWithinSchedule($notification)) {
                continue;
            }

            return $notification;
        }

        return null;
    }

    private function resolveSitewide(): ?BeaconNotification
    {
        return BeaconNotification::active()->where('handle', 'sitewide')->first();
    }

    private function isWithinSchedule(BeaconNotification $notification): bool
    {
        $now = \Carbon\Carbon::now();

        if ($notification->active_from && $notification->active_from->gt($now)) {
            return false;
        }

        if ($notification->active_until && $notification->active_until->lt($now)) {
            return false;
        }

        return true;
    }

    private function isDevLoggingEnabled(): bool
    {
        return (bool) config('beacon.dev_mode_logging', true) && ! app()->isProduction();
    }

    private function cacheKey(Entry $entry): string
    {
        $version = Cache::get('beacon:cache_version', 0);

        return 'beacon:resolver:'.md5($entry->id().($entry->collection()->handle() ?? '').$version);
    }
}

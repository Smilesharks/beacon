<?php

namespace Smilesharks\Beacon\Services;

use Smilesharks\Beacon\NotificationData;
use Smilesharks\Beacon\Repositories\CollectionRuleRepository;
use Smilesharks\Beacon\Repositories\NotificationRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Statamic\Contracts\Entries\Entry;

class NotificationResolver
{
    public function __construct(
        private readonly NotificationRepository $notifRepo,
        private readonly CollectionRuleRepository $ruleRepo,
    ) {}

    /**
     * Resolve the active notification for an entry, using three-level priority:
     * 1. Entry-level override (stored in the entry's beacon_notification field)
     * 2. Collection rule
     * 3. Sitewide fallback (handle = 'sitewide')
     */
    public function resolve(Entry $entry): ?NotificationData
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

    private function doResolve(Entry $entry): ?NotificationData
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

    private function resolveFromEntry(Entry $entry): ?NotificationData
    {
        $data = $entry->get('beacon_notification');

        if (empty($data) || empty($data['enabled'])) {
            return null;
        }

        $notification = new NotificationData([
            'handle' => 'entry:'.$entry->id(),
            'type' => $data['type'] ?? 'announcement',
            'enabled' => true,
            'position' => $data['position'] ?? config('beacon.default_position', 'bottom-right'),
            'trigger' => $data['trigger'] ?? 'immediate',
            'trigger_value' => $data['trigger_value'] ?? null,
            'frequency' => $data['frequency'] ?? 'session',
            'active_from' => $data['active_from'] ?? null,
            'active_until' => $data['active_until'] ?? null,
            'payload' => $data['payload'] ?? [],
        ]);

        if (! $this->isWithinSchedule($notification)) {
            return null;
        }

        return $notification;
    }

    private function resolveFromCollection(Entry $entry): ?NotificationData
    {
        $collection = $entry->collection();

        if ($collection === null) {
            return null;
        }

        $currentPath = request()->path();
        $rules = $this->ruleRepo->forCollection($collection->handle());

        foreach ($rules as $rule) {
            if (empty($rule['notification'])) {
                continue;
            }

            if (($rule['url_pattern'] ?? null) !== null && ! fnmatch(ltrim($rule['url_pattern'], '/'), $currentPath)) {
                continue;
            }

            $notification = new NotificationData($rule['notification']);

            if (! $notification->enabled || ! $this->isWithinSchedule($notification)) {
                continue;
            }

            return $notification;
        }

        return null;
    }

    private function resolveSitewide(): ?NotificationData
    {
        $data = $this->notifRepo->findByHandle('sitewide');

        if ($data === null) {
            return null;
        }

        $notification = new NotificationData($data);

        if (! $notification->enabled || ! $this->isWithinSchedule($notification)) {
            return null;
        }

        return $notification;
    }

    private function isWithinSchedule(NotificationData $notification): bool
    {
        $now = Carbon::now();

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

        return 'beacon:resolver:'.md5($entry->id().(($entry->collection()?->handle()) ?? '').$version);
    }
}

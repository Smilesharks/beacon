<?php

namespace Smilesharks\Beacon\Repositories;

use Illuminate\Support\Facades\File;

class CollectionRuleRepository
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    private function storagePath(): string
    {
        $base = config('beacon.storage_path', storage_path('app/beacon'));

        return $base.'/collection-rules.json';
    }

    private function read(): array
    {
        $path = $this->storagePath();

        if (! File::exists($path)) {
            return [];
        }

        $contents = File::get($path);
        $data = json_decode($contents, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "beacon: collection-rules.json is corrupt ({$path}): ".json_last_error_msg()
            );
        }

        return $data ?? [];
    }

    private function write(array $data): void
    {
        $path = $this->storagePath();
        File::ensureDirectoryExists(dirname($path));

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $tmp = tempnam(dirname($path), 'beacon-');
        if ($tmp === false) {
            throw new \RuntimeException("Unable to create temp file in ".dirname($path));
        }

        try {
            File::put($tmp, $json);
            chmod($tmp, 0664 & ~umask());
            if (! rename($tmp, $path)) {
                throw new \RuntimeException("Failed to rename {$tmp} to {$path}");
            }
        } catch (\Throwable $e) {
            @unlink($tmp);
            throw $e;
        }
    }

    public function findByCollectionHandle(string $collectionHandle): ?array
    {
        return $this->read()[$collectionHandle] ?? null;
    }

    public function all(): array
    {
        $rules = $this->read();

        foreach ($rules as &$rule) {
            $rule['notification'] = ! empty($rule['notification_handle'])
                ? $this->notifications->findByHandle($rule['notification_handle'])
                : null;
        }

        return $rules;
    }

    public function allEnabled(): array
    {
        $rules = array_values(array_filter($this->read(), fn ($r) => ! empty($r['enabled'])));

        return array_map(function (array $rule) {
            $rule['notification'] = ! empty($rule['notification_handle'])
                ? $this->notifications->findByHandle($rule['notification_handle'])
                : null;

            return $rule;
        }, $rules);
    }

    public function forCollection(string $handle): array
    {
        $rules = array_filter(
            $this->read(),
            fn ($r) => ($r['collection_handle'] ?? '') === $handle && ! empty($r['enabled'])
        );

        usort($rules, fn ($a, $b) => ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0));

        return array_map(function (array $rule) {
            $rule['notification'] = ! empty($rule['notification_handle'])
                ? $this->notifications->findByHandle($rule['notification_handle'])
                : null;

            return $rule;
        }, array_values($rules));
    }

    public function save(array $data): array
    {
        $handle = $data['collection_handle'] ?? null;

        if (! $handle) {
            throw new \InvalidArgumentException('Collection handle is required.');
        }

        $store = $this->read();
        $store[$handle] = $data;
        $this->write($store);

        app(\Smilesharks\Beacon\Services\NotificationResolver::class)->bumpVersion();

        return $data;
    }

    public function delete(string $collectionHandle): void
    {
        $store = $this->read();
        unset($store[$collectionHandle]);
        $this->write($store);

        app(\Smilesharks\Beacon\Services\NotificationResolver::class)->bumpVersion();
    }
}

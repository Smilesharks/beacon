<?php

namespace Smilesharks\Beacon\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class NotificationRepository
{
    private function storagePath(): string
    {
        $base = config('beacon.storage_path', storage_path('app/beacon'));

        return $base.'/notifications.json';
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
                "beacon: notifications.json is corrupt ({$path}): ".json_last_error_msg()
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

    public function all(): array
    {
        return array_values($this->read());
    }

    public function active(): array
    {
        $now = Carbon::now();

        return array_values(array_filter($this->read(), function (array $n) use ($now) {
            if (empty($n['enabled'])) {
                return false;
            }
            try {
                if (! empty($n['active_from']) && Carbon::parse($n['active_from'])->gt($now)) {
                    return false;
                }
                if (! empty($n['active_until']) && Carbon::parse($n['active_until'])->lt($now)) {
                    return false;
                }
            } catch (\Throwable $e) {
                Log::warning('beacon: malformed date on notification '.($n['handle'] ?? '?').': '.$e->getMessage());
                return false;
            }

            return true;
        }));
    }

    public function findByHandle(string $handle): ?array
    {
        return $this->read()[$handle] ?? null;
    }

    public function save(array $data): array
    {
        $handle = $data['handle'] ?? null;

        if (! $handle) {
            throw new \InvalidArgumentException('Notification handle is required.');
        }

        $store = $this->read();
        $store[$handle] = $data;
        $this->write($store);

        app(\Smilesharks\Beacon\Services\NotificationResolver::class)->bumpVersion();

        return $data;
    }

    public function delete(string $handle): void
    {
        $store = $this->read();
        unset($store[$handle]);
        $this->write($store);

        app(\Smilesharks\Beacon\Services\NotificationResolver::class)->bumpVersion();
    }

    public function firstOrNew(string $handle, array $defaults): array
    {
        return $this->read()[$handle] ?? array_merge($defaults, ['handle' => $handle]);
    }
}

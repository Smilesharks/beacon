<?php

namespace Smilesharks\Beacon;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight value object wrapping a notification array.
 * Allows property-access syntax ($n->handle, $n->type, etc.) throughout
 * the codebase without depending on Eloquent models.
 */
class NotificationData
{
    public function __construct(private readonly array $data) {}

    public function __get(string $name): mixed
    {
        $value = $this->data[$name] ?? null;

        if ($name === 'enabled') {
            return (bool) $value;
        }

        if (in_array($name, ['active_from', 'active_until'], true) && $value !== null) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                Log::warning('beacon: malformed '.$name.' on notification '.($this->data['handle'] ?? '?').': '.$e->getMessage());
                return null;
            }
        }

        return $value;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

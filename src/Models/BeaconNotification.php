<?php

namespace Smilesharks\Beacon\Models;

use Smilesharks\Beacon\Database\Factories\BeaconNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BeaconNotification extends Model
{
    use HasFactory;

    protected static function newFactory(): BeaconNotificationFactory
    {
        return BeaconNotificationFactory::new();
    }

    protected $table = 'beacon_notifications';

    protected $fillable = [
        'handle',
        'type',
        'enabled',
        'position',
        'trigger',
        'trigger_value',
        'frequency',
        'active_from',
        'active_until',
        'payload',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'payload' => 'array',
    ];

    public function collectionRules(): HasMany
    {
        return $this->hasMany(BeaconCollectionRule::class, 'notification_id');
    }

    /**
     * Scope to notifications that are enabled and within their active date range.
     * Null active_from / active_until are treated as open-ended (always active).
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('enabled', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('active_from')
                    ->orWhere('active_from', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('active_until')
                    ->orWhere('active_until', '>=', $now);
            });
    }
}

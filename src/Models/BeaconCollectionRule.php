<?php

namespace Smilesharks\Beacon\Models;

use Smilesharks\Beacon\Database\Factories\BeaconCollectionRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeaconCollectionRule extends Model
{
    use HasFactory;

    protected static function newFactory(): BeaconCollectionRuleFactory
    {
        return BeaconCollectionRuleFactory::new();
    }

    protected $table = 'beacon_collection_rules';

    protected $fillable = [
        'collection_handle',
        'notification_id',
        'url_pattern',
        'enabled',
        'priority',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'priority' => 'integer',
    ];

    public function notification(): BelongsTo
    {
        return $this->belongsTo(BeaconNotification::class, 'notification_id');
    }

    public function scopeForCollection(Builder $query, string $handle): Builder
    {
        return $query->where('collection_handle', $handle)->where('enabled', true);
    }
}

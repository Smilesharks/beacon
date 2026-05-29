<?php

namespace Arielcerda\Beacon\Database\Factories;

use Arielcerda\Beacon\Models\BeaconCollectionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeaconCollectionRuleFactory extends Factory
{
    protected $model = BeaconCollectionRule::class;

    public function definition(): array
    {
        return [
            'collection_handle' => $this->faker->slug(1),
            'notification_id' => null,
            'url_pattern' => null,
            'enabled' => true,
            'priority' => 0,
        ];
    }
}

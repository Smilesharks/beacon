<?php

namespace Arielcerda\Beacon\Database\Factories;

use Arielcerda\Beacon\Models\BeaconNotification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BeaconNotificationFactory extends Factory
{
    protected $model = BeaconNotification::class;

    public function definition(): array
    {
        return [
            'handle' => 'notification-'.Str::ulid(),
            'type' => $this->faker->randomElement(['announcement', 'discount', 'cta', 'consent']),
            'enabled' => true,
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'trigger_value' => null,
            'frequency' => 'session',
            'active_from' => null,
            'active_until' => null,
            'payload' => ['message' => $this->faker->sentence()],
        ];
    }

    public function disabled(): static
    {
        return $this->state(['enabled' => false]);
    }

    public function sitewide(): static
    {
        return $this->state(['handle' => 'sitewide']);
    }
}

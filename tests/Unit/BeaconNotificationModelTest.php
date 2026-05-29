<?php

namespace Smilesharks\Beacon\Tests\Unit;

use Smilesharks\Beacon\Models\BeaconNotification;
use Smilesharks\Beacon\Tests\TestCase;
use Carbon\Carbon;

class BeaconNotificationModelTest extends TestCase
{
    public function test_active_scope_excludes_disabled_records(): void
    {
        BeaconNotification::factory()->create(['enabled' => false]);

        $this->assertCount(0, BeaconNotification::active()->get());
    }

    public function test_active_scope_excludes_records_with_future_active_from(): void
    {
        BeaconNotification::factory()->create([
            'enabled' => true,
            'active_from' => Carbon::now()->addHour(),
        ]);

        $this->assertCount(0, BeaconNotification::active()->get());
    }

    public function test_active_scope_excludes_records_past_active_until(): void
    {
        BeaconNotification::factory()->create([
            'enabled' => true,
            'active_until' => Carbon::now()->subHour(),
        ]);

        $this->assertCount(0, BeaconNotification::active()->get());
    }

    public function test_active_scope_includes_records_with_null_active_from(): void
    {
        BeaconNotification::factory()->create([
            'enabled' => true,
            'active_from' => null,
            'active_until' => null,
        ]);

        $this->assertCount(1, BeaconNotification::active()->get());
    }

    public function test_active_scope_includes_records_within_date_range(): void
    {
        BeaconNotification::factory()->create([
            'enabled' => true,
            'active_from' => Carbon::now()->subHour(),
            'active_until' => Carbon::now()->addHour(),
        ]);

        $this->assertCount(1, BeaconNotification::active()->get());
    }

    public function test_payload_is_cast_to_array(): void
    {
        $notification = BeaconNotification::factory()->create([
            'payload' => ['message' => 'Hello world'],
        ]);

        $fresh = BeaconNotification::find($notification->id);
        $this->assertIsArray($fresh->payload);
        $this->assertEquals('Hello world', $fresh->payload['message']);
    }
}

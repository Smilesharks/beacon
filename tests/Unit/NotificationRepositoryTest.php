<?php

namespace Smilesharks\Beacon\Tests\Unit;

use Smilesharks\Beacon\Repositories\NotificationRepository;
use Smilesharks\Beacon\Tests\TestCase;
use Carbon\Carbon;

class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(NotificationRepository::class);
    }

    public function test_active_scope_excludes_disabled_records(): void
    {
        $this->repo->save($this->notificationData(['enabled' => false]));

        $this->assertCount(0, $this->repo->active());
    }

    public function test_active_scope_excludes_records_with_future_active_from(): void
    {
        $this->repo->save($this->notificationData([
            'enabled' => true,
            'active_from' => Carbon::now()->addHour()->toIso8601String(),
        ]));

        $this->assertCount(0, $this->repo->active());
    }

    public function test_active_scope_excludes_records_past_active_until(): void
    {
        $this->repo->save($this->notificationData([
            'enabled' => true,
            'active_until' => Carbon::now()->subHour()->toIso8601String(),
        ]));

        $this->assertCount(0, $this->repo->active());
    }

    public function test_active_scope_includes_records_with_null_active_from(): void
    {
        $this->repo->save($this->notificationData([
            'enabled' => true,
            'active_from' => null,
            'active_until' => null,
        ]));

        $this->assertCount(1, $this->repo->active());
    }

    public function test_active_scope_includes_records_within_date_range(): void
    {
        $this->repo->save($this->notificationData([
            'enabled' => true,
            'active_from' => Carbon::now()->subHour()->toIso8601String(),
            'active_until' => Carbon::now()->addHour()->toIso8601String(),
        ]));

        $this->assertCount(1, $this->repo->active());
    }

    public function test_payload_is_stored_and_retrieved_as_array(): void
    {
        $this->repo->save($this->notificationData([
            'payload' => ['message' => 'Hello world'],
        ]));

        $retrieved = $this->repo->findByHandle('test-notification');
        $this->assertIsArray($retrieved['payload']);
        $this->assertEquals('Hello world', $retrieved['payload']['message']);
    }

    private function notificationData(array $overrides = []): array
    {
        return array_merge([
            'handle' => 'test-notification',
            'type' => 'announcement',
            'enabled' => true,
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'frequency' => 'session',
            'payload' => [],
        ], $overrides);
    }
}

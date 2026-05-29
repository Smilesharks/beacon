<?php

namespace Smilesharks\Beacon\Tests\Unit;

use Smilesharks\Beacon\Repositories\CollectionRuleRepository;
use Smilesharks\Beacon\Repositories\NotificationRepository;
use Smilesharks\Beacon\Services\NotificationResolver;
use Smilesharks\Beacon\Tests\TestCase;
use Carbon\Carbon;
use Mockery;
use Statamic\Contracts\Entries\Collection;
use Statamic\Entries\Entry;

class NotificationResolverTest extends TestCase
{
    private NotificationResolver $resolver;
    private NotificationRepository $notifRepo;
    private CollectionRuleRepository $ruleRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notifRepo = app(NotificationRepository::class);
        $this->ruleRepo = app(CollectionRuleRepository::class);
        $this->resolver = app(NotificationResolver::class);
    }

    public function test_entry_level_takes_precedence_over_collection_rule(): void
    {
        $this->mockCollection('blog');

        $this->notifRepo->save($this->notificationData([
            'handle' => 'collection-notification',
            'type' => 'announcement',
        ]));
        $this->ruleRepo->save([
            'collection_handle' => 'blog',
            'notification_handle' => 'collection-notification',
            'enabled' => true,
            'priority' => 0,
        ]);

        $entry = $this->mockEntry('blog', [
            'enabled' => true,
            'type' => 'discount',
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'frequency' => 'session',
            'payload' => ['message' => 'Entry notification', 'code' => 'SAVE10'],
        ]);

        $result = $this->resolver->resolve($entry);

        $this->assertNotNull($result);
        $this->assertStringStartsWith('entry:', $result->handle);
        $this->assertEquals('discount', $result->type);
    }

    public function test_collection_rule_takes_precedence_over_sitewide(): void
    {
        $this->mockCollection('blog');

        $this->notifRepo->save($this->notificationData([
            'handle' => 'sitewide',
            'type' => 'announcement',
        ]));

        $this->notifRepo->save($this->notificationData([
            'handle' => 'collection-notification',
            'type' => 'cta',
        ]));
        $this->ruleRepo->save([
            'collection_handle' => 'blog',
            'notification_handle' => 'collection-notification',
            'enabled' => true,
            'priority' => 0,
        ]);

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNotNull($result);
        $this->assertEquals('collection-notification', $result->handle);
        $this->assertEquals('cta', $result->type);
    }

    public function test_sitewide_fallback_when_no_other_rule_matches(): void
    {
        $this->notifRepo->save($this->notificationData([
            'handle' => 'sitewide',
            'type' => 'consent',
        ]));

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNotNull($result);
        $this->assertEquals('sitewide', $result->handle);
    }

    public function test_returns_null_when_no_rules_match(): void
    {
        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
    }

    public function test_notifications_outside_active_from_range_are_not_resolved(): void
    {
        $this->notifRepo->save($this->notificationData([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'active_from' => Carbon::now()->addHour()->toIso8601String(),
        ]));

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
    }

    public function test_notifications_outside_active_until_range_are_not_resolved(): void
    {
        $this->notifRepo->save($this->notificationData([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'active_until' => Carbon::now()->subHour()->toIso8601String(),
        ]));

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
    }

    public function test_disabled_notifications_are_not_resolved(): void
    {
        $this->notifRepo->save($this->notificationData([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'enabled' => false,
        ]));

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
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

    private function mockCollection(string $handle): Collection
    {
        $collection = Mockery::mock(Collection::class);
        $collection->shouldReceive('handle')->andReturn($handle);
        return $collection;
    }

    private function mockEntry(string $collectionHandle, ?array $beaconData): Entry
    {
        $collection = $this->mockCollection($collectionHandle);

        $entry = Mockery::mock(Entry::class);
        $entry->shouldReceive('collection')->andReturn($collection);
        $entry->shouldReceive('id')->andReturn('test-entry-123');
        $entry->shouldReceive('get')->with('beacon_notification')->andReturn($beaconData);

        return $entry;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}

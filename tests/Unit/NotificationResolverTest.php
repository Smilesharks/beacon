<?php

namespace Arielcerda\Beacon\Tests\Unit;

use Arielcerda\Beacon\Models\BeaconCollectionRule;
use Arielcerda\Beacon\Models\BeaconNotification;
use Arielcerda\Beacon\Services\NotificationResolver;
use Arielcerda\Beacon\Tests\TestCase;
use Carbon\Carbon;
use Mockery;
use Statamic\Contracts\Entries\Collection;
use Statamic\Entries\Entry;

class NotificationResolverTest extends TestCase
{
    private NotificationResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new NotificationResolver();
    }

    public function test_entry_level_takes_precedence_over_collection_rule(): void
    {
        $collection = $this->mockCollection('blog');

        // Create a collection-level notification
        $collectionNotification = BeaconNotification::factory()->create([
            'handle' => 'collection-notification',
            'type' => 'announcement',
            'enabled' => true,
        ]);
        BeaconCollectionRule::factory()->create([
            'collection_handle' => 'blog',
            'notification_id' => $collectionNotification->id,
            'enabled' => true,
        ]);

        // Entry-level data overrides the collection rule
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
        $collection = $this->mockCollection('blog');

        // Sitewide notification
        BeaconNotification::factory()->create([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'enabled' => true,
        ]);

        // Collection-level notification
        $collectionNotification = BeaconNotification::factory()->create([
            'handle' => 'collection-notification',
            'type' => 'cta',
            'enabled' => true,
        ]);
        BeaconCollectionRule::factory()->create([
            'collection_handle' => 'blog',
            'notification_id' => $collectionNotification->id,
            'enabled' => true,
        ]);

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNotNull($result);
        $this->assertEquals('collection-notification', $result->handle);
        $this->assertEquals('cta', $result->type);
    }

    public function test_sitewide_fallback_when_no_other_rule_matches(): void
    {
        BeaconNotification::factory()->create([
            'handle' => 'sitewide',
            'type' => 'consent',
            'enabled' => true,
        ]);

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
        BeaconNotification::factory()->create([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'enabled' => true,
            'active_from' => Carbon::now()->addHour(), // future
        ]);

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
    }

    public function test_notifications_outside_active_until_range_are_not_resolved(): void
    {
        BeaconNotification::factory()->create([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'enabled' => true,
            'active_until' => Carbon::now()->subHour(), // past
        ]);

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
    }

    public function test_disabled_notifications_are_not_resolved(): void
    {
        BeaconNotification::factory()->create([
            'handle' => 'sitewide',
            'type' => 'announcement',
            'enabled' => false,
        ]);

        $entry = $this->mockEntry('blog', null);

        $result = $this->resolver->resolve($entry);

        $this->assertNull($result);
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

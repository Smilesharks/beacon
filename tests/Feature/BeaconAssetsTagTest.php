<?php

namespace Smilesharks\Beacon\Tests\Feature;

use Smilesharks\Beacon\NotificationData;
use Smilesharks\Beacon\Services\NotificationResolver;
use Smilesharks\Beacon\Tags\BeaconAssetsTag;
use Smilesharks\Beacon\Tests\TestCase;
use Mockery;
use Statamic\Entries\Entry;
use Statamic\Tags\Context;

class BeaconAssetsTagTest extends TestCase
{
    private function makeTag(?NotificationData $resolvedNotification): BeaconAssetsTag
    {
        $resolver = Mockery::mock(NotificationResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($resolvedNotification);

        $tag = new BeaconAssetsTag($resolver);

        $entry = Mockery::mock(Entry::class);
        $entry->shouldReceive('id')->andReturn('test-entry');

        $context = new Context(['entry' => $entry]);
        $tag->setContext($context);

        return $tag;
    }

    private function makeNotification(array $data = []): NotificationData
    {
        return new NotificationData(array_merge([
            'handle' => 'test',
            'type' => 'announcement',
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'trigger_value' => null,
            'frequency' => 'session',
            'payload' => [],
        ], $data));
    }

    public function test_outputs_empty_string_when_no_active_notification(): void
    {
        $tag = $this->makeTag(null);

        $output = $tag->assets();

        $this->assertSame('', $output);
    }

    public function test_outputs_script_tag_when_notification_is_active(): void
    {
        $notification = $this->makeNotification(['payload' => ['message' => 'Hello']]);

        $tag = $this->makeTag($notification);
        $output = $tag->assets();

        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('beacon.js', $output);
    }

    public function test_outputs_link_tag_when_notification_is_active(): void
    {
        $notification = $this->makeNotification(['payload' => ['message' => 'Hello']]);

        $tag = $this->makeTag($notification);
        $output = $tag->assets();

        $this->assertStringContainsString('<link', $output);
        $this->assertStringContainsString('beacon.css', $output);
    }

    public function test_outputs_valid_json_config_block(): void
    {
        $notification = $this->makeNotification([
            'handle' => 'test-discount',
            'type' => 'discount',
            'payload' => ['message' => 'Save 20%', 'code' => 'SAVE20'],
        ]);

        $tag = $this->makeTag($notification);
        $output = $tag->assets();

        $this->assertStringContainsString('id="beacon-config"', $output);

        preg_match('/<script type="application\/json" id="beacon-config">(.+?)<\/script>/s', $output, $matches);
        $this->assertNotEmpty($matches[1] ?? '');
        $config = json_decode($matches[1], true);
        $this->assertIsArray($config);
        $this->assertNull(json_last_error() === JSON_ERROR_NONE ? null : 'JSON parse error');
    }

    public function test_json_config_contains_correct_keys_for_announcement_type(): void
    {
        $notification = $this->makeNotification([
            'handle' => 'announcement-1',
            'type' => 'announcement',
            'position' => 'top-bar',
            'frequency' => 'always',
            'payload' => ['message' => 'Big news!', 'cta_label' => 'Read more', 'cta_url' => 'https://example.com'],
        ]);

        $tag = $this->makeTag($notification);
        $output = $tag->assets();

        preg_match('/<script type="application\/json" id="beacon-config">(.+?)<\/script>/s', $output, $matches);
        $config = json_decode($matches[1], true);

        $this->assertEquals('announcement', $config['type']);
        $this->assertEquals('top-bar', $config['position']);
        $this->assertArrayHasKey('payload', $config);
        $this->assertEquals('Big news!', $config['payload']['message']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }
}

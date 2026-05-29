<?php

namespace Smilesharks\Beacon\Tests\Feature;

use Smilesharks\Beacon\Fieldtypes\BeaconNotificationFieldtype;
use Smilesharks\Beacon\Tests\TestCase;

class BeaconNotificationFieldtypeTest extends TestCase
{
    private BeaconNotificationFieldtype $fieldtype;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fieldtype = new BeaconNotificationFieldtype();
    }

    public function test_saves_and_loads_announcement_fields(): void
    {
        $input = [
            'enabled' => true,
            'type' => 'announcement',
            'position' => 'top-bar',
            'trigger' => 'immediate',
            'trigger_value' => null,
            'frequency' => 'always',
            'active_from' => null,
            'active_until' => null,
            'override_collection' => false,
            'payload' => [
                'message' => 'New product launch!',
                'cta_label' => 'Shop now',
                'cta_url' => 'https://example.com/shop',
            ],
        ];

        $processed = $this->fieldtype->process($input);
        $preProcessed = $this->fieldtype->preProcess($processed);

        $this->assertEquals(true, $preProcessed['enabled']);
        $this->assertEquals('announcement', $preProcessed['type']);
        $this->assertEquals('New product launch!', $preProcessed['payload']['message']);
        $this->assertEquals('Shop now', $preProcessed['payload']['cta_label']);
    }

    public function test_saves_and_loads_discount_fields(): void
    {
        $input = [
            'enabled' => true,
            'type' => 'discount',
            'position' => 'bottom-right',
            'trigger' => 'delay',
            'trigger_value' => '5',
            'frequency' => 'session',
            'active_from' => null,
            'active_until' => null,
            'override_collection' => false,
            'payload' => [
                'message' => 'Use code SAVE20',
                'code' => 'SAVE20',
                'code_label' => 'Copy',
                'show_countdown' => true,
                'countdown_until' => '2027-12-31T23:59:59',
            ],
        ];

        $processed = $this->fieldtype->process($input);
        $preProcessed = $this->fieldtype->preProcess($processed);

        $this->assertEquals('discount', $preProcessed['type']);
        $this->assertEquals('SAVE20', $preProcessed['payload']['code']);
        $this->assertTrue($preProcessed['payload']['show_countdown']);
    }

    public function test_saves_and_loads_cta_fields(): void
    {
        $input = [
            'enabled' => true,
            'type' => 'cta',
            'position' => 'modal-center',
            'trigger' => 'scroll',
            'trigger_value' => '50',
            'frequency' => 'session',
            'active_from' => null,
            'active_until' => null,
            'override_collection' => false,
            'payload' => [
                'headline' => 'Special offer',
                'description' => 'Limited time only',
                'primary_label' => 'Get deal',
                'primary_url' => 'https://example.com/deal',
                'secondary_label' => 'No thanks',
                'secondary_url' => '#',
            ],
        ];

        $processed = $this->fieldtype->process($input);
        $preProcessed = $this->fieldtype->preProcess($processed);

        $this->assertEquals('cta', $preProcessed['type']);
        $this->assertEquals('Special offer', $preProcessed['payload']['headline']);
        $this->assertEquals('Get deal', $preProcessed['payload']['primary_label']);
    }

    public function test_saves_and_loads_consent_fields(): void
    {
        $input = [
            'enabled' => true,
            'type' => 'consent',
            'position' => 'bottom-bar',
            'trigger' => 'immediate',
            'trigger_value' => null,
            'frequency' => 'permanent',
            'active_from' => null,
            'active_until' => null,
            'override_collection' => false,
            'payload' => [
                'message' => 'We use cookies.',
                'accept_label' => 'Accept all',
                'decline_label' => 'Decline',
                'consent_hook' => 'window.gtag',
            ],
        ];

        $processed = $this->fieldtype->process($input);
        $preProcessed = $this->fieldtype->preProcess($processed);

        $this->assertEquals('consent', $preProcessed['type']);
        $this->assertEquals('We use cookies.', $preProcessed['payload']['message']);
        $this->assertEquals('Accept all', $preProcessed['payload']['accept_label']);
        $this->assertEquals('window.gtag', $preProcessed['payload']['consent_hook']);
    }

    public function test_returns_defaults_when_empty_value_passed(): void
    {
        $result = $this->fieldtype->preProcess(null);

        $this->assertFalse($result['enabled']);
        $this->assertEquals('announcement', $result['type']);
        $this->assertEquals('bottom-right', $result['position']);
    }

    public function test_process_ensures_payload_is_array(): void
    {
        $input = [
            'enabled' => false,
            'type' => 'announcement',
            'payload' => 'not-an-array',
        ];

        $result = $this->fieldtype->process($input);

        $this->assertIsArray($result['payload']);
    }
}

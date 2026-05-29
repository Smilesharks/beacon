<?php

namespace Arielcerda\Beacon\Fieldtypes;

use Illuminate\Support\Facades\Log;
use Statamic\Fields\Fieldtype;

class BeaconNotificationFieldtype extends Fieldtype
{
    protected static $handle = 'beacon_notification';

    protected $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6zM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3z"/></svg>';

    public function configFieldItems(): array
    {
        return [];
    }

    public function preProcess(mixed $value): array
    {
        if (empty($value) || ! is_array($value)) {
            return $this->defaultValue();
        }

        return $value;
    }

    public function process(mixed $value): array
    {
        if (empty($value) || ! is_array($value)) {
            return $this->defaultValue();
        }

        if (isset($value['payload']) && ! is_array($value['payload'])) {
            Log::warning('[Beacon] Fieldtype payload discarded — unexpected type: '.gettype($value['payload']));
            $value['payload'] = [];
        }

        return $value;
    }

    public function preProcessIndex(mixed $value): string
    {
        if (empty($value['enabled'])) {
            return __('beacon::fieldtype.status_disabled');
        }

        $type = $value['type'] ?? 'announcement';

        return __('beacon::fieldtype.status_enabled', ['type' => __('beacon::fieldtype.type_'.$type)]);
    }

    private function defaultValue(): array
    {
        return [
            'enabled' => false,
            'type' => 'announcement',
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'trigger_value' => null,
            'frequency' => 'session',
            'active_from' => null,
            'active_until' => null,
            'override_collection' => false,
            'payload' => [],
        ];
    }
}

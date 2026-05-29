<?php

namespace Arielcerda\Beacon\Tags;

use Arielcerda\Beacon\Services\NotificationResolver;
use Illuminate\Support\Facades\Log;
use Statamic\Tags\Tags;
use Statamic\View\Cascade;

class BeaconAssetsTag extends Tags
{
    protected static $handle = 'beacon';

    protected NotificationResolver $resolver;

    public function __construct(NotificationResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * {{ beacon:assets }}
     *
     * Outputs nothing when no active notification is found for the current page.
     * Outputs asset tags + inline JSON config when an active notification is found.
     */
    public function assets(): string
    {
        // Try cascade keys in priority order: current_page (standard page render),
        // then entry (collection tag loop context), then page (legacy alias)
        $entry = $this->context->get('current_page')
            ?? $this->context->get('entry')
            ?? $this->context->get('page');

        if (! $entry) {
            // Last resort: ask Statamic's cascade directly
            try {
                $entry = Cascade::instance()->get('current_page');
            } catch (\Throwable) {
                // Cascade may not be available in all contexts
            }
        }

        if (! $entry) {
            if (! app()->isProduction()) {
                Log::warning('[Beacon] {{ beacon:assets }} could not find the current entry in template context.');
            }

            return '';
        }

        $notification = $this->resolver->resolve($entry);

        if (! $notification) {
            return '';
        }

        return view('beacon::tags.beacon-assets', [
            'notification' => $notification,
            'config' => $this->buildConfig($notification),
        ])->render();
    }

    private function buildConfig(mixed $notification): string
    {
        $data = [
            'handle' => $notification->handle,
            'type' => $notification->type,
            'position' => $notification->position,
            'trigger' => $notification->trigger,
            'trigger_value' => $notification->trigger_value,
            'frequency' => $notification->frequency,
            'default_delay' => config('beacon.default_delay', 2),
            'payload' => $notification->payload ?? [],
        ];

        return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
    }
}

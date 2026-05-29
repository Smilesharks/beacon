<?php

namespace Arielcerda\Beacon\Http\Middleware;

use Arielcerda\Beacon\Services\NotificationResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BeaconTagPresenceMiddleware
{
    public function __construct(private NotificationResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only run in non-production and only for HTML responses
        if (app()->isProduction()) {
            return $response;
        }

        if (! method_exists($response, 'getContent')) {
            return $response;
        }

        $content = $response->getContent();

        // Only check pages that contain actual HTML
        if (! $content || ! str_contains($content, '<html')) {
            return $response;
        }

        // If beacon-config is already present, tag is in place
        if (str_contains($content, 'id="beacon-config"')) {
            return $response;
        }

        // Resolve the current entry from the Statamic cascade
        try {
            $entry = \Statamic\View\Cascade::instance()->get('current_page');
        } catch (\Throwable) {
            return $response;
        }

        if (! $entry) {
            return $response;
        }

        $notification = $this->resolver->resolve($entry);

        if ($notification) {
            Log::warning('[Beacon] Active notification "'.$notification->handle.'" found for this page but {{ beacon:assets }} tag is missing from the page output. Add {{ beacon:assets }} to your layout to display notifications.');
        }

        return $response;
    }
}

<?php

namespace Arielcerda\Beacon\Observers;

use Arielcerda\Beacon\Models\BeaconNotification;
use Arielcerda\Beacon\Services\NotificationResolver;

class BeaconNotificationObserver
{
    public function saved(BeaconNotification $notification): void
    {
        app(NotificationResolver::class)->bumpVersion();
    }

    public function deleted(BeaconNotification $notification): void
    {
        app(NotificationResolver::class)->bumpVersion();
    }
}

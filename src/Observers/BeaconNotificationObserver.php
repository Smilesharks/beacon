<?php

namespace Smilesharks\Beacon\Observers;

use Smilesharks\Beacon\Models\BeaconNotification;
use Smilesharks\Beacon\Services\NotificationResolver;

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

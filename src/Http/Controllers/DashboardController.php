<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\NotificationData;
use Smilesharks\Beacon\Repositories\CollectionRuleRepository;
use Smilesharks\Beacon\Repositories\NotificationRepository;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $notifRepo,
        private readonly CollectionRuleRepository $ruleRepo,
    ) {
        $this->middleware('can:view beacon');
    }

    public function index(Request $request)
    {
        $activeCount = count($this->notifRepo->active());
        $sitewideRaw = $this->notifRepo->findByHandle('sitewide');
        $sitewide = $sitewideRaw ? new NotificationData($sitewideRaw) : null;
        $collectionRules = $this->ruleRepo->allEnabled();

        return view('beacon::cp.dashboard', [
            'title' => __('beacon::nav.dashboard'),
            'activeCount' => $activeCount,
            'sitewide' => $sitewide,
            'collectionRules' => $collectionRules,
        ]);
    }
}

<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\Models\BeaconCollectionRule;
use Smilesharks\Beacon\Models\BeaconNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view beacon');
    }

    public function index(Request $request)
    {
        $activeCount = BeaconNotification::active()->count();
        $sitewide = BeaconNotification::where('handle', 'sitewide')->first();
        $collectionRules = BeaconCollectionRule::with('notification')
            ->where('enabled', true)
            ->get();

        return view('beacon::cp.dashboard', [
            'title' => __('beacon::nav.dashboard'),
            'activeCount' => $activeCount,
            'sitewide' => $sitewide,
            'collectionRules' => $collectionRules,
        ]);
    }
}

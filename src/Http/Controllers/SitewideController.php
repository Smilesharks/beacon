<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\NotificationData;
use Smilesharks\Beacon\Repositories\NotificationRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SitewideController extends Controller
{
    public function __construct(private readonly NotificationRepository $repo)
    {
        $this->middleware('can:view beacon')->only('index');
        $this->middleware('can:edit beacon')->only('update');
    }

    public function index()
    {
        $notification = new NotificationData($this->repo->firstOrNew('sitewide', [
            'type' => 'announcement',
            'enabled' => false,
            'position' => 'bottom-right',
            'trigger' => 'immediate',
            'frequency' => 'session',
            'payload' => [],
        ]));

        return view('beacon::cp.sitewide', [
            'title' => __('beacon::nav.sitewide'),
            'notification' => $notification,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['boolean'],
            'type' => ['required', 'in:announcement,discount,cta,consent'],
            'position' => ['required', 'in:top-bar,bottom-bar,bottom-right,bottom-left,modal-center'],
            'trigger' => ['required', 'in:immediate,delay,scroll,exit_intent'],
            'trigger_value' => ['nullable', 'string'],
            'frequency' => ['required', 'in:always,session,daily,permanent,dismissed'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
            'payload' => ['array'],
            'payload.cta_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            'payload.primary_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            'payload.secondary_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            'payload.consent_hook' => ['nullable', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9]{0,30}(\.[a-zA-Z][a-zA-Z0-9]{0,30}){0,2}$/'],
        ]);

        $validated['enabled'] = (bool) ($validated['enabled'] ?? false);
        $validated['handle'] = 'sitewide';

        $this->repo->save($validated);

        return redirect()->route('statamic.cp.beacon.sitewide.index')
            ->with('success', __('beacon::settings.saved'));
    }
}

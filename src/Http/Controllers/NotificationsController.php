<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\Models\BeaconNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:edit beacon');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateNotification($request);

        if (empty($validated['handle'])) {
            $validated['handle'] = $validated['type'].'-'.Str::ulid();
        }

        try {
            $notification = BeaconNotification::create($validated);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'unique')) {
                throw ValidationException::withMessages(['handle' => 'A notification with this handle already exists.']);
            }
            throw $e;
        }

        return response()->json($notification, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $notification = BeaconNotification::findOrFail($id);

        $validated = $this->validateNotification($request);
        $notification->update($validated);

        return response()->json($notification->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $notification = BeaconNotification::findOrFail($id);
        $notification->delete();

        return response()->json(null, 204);
    }

    private function validateNotification(Request $request): array
    {
        return $request->validate([
            'handle' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:announcement,discount,cta,consent'],
            'enabled' => ['boolean'],
            'position' => ['required', 'in:top-bar,bottom-bar,bottom-right,bottom-left,modal-center'],
            'trigger' => ['required', 'in:immediate,delay,scroll,exit_intent'],
            'trigger_value' => ['nullable', 'string'],
            'frequency' => ['required', 'in:always,session,daily,permanent,dismissed'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date'],
            'payload' => ['array'],
            // URL fields: only http/https/relative URLs allowed — blocks javascript: and data: schemes
            'payload.cta_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            'payload.primary_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            'payload.secondary_url' => ['nullable', 'string', 'regex:/^(https?:\/\/|\/|#)/i'],
            // Consent hook: alphanumeric + dots only, max 3 segments (e.g. gtag, dataLayer.push)
            'payload.consent_hook' => ['nullable', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9]{0,30}(\.[a-zA-Z][a-zA-Z0-9]{0,30}){0,2}$/'],
        ]);
    }
}

<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\Repositories\NotificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationsController extends Controller
{
    public function __construct(private readonly NotificationRepository $repo)
    {
        $this->middleware('can:edit beacon');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateNotification($request);

        if (empty($validated['handle'])) {
            $validated['handle'] = $validated['type'].'-'.strtolower(Str::ulid());
        } else {
            $validated['handle'] = strtolower(trim($validated['handle']));
        }

        if ($this->repo->findByHandle($validated['handle']) !== null) {
            throw ValidationException::withMessages(['handle' => 'A notification with this handle already exists.']);
        }

        $notification = $this->repo->save($validated);

        return response()->json($notification, 201);
    }

    public function update(Request $request, string $handle): JsonResponse
    {
        $existing = $this->repo->findByHandle($handle);

        if ($existing === null) {
            abort(404);
        }

        $validated = $this->validateNotification($request);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? false);
        $notification = $this->repo->save(array_merge($existing, $validated, ['handle' => $handle]));

        return response()->json($notification);
    }

    public function destroy(string $handle): JsonResponse
    {
        if ($this->repo->findByHandle($handle) === null) {
            abort(404);
        }

        $this->repo->delete($handle);

        return response()->json(null, 204);
    }

    private function validateNotification(Request $request): array
    {
        return $request->validate([
            'handle' => ['nullable', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/i'],
            'type' => ['required', 'in:announcement,discount,cta,consent'],
            'enabled' => ['boolean'],
            'position' => ['required', 'in:top-bar,bottom-bar,bottom-right,bottom-left,modal-center'],
            'trigger' => ['required', 'in:immediate,delay,scroll,exit_intent'],
            'trigger_value' => ['nullable', 'string'],
            'frequency' => ['required', 'in:always,session,daily,permanent,dismissed'],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
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

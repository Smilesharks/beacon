<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\Models\BeaconCollectionRule;
use Smilesharks\Beacon\Models\BeaconNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Statamic\Facades\Collection;

class CollectionRulesController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view beacon')->only('index');
        $this->middleware('can:edit beacon')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $collections = Collection::all();
        $rules = BeaconCollectionRule::with('notification')->get()->keyBy('collection_handle');
        $notifications = BeaconNotification::active()->get();

        return view('beacon::cp.collection-rules', [
            'title' => __('beacon::nav.collections'),
            'collections' => $collections,
            'rules' => $rules,
            'notifications' => $notifications,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_handle' => ['required', 'string'],
            'notification_id' => ['nullable', 'integer', 'exists:beacon_notifications,id'],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'priority' => ['integer', 'min:0'],
        ]);

        $rule = BeaconCollectionRule::create($validated);

        return response()->json($rule->load('notification'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rule = BeaconCollectionRule::findOrFail($id);

        $validated = $request->validate([
            'notification_id' => ['nullable', 'integer', 'exists:beacon_notifications,id'],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'priority' => ['integer', 'min:0'],
        ]);

        $rule->update($validated);

        return response()->json($rule->load('notification'));
    }

    public function destroy(int $id): JsonResponse
    {
        $rule = BeaconCollectionRule::findOrFail($id);
        $rule->delete();

        return response()->json(null, 204);
    }
}

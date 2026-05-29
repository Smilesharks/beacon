<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Smilesharks\Beacon\Repositories\CollectionRuleRepository;
use Smilesharks\Beacon\Repositories\NotificationRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;

class CollectionRulesController extends Controller
{
    public function __construct(
        private readonly NotificationRepository $notifRepo,
        private readonly CollectionRuleRepository $ruleRepo,
    ) {
        $this->middleware('can:view beacon')->only('index');
        $this->middleware('can:edit beacon')->only(['store', 'update', 'destroy']);
    }

    public function index()
    {
        $collections = Collection::all();
        $rules = $this->ruleRepo->all();
        $notifications = $this->notifRepo->active();

        return view('beacon::cp.collection-rules', [
            'title' => __('beacon::nav.collections'),
            'collections' => $collections,
            'rules' => $rules,
            'notifications' => $notifications,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRule($request);

        $rule = $this->ruleRepo->save($validated);

        return response()->json($rule, 201);
    }

    public function update(Request $request, string $collectionHandle): JsonResponse
    {
        $existing = $this->ruleRepo->findByCollectionHandle($collectionHandle);

        if ($existing === null) {
            abort(404);
        }

        $validated = $this->validateRule($request, includeCollectionHandle: false);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? false);
        // Strip any resolved 'notification' key so we never persist denormalised data
        unset($existing['notification']);
        $rule = $this->ruleRepo->save(array_merge($existing, $validated, ['collection_handle' => $collectionHandle]));

        return response()->json($rule);
    }

    public function destroy(string $collectionHandle): JsonResponse
    {
        if ($this->ruleRepo->findByCollectionHandle($collectionHandle) === null) {
            abort(404);
        }

        $this->ruleRepo->delete($collectionHandle);

        return response()->json(null, 204);
    }

    private function validateRule(Request $request, bool $includeCollectionHandle = true): array
    {
        $rules = [
            'notification_handle' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && $this->notifRepo->findByHandle($value) === null) {
                    $fail('The selected notification does not exist.');
                }
            }],
            'url_pattern' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
            'priority' => ['integer', 'min:0'],
        ];

        if ($includeCollectionHandle) {
            $rules['collection_handle'] = ['required', 'string'];
        }

        return $request->validate($rules);
    }
}

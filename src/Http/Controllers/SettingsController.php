<?php

namespace Smilesharks\Beacon\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    private string $settingsPath;

    public function __construct()
    {
        $this->middleware('can:manage beacon settings');
        $this->settingsPath = storage_path('app/beacon-settings.json');
    }

    public function index()
    {
        $stored = $this->readSettings();

        return view('beacon::cp.settings', [
            'title' => __('beacon::nav.settings'),
            'default_delay' => $stored['default_delay'] ?? config('beacon.default_delay', 2),
            'dev_mode_logging' => $stored['dev_mode_logging'] ?? config('beacon.dev_mode_logging', true),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_delay' => ['required', 'integer', 'min:0', 'max:300'],
            'dev_mode_logging' => ['boolean'],
        ]);

        $settings = [
            'default_delay' => (int) $validated['default_delay'],
            'dev_mode_logging' => (bool) ($validated['dev_mode_logging'] ?? false),
        ];

        File::put($this->settingsPath, json_encode($settings, JSON_PRETTY_PRINT));

        // Update runtime config so subsequent requests in this process see the new values
        config([
            'beacon.default_delay' => $settings['default_delay'],
            'beacon.dev_mode_logging' => $settings['dev_mode_logging'],
        ]);

        return redirect()->route('beacon.settings')
            ->with('success', __('beacon::settings.saved'));
    }

    private function readSettings(): array
    {
        if (! File::exists($this->settingsPath)) {
            return [];
        }

        $contents = File::get($this->settingsPath);
        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }
}

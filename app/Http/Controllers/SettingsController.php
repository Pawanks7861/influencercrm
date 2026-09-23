<?php

namespace App\Http\Controllers;

use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {
        $this->middleware('permission:settings.manage');
    }

    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => $this->settingsService->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'studio_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:10'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'max:50'],
            'timezone' => ['required', 'string', 'max:100'],
        ]);

        $this->settingsService->setMany($validated);

        return back()->with('success', 'Settings updated.');
    }
}

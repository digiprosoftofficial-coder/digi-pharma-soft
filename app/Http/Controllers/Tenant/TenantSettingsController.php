<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TenantSettingsController extends Controller
{
    public function edit(): Response
    {
        abort_unless(auth()->user()?->can('settings.view'), 403);

        $tenant = tenant();
        abort_unless($tenant !== null, 404);

        return Inertia::render('Settings/Edit', [
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'settings' => $tenant->settings ?? [],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->can('settings.manage'), 403);

        $tenant = tenant();
        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:64'],
            'settings.address' => ['nullable', 'string', 'max:500'],
        ]);

        $tenant->name = $validated['name'];
        $settings = $tenant->settings ?? [];
        if (isset($validated['settings'])) {
            $settings = array_merge($settings, array_filter($validated['settings']));
        }
        $tenant->settings = $settings;
        $tenant->save();

        return redirect()->route('tenant.settings.edit')->with('success', __('Settings saved.'));
    }
}

<?php

namespace App\Http\Controllers\Central;

use App\Domain\Platform\Models\PlatformSetting;
use App\Http\Controllers\Controller;
use App\Support\Platform\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformSettingsController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('viewAny', PlatformSetting::class);

        return Inertia::render('Platform/Settings/Edit', [
            'settings' => PlatformSettings::get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', PlatformSetting::class);

        $validated = $request->validate([
            'default_trial_days' => ['required', 'integer', 'min:1', 'max:365'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:64'],
            'sms_provider' => ['nullable', 'string', 'max:64'],
            'sms_api_key' => ['nullable', 'string', 'max:500'],
            'clear_sms_api_key' => ['boolean'],
            'feature_flags.pos' => ['boolean'],
            'feature_flags.reports' => ['boolean'],
            'feature_flags.stock_transfers' => ['boolean'],
        ]);

        PlatformSettings::update([
            'default_trial_days' => $validated['default_trial_days'],
            'support_email' => $validated['support_email'] ?? null,
            'support_phone' => $validated['support_phone'] ?? null,
            'sms_provider' => $validated['sms_provider'] ?? null,
            'sms_api_key' => $validated['sms_api_key'] ?? null,
            'clear_sms_api_key' => $request->boolean('clear_sms_api_key'),
            'feature_flags' => [
                'pos' => $request->boolean('feature_flags.pos'),
                'reports' => $request->boolean('feature_flags.reports'),
                'stock_transfers' => $request->boolean('feature_flags.stock_transfers'),
            ],
        ]);

        activity()
            ->causedBy($request->user())
            ->event('platform.settings_updated')
            ->log('Platform settings updated');

        return redirect()
            ->route('platform.settings.edit')
            ->with('success', __('platform.settings_saved'));
    }
}

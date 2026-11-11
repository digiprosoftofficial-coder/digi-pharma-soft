<?php

namespace App\Http\Controllers\Central;

use App\Domain\Platform\Models\PlatformSetting;
use App\Http\Controllers\Controller;
use App\Support\Platform\MarketingSiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformMarketingSettingsController extends Controller
{
    public function edit(): Response
    {
        $this->authorize('viewAny', PlatformSetting::class);

        return Inertia::render('Platform/MarketingSettings/Edit', [
            'marketing' => MarketingSiteSettings::get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', PlatformSetting::class);

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'tagline' => ['required', 'string', 'max:255'],
            'product_of' => ['required', 'string', 'max:255'],
            'seo_title' => ['required', 'string', 'max:255'],
            'seo_description' => ['required', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'icon' => ['nullable', 'image', 'max:1024'],
            'og_image' => ['nullable', 'image', 'max:2048'],
        ]);

        MarketingSiteSettings::update(
            $validated,
            $request->file('logo'),
            $request->file('icon'),
            $request->file('og_image'),
        );

        return redirect()
            ->route('platform.marketing-settings.edit')
            ->with('success', __('platform.marketing_settings_saved'));
    }
}

<?php

namespace App\Http\Controllers\Marketing;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Services\DemoRequestService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\StoreDemoRequest;
use App\Support\Platform\PlatformSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

final class MarketingController extends Controller
{
    public function home(): Response
    {
        return Inertia::render('Marketing/Home', [
            'plans' => $this->publicPlans(),
            'currency' => PlatformSettings::defaultCurrency(),
            'selfServiceEnabled' => PlatformSettings::selfServiceEnabled(),
            'support' => $this->support(),
            'screenshots' => $this->screenshots(),
        ]);
    }

    public function pricing(): Response
    {
        return Inertia::render('Marketing/Pricing', [
            'plans' => $this->publicPlans(),
            'currency' => PlatformSettings::defaultCurrency(),
            'selfServiceEnabled' => PlatformSettings::selfServiceEnabled(),
            'support' => $this->support(),
        ]);
    }

    public function demoForm(): Response
    {
        return Inertia::render('Marketing/Demo', [
            'support' => $this->support(),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Marketing/Privacy', [
            'support' => $this->support(),
        ]);
    }

    public function terms(): Response
    {
        return Inertia::render('Marketing/Terms', [
            'support' => $this->support(),
        ]);
    }

    public function storeDemo(StoreDemoRequest $request, DemoRequestService $demos): RedirectResponse
    {
        $demos->submitPublic($request->validated());

        return back()->with('success', __('platform.demo_submitted'));
    }

    public function sitemap()
    {
        $base = rtrim(config('app.url'), '/');
        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0'],
            ['loc' => $base.'/pricing', 'priority' => '0.9'],
            ['loc' => $base.'/demo', 'priority' => '0.9'],
            ['loc' => $base.'/get-started', 'priority' => '0.8'],
            ['loc' => $base.'/privacy', 'priority' => '0.3'],
            ['loc' => $base.'/terms', 'priority' => '0.3'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            ."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            ."\n";

        foreach ($urls as $url) {
            $xml .= '  <url>'
                ."\n"
                .'    <loc>'.e($url['loc']).'</loc>'
                ."\n"
                .'    <changefreq>weekly</changefreq>'
                ."\n"
                .'    <priority>'.$url['priority'].'</priority>'
                ."\n"
                .'  </url>'
                ."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * @return list<SubscriptionPlan>
     */
    private function publicPlans(): array
    {
        return SubscriptionPlan::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->all();
    }

    /**
     * @return array{email: ?string, phone: ?string}
     */
    private function support(): array
    {
        $settings = PlatformSettings::get();

        return [
            'email' => $settings['support_email'] ?? null,
            'phone' => $settings['support_phone'] ?? null,
        ];
    }

    /**
     * Optional real product screenshots dropped into public/images/marketing.
     *
     * @return array<string, string>
     */
    private function screenshots(): array
    {
        $dir = public_path('images/marketing');
        if (! File::isDirectory($dir)) {
            return [];
        }

        $keys = [
            'pos',
            'inventory',
            'expiry',
            'purchase',
            'supplier',
            'customer',
            'accounting',
            'reports',
            'branches',
            'dashboard',
        ];

        $map = [];
        foreach ($keys as $key) {
            foreach (['webp', 'png', 'jpg', 'jpeg'] as $ext) {
                $path = $dir.DIRECTORY_SEPARATOR.$key.'.'.$ext;
                if (File::isFile($path)) {
                    $map[$key] = '/images/marketing/'.$key.'.'.$ext;

                    break;
                }
            }
        }

        return $map;
    }
}

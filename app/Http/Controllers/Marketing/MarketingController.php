<?php

namespace App\Http\Controllers\Marketing;

use App\Domain\Billing\Models\PlatformPayment;
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
        $settings = PlatformSettings::get();

        return Inertia::render('Marketing/Home', [
            'plans' => $this->publicPlans(),
            'currency' => PlatformSettings::defaultCurrency(),
            'selfServiceEnabled' => PlatformSettings::selfServiceEnabled(),
            'showTryLiveDemo' => PlatformSettings::showTryLiveDemo(),
            'support' => $this->support(),
            'screenshots' => $this->screenshots(),
            'paymentMethods' => PlatformPayment::METHODS,
            'paymentInfo' => [
                'instructions' => $settings['payment_instructions'] ?? null,
                'bkash' => $settings['bkash_merchant_number'] ?? null,
                'nagad' => $settings['nagad_merchant_number'] ?? null,
                'bank' => $settings['bank_transfer_details'] ?? null,
            ],
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

    public function robots()
    {
        $base = rtrim((string) config('app.url'), '/');
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Allow: /pricing',
            'Allow: /demo',
            'Allow: /get-started',
            'Allow: /privacy',
            'Allow: /terms',
            'Allow: /brand/',
            'Allow: /images/',
            'Allow: /llms.txt',
            '',
            'Disallow: /platform/',
            'Disallow: /dashboard',
            'Disallow: /pos',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /password/',
            'Disallow: /user/',
            'Disallow: /email/',
            'Disallow: /two-factor-challenge',
            'Disallow: /onboarding',
            'Disallow: /sales',
            'Disallow: /purchases',
            'Disallow: /catalog',
            'Disallow: /inventory',
            'Disallow: /reports',
            'Disallow: /settings',
            'Disallow: /employees',
            'Disallow: /notes',
            'Disallow: /billing',
            'Disallow: /team',
            'Disallow: /branches',
            'Disallow: /suppliers',
            'Disallow: /customers',
            'Disallow: /accounts',
            'Disallow: /promotions',
            'Disallow: /global-settings',
            'Disallow: /get-started/pending',
            'Disallow: /get-started/otp',
            '',
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ];

        return response(implode("\n", $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function llmsTxt()
    {
        $base = rtrim((string) config('app.url'), '/');
        $body = <<<TXT
# SoftoPharma
> SoftoPharma is pharmacy management software by DigiProSoft for Bangladesh pharmacies (POS, inventory, batch expiry, purchases, multi-branch, accounts).

## Product
- Home: {$base}/
- Pricing: {$base}/pricing
- Book a demo: {$base}/demo
- Get started / trial: {$base}/get-started
- Privacy: {$base}/privacy
- Terms: {$base}/terms

## Brand
- Product: SoftoPharma
- Company: DigiProSoft
- Category: Pharmacy management software / pharmacy POS Bangladesh
- Primary market: Bangladesh pharmacies

## Contact
Use the contact details published on {$base}/ (support email / phone from platform settings).

TXT;

        return response($body, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function sitemap()
    {
        $base = rtrim((string) config('app.url'), '/');
        $lastmod = now()->toAtomString();
        $urls = [
            ['loc' => $base.'/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base.'/pricing', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => $base.'/demo', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => $base.'/get-started', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => $base.'/privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $base.'/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
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
                .'    <lastmod>'.e($lastmod).'</lastmod>'
                ."\n"
                .'    <changefreq>'.$url['changefreq'].'</changefreq>'
                ."\n"
                .'    <priority>'.$url['priority'].'</priority>'
                ."\n"
                .'  </url>'
                ."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
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

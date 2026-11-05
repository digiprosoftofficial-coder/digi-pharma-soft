<?php

namespace App\Support\Notifications;

use App\Support\Platform\PlatformSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Configurable WhatsApp provider adapter. Credentials come from platform settings / env — never hardcoded.
 */
final class WhatsAppNotifier
{
    public function configured(): bool
    {
        $settings = PlatformSettings::get();

        return filled($settings['whatsapp_provider'] ?? null)
            && (bool) ($settings['whatsapp_api_key_set'] ?? false);
    }

    public function send(string $to, string $message): bool
    {
        if (! $this->configured()) {
            Log::info('WhatsApp skipped (not configured)', ['to' => $to]);

            return false;
        }

        $row = \App\Domain\Platform\Models\PlatformSetting::query()->first();
        $provider = (string) ($row?->whatsapp_provider ?: PlatformSettings::get()['whatsapp_provider']);
        $apiKey = $row?->whatsapp_api_key ?: config('services.whatsapp.api_key');
        $endpoint = config('services.whatsapp.endpoint');

        if (! $endpoint || ! $apiKey) {
            Log::warning('WhatsApp provider set but endpoint/API key missing', [
                'provider' => $provider,
            ]);

            return false;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post($endpoint, [
                    'to' => $to,
                    'message' => $message,
                    'provider' => $provider,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}

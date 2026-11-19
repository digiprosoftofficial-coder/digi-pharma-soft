<?php

namespace App\Support\Catalog;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Reads a medicine pack photo via Gemini Vision and returns a structured search hint.
 * Images are sent to Google and are not stored by this app.
 */
final class GeminiMedicineRecognizer
{
    private const PROMPT = <<<'PROMPT'
You identify medicines from a pharmacy pack / strip / bottle photo for Bangladesh retail POS.

Return ONLY a JSON object with these keys:
- brand_name: string|null (trade / brand name printed on the pack)
- generic_name: string|null (active ingredient / generic)
- strength: string|null (e.g. "500 mg", "5 mg/5 ml")
- barcode: string|null (digits only if a barcode number is clearly readable; otherwise null)
- query: string|null (best short search phrase to find this product — prefer brand_name, else generic_name + strength)
- confidence: number from 0 to 1

Rules:
- If the image is blurry, not a medicine pack, or unreadable, set query to null and confidence <= 0.2.
- Prefer English spellings commonly used on Bangladeshi packs.
- Do not invent names that are not visible.
- Never include markdown or commentary — JSON only.
PROMPT;

    public function configured(): bool
    {
        return filled(config('services.gemini.api_key'));
    }

    /**
     * @return array{
     *     brand_name: ?string,
     *     generic_name: ?string,
     *     strength: ?string,
     *     barcode: ?string,
     *     query: ?string,
     *     confidence: float
     * }
     */
    public function recognize(string $imageBytes, string $mimeType): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $mimeType = $this->normalizeMime($mimeType);
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-2.0-flash');
        $base = rtrim((string) config('services.gemini.endpoint'), '/');
        $timeout = max(5, (int) config('services.gemini.timeout', 20));

        $url = sprintf('%s/%s:generateContent', $base, rawurlencode($model));

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->asJson()
                ->post($url.'?key='.urlencode($apiKey), [
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [
                            ['text' => self::PROMPT],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => base64_encode($imageBytes),
                                ],
                            ],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Gemini medicine recognition timed out or failed to connect', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Medicine recognition timed out. Try again.', 0, $e);
        }

        if ($response->failed()) {
            Log::warning('Gemini medicine recognition HTTP error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException('Medicine recognition service returned an error.');
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $parsed = $this->parseJsonPayload($text);

        return $this->normalizeResult($parsed);
    }

    private function normalizeMime(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        return match ($mimeType) {
            'image/jpg', 'image/pjpeg' => 'image/jpeg',
            'image/x-png' => 'image/png',
            'image/jpeg', 'image/png', 'image/webp' => $mimeType,
            default => throw new RuntimeException('Unsupported image type.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonPayload(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new RuntimeException('Medicine recognition returned an empty response.');
        }

        // Some models still wrap JSON in fences despite responseMimeType.
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/s', $text, $m) === 1) {
            $text = $m[1];
        } elseif ($text[0] !== '{') {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start === false || $end === false || $end <= $start) {
                throw new RuntimeException('Medicine recognition returned invalid JSON.');
            }
            $text = substr($text, $start, $end - $start + 1);
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($text, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('Medicine recognition returned invalid JSON.', 0, $e);
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array{
     *     brand_name: ?string,
     *     generic_name: ?string,
     *     strength: ?string,
     *     barcode: ?string,
     *     query: ?string,
     *     confidence: float
     * }
     */
    private function normalizeResult(array $parsed): array
    {
        $brand = $this->nullableString($parsed['brand_name'] ?? null);
        $generic = $this->nullableString($parsed['generic_name'] ?? null);
        $strength = $this->nullableString($parsed['strength'] ?? null);
        $barcode = $this->nullableBarcode($parsed['barcode'] ?? null);
        $query = $this->nullableString($parsed['query'] ?? null);
        $confidence = $this->clampConfidence($parsed['confidence'] ?? 0);

        if ($query === null) {
            $parts = array_values(array_filter([$brand, $generic, $strength]));
            $query = $parts !== [] ? implode(' ', $parts) : null;
        }

        if ($query !== null) {
            $query = mb_substr(preg_replace('/\s+/u', ' ', $query) ?? $query, 0, 100);
            $query = trim($query) !== '' ? trim($query) : null;
        }

        return [
            'brand_name' => $brand,
            'generic_name' => $generic,
            'strength' => $strength,
            'barcode' => $barcode,
            'query' => $query,
            'confidence' => $confidence,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }

        return mb_substr($value, 0, 120);
    }

    private function nullableBarcode(mixed $value): ?string
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return strlen($digits) >= 6 && strlen($digits) <= 32 ? $digits : null;
    }

    private function clampConfidence(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return max(0.0, min(1.0, round((float) $value, 3)));
    }
}

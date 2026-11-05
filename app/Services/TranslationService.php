<?php
// path: app/Services/TranslationService.php

namespace App\Services;

use Exception;

class TranslationService
{
    private string $endpoint;
    private ?string $apiKey;
    private string $langDir;

    public function __construct(string $endpoint, ?string $apiKey = null)
    {
        $this->endpoint = rtrim($endpoint, '/');
        $this->apiKey   = !empty($apiKey) ? $apiKey : null;
        $this->langDir  = dirname(__DIR__, 2) . '/languages';
    }

    /**
     * Translates a limited chunk of pending keys for a target language.
     * Prevents Cloudflare 524 gateway timeout by keeping execution under 5 seconds.
     */
    public function translateNextBatch(string $targetLang, int $limit = 6): array
    {
        $sourceFile = $this->langDir . '/en.json';
        $targetFile = $this->langDir . "/{$targetLang}.json";

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file languages/en.json not found.");
        }

        $sourceData = json_decode((string)file_get_contents($sourceFile), true) ?? [];
        $targetData = file_exists($targetFile) ? (json_decode((string)file_get_contents($targetFile), true) ?? []) : [];

        $pendingKeys = $this->getPendingKeys($sourceData, $targetData, $targetLang);
        $totalPending = count($pendingKeys);

        if ($totalPending === 0) {
            return [
                'done'             => true,
                'translated_count' => 0,
                'remaining'        => 0,
                'total_pending'    => 0,
                'lang'             => $targetLang
            ];
        }

        // Slice up to $limit items for this batch
        $chunk = array_slice($pendingKeys, 0, $limit, true);
        $translatedThisBatch = 0;

        foreach ($chunk as $path => $sourceText) {
            $translatedText = $this->requestTranslationWithPlaceholderProtection((string)$sourceText, 'en', $targetLang);
            $this->setNestedValue($targetData, $path, $translatedText);
            $translatedThisBatch++;
        }

        // Save progress back to JSON file immediately
        file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $remaining = $totalPending - $translatedThisBatch;

        return [
            'done'             => ($remaining <= 0),
            'translated_count' => $translatedThisBatch,
            'remaining'        => max(0, $remaining),
            'total_pending'    => $totalPending,
            'lang'             => $targetLang
        ];
    }

    /**
     * Scans source and target structures to find keys requiring translation.
     */
    public function getPendingKeys(array $sourceData, array $targetData, string $targetLang): array
    {
        $flatSource = $this->flattenArray($sourceData);
        $pending = [];

        foreach ($flatSource as $path => $sourceText) {
            $currentVal = (string)($this->getNestedValue($targetData, $path) ?? '');

            $hasBrokenToken = str_contains($currentVal, 'X0X') || str_contains($currentVal, '[[') || str_contains($currentVal, ':conto');
            $needsTranslation = empty($currentVal) || ($targetLang !== 'en' && $currentVal === $sourceText) || $hasBrokenToken;

            if ($needsTranslation && !empty($sourceText)) {
                $pending[$path] = $sourceText;
            }
        }

        return $pending;
    }

    /**
     * Full synchronous sync method (kept for background cron or CLI execution).
     */
    public function syncAndTranslate(string $targetLang): array
    {
        $sourceFile = $this->langDir . '/en.json';
        $targetFile = $this->langDir . "/{$targetLang}.json";

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file languages/en.json not found.");
        }

        $sourceData = json_decode((string)file_get_contents($sourceFile), true) ?? [];
        $targetData = file_exists($targetFile) ? (json_decode((string)file_get_contents($targetFile), true) ?? []) : [];

        $translatedCount = 0;
        $targetData = $this->processArray($sourceData, $targetData, $targetLang, $translatedCount);

        file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'status'          => 'success',
            'translated_keys' => $translatedCount,
            'file'            => "{$targetLang}.json"
        ];
    }

    private function processArray(array $source, array $target, string $targetLang, int &$count): array
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $target[$key] = $this->processArray($value, $target[$key] ?? [], $targetLang, $count);
            } else {
                $sourceText = (string)$value;
                $currentVal = (string)($target[$key] ?? '');

                $hasBrokenToken = str_contains($currentVal, 'X0X') || str_contains($currentVal, '[[') || str_contains($currentVal, ':conto');
                $needsTranslation = !isset($target[$key]) || empty($currentVal) || ($targetLang !== 'en' && $currentVal === $sourceText) || $hasBrokenToken;

                if ($needsTranslation && !empty($sourceText)) {
                    $translated = $this->requestTranslationWithPlaceholderProtection($sourceText, 'en', $targetLang);
                    $target[$key] = $translated;
                    $count++;
                }
            }
        }
        return $target;
    }

    /**
     * Protect :placeholders using unique 6-digit natural numbers.
     */
    private function requestTranslationWithPlaceholderProtection(string $text, string $from, string $to): string
    {
        $tokens = [];
        $baseNumber = 987650;

        $maskedText = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function ($matches) use (&$tokens, &$baseNumber) {
            $placeholder = $matches[0];
            $tokenNum = (string)$baseNumber++;
            $tokens[$tokenNum] = $placeholder;
            return $tokenNum;
        }, $text);

        $translated = $this->requestTranslation($maskedText, $from, $to);

        foreach ($tokens as $tokenNum => $originalPlaceholder) {
            $translated = str_replace($tokenNum, $originalPlaceholder, $translated);
        }

        $translated = preg_replace('/\[+X0X\]*/i', ':count', $translated);
        $translated = str_replace([':conto', ':compte', ':cuenta'], ':count', $translated);

        return $translated;
    }

    private function requestTranslation(string $text, string $from, string $to): string
    {
        $payload = [
            'q'      => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'text'
        ];

        if ($this->apiKey) {
            $payload['api_key'] = $this->apiKey;
        }

        $url = $this->endpoint;
        if (!str_ends_with($url, '/translate')) {
            $url .= '/translate';
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 12
        ]);

        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new Exception("LibreTranslate connection error: {$curlErr}");
        }

        if ($httpCode !== 200 || !$res) {
            throw new Exception("LibreTranslate failed with HTTP {$httpCode}");
        }

        $json = json_decode((string)$res, true);
        if (!empty($json['error'])) {
            throw new Exception("LibreTranslate error: " . $json['error']);
        }

        return $json['translatedText'] ?? $text;
    }

    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix === '' ? $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $result += $this->flattenArray($value, $fullKey);
            } else {
                $result[$fullKey] = (string)$value;
            }
        }
        return $result;
    }

    private function setNestedValue(array &$array, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $current = &$array;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    private function getNestedValue(array $array, string $path): mixed
    {
        $keys = explode('.', $path);
        $current = $array;
        foreach ($keys as $key) {
            if (!is_array($current) || !array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        return $current;
    }
}

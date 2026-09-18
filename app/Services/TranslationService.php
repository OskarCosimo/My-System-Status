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

    public function syncAndTranslate(string $targetLang): array
    {
        $sourceFile = $this->langDir . '/en.json';
        $targetFile = $this->langDir . "/{$targetLang}.json";

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file languages/en.json not found.");
        }

        $sourceData = json_decode(file_get_contents($sourceFile), true) ?? [];
        $targetData = file_exists($targetFile) ? (json_decode(file_get_contents($targetFile), true) ?? []) : [];

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

                // Auto-repair: re-translate if missing, empty, matching English, or contains broken tokens like [[X0X] or :conto
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
     * Machine translation models NEVER drop or alter natural numbers!
     */
    private function requestTranslationWithPlaceholderProtection(string $text, string $from, string $to): string
    {
        // 1. Mask :placeholders with unique natural 6-digit numbers (e.g. :count -> 987650)
        $tokens = [];
        $baseNumber = 987650;

        $maskedText = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function ($matches) use (&$tokens, &$baseNumber) {
            $placeholder = $matches[0]; // e.g. :count
            $tokenNum = (string)$baseNumber++;
            $tokens[$tokenNum] = $placeholder;
            return $tokenNum;
        }, $text);

        // 2. Send clean number to LibreTranslate
        $translated = $this->requestTranslation($maskedText, $from, $to);

        // 3. Restore original :placeholders from numbers
        foreach ($tokens as $tokenNum => $originalPlaceholder) {
            $translated = str_replace($tokenNum, $originalPlaceholder, $translated);
        }

        // 4. Safety net cleanup for any old artifacts
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
}

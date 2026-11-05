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
     * Translates a slice of source keys determined by offset and limit.
     * Guaranteed progression: never loops on words identical in both languages (e.g. Dashboard).
     */
    public function translateChunkByOffset(string $targetLang, int $offset, int $limit = 6): array
    {
        $sourceFile = $this->langDir . '/en.json';
        $targetFile = $this->langDir . "/{$targetLang}.json";

        if (!file_exists($sourceFile)) {
            throw new Exception("Source file languages/en.json not found.");
        }

        $sourceData = json_decode((string)file_get_contents($sourceFile), true) ?? [];
        $targetData = file_exists($targetFile) ? (json_decode((string)file_get_contents($targetFile), true) ?? []) : [];

        // Auto-repair stutters on first chunk (offset === 0)
        if ($offset === 0) {
            $this->autoRepairStutters($targetData);
            file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $flatSource = $this->flattenArray($sourceData);
        $totalKeys  = count($flatSource);

        if ($offset >= $totalKeys) {
            return [
                'done'             => true,
                'translated_count' => 0,
                'next_offset'      => $totalKeys,
                'total'            => $totalKeys,
                'lang'             => $targetLang
            ];
        }

        // Slice exact keys to process for this offset
        $slice = array_slice($flatSource, $offset, $limit, true);
        $translatedThisBatch = 0;
        $modified = false;

        foreach ($slice as $path => $sourceText) {
            $currentVal = (string)($this->getNestedValue($targetData, $path) ?? '');

            $hasBrokenToken   = str_contains($currentVal, 'X0X') || str_contains($currentVal, '[[') || str_contains($currentVal, ':conto');
            $isUntranslated   = ($targetLang !== 'en' && $currentVal === (string)$sourceText);
            $needsTranslation = empty($currentVal) || $hasBrokenToken || $isUntranslated;

            if ($needsTranslation && !empty($sourceText)) {
                try {
                    $translated = $this->requestTranslationWithPlaceholderProtection((string)$sourceText, 'en', $targetLang);
                    $cleaned = $this->cleanStutters($translated);
                    $this->setNestedValue($targetData, $path, $cleaned);
                    $translatedThisBatch++;
                    $modified = true;
                } catch (Exception $e) {
                    // Fallback on error: keep source text and continue queue progression
                    $this->setNestedValue($targetData, $path, $sourceText);
                    $modified = true;
                }
            } else {
                // Key already translated; clean potential stutters without calling external API
                $cleaned = $this->cleanStutters($currentVal);
                if ($cleaned !== $currentVal) {
                    $this->setNestedValue($targetData, $path, $cleaned);
                    $modified = true;
                }
            }
        }

        if ($modified) {
            file_put_contents($targetFile, json_encode($targetData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $nextOffset = $offset + count($slice);
        $isDone     = ($nextOffset >= $totalKeys);

        return [
            'done'             => $isDone,
            'translated_count' => $translatedThisBatch,
            'next_offset'      => $nextOffset,
            'total'            => $totalKeys,
            'lang'             => $targetLang
        ];
    }

    /**
     * Get total count of source keys in en.json.
     */
    public function getSourceTotal(): int
    {
        $sourceFile = $this->langDir . '/en.json';
        if (!file_exists($sourceFile)) {
            return 0;
        }
        $sourceData = json_decode((string)file_get_contents($sourceFile), true) ?? [];
        return count($this->flattenArray($sourceData));
    }

    /**
     * Traverses existing target array and repairs stuttered duplicate values in-place.
     */
    private function autoRepairStutters(array &$data): int
    {
        $repaired = 0;
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $repaired += $this->autoRepairStutters($value);
            } elseif (is_string($value)) {
                $cleaned = $this->cleanStutters($value);
                if ($cleaned !== $value) {
                    $value = $cleaned;
                    $repaired++;
                }
            }
        }
        return $repaired;
    }

    /**
     * Clean up duplicate stutters commonly produced by translation models on single tokens.
     */
    private function cleanStutters(string $text): string
    {
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }

        // 1. Single-word duplicate check (e.g. "Impostazioni Impostazioni")
        $dedupedWord = preg_replace('/^(\b[\p{L}0-9_\'-]+\b)(?:\s+\1)+$/iu', '$1', $text);
        if ($dedupedWord !== null && $dedupedWord !== $text) {
            return $dedupedWord;
        }

        // 2. Multi-word phrase duplicate check (e.g. "Salva modifiche Salva modifiche")
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $count = count($words);
        if ($count >= 2 && $count % 2 === 0) {
            $half = (int)($count / 2);
            $firstHalf = implode(' ', array_slice($words, 0, $half));
            $secondHalf = implode(' ', array_slice($words, $half));
            if (mb_strtolower($firstHalf) === mb_strtolower($secondHalf)) {
                return $firstHalf;
            }
        }

        return $text;
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

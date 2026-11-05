<?php
// path: app/Controllers/Admin/TranslationController.php

namespace App\Controllers\Admin;

use App\Services\TranslationService;
use App\Services\SettingService;
use Throwable;

class TranslationController
{
    public function __construct()
    {
        if (empty($_SESSION['user_id'])) {
            if ($this->isJsonRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header('Location: /auth/login');
            exit;
        }
    }

    private function isJsonRequest(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Prepares the list of languages and reports total keys to process.
     */
    public function queue(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $targetLang = trim($_POST['target_lang'] ?? '');
        $endpoint   = setting('libretranslate_endpoint');

        if (empty($endpoint)) {
            echo json_encode(['status' => 'error', 'message' => 'LibreTranslate endpoint is not configured in Settings.']);
            exit;
        }

        if ($targetLang === 'all') {
            $enabledLocales = array_keys(SettingService::getEnabledLocales());
            $locales = array_values(array_filter($enabledLocales, fn($code) => $code !== 'en'));
        } else {
            if (!preg_match('/^[a-z]{2}$/', $targetLang)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid language code.']);
                exit;
            }
            $locales = [$targetLang];
        }

        if (empty($locales)) {
            echo json_encode(['status' => 'error', 'message' => 'No secondary languages available in queue.']);
            exit;
        }

        $service = new TranslationService($endpoint);
        $totalKeys = $service->getSourceTotal();

        echo json_encode([
            'status'     => 'success',
            'queue'      => $locales,
            'total_keys' => $totalKeys
        ]);
        exit;
    }

    /**
     * Translates a slice of keys by offset.
     */
    public function chunk(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $targetLang = trim($_POST['target_lang'] ?? '');
        $offset     = max(0, (int)($_POST['offset'] ?? 0));
        $limit      = max(1, min(15, (int)($_POST['limit'] ?? 6)));
        $endpoint   = setting('libretranslate_endpoint');
        $apiKey     = setting('libretranslate_api_key');

        if (empty($endpoint)) {
            echo json_encode(['status' => 'error', 'message' => 'LibreTranslate endpoint is not configured.']);
            exit;
        }

        if (empty($targetLang) || !preg_match('/^[a-z]{2}$/', $targetLang)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid language code.']);
            exit;
        }

        try {
            $service = new TranslationService($endpoint, $apiKey);
            $result  = $service->translateChunkByOffset($targetLang, $offset, $limit);

            echo json_encode([
                'status'           => 'success',
                'lang'             => $targetLang,
                'translated_count' => $result['translated_count'],
                'next_offset'      => $result['next_offset'],
                'total'            => $result['total'],
                'done'             => $result['done']
            ]);
            exit;
        } catch (Throwable $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }
}

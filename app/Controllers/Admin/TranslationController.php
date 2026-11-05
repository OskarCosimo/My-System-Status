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
     * Prepares and returns the queue of language codes to translate.
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
            echo json_encode(['status' => 'error', 'message' => 'No languages available in the queue.']);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'queue'  => $locales
        ]);
        exit;
    }

    /**
     * Executes a single lightweight batch translation via AJAX.
     */
    public function chunk(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $targetLang = trim($_POST['target_lang'] ?? '');
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
            $result  = $service->translateNextBatch($targetLang, $limit);

            echo json_encode([
                'status'           => 'success',
                'lang'             => $targetLang,
                'translated_count' => $result['translated_count'],
                'remaining'        => $result['remaining'],
                'total_pending'    => $result['total_pending'],
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

    /**
     * Synchronous fallback sync.
     */
    public function sync(): void
    {
        $targetLang = trim($_POST['target_lang'] ?? '');
        $endpoint   = setting('libretranslate_endpoint');
        $apiKey     = setting('libretranslate_api_key');

        $referer = $_SERVER['HTTP_REFERER'] ?? '/admin/settings';
        $cleanReferer = strtok($referer, '?');

        if (empty($endpoint)) {
            header('Location: ' . $cleanReferer . '?error=' . urlencode('LibreTranslate endpoint is not configured in Settings.'));
            exit;
        }

        try {
            $service = new TranslationService($endpoint, $apiKey);

            if ($targetLang === 'all') {
                $enabledLocales = array_keys(SettingService::getEnabledLocales());
                $localesToSync = array_values(array_filter($enabledLocales, fn($code) => $code !== 'en'));

                if (empty($localesToSync)) {
                    header('Location: ' . $cleanReferer . '?error=' . urlencode('No secondary languages are currently enabled in Settings.'));
                    exit;
                }

                $totalTranslated = 0;
                $syncedLanguages = [];

                foreach ($localesToSync as $code) {
                    $res = $service->syncAndTranslate($code);
                    $totalTranslated += (int)($res['translated_keys'] ?? 0);
                    $syncedLanguages[] = strtoupper($code);
                }

                $msg = sprintf(
                    "Successfully synchronized all enabled languages (%s)! Total keys translated: %d.",
                    implode(', ', $syncedLanguages),
                    $totalTranslated
                );

                header('Location: ' . $cleanReferer . '?translated=1&msg=' . urlencode($msg));
                exit;
            }

            if (empty($targetLang) || !preg_match('/^[a-z]{2}$/', $targetLang)) {
                header('Location: ' . $cleanReferer . '?error=' . urlencode('Invalid language code.'));
                exit;
            }

            $result = $service->syncAndTranslate($targetLang);
            $msg = sprintf("Successfully translated %d missing keys into %s!", $result['translated_keys'], $result['file']);
            header('Location: ' . $cleanReferer . '?translated=1&msg=' . urlencode($msg));
            exit;

        } catch (Throwable $e) {
            header('Location: ' . $cleanReferer . '?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

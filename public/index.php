<?php
// path: public/index.php

declare(strict_types=1);

// Configure secure session cookie lifetime & flags
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

session_set_cookie_params([
    'lifetime' => 86400 * 180, // 6 months cookie lifetime for active PWA sessions
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// 1. PSR-4 Class Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    
    if (str_starts_with($class, 'App\\Core\\')) {
        $file = dirname(__DIR__) . '/core/' . substr($class, 9) . '.php';
        if (file_exists($file)) require_once $file;
        return;
    }

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require_once $file;
});

// 2. Load Global Helper Functions
require_once dirname(__DIR__) . '/core/helpers.php';

// 3. Check if system is installed
if (!file_exists(dirname(__DIR__) . '/install/installed.lock') && file_exists(dirname(__DIR__) . '/install/index.php')) {
    header('Location: /install/');
    exit;
}

// 4. Auto-login via Persistent Remember-Me Cookie if session is empty
if (empty($_SESSION['user_id'])) {
    \App\Services\RememberMeService::verifyAndLogin();
}

// 5. Initialize i18n
\App\Core\I18n::init();

// 6. Initialize Router
$router = new \App\Core\Router();

// ==========================================
// PUBLIC ROUTES
// ==========================================
$router->get('/', [\App\Controllers\StatusPageController::class, 'index']);
$router->get('/timezone/set', [\App\Controllers\TimezoneController::class, 'set']);
$router->post('/timezone/set', [\App\Controllers\TimezoneController::class, 'set']);
$router->get('/api/v1/alerts', [\App\Controllers\Api\AlertController::class, 'getActiveAlerts']);
// Edge Worker Outage Recovery Hook
$router->post('/api/v1/edge/recovery', [\App\Controllers\Api\EdgeRecoveryController::class, 'recordRecovery']);
// Subscription & Universal Magic Link Unsubscribe
$router->post('/subscribe', [\App\Controllers\StatusPageController::class, 'subscribe']);
$router->get('/subscribe/verify', [\App\Controllers\StatusPageController::class, 'verify']);
$router->get('/subscribe/unsubscribe', [\App\Controllers\StatusPageController::class, 'unsubscribe']);
$router->post('/subscribe/request-unsubscribe', [\App\Controllers\StatusPageController::class, 'requestUnsubscribe']);
$router->get('/subscribe/confirm-unsubscribe', [\App\Controllers\StatusPageController::class, 'confirmUnsubscribe']);
// Public REST API Endpoints (Protected by API Key)
$router->post('/api/v1/maintenance/enable', [\App\Controllers\Api\MaintenanceApiController::class, 'enable']);
$router->post('/api/v1/maintenance/disable', [\App\Controllers\Api\MaintenanceApiController::class, 'disable']);
// Dynamic Content Translations Endpoints
$router->post('/admin/incidents/translate', [\App\Controllers\Admin\IncidentController::class, 'translate']);
$router->post('/admin/maintenance/translate', [\App\Controllers\Admin\MaintenanceController::class, 'translate']);
// Daily Telemetry Checks Breakdown for Modal
$router->get('/api/v1/monitor/day-logs', [\App\Controllers\StatusPageController::class, 'getDayLogs']);

// ==========================================
// AUTHENTICATION & 2FA ROUTES
// ==========================================
$router->get('/auth/login', [\App\Controllers\AuthController::class, 'login']);
$router->post('/auth/authenticate', [\App\Controllers\AuthController::class, 'authenticate']);
$router->get('/auth/logout', [\App\Controllers\AuthController::class, 'logout']);
$router->get('/auth/oauth', [\App\Controllers\AuthController::class, 'oauthRedirect']);
$router->get('/auth/callback', [\App\Controllers\AuthController::class, 'oauthCallback']);
$router->get('/auth/2fa', [\App\Controllers\AuthController::class, 'twoFactorView']);
$router->post('/auth/2fa-verify', [\App\Controllers\AuthController::class, 'twoFactorVerify']);

// ==========================================
// ADMIN DASHBOARD & CORE
// ==========================================
$router->get('/admin', [\App\Controllers\Admin\DashboardController::class, 'index']);

// Monitors (Routes)
$router->get('/admin/monitors', [\App\Controllers\Admin\MonitorController::class, 'index']);
$router->post('/admin/monitors/store', [\App\Controllers\Admin\MonitorController::class, 'store']);
$router->post('/admin/monitors/delete', [\App\Controllers\Admin\MonitorController::class, 'delete']);
$router->post('/admin/monitors/save-order', [\App\Controllers\Admin\MonitorController::class, 'saveOrder']);

// Admin API Keys Management
$router->get('/admin/api-keys', [\App\Controllers\Admin\ApiKeyController::class, 'index']);
$router->post('/admin/api-keys/store', [\App\Controllers\Admin\ApiKeyController::class, 'store']);
$router->post('/admin/api-keys/delete', [\App\Controllers\Admin\ApiKeyController::class, 'delete']);

// Incidents & AI
$router->get('/admin/incidents', [\App\Controllers\Admin\IncidentController::class, 'index']);
$router->post('/admin/incidents/store', [\App\Controllers\Admin\IncidentController::class, 'store']);
$router->post('/admin/incidents/update-status', [\App\Controllers\Admin\IncidentController::class, 'updateStatus']);
$router->post('/admin/incidents/update', [\App\Controllers\Admin\IncidentController::class, 'update']);
$router->post('/admin/incidents/delete', [\App\Controllers\Admin\IncidentController::class, 'delete']);
$router->post('/admin/incidents/ai-generate', [\App\Controllers\Admin\IncidentController::class, 'generateAiSummary']);

// Maintenance & Calendar
$router->get('/admin/maintenance', [\App\Controllers\Admin\MaintenanceController::class, 'index']);
$router->get('/admin/maintenance/events', [\App\Controllers\Admin\MaintenanceController::class, 'events']);
$router->post('/admin/maintenance/store', [\App\Controllers\Admin\MaintenanceController::class, 'store']);
$router->post('/admin/maintenance/update', [\App\Controllers\Admin\MaintenanceController::class, 'update']);
$router->post('/admin/maintenance/delete', [\App\Controllers\Admin\MaintenanceController::class, 'delete']);

// Subscribers
$router->get('/admin/subscribers', [\App\Controllers\Admin\SubscriberController::class, 'index']);
$router->post('/admin/subscribers/delete', [\App\Controllers\Admin\SubscriberController::class, 'delete']);
$router->post('/admin/subscribers/broadcast', [\App\Controllers\Admin\SubscriberController::class, 'broadcast']);

// Plugins & Integrations
$router->get('/admin/plugins', [\App\Controllers\Admin\PluginController::class, 'index']);
$router->post('/admin/plugins/sync-feeds', [\App\Controllers\Admin\PluginController::class, 'syncFeeds']);
$router->post('/admin/plugins/save-feeds-config', [\App\Controllers\Admin\PluginController::class, 'saveFeedsConfig']);

// Settings Routes
$router->get('/admin/settings', [\App\Controllers\Admin\SettingController::class, 'index']);
$router->post('/admin/settings/update', [\App\Controllers\Admin\SettingController::class, 'update']);

// System Logs
$router->get('/admin/logs', [\App\Controllers\Admin\LogController::class, 'index']);

// Auto-Updater (GitHub Releases)
$router->get('/admin/updater', [\App\Controllers\Admin\UpdaterController::class, 'index']);
$router->post('/admin/updater/apply', [\App\Controllers\Admin\UpdaterController::class, 'apply']);

// Language Switcher Route
$router->get('/lang/switch', [\App\Controllers\LanguageController::class, 'switch']);

// LibreTranslate Auto-Translator Route
$router->post('/admin/translations/sync', [\App\Controllers\Admin\TranslationController::class, 'sync']);

// Admin Profile & Security
$router->get('/admin/profile', [\App\Controllers\Admin\ProfileController::class, 'index']);
$router->post('/admin/profile/update-info', [\App\Controllers\Admin\ProfileController::class, 'updateInfo']);
$router->post('/admin/profile/update-password', [\App\Controllers\Admin\ProfileController::class, 'updatePassword']);
$router->post('/admin/profile/enable-2fa', [\App\Controllers\Admin\ProfileController::class, 'enable2fa']);
$router->post('/admin/profile/disable-2fa', [\App\Controllers\Admin\ProfileController::class, 'disable2fa']);

// ==========================================
// DISPATCH REQUEST
// ==========================================
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

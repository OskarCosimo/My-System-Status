<!-- path: app/Views/admin/settings/index.php -->
<?php 
use App\Services\SettingService;
use App\Services\DateService;

$currentTimezone = setting('app_timezone', 'UTC');
$allTimezones    = DateService::getTimezonesList();
$allLanguages    = SettingService::getAllLanguages();
$enabledLocales  = array_keys(SettingService::getEnabledLocales());
$appBaseUrl      = rtrim(setting('app_url', app_url()), '/');

// Detect absolute path to project root and cron runner script
$projectRoot    = realpath(dirname(__DIR__, 3));
$cronScriptPath = $projectRoot ? ($projectRoot . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'runner.php') : '/path/to/statuspage/cron/runner.php';
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('settings.page_title') ?></h2>
            <p class="text-body-secondary mb-0"><?= __('settings.page_subtitle') ?></p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('settings.alert_saved') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['translated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg'] ?? __('settings.alert_translated')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="/admin/settings/update" method="POST" id="platformSettingsForm" class="card shadow-sm border-0">
        <!-- Navigation Tabs -->
        <div class="card-header bg-body border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#generalTab" role="tab">
                        <i class="bi bi-sliders me-1"></i> <?= __('settings.tab_general') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#cronTab" role="tab">
                        <i class="bi bi-clock-history text-info me-1"></i> <?= __('settings.tab_cron') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#oauthTab" role="tab">
                        <i class="bi bi-person-badge text-primary me-1"></i> <?= __('settings.tab_oauth') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#aiTab" role="tab">
                        <i class="bi bi-robot text-success me-1"></i> <?= __('settings.tab_ai') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#adsTab" role="tab">
                        <i class="bi bi-badge-ad text-warning me-1"></i> <?= __('settings.tab_ads') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#securityTab" role="tab">
                        <i class="bi bi-shield-lock-fill text-danger me-1"></i> <?= __('settings.tab_security') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#smtpTab" role="tab">
                        <i class="bi bi-envelope-at me-1"></i> <?= __('settings.tab_smtp') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#integrationsTab" role="tab">
                        <i class="bi bi-broadcast me-1"></i> <?= __('settings.tab_integrations') ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content p-4">
            <!-- 1. GENERAL TAB -->
            <div class="tab-pane fade show active" id="generalTab" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.app_name') ?></label>
                        <input type="text" name="app_name" value="<?= htmlspecialchars(setting('app_name', 'My System Status')) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.app_url') ?></label>
                        <input type="url" name="app_url" value="<?= htmlspecialchars(setting('app_url', app_url())) ?>" class="form-control" placeholder="https://status.example.com" required>
                        <small class="text-body-secondary"><?= __('settings.app_url_help') ?></small>
                    </div>

                    <!-- Footer Legal Links -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.terms_url') ?></label>
                        <input type="url" name="terms_url" value="<?= htmlspecialchars(setting('terms_url', '')) ?>" class="form-control" placeholder="https://example.com/terms">
                        <small class="text-body-secondary"><?= __('settings.terms_url_help') ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.privacy_policy_url') ?></label>
                        <input type="url" name="privacy_policy_url" value="<?= htmlspecialchars(setting('privacy_policy_url', '')) ?>" class="form-control" placeholder="https://example.com/privacy">
                        <small class="text-body-secondary"><?= __('settings.privacy_url_help') ?></small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.app_timezone') ?></label>
                        <select name="app_timezone" class="form-select">
                            <?php foreach ($allTimezones as $tz): ?>
                                <option value="<?= $tz ?>" <?= $tz === $currentTimezone ? 'selected' : '' ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-body-secondary"><?= __('settings.app_timezone_help') ?></small>
                    </div>

                    <!-- Supported Languages in Header Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.enabled_languages') ?></label>
                        <div class="p-3 bg-body-tertiary rounded-3 border" style="max-height: 220px; overflow-y: auto;">
                            <div class="row g-2">
                                <?php foreach ($allLanguages as $code => $info): ?>
                                    <div class="col-6 col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="enabled_locales[]" 
                                                   value="<?= $code ?>" 
                                                   id="lang_<?= $code ?>" 
                                                   <?= $code === 'en' ? 'checked disabled' : (in_array($code, $enabledLocales, true) ? 'checked' : '') ?>>
                                            <label class="form-check-label small fw-semibold text-truncate" for="lang_<?= $code ?>" title="<?= htmlspecialchars($info['name']) ?>">
                                                <?= htmlspecialchars($info['name']) ?> (<?= $info['flag'] ?>)
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <small class="text-body-secondary d-block mt-1"><?= __('settings.enabled_languages_help') ?></small>
                    </div>
                </div>
            </div>

            <!-- 2. CRONJOBS TAB -->
            <div class="tab-pane fade" id="cronTab" role="tabpanel">
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="p-2 bg-info bg-opacity-10 text-info rounded-3 fs-4">
                            <i class="bi bi-clock-history"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-0"><?= __('settings.cron_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.cron_desc') ?></p>
                        </div>
                    </div>

                    <div class="alert alert-warning border-warning-subtle small mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        <strong><?= __('settings.cron_important') ?></strong> <?= __('settings.cron_notice') ?>
                    </div>

                    <!-- Recommended Crontab Entry -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold"><?= __('settings.cron_command_heading') ?></label>
                        <div class="input-group">
                            <span class="input-group-text font-monospace bg-body-secondary"><?= __('settings.cron_badge_every_minute') ?></span>
                            <input type="text" class="form-control font-monospace" id="cronCliInput" readonly 
                                   value="* * * * * php <?= htmlspecialchars($cronScriptPath) ?> > /dev/null 2>&1">
                            <button class="btn btn-outline-primary" type="button" onclick="copyInputText('cronCliInput', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1"><?= __('settings.cron_path_help') ?></small>
                    </div>

                    <!-- Setup Guide -->
                    <div class="card border mb-3">
                        <div class="card-header bg-body fw-semibold py-2">
                            <i class="bi bi-terminal me-1 text-primary"></i> <?= __('settings.cron_install_guide_title') ?>
                        </div>
                        <div class="card-body small">
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">
                                    <?= __('settings.cron_step1') ?>
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">crontab -e</code>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <?= __('settings.cron_step2') ?>
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">* * * * * php <?= htmlspecialchars($cronScriptPath) ?> &gt; /dev/null 2&gt;&amp;1</code>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <?= __('settings.cron_step3') ?>
                                </li>
                                <li>
                                    <?= __('settings.cron_step4') ?>
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">crontab -l</code>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Manual Test Execution -->
                    <div class="p-3 bg-body rounded-3 border">
                        <h6 class="fw-bold mb-1"><i class="bi bi-play-circle text-success me-1"></i> <?= __('settings.cron_manual_test_title') ?></h6>
                        <p class="text-body-secondary small mb-2"><?= __('settings.cron_manual_test_desc') ?></p>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="cronManualInput" readonly 
                                   value="php <?= htmlspecialchars($cronScriptPath) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('cronManualInput', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Runner Tasks Breakdown -->
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-hdd-network text-primary fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body"><?= __('settings.cron_task_origin') ?></strong>
                            <span class="small text-body-secondary"><?= __('settings.cron_task_origin_desc') ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-globe-americas text-warning fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body"><?= __('settings.cron_task_edge') ?></strong>
                            <span class="small text-body-secondary"><?= __('settings.cron_task_edge_desc') ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-cloud-arrow-down text-info fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body"><?= __('settings.cron_task_feeds') ?></strong>
                            <span class="small text-body-secondary"><?= __('settings.cron_task_feeds_desc') ?></span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-discord text-indigo fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body"><?= __('settings.cron_task_discord') ?></strong>
                            <span class="small text-body-secondary"><?= __('settings.cron_task_discord_desc') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. OAUTH SSO TAB -->
            <div class="tab-pane fade" id="oauthTab" role="tabpanel">
                <div class="alert alert-info border-info-subtle small mb-4">
                    <i class="bi bi-info-circle-fill text-info me-1"></i>
                    <?= __('settings.oauth_intro') ?>
                </div>

                <!-- 1. MYETV SSO -->
                <div class="p-3 bg-body-tertiary rounded-3 mb-4 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-tv text-primary fs-4"></i>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.oauth_myetv_title') ?></h5>
                                <small class="text-body-secondary"><?= __('settings.oauth_myetv_desc') ?></small>
                            </div>
                        </div>
                        <a href="https://developers.myetv.tv" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> <?= __('settings.oauth_myetv_portal') ?>
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary"><?= __('settings.oauth_myetv_callback') ?></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="myetvCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback/myetv') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('myetvCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                        <small class="text-muted" style="font-size: 11px;"><?= __('settings.oauth_myetv_help') ?></small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_myetv_client_id') ?></label>
                            <input type="text" name="oauth_myetv_client_id" value="<?= htmlspecialchars(setting('oauth_myetv_client_id', '')) ?>" class="form-control" placeholder="<?= htmlspecialchars(__('settings.oauth_leave_empty_disable')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_myetv_client_secret') ?></label>
                            <input type="password" name="oauth_myetv_client_secret" value="<?= htmlspecialchars(setting('oauth_myetv_client_secret', '')) ?>" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <!-- 2. Google OAuth 2.0 -->
                <div class="p-3 bg-body-tertiary rounded-3 mb-4 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-google text-danger fs-4"></i>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.oauth_google_title') ?></h5>
                                <small class="text-body-secondary"><?= __('settings.oauth_google_desc') ?></small>
                            </div>
                        </div>
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-up-right me-1"></i> <?= __('settings.oauth_google_portal') ?>
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary"><?= __('settings.oauth_google_callback') ?></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="googleCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback/google') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('googleCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            <?= __('settings.oauth_google_help') ?>
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_google_client_id') ?></label>
                            <input type="text" name="oauth_google_client_id" value="<?= htmlspecialchars(setting('oauth_google_client_id', '')) ?>" class="form-control font-monospace" placeholder="xxxx.apps.googleusercontent.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_google_client_secret') ?></label>
                            <input type="password" name="oauth_google_client_secret" value="<?= htmlspecialchars(setting('oauth_google_client_secret', '')) ?>" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <!-- 3. Microsoft Azure AD -->
                <div class="p-3 bg-body-tertiary rounded-3 mb-4 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-microsoft text-info fs-4"></i>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.oauth_ms_title') ?></h5>
                                <small class="text-body-secondary"><?= __('settings.oauth_ms_desc') ?></small>
                            </div>
                        </div>
                        <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-box-arrow-up-right me-1"></i> <?= __('settings.oauth_ms_portal') ?>
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary"><?= __('settings.oauth_ms_callback') ?></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="msCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback/microsoft') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('msCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            <?= __('settings.oauth_ms_help') ?>
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_ms_client_id') ?></label>
                            <input type="text" name="oauth_microsoft_client_id" value="<?= htmlspecialchars(setting('oauth_microsoft_client_id', '')) ?>" class="form-control font-monospace" placeholder="UUID e.g. 11111111-2222-3333-4444-555555555555">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_ms_client_secret') ?></label>
                            <input type="password" name="oauth_microsoft_client_secret" value="<?= htmlspecialchars(setting('oauth_microsoft_client_secret', '')) ?>" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <!-- 4. Facebook Login -->
                <div class="p-3 bg-body-tertiary rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-facebook text-primary fs-4"></i>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.oauth_fb_title') ?></h5>
                                <small class="text-body-secondary"><?= __('settings.oauth_fb_desc') ?></small>
                            </div>
                        </div>
                        <a href="https://developers.facebook.com/apps/" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> <?= __('settings.oauth_fb_portal') ?>
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary"><?= __('settings.oauth_fb_callback') ?></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="fbCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback/facebook') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('fbCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> <?= __('settings.btn_copy') ?>
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            <?= __('settings.oauth_fb_help') ?>
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_fb_app_id') ?></label>
                            <input type="text" name="oauth_facebook_client_id" value="<?= htmlspecialchars(setting('oauth_facebook_client_id', '')) ?>" class="form-control font-monospace" placeholder="<?= htmlspecialchars(__('settings.oauth_leave_empty_disable')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.oauth_fb_app_secret') ?></label>
                            <input type="password" name="oauth_facebook_client_secret" value="<?= htmlspecialchars(setting('oauth_facebook_client_secret', '')) ?>" class="form-control" placeholder="••••••••">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. AI ENGINE & TRANSLATIONS TAB -->
            <div class="tab-pane fade" id="aiTab" role="tabpanel">
                <!-- AI Incident Summarization Engine -->
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="p-2 bg-success bg-opacity-10 text-success rounded-3 fs-4">
                            <i class="bi bi-robot"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-0"><?= __('settings.ai_engine_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.ai_engine_desc') ?></p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold"><?= __('settings.ai_provider_label') ?></label>
                            <?php $aiProvider = setting('ai_provider', 'gemini'); ?>
                            <select name="ai_provider" class="form-select" id="aiProviderSelect">
                                <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>><?= __('settings.ai_provider_gemini') ?></option>
                                <option value="ollama" <?= $aiProvider === 'ollama' ? 'selected' : '' ?>><?= __('settings.ai_provider_ollama') ?></option>
                                <option value="openai-chat" <?= $aiProvider === 'openai-chat' ? 'selected' : '' ?>><?= __('settings.ai_provider_openai') ?></option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold"><?= __('settings.ai_model_name') ?></label>
                            <input type="text" name="ai_model" value="<?= htmlspecialchars(setting('ai_model', '')) ?>" class="form-control" id="aiModelInput" placeholder="e.g. gemini-1.5-flash or llama3">
                        </div>

                        <!-- Gemini Specific Fields -->
                        <div class="col-12" id="geminiGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <label class="form-label fw-semibold"><?= __('settings.ai_gemini_key') ?></label>
                                <input type="password" name="ai_api_key" value="<?= htmlspecialchars(setting('ai_api_key', '')) ?>" class="form-control" placeholder="AIzaSy... (leave empty if not using Gemini)">
                            </div>
                        </div>

                        <!-- Ollama Specific Fields -->
                        <div class="col-12" id="ollamaGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <label class="form-label fw-semibold"><?= __('settings.ai_ollama_endpoint') ?></label>
                                <input type="url" name="ai_endpoint" value="<?= htmlspecialchars(setting('ai_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:11434 (leave empty for default)">
                            </div>
                        </div>

                        <!-- OpenAI-Compatible Chat Specific Fields -->
                        <div class="col-12" id="openaiGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold"><?= __('settings.ai_openai_endpoint') ?></label>
                                        <input type="url" name="ai_openai_endpoint" value="<?= htmlspecialchars(setting('ai_openai_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:11435/v1/chat/completions">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold"><?= __('settings.ai_openai_key') ?></label>
                                        <input type="password" name="ai_openai_api_key" value="<?= htmlspecialchars(setting('ai_openai_api_key', '')) ?>" class="form-control" placeholder="Leave empty for local inference">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LibreTranslate Configuration & Auto-Sync Section -->
                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-translate"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.translate_generator_title') ?></h5>
                                <p class="text-body-secondary small mb-0"><?= __('settings.translate_generator_desc') ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#syncJsonModal">
                            <i class="bi bi-magic me-1"></i> <?= __('settings.btn_autotranslate_files') ?>
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.translate_endpoint_label') ?></label>
                            <input type="url" name="libretranslate_endpoint" value="<?= htmlspecialchars(setting('libretranslate_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:5000/translate (leave empty if disabled)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.translate_key_label') ?></label>
                            <input type="password" name="libretranslate_api_key" value="<?= htmlspecialchars(setting('libretranslate_api_key', '')) ?>" class="form-control" placeholder="Leave blank if self-hosted without key">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. ADVERTISEMENTS & GOOGLE ADSENSE TAB -->
            <div class="tab-pane fade" id="adsTab" role="tabpanel">
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-badge-ad text-primary me-2"></i><?= __('settings.ads_global_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.ads_global_desc') ?></p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="ads_enabled" value="1" id="adsSwitch" <?= setting('ads_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.ads_publisher_id') ?></label>
                            <input type="text" name="adsense_client_id" value="<?= htmlspecialchars(setting('adsense_client_id', '')) ?>" class="form-control font-monospace" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                            <small class="text-body-secondary"><?= __('settings.ads_publisher_id_help') ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.ads_header_script') ?></label>
                            <textarea id="ads_header_code_input" class="form-control font-monospace" rows="2" placeholder="<!-- Additional ad tags or meta scripts -->"><?= htmlspecialchars(setting('ads_header_code', '')) ?></textarea>
                            <input type="hidden" name="ads_header_code_b64" id="ads_header_code_b64">
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-layout-text-window-reverse text-success me-2"></i><?= __('settings.ads_footer_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.ads_footer_desc') ?></p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="ads_footer_enabled" value="1" id="adsFooterSwitch" <?= setting('ads_footer_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold"><?= __('settings.ads_footer_code') ?></label>
                        <textarea id="ads_footer_code_input" class="form-control font-monospace" rows="5" placeholder="&lt;ins class=&quot;adsbygoogle&quot;&#10;     style=&quot;display:block&quot;&#10;     data-ad-client=&quot;ca-pub-XXXXXXXXXXXXXXXX&quot;&#10;     data-ad-slot=&quot;1234567890&quot;&#10;     data-ad-format=&quot;auto&quot;&#10;     data-full-width-responsive=&quot;true&quot;&gt;&lt;/ins&gt;&#10;&lt;script&gt;&#10;     (adsbygoogle = window.adsbygoogle || []).push({});&#10;&lt;/script&gt;"><?= htmlspecialchars(setting('ads_footer_code', '')) ?></textarea>
                        <input type="hidden" name="ads_footer_code_b64" id="ads_footer_code_b64">
                        <small class="text-body-secondary d-block mt-1"><?= __('settings.ads_footer_code_help') ?></small>
                    </div>
                </div>
            </div>

            <!-- 6. SECURITY TAB (Turnstile & Rate Limiter) -->
            <div class="tab-pane fade" id="securityTab" role="tabpanel">
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i><?= __('settings.turnstile_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.turnstile_desc') ?></p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="turnstile_enabled" value="1" id="turnstileSwitch" <?= setting('turnstile_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.turnstile_site_key') ?></label>
                            <input type="text" name="turnstile_site_key" value="<?= htmlspecialchars(setting('turnstile_site_key', '')) ?>" class="form-control font-monospace" placeholder="0x4AAAAAA...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.turnstile_secret_key') ?></label>
                            <input type="password" name="turnstile_secret_key" value="<?= htmlspecialchars(setting('turnstile_secret_key', '')) ?>" class="form-control font-monospace" placeholder="0x4AAAAAA...">
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-danger me-2"></i><?= __('settings.rate_limit_title') ?></h5>
                            <p class="text-body-secondary small mb-0"><?= __('settings.rate_limit_desc') ?></p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="rate_limit_enabled" value="1" id="rateLimitSwitch" <?= setting('rate_limit_enabled', '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.rate_limit_max_attempts') ?></label>
                            <input type="number" name="rate_limit_max_attempts" value="<?= htmlspecialchars(setting('rate_limit_max_attempts', '5')) ?>" class="form-control" min="1" max="50">
                            <small class="text-body-secondary"><?= __('settings.rate_limit_max_attempts_help') ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.rate_limit_lockout') ?></label>
                            <input type="number" name="rate_limit_lockout_minutes" value="<?= htmlspecialchars(setting('rate_limit_lockout_minutes', '15')) ?>" class="form-control" min="1" max="1440">
                            <small class="text-body-secondary"><?= __('settings.rate_limit_lockout_help') ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. SMTP (EMAIL) TAB -->
            <div class="tab-pane fade" id="smtpTab" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_host') ?></label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars(setting('smtp_host', '')) ?>" class="form-control" placeholder="smtp.example.com (leave empty if disabled)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_port') ?></label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars(setting('smtp_port', '587')) ?>" class="form-control" placeholder="587">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_username') ?></label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars(setting('smtp_user', '')) ?>" class="form-control" placeholder="user@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_password') ?></label>
                        <input type="password" name="smtp_pass" value="<?= htmlspecialchars(setting('smtp_pass', '')) ?>" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_encryption') ?></label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="starttls" <?= setting('smtp_encryption', 'starttls') === 'starttls' ? 'selected' : '' ?>><?= __('settings.smtp_starttls') ?></option>
                            <option value="ssl" <?= setting('smtp_encryption', 'ssl') === 'selected' ? 'selected' : '' ?>><?= __('settings.smtp_ssl') ?></option>
                            <option value="none" <?= setting('smtp_encryption', 'none') === 'selected' ? 'selected' : '' ?>><?= __('settings.smtp_none') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('settings.smtp_from') ?></label>
                        <input type="email" name="smtp_from" value="<?= htmlspecialchars(setting('smtp_from', '')) ?>" class="form-control" placeholder="noreply@example.com">
                    </div>
                </div>
            </div>

            <!-- 8. CLOUDFLARE & DISCORD INTEGRATIONS TAB -->
            <div class="tab-pane fade" id="integrationsTab" role="tabpanel">
                <div class="mb-4">
                    <label class="form-label fw-semibold"><i class="bi bi-discord text-primary me-1"></i> <?= __('settings.discord_webhook') ?></label>
                    <input type="url" name="discord_webhook_url" value="<?= htmlspecialchars(setting('discord_webhook_url', '')) ?>" class="form-control" placeholder="https://discord.com/api/webhooks/... (leave empty to disable)">
                    <small class="text-body-secondary"><?= __('settings.discord_webhook_help') ?></small>
                </div>

                <!-- Cloudflare Zero Trust Tunnel Section -->
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-shield-shaded"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.cf_tunnel_title') ?></h5>
                                <p class="text-body-secondary small mb-0"><?= __('settings.cf_tunnel_desc') ?></p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" name="cf_tunnel_is_primary" value="1" id="tunnelPrimarySwitch" <?= setting('cf_tunnel_is_primary', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="tunnelPrimarySwitch"><?= __('settings.cf_tunnel_core') ?></label>
                        </div>
                    </div>

                    <!-- Detailed Collapsible Setup Guide for Cloudflare Tunnel -->
                    <div class="alert alert-info border-info-subtle mb-3 p-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-body small"><i class="bi bi-info-circle me-1 text-primary"></i> <?= __('settings.cf_tunnel_guide_title') ?></strong>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#tunnelGuideCollapse">
                                <?= __('settings.show_setup_guide') ?> <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                        <div class="collapse mt-3 pt-2 border-top border-info-subtle small text-body" id="tunnelGuideCollapse">
                            <ol class="ps-3 mb-2">
                                <li class="mb-2">
                                    <strong><?= __('settings.cf_tunnel_step1_title') ?></strong>
                                    <div class="text-body-secondary mt-1">
                                        <?= __('settings.cf_tunnel_step1_desc') ?>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <strong><?= __('settings.cf_tunnel_step2_title') ?></strong>
                                    <div class="text-body-secondary mt-1">
                                        <?= __('settings.cf_tunnel_step2_desc') ?>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <strong><?= __('settings.cf_tunnel_step3_title') ?></strong>
                                    <div class="text-body-secondary mt-1">
                                        <?= __('settings.cf_tunnel_step3_desc') ?>
                                        <ul class="mt-1 ps-3">
                                            <li><?= __('settings.cf_tunnel_step3_item1') ?></li>
                                            <li><?= __('settings.cf_tunnel_step3_item2') ?></li>
                                            <li><?= __('settings.cf_tunnel_step3_item3') ?></li>
                                            <li><?= __('settings.cf_tunnel_step3_item4') ?></li>
                                            <li><?= __('settings.cf_tunnel_step3_item5') ?></li>
                                            <li><?= __('settings.cf_tunnel_step3_item6') ?></li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="mb-1">
                                    <strong><?= __('settings.cf_tunnel_step4_title') ?></strong>
                                    <div class="text-body-secondary mt-1">
                                        <?= __('settings.cf_tunnel_step4_desc') ?>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_tunnel_label') ?></label>
                            <input type="text" name="cf_tunnel_name" value="<?= htmlspecialchars(setting('cf_tunnel_name', '')) ?>" class="form-control" placeholder="e.g. MyETV Production Tunnel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_account_id') ?></label>
                            <input type="text" name="cf_tunnel_account_id" value="<?= htmlspecialchars(setting('cf_tunnel_account_id', '')) ?>" class="form-control font-monospace" placeholder="32-character account ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_tunnel_id_label') ?></label>
                            <input type="text" name="cf_tunnel_id" value="<?= htmlspecialchars(setting('cf_tunnel_id', '')) ?>" class="form-control font-monospace" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_api_token_label') ?></label>
                            <input type="password" name="cf_tunnel_api_token" value="<?= htmlspecialchars(setting('cf_tunnel_api_token', '')) ?>" class="form-control font-monospace" placeholder="API Token with Tunnel:Read permission">
                            <small class="text-body-secondary"><?= __('settings.cf_api_token_help') ?></small>
                        </div>
                    </div>
                </div>

                <!-- Cloudflare Edge Worker Section -->
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                <i class="bi bi-globe-americas"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('settings.cf_worker_title') ?></h5>
                                <p class="text-body-secondary small mb-0"><?= __('settings.cf_worker_desc') ?></p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="edge_worker_enabled" value="1" id="edgeWorkerSwitch" <?= setting('edge_worker_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="alert alert-info border-info-subtle mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-body small"><i class="bi bi-info-circle me-1 text-primary"></i> <?= __('settings.cf_worker_guide_title') ?></strong>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#workerGuideCollapse">
                                <?= __('settings.show_setup_guide') ?> <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                        <div class="collapse mt-3 pt-2 border-top border-info-subtle small text-body" id="workerGuideCollapse">
                            <ol class="ps-3 mb-2">
                                <li class="mb-1">
                                    <strong><?= __('settings.cf_worker_step1') ?></strong>
                                </li>
                                <li class="mb-1">
                                    <strong><?= __('settings.cf_worker_step2') ?></strong>
                                </li>
                                <li class="mb-1">
                                    <strong><?= __('settings.cf_worker_step3') ?></strong>
                                    <ul class="mt-1 ps-3 text-body-secondary">
                                        <li><?= __('settings.cf_worker_var_secret') ?></li>
                                        <li><?= __('settings.cf_worker_var_origin', ['url' => htmlspecialchars($appBaseUrl)]) ?></li>
                                        <li><?= __('settings.cf_worker_var_discord') ?></li>
                                    </ul>
                                </li>
                                <li class="mb-1">
                                    <strong><?= __('settings.cf_worker_step4') ?></strong>
                                </li>
                                <li>
                                    <strong><?= __('settings.cf_worker_step5') ?></strong>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_worker_url') ?></label>
                            <input type="url" name="edge_worker_url" value="<?= htmlspecialchars(setting('edge_worker_url', '')) ?>" class="form-control" placeholder="https://my-edge-probe.workers.dev">
                            <small class="text-body-secondary"><?= __('settings.cf_worker_url_help') ?></small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><?= __('settings.cf_worker_secret_token') ?></label>
                            <input type="password" name="edge_worker_token" value="<?= htmlspecialchars(setting('edge_worker_token', '')) ?>" class="form-control" placeholder="Matches SHARED_SECRET_TOKEN in Worker">
                            <small class="text-body-secondary"><?= __('settings.cf_worker_token_help') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-body-tertiary border-top p-3 text-end">
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-save me-1"></i> <?= __('settings.btn_save_settings') ?>
            </button>
        </div>
    </form>
</div>

<!-- Modal: Sync Language JSONs via LibreTranslate (Asynchronous Batch Runner) -->
<div class="modal fade" id="syncJsonModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form id="asyncTranslateFormSettings" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-translate me-2 text-primary"></i><?= __('settings.modal_sync_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" id="syncJsonCloseBtn"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= __('settings.modal_sync_info') ?>
                </div>

                <div class="mb-3" id="syncScopeGroup">
                    <label class="form-label fw-semibold"><?= __('settings.modal_sync_scope') ?></label>
                    <select name="target_lang" id="targetLangSelectSettings" class="form-select" required>
                        <option value="all" class="fw-bold text-primary" selected>
                            <?= __('settings.modal_sync_all_enabled') ?>
                        </option>
                        
                        <optgroup label="<?= htmlspecialchars(__('settings.modal_sync_single_group')) ?>">
                            <?php foreach ($allLanguages as $code => $info): ?>
                                <?php if ($code === 'en') continue; ?>
                                <option value="<?= $code ?>">
                                    <?= htmlspecialchars($info['name']) ?> (languages/<?= $code ?>.json)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <!-- Async Progress Section (Initially Hidden) -->
                <div id="asyncProgressContainerSettings" class="d-none my-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-semibold text-body" id="asyncStatusLabelSettings">Preparing queue...</span>
                        <span class="small text-secondary fw-bold" id="asyncPercentageLabelSettings">0%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div id="asyncProgressBarSettings" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="text-danger small mt-2 d-none" id="asyncErrorBoxSettings"></div>
                </div>

                <div class="p-3 bg-body-tertiary rounded-3 border small">
                    <strong class="text-body d-block mb-1"><?= __('settings.modal_sync_current_languages') ?></strong>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($enabledLocales as $code): ?>
                            <span class="badge bg-<?= $code === 'en' ? 'secondary' : 'primary' ?>">
                                <?= strtoupper($code) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-body-secondary d-block mt-2"><?= __('settings.modal_sync_notice') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="syncCancelBtnSettings"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-semibold" id="startTranslateBtnSettings">
                    <i class="bi bi-magic me-1"></i> <?= __('settings.btn_start_translation') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Copy input value to clipboard with temporary feedback
    function copyInputText(elementId, btnElement) {
        const input = document.getElementById(elementId);
        if (!input) return;

        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            const originalHtml = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> ' + <?= json_encode(__('settings.btn_copied')) ?>;
            btnElement.classList.replace('btn-outline-primary', 'btn-success');
            btnElement.classList.replace('btn-outline-secondary', 'btn-success');
            setTimeout(() => {
                btnElement.innerHTML = originalHtml;
                btnElement.classList.replace('btn-success', 'btn-outline-primary');
                btnElement.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        }).catch(err => {
            console.error('Copy failed: ', err);
        });
    }

    // Dynamic visibility toggle for AI provider input fields
    function updateAIFields() {
        const provider = document.getElementById('aiProviderSelect').value;
        const geminiGroup = document.getElementById('geminiGroup');
        const ollamaGroup = document.getElementById('ollamaGroup');
        const openaiGroup = document.getElementById('openaiGroup');
        const modelInput  = document.getElementById('aiModelInput');

        geminiGroup.style.display = (provider === 'gemini') ? 'block' : 'none';
        ollamaGroup.style.display = (provider === 'ollama') ? 'block' : 'none';
        openaiGroup.style.display = (provider === 'openai-chat') ? 'block' : 'none';

        if (provider === 'gemini') {
            modelInput.placeholder = 'e.g. gemini-1.5-flash';
        } else if (provider === 'ollama') {
            modelInput.placeholder = 'e.g. llama3, mistral, gemma';
        } else if (provider === 'openai-chat') {
            modelInput.placeholder = 'e.g. gpt-4o, llama-3, gemma-2';
        }
    }

    // Base64 encoder with full UTF-8 support
    function utf8ToBase64(str) {
        return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, function(match, p1) {
            return String.fromCharCode('0x' + p1);
        }));
    }

    document.addEventListener('DOMContentLoaded', function() {
        const providerSelect = document.getElementById('aiProviderSelect');
        if (providerSelect) {
            providerSelect.addEventListener('change', updateAIFields);
            updateAIFields();
        }

        const settingsForm = document.getElementById('platformSettingsForm');
        if (settingsForm) {
            settingsForm.addEventListener('submit', function() {
                const headerInput = document.getElementById('ads_header_code_input');
                const footerInput = document.getElementById('ads_footer_code_input');
                const headerB64   = document.getElementById('ads_header_code_b64');
                const footerB64   = document.getElementById('ads_footer_code_b64');

                if (headerInput && headerB64) {
                    headerB64.value = utf8ToBase64(headerInput.value);
                }
                if (footerInput && footerB64) {
                    footerB64.value = utf8ToBase64(footerInput.value);
                }
            });
        }

        // Asynchronous batch translation runner
        const asyncForm = document.getElementById('asyncTranslateFormSettings');
        if (asyncForm) {
            asyncForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const selectTarget   = document.getElementById('targetLangSelectSettings').value;
                const progressBox    = document.getElementById('asyncProgressContainerSettings');
                const progressBar    = document.getElementById('asyncProgressBarSettings');
                const statusLabel    = document.getElementById('asyncStatusLabelSettings');
                const percentLabel   = document.getElementById('asyncPercentageLabelSettings');
                const errorBox       = document.getElementById('asyncErrorBoxSettings');
                const startBtn       = document.getElementById('startTranslateBtnSettings');
                const cancelBtn      = document.getElementById('syncCancelBtnSettings');
                const closeBtn       = document.getElementById('syncJsonCloseBtn');

                // UI Lock
                startBtn.disabled = true;
                cancelBtn.disabled = true;
                closeBtn.disabled = true;
                startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Synchronizing...';
                progressBox.classList.remove('d-none');
                errorBox.classList.add('d-none');
                progressBar.style.width = '5%';
                percentLabel.textContent = '5%';
                statusLabel.textContent = 'Fetching translation queue...';

                try {
                    // 1. Fetch queue of languages
                    const qRes = await fetch('/admin/translations/queue', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: new URLSearchParams({ target_lang: selectTarget })
                    });
                    const qData = await qRes.json();

                    if (qData.status !== 'success' || !qData.queue || qData.queue.length === 0) {
                        throw new Error(qData.message || 'Unable to build translation queue.');
                    }

                    const queue = qData.queue;
                    let totalTrans = 0;

                    // 2. Iterate through each language in small chunks (6 keys per call)
                    for (let i = 0; i < queue.length; i++) {
                        const langCode = queue[i];
                        let isLangDone = false;

                        while (!isLangDone) {
                            statusLabel.textContent = `Translating [${langCode.toUpperCase()}] in progress...`;

                            const cRes = await fetch('/admin/translations/chunk', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                                body: new URLSearchParams({ target_lang: langCode, limit: '6' })
                            });
                            const cData = await cRes.json();

                            if (cData.status !== 'success') {
                                throw new Error(cData.message || `Error translating ${langCode}`);
                            }

                            totalTrans += (cData.translated_count || 0);

                            if (cData.total_pending > 0) {
                                const currentProgress = Math.min(95, Math.round(((cData.total_pending - cData.remaining) / cData.total_pending) * 100));
                                progressBar.style.width = currentProgress + '%';
                                percentLabel.textContent = currentProgress + '%';
                                statusLabel.textContent = `[${langCode.toUpperCase()}] ${cData.remaining} keys remaining...`;
                            }

                            if (cData.done === true || cData.remaining <= 0) {
                                isLangDone = true;
                            }
                        }
                    }

                    // 3. Completed
                    progressBar.style.width = '100%';
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-success');
                    percentLabel.textContent = '100%';
                    statusLabel.textContent = 'Completed! Reloading...';

                    setTimeout(() => {
                        window.location.href = window.location.pathname + '?translated=1&msg=' + encodeURIComponent(`Translation completed! Total keys translated: ${totalTrans}`);
                    }, 1200);

                } catch (err) {
                    errorBox.textContent = err.message;
                    errorBox.classList.remove('d-none');
                    statusLabel.textContent = 'Translation halted.';
                    startBtn.disabled = false;
                    cancelBtn.disabled = false;
                    closeBtn.disabled = false;
                    startBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Retry Translation';
                }
            });
        }
    });
</script>

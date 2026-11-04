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
            <h2 class="fw-bold mb-0">Platform Settings</h2>
            <p class="text-body-secondary mb-0">Manage system configuration, automated cronjobs, OAuth authentication, AI engines, and external integrations.</p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Settings have been successfully saved to the database!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['translated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg'] ?? 'Translation completed!') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="/admin/settings/update" method="POST" id="platformSettingsForm" class="card shadow-sm border-0">
        <!-- Navigation Tabs -->
        <div class="card-header bg-body border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#generalTab" role="tab">
                        <i class="bi bi-sliders me-1"></i> General
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#cronTab" role="tab">
                        <i class="bi bi-clock-history text-info me-1"></i> Cronjobs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#oauthTab" role="tab">
                        <i class="bi bi-person-badge text-primary me-1"></i> OAuth SSO
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#aiTab" role="tab">
                        <i class="bi bi-robot text-success me-1"></i> AI Engine & Translations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#adsTab" role="tab">
                        <i class="bi bi-badge-ad text-warning me-1"></i> Advertisements
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#securityTab" role="tab">
                        <i class="bi bi-shield-lock-fill text-danger me-1"></i> Security
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#smtpTab" role="tab">
                        <i class="bi bi-envelope-at me-1"></i> SMTP (Email)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#integrationsTab" role="tab">
                        <i class="bi bi-broadcast me-1"></i> Cloudflare & Discord
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content p-4">
            <!-- 1. GENERAL TAB -->
            <div class="tab-pane fade show active" id="generalTab" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Application Brand Name</label>
                        <input type="text" name="app_name" value="<?= htmlspecialchars(setting('app_name', 'My System Status')) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Application Public URL</label>
                        <input type="url" name="app_url" value="<?= htmlspecialchars(setting('app_url', app_url())) ?>" class="form-control" placeholder="https://status.example.com" required>
                        <small class="text-body-secondary">Used for subscriber email verification links, password resets, and OAuth callbacks.</small>
                    </div>

                    <!-- Footer Legal Links -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Terms of Service URL</label>
                        <input type="url" name="terms_url" value="<?= htmlspecialchars(setting('terms_url', '')) ?>" class="form-control" placeholder="https://example.com/terms">
                        <small class="text-body-secondary">Displayed in the public page footer (leave empty to hide).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Privacy Policy URL</label>
                        <input type="url" name="privacy_policy_url" value="<?= htmlspecialchars(setting('privacy_policy_url', '')) ?>" class="form-control" placeholder="https://example.com/privacy">
                        <small class="text-body-secondary">Displayed in the public page footer (leave empty to hide).</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Default System Timezone (Layer 1)</label>
                        <select name="app_timezone" class="form-select">
                            <?php foreach ($allTimezones as $tz): ?>
                                <option value="<?= $tz ?>" <?= $tz === $currentTimezone ? 'selected' : '' ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-body-secondary">Fallback timezone when browser auto-detection is not active.</small>
                    </div>

                    <!-- Supported Languages in Header Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Enabled Header Languages</label>
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
                        <small class="text-body-secondary d-block mt-1">Select which languages will appear in the navigation header switchers.</small>
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
                            <h5 class="fw-bold mb-0">Automated Background Cronjob Setup</h5>
                            <p class="text-body-secondary small mb-0">System health monitoring, probe checks, external feed sync, and Discord notifications are triggered via the CLI runner.</p>
                        </div>
                    </div>

                    <div class="alert alert-warning border-warning-subtle small mb-4">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                        <strong>Important:</strong> For real-time 90-day telemetry, live incident detection, and automatic recovery notices, this cron task must run <strong>every minute</strong>.
                    </div>

                    <!-- Recommended Crontab Entry -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">1. Recommended Crontab Command</label>
                        <div class="input-group">
                            <span class="input-group-text font-monospace bg-body-secondary">Crontab (every minute)</span>
                            <input type="text" class="form-control font-monospace" id="cronCliInput" readonly 
                                   value="* * * * * php <?= htmlspecialchars($cronScriptPath) ?> > /dev/null 2>&1">
                            <button class="btn btn-outline-primary" type="button" onclick="copyInputText('cronCliInput', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">If your host uses a specific PHP binary path (e.g. <code>/usr/bin/php8.2</code>), replace <code>php</code> with your full binary path.</small>
                    </div>

                    <!-- Setup Guide -->
                    <div class="card border mb-3">
                        <div class="card-header bg-body fw-semibold py-2">
                            <i class="bi bi-terminal me-1 text-primary"></i> How to install this Cronjob on your Linux Server (Debian/Ubuntu/CentOS/cPanel)
                        </div>
                        <div class="card-body small">
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">
                                    Open your server terminal (SSH) as the web server user (e.g. <code>www-data</code>, <code>nginx</code>, or your cPanel username):
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">crontab -e</code>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    Scroll to the bottom of the crontab file and paste the copied line:
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">* * * * * php <?= htmlspecialchars($cronScriptPath) ?> &gt; /dev/null 2&gt;&amp;1</code>
                                    </div>
                                </li>
                                <li class="mb-2">
                                    Save and exit (in Nano: press <kbd>Ctrl+O</kbd>, <kbd>Enter</kbd>, then <kbd>Ctrl+X</kbd>).
                                </li>
                                <li>
                                    Verify that the cronjob is active:
                                    <div class="mt-1">
                                        <code class="p-1 bg-body-tertiary rounded border">crontab -l</code>
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <!-- Manual Test Execution -->
                    <div class="p-3 bg-body rounded-3 border">
                        <h6 class="fw-bold mb-1"><i class="bi bi-play-circle text-success me-1"></i> Manual Test Execution</h6>
                        <p class="text-body-secondary small mb-2">You can run the script manually from your terminal to verify that probes and database logging execute without errors:</p>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="cronManualInput" readonly 
                                   value="php <?= htmlspecialchars($cronScriptPath) ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('cronManualInput', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Runner Tasks Breakdown -->
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-hdd-network text-primary fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body">Origin Health Probes</strong>
                            <span class="small text-body-secondary">Executes HTTP/TCP/Ping checks for all enabled local monitors.</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-globe-americas text-warning fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body">Cloudflare Edge Checks</strong>
                            <span class="small text-body-secondary">Polls worldwide multi-location edge workers if Sentinel is enabled.</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-cloud-arrow-down text-info fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body">External Cloud Feeds</strong>
                            <span class="small text-body-secondary">Synchronizes status RSS/JSON feeds from Cloudflare, AWS, Stripe, etc.</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-3 bg-body-tertiary rounded-3 border text-center h-100">
                            <i class="bi bi-discord text-indigo fs-3 d-block mb-1"></i>
                            <strong class="d-block text-body">Discord Outage Alerts</strong>
                            <span class="small text-body-secondary">Sends instant webhook alerts whenever a service changes status.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. OAUTH SSO TAB -->
            <div class="tab-pane fade" id="oauthTab" role="tabpanel">
                <div class="alert alert-info border-info-subtle small mb-4">
                    <i class="bi bi-info-circle-fill text-info me-1"></i>
                    OAuth Single Sign-On allows administrators to log in using their enterprise or personal accounts. 
                    Configure each provider below and register the matching <strong>Authorized Callback / Redirect URI</strong> in their developer portals.
                </div>

                <!-- 1. MYETV SSO -->
                <div class="p-3 bg-body-tertiary rounded-3 mb-4 border">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-tv text-primary fs-4"></i>
                            <div>
                                <h5 class="fw-bold mb-0">MYETV SSO Provider</h5>
                                <small class="text-body-secondary">Enterprise OAuth 2.0 integration via MYETV Developer Network</small>
                            </div>
                        </div>
                        <a href="https://developers.myetv.tv" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> MYETV Dev Portal
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">MYETV Authorized Callback URI (Redirect URI):</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="myetvCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback?provider=myetv') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('myetvCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">MYETV Client ID</label>
                            <input type="text" name="oauth_myetv_client_id" value="<?= htmlspecialchars(setting('oauth_myetv_client_id', '')) ?>" class="form-control" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">MYETV Client Secret</label>
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
                                <h5 class="fw-bold mb-0">Google OAuth 2.0</h5>
                                <small class="text-body-secondary">Google Cloud Console credentials (Web Application)</small>
                            </div>
                        </div>
                        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Google Cloud Console
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Google Authorized Redirect URI:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="googleCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback?provider=google') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('googleCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            In Google Cloud Console &rarr; <em>APIs &amp; Services</em> &rarr; <em>Credentials</em> &rarr; Create or Edit your OAuth 2.0 Client ID (type: Web Application) &rarr; Paste this under <strong>Authorized redirect URIs</strong>.
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Google Client ID</label>
                            <input type="text" name="oauth_google_client_id" value="<?= htmlspecialchars(setting('oauth_google_client_id', '')) ?>" class="form-control font-monospace" placeholder="xxxx.apps.googleusercontent.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Google Client Secret</label>
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
                                <h5 class="fw-bold mb-0">Microsoft Azure AD (Entra ID)</h5>
                                <small class="text-body-secondary">Azure Portal App Registration for work/school and personal Microsoft accounts</small>
                            </div>
                        </div>
                        <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Azure App Registrations
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Microsoft Redirect URI (Web platform):</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="msCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback?provider=microsoft') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('msCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            In Azure Portal &rarr; <em>App registrations</em> &rarr; <em>Authentication</em> &rarr; Add a platform: <strong>Web</strong> &rarr; Paste this Redirect URI.
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Microsoft Application (Client) ID</label>
                            <input type="text" name="oauth_microsoft_client_id" value="<?= htmlspecialchars(setting('oauth_microsoft_client_id', '')) ?>" class="form-control font-monospace" placeholder="UUID e.g. 11111111-2222-3333-4444-555555555555">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Microsoft Client Secret</label>
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
                                <h5 class="fw-bold mb-0">Facebook Login</h5>
                                <small class="text-body-secondary">Meta for Developers App Dashboard</small>
                            </div>
                        </div>
                        <a href="https://developers.facebook.com/apps/" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Meta Dev Dashboard
                        </a>
                    </div>

                    <!-- Callback URL Box -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-body-secondary">Valid OAuth Redirect URI:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="fbCallbackUrl" readonly 
                                   value="<?= htmlspecialchars($appBaseUrl . '/auth/callback?provider=facebook') ?>">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyInputText('fbCallbackUrl', this)">
                                <i class="bi bi-clipboard me-1"></i> Copy
                            </button>
                        </div>
                        <small class="text-body-secondary d-block mt-1">
                            In Meta Developer Dashboard &rarr; <em>Facebook Login</em> &rarr; <em>Settings</em> &rarr; Paste this into <strong>Valid OAuth Redirect URIs</strong>.
                        </small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Facebook App ID</label>
                            <input type="text" name="oauth_facebook_client_id" value="<?= htmlspecialchars(setting('oauth_facebook_client_id', '')) ?>" class="form-control font-monospace" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Facebook App Secret</label>
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
                            <h5 class="fw-bold mb-0">AI Incident Analysis Engine</h5>
                            <p class="text-body-secondary small mb-0">Automatically summarizes technical probe failures into clear, human-readable public notices.</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">AI Provider Engine</label>
                            <?php $aiProvider = setting('ai_provider', 'gemini'); ?>
                            <select name="ai_provider" class="form-select" id="aiProviderSelect">
                                <option value="gemini" <?= $aiProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini API (Cloud)</option>
                                <option value="ollama" <?= $aiProvider === 'ollama' ? 'selected' : '' ?>>Ollama (Local / Self-Hosted)</option>
                                <option value="openai-chat" <?= $aiProvider === 'openai-chat' ? 'selected' : '' ?>>OpenAI-Compatible Chat (MLX-LM, LocalAI, vLLM, OpenAI)</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Model Name</label>
                            <input type="text" name="ai_model" value="<?= htmlspecialchars(setting('ai_model', '')) ?>" class="form-control" id="aiModelInput" placeholder="e.g. gemini-1.5-flash or llama3">
                        </div>

                        <!-- Gemini Specific Fields -->
                        <div class="col-12" id="geminiGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <label class="form-label fw-semibold">Gemini API Key</label>
                                <input type="password" name="ai_api_key" value="<?= htmlspecialchars(setting('ai_api_key', '')) ?>" class="form-control" placeholder="AIzaSy... (leave empty if not using Gemini)">
                            </div>
                        </div>

                        <!-- Ollama Specific Fields -->
                        <div class="col-12" id="ollamaGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <label class="form-label fw-semibold">Ollama Server Endpoint URL</label>
                                <input type="url" name="ai_endpoint" value="<?= htmlspecialchars(setting('ai_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:11434 (leave empty for default)">
                            </div>
                        </div>

                        <!-- OpenAI-Compatible Chat Specific Fields -->
                        <div class="col-12" id="openaiGroup">
                            <div class="p-3 bg-body rounded-3 border">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold">Chat Completions Endpoint URL</label>
                                        <input type="url" name="ai_openai_endpoint" value="<?= htmlspecialchars(setting('ai_openai_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:11435/v1/chat/completions">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">API Key / Bearer Token (Optional)</label>
                                        <input type="password" name="ai_openai_api_key" value="<?= htmlspecialchars(setting('ai_openai_api_key', '')) ?>" class="form-control" placeholder="Leave empty for local inference">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LibreTranslate Configuration & Auto-Sync Section (Moved from General) -->
                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-translate"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">LibreTranslate Language Files Generator</h5>
                                <p class="text-body-secondary small mb-0">Auto-translate missing keys from <code>languages/en.json</code> into other language JSON files.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#syncJsonModal">
                            <i class="bi bi-magic me-1"></i> Auto-Translate JSON Files
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">LibreTranslate API Endpoint</label>
                            <input type="url" name="libretranslate_endpoint" value="<?= htmlspecialchars(setting('libretranslate_endpoint', '')) ?>" class="form-control" placeholder="http://localhost:5000/translate (leave empty if disabled)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">LibreTranslate API Key (Optional)</label>
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
                            <h5 class="fw-bold mb-1"><i class="bi bi-badge-ad text-primary me-2"></i>Global Advertisement Settings</h5>
                            <p class="text-body-secondary small mb-0">Enable ads and auto-inject Google AdSense into the <code>&lt;head&gt;</code> section of public pages.</p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="ads_enabled" value="1" id="adsSwitch" <?= setting('ads_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Google AdSense Publisher ID</label>
                            <input type="text" name="adsense_client_id" value="<?= htmlspecialchars(setting('adsense_client_id', '')) ?>" class="form-control font-monospace" placeholder="ca-pub-XXXXXXXXXXXXXXXX">
                            <small class="text-body-secondary">Automatically injects the official async loader script in <code>&lt;head&gt;</code> across the public status site.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Custom / Additional Ad Header Script (Optional)</label>
                            <textarea id="ads_header_code_input" class="form-control font-monospace" rows="2" placeholder="<!-- Additional ad tags or meta scripts -->"><?= htmlspecialchars(setting('ads_header_code', '')) ?></textarea>
                            <input type="hidden" name="ads_header_code_b64" id="ads_header_code_b64">
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-layout-text-window-reverse text-success me-2"></i>Footer Ad Unit Placement</h5>
                            <p class="text-body-secondary small mb-0">Display a responsive banner ad unit at the bottom of public pages, above the footer.</p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="ads_footer_enabled" value="1" id="adsFooterSwitch" <?= setting('ads_footer_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Footer Ad Unit Code (HTML / JavaScript)</label>
                        <textarea id="ads_footer_code_input" class="form-control font-monospace" rows="5" placeholder="&lt;ins class=&quot;adsbygoogle&quot;&#10;     style=&quot;display:block&quot;&#10;     data-ad-client=&quot;ca-pub-XXXXXXXXXXXXXXXX&quot;&#10;     data-ad-slot=&quot;1234567890&quot;&#10;     data-ad-format=&quot;auto&quot;&#10;     data-full-width-responsive=&quot;true&quot;&gt;&lt;/ins&gt;&#10;&lt;script&gt;&#10;     (adsbygoogle = window.adsbygoogle || []).push({});&#10;&lt;/script&gt;"><?= htmlspecialchars(setting('ads_footer_code', '')) ?></textarea>
                        <input type="hidden" name="ads_footer_code_b64" id="ads_footer_code_b64">
                        <small class="text-body-secondary d-block mt-1">Paste your AdSense ad unit block (the <code>&lt;ins&gt;</code> container and script) or any HTML/JS banner snippet.</small>
                    </div>
                </div>
            </div>

            <!-- 6. SECURITY TAB (Turnstile & Rate Limiter) -->
            <div class="tab-pane fade" id="securityTab" role="tabpanel">
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Cloudflare Turnstile Bot Protection</h5>
                            <p class="text-body-secondary small mb-0">Protect the admin login form against automated brute-force attacks.</p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="turnstile_enabled" value="1" id="turnstileSwitch" <?= setting('turnstile_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Turnstile Site Key</label>
                            <input type="text" name="turnstile_site_key" value="<?= htmlspecialchars(setting('turnstile_site_key', '')) ?>" class="form-control font-monospace" placeholder="0x4AAAAAA...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Turnstile Secret Key</label>
                            <input type="password" name="turnstile_secret_key" value="<?= htmlspecialchars(setting('turnstile_secret_key', '')) ?>" class="form-control font-monospace" placeholder="0x4AAAAAA...">
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-body-tertiary rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-danger me-2"></i>Anti-Brute Force Rate Limiter</h5>
                            <p class="text-body-secondary small mb-0">Automatically throttle repeated failed login attempts from suspicious IP addresses.</p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="rate_limit_enabled" value="1" id="rateLimitSwitch" <?= setting('rate_limit_enabled', '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Login Attempts Allowed</label>
                            <input type="number" name="rate_limit_max_attempts" value="<?= htmlspecialchars(setting('rate_limit_max_attempts', '5')) ?>" class="form-control" min="1" max="50">
                            <small class="text-body-secondary">Threshold before temporary IP lockout is triggered.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lockout Duration (Minutes)</label>
                            <input type="number" name="rate_limit_lockout_minutes" value="<?= htmlspecialchars(setting('rate_limit_lockout_minutes', '15')) ?>" class="form-control" min="1" max="1440">
                            <small class="text-body-secondary">Minutes the attacker IP must wait before retrying.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 7. SMTP (EMAIL) TAB -->
            <div class="tab-pane fade" id="smtpTab" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?= htmlspecialchars(setting('smtp_host', '')) ?>" class="form-control" placeholder="smtp.example.com (leave empty if disabled)">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">SMTP Port</label>
                        <input type="number" name="smtp_port" value="<?= htmlspecialchars(setting('smtp_port', '587')) ?>" class="form-control" placeholder="587">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SMTP Username</label>
                        <input type="text" name="smtp_user" value="<?= htmlspecialchars(setting('smtp_user', '')) ?>" class="form-control" placeholder="user@example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">SMTP Password</label>
                        <input type="password" name="smtp_pass" value="<?= htmlspecialchars(setting('smtp_pass', '')) ?>" class="form-control" placeholder="••••••••">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Encryption Protocol</label>
                        <select name="smtp_encryption" class="form-select">
                            <option value="starttls" <?= setting('smtp_encryption', 'starttls') === 'starttls' ? 'selected' : '' ?>>STARTTLS (Default Port 587)</option>
                            <option value="ssl" <?= setting('smtp_encryption', 'ssl') === 'selected' ? 'selected' : '' ?>>SSL / TLS (Port 465)</option>
                            <option value="none" <?= setting('smtp_encryption', 'none') === 'selected' ? 'selected' : '' ?>>None (Plain Port 25)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">From Email Address</label>
                        <input type="email" name="smtp_from" value="<?= htmlspecialchars(setting('smtp_from', '')) ?>" class="form-control" placeholder="noreply@example.com">
                    </div>
                </div>
            </div>

            <!-- 8. CLOUDFLARE & DISCORD INTEGRATIONS TAB -->
            <div class="tab-pane fade" id="integrationsTab" role="tabpanel">
                <div class="mb-4">
                    <label class="form-label fw-semibold"><i class="bi bi-discord text-primary me-1"></i> Discord Channel Webhook URL</label>
                    <input type="url" name="discord_webhook_url" value="<?= htmlspecialchars(setting('discord_webhook_url', '')) ?>" class="form-control" placeholder="https://discord.com/api/webhooks/... (leave empty to disable)">
                    <small class="text-body-secondary">Probes will post down/up alert embeds directly to this Discord channel.</small>
                </div>

                <!-- Cloudflare Zero Trust Tunnel Section -->
                <div class="p-4 bg-body-tertiary rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-shield-shaded"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">Cloudflare Zero Trust Tunnel Monitor (Optional)</h5>
                                <p class="text-body-secondary small mb-0">Monitor the live health status of your private Cloudflare Tunnel (cloudflared).</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" name="cf_tunnel_is_primary" value="1" id="tunnelPrimarySwitch" <?= setting('cf_tunnel_is_primary', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="tunnelPrimarySwitch">Place in Core Systems</label>
                        </div>
                    </div>

                    <!-- Detailed Collapsible Setup Guide for Cloudflare Tunnel -->
                    <div class="alert alert-info border-info-subtle mb-3 p-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-body small"><i class="bi bi-info-circle me-1 text-primary"></i> How to setup Cloudflare Zero Trust Tunnel Monitoring:</strong>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#tunnelGuideCollapse">
                                Show Setup Guide <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                        <div class="collapse mt-3 pt-2 border-top border-info-subtle small text-body" id="tunnelGuideCollapse">
                            <ol class="ps-3 mb-2">
                                <li class="mb-2">
                                    <strong>1. Locate your Cloudflare Account ID:</strong>
                                    <div class="text-body-secondary mt-1">
                                        Log in to the <a href="https://dash.cloudflare.com" target="_blank" class="text-primary fw-semibold">Cloudflare Dashboard</a>. Choose any domain or check your browser address bar: <code>https://dash.cloudflare.com/&lt;ACCOUNT_ID&gt;</code>. Alternatively, on any domain Overview page, scroll down the right sidebar to copy the 32-character <strong>Account ID</strong>.
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <strong>2. Locate your Tunnel ID (UUID):</strong>
                                    <div class="text-body-secondary mt-1">
                                        Open the <strong>Zero Trust Dashboard</strong> &rarr; <strong>Networks</strong> &rarr; <strong>Tunnels</strong>. Select your active tunnel. The <strong>Tunnel ID</strong> (UUID format <code>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx</code>) is displayed in the tunnel overview and in the URL (<code>/tunnels/&lt;TUNNEL_ID&gt;</code>).
                                    </div>
                                </li>
                                <li class="mb-2">
                                    <strong>3. Create an API Token with Read permission:</strong>
                                    <div class="text-body-secondary mt-1">
                                        In Cloudflare Dashboard, click your profile in the top-right corner &rarr; <strong>My Profile</strong> &rarr; <strong>API Tokens</strong> &rarr; <strong>Create Token</strong>:
                                        <ul class="mt-1 ps-3">
                                            <li>Scroll to the bottom and click <strong>Create Custom Token</strong>.</li>
                                            <li><strong>Token name:</strong> e.g. <code>StatusPage - Tunnel Reader</code>.</li>
                                            <li><strong>Permissions:</strong> Add <code>Account</code> &gt; <code>Cloudflare Tunnel</code> &gt; <code>Read</code>.</li>
                                            <li><strong>Account Resources:</strong> Set <code>Include</code> &gt; <code>All accounts</code> (or select your specific account).</li>
                                            <li>Click <strong>Continue to summary</strong> &rarr; <strong>Create Token</strong>.</li>
                                            <li>Copy the generated secret token and paste it into the <strong>Cloudflare API Token</strong> field below.</li>
                                        </ul>
                                    </div>
                                </li>
                                <li class="mb-1">
                                    <strong>4. Tier Placement:</strong>
                                    <div class="text-body-secondary mt-1">
                                        Keep <em>"Place in Core Systems"</em> checked if tunnel downtime should mark the global status banner as Major Outage. If disabled, it will be placed as a secondary service under the Cloudflare group.
                                    </div>
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Custom Tunnel Label</label>
                            <input type="text" name="cf_tunnel_name" value="<?= htmlspecialchars(setting('cf_tunnel_name', '')) ?>" class="form-control" placeholder="e.g. MyETV Production Tunnel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cloudflare Account ID</label>
                            <input type="text" name="cf_tunnel_account_id" value="<?= htmlspecialchars(setting('cf_tunnel_account_id', '')) ?>" class="form-control font-monospace" placeholder="32-character account ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tunnel ID (UUID)</label>
                            <input type="text" name="cf_tunnel_id" value="<?= htmlspecialchars(setting('cf_tunnel_id', '')) ?>" class="form-control font-monospace" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Cloudflare API Token</label>
                            <input type="password" name="cf_tunnel_api_token" value="<?= htmlspecialchars(setting('cf_tunnel_api_token', '')) ?>" class="form-control font-monospace" placeholder="API Token with Tunnel:Read permission">
                            <small class="text-body-secondary">Requires <code>Account &gt; Cloudflare Tunnel &gt; Read</code> permissions.</small>
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
                                <h5 class="fw-bold mb-0">Cloudflare Edge Worker Probes & Sentinel (Optional)</h5>
                                <p class="text-body-secondary small mb-0">Run multi-location latency probes from Cloudflare edge and monitor server downtime autonomously.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="edge_worker_enabled" value="1" id="edgeWorkerSwitch" <?= setting('edge_worker_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="alert alert-info border-info-subtle mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-body small"><i class="bi bi-info-circle me-1 text-primary"></i> How to setup your Cloudflare Worker:</strong>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#workerGuideCollapse">
                                Show Setup Guide <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                        <div class="collapse mt-3 pt-2 border-top border-info-subtle small text-body" id="workerGuideCollapse">
                            <ol class="ps-3 mb-2">
                                <li class="mb-1">
                                    <strong>Get the template code:</strong> Open the local file <code>edge/cloudflare-worker.js</code> in your repository.
                                </li>
                                <li class="mb-1">
                                    <strong>Create the Worker:</strong> In <a href="https://dash.cloudflare.com" target="_blank" class="text-primary fw-semibold">Cloudflare Dashboard</a> &rarr; <em>Workers &amp; Pages</em> &rarr; <em>Create Application</em> &rarr; <em>Create Worker</em>, and paste the code from <code>edge/cloudflare-worker.js</code>.
                                </li>
                                <li class="mb-1">
                                    <strong>Configure Variables (Worker Settings &rarr; Variables):</strong>
                                    <ul class="mt-1 ps-3 text-body-secondary">
                                        <li><code>SHARED_SECRET_TOKEN</code>: A custom password/token (must match the token entered below).</li>
                                        <li><code>ORIGIN_STATUS_URL</code>: Your status page URL (<code><?= htmlspecialchars($appBaseUrl) ?></code>).</li>
                                        <li><code>DISCORD_WEBHOOK_URL</code>: <em>(Optional)</em> Discord webhook for alerts if your origin server dies.</li>
                                    </ul>
                                </li>
                                <li class="mb-1">
                                    <strong>Enable Cron Trigger (Worker Settings &rarr; Triggers &rarr; Cron Triggers):</strong> Add <code>* * * * *</code> (every minute) so Cloudflare monitors your server even if your VPS crashes.</li>
                                <li>
                                    <strong>Paste URL & Token below:</strong> Copy your <code>https://your-worker.workers.dev</code> address into the field below.
                                </li>
                            </ol>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Worker URL</label>
                            <input type="url" name="edge_worker_url" value="<?= htmlspecialchars(setting('edge_worker_url', '')) ?>" class="form-control" placeholder="https://my-edge-probe.workers.dev">
                            <small class="text-body-secondary">The public <code>.workers.dev</code> endpoint of your deployed worker.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Shared Secret Token</label>
                            <input type="password" name="edge_worker_token" value="<?= htmlspecialchars(setting('edge_worker_token', '')) ?>" class="form-control" placeholder="Matches SHARED_SECRET_TOKEN in Worker">
                            <small class="text-body-secondary">Used to authenticate requests between your server and the Cloudflare Worker.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-body-tertiary border-top p-3 text-end">
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-save me-1"></i> Save Platform Settings
            </button>
        </div>
    </form>
</div>

<!-- Modal: Sync Language JSONs via LibreTranslate -->
<div class="modal fade" id="syncJsonModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/translations/sync" method="POST" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-translate me-2 text-primary"></i>Sync Language JSON Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    This tool reads master keys from <code>languages/en.json</code>, identifies missing keys in the target file(s), translates them via LibreTranslate, and saves the updated <code>.json</code> file(s).
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Target Translation Scope</label>
                    <select name="target_lang" class="form-select" required>
                        <option value="all" class="fw-bold text-primary" selected>
                            ★ All Enabled Languages (Batch Sync)
                        </option>
                        
                        <optgroup label="Single Language Translation">
                            <?php foreach ($allLanguages as $code => $info): ?>
                                <?php if ($code === 'en') continue; ?>
                                <option value="<?= $code ?>">
                                    <?= htmlspecialchars($info['name']) ?> (languages/<?= $code ?>.json)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="p-3 bg-body-tertiary rounded-3 border small">
                    <strong class="text-body d-block mb-1">Currently Enabled Languages:</strong>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($enabledLocales as $code): ?>
                            <span class="badge bg-<?= $code === 'en' ? 'secondary' : 'primary' ?>">
                                <?= strtoupper($code) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-body-secondary d-block mt-2">Selecting "All Enabled Languages" will generate/sync all badges above except English.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold">
                    <i class="bi bi-magic me-1"></i> Start Auto-Translation
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
            btnElement.innerHTML = '<i class="bi bi-check2 text-success me-1"></i> Copied!';
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

    // Base64 encoder with full UTF-8 support to prevent ModSecurity / WAF false positives
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

        // Intercept form submit and encode raw HTML/JS code into Base64 hidden inputs
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
    });
</script>

<!-- path: app/Views/admin/settings/index.php -->
<?php 
use App\Services\SettingService;
use App\Services\DateService;

$currentTimezone = setting('app_timezone', 'UTC');
$allTimezones    = DateService::getTimezonesList();
$allLanguages    = SettingService::getAllLanguages();
$enabledLocales  = array_keys(SettingService::getEnabledLocales());
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Platform Settings</h2>
            <p class="text-muted">Manage system configuration, mail servers, legal links, security, and external services.</p>
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

    <form action="/admin/settings/update" method="POST" class="card shadow-sm border-0">
        <!-- Navigation Tabs -->
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#generalTab" role="tab">
                        <i class="bi bi-sliders me-1"></i> General
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
                    <a class="nav-link" data-bs-toggle="tab" href="#oauthTab" role="tab">
                        <i class="bi bi-person-badge me-1"></i> OAuth SSO
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#aiTab" role="tab">
                        <i class="bi bi-robot me-1"></i> AI Engine
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#integrationsTab" role="tab">
                        <i class="bi bi-broadcast me-1"></i> Discord & Webhooks
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
                        <small class="text-muted">Used for subscriber email verification links and OAuth callbacks.</small>
                    </div>

                    <!-- Footer Legal Links -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Terms of Service URL</label>
                        <input type="url" name="terms_url" value="<?= htmlspecialchars(setting('terms_url', '')) ?>" class="form-control" placeholder="https://example.com/terms">
                        <small class="text-muted">Displayed in the public page footer (leave empty to hide).</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Privacy Policy URL</label>
                        <input type="url" name="privacy_policy_url" value="<?= htmlspecialchars(setting('privacy_policy_url', '')) ?>" class="form-control" placeholder="https://example.com/privacy">
                        <small class="text-muted">Displayed in the public page footer (leave empty to hide).</small>
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
                        <small class="text-muted">Fallback timezone when browser auto-detection is not active.</small>
                    </div>

                    <!-- Supported Languages in Header Selection -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Enabled Header Languages</label>
                        <div class="p-3 bg-light rounded-3 border" style="max-height: 220px; overflow-y: auto;">
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
                        <small class="text-muted d-block mt-1">Select which languages will appear in the navigation header switchers.</small>
                    </div>

                    <!-- LibreTranslate Configuration & Auto-Sync Section -->
                    <div class="col-12 mt-4">
                        <div class="p-4 bg-light rounded-3 border">
                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="p-2 bg-warning bg-opacity-10 text-dark rounded-3 fs-4">
                                        <i class="bi bi-translate"></i>
                                    </span>
                                    <div>
                                        <h5 class="fw-bold mb-0">LibreTranslate Language Files Generator</h5>
                                        <p class="text-muted small mb-0">Auto-translate missing keys from <code>languages/en.json</code> into other language JSON files.</p>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#syncJsonModal">
                                    <i class="bi bi-magic me-1"></i> Auto-Translate JSON Files
                                </button>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">LibreTranslate API Endpoint</label>
                                    <input type="url" name="libretranslate_endpoint" value="<?= htmlspecialchars(setting('libretranslate_endpoint', '')) ?>" class="form-control" placeholder="http://192.168.x.x:5055/translate (leave empty if disabled)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">LibreTranslate API Key (Optional)</label>
                                    <input type="password" name="libretranslate_api_key" value="<?= htmlspecialchars(setting('libretranslate_api_key', '')) ?>" class="form-control" placeholder="Leave blank if self-hosted without key">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SECURITY TAB (Turnstile & Rate Limiter) -->
            <div class="tab-pane fade" id="securityTab" role="tabpanel">
                <!-- Cloudflare Turnstile Section -->
                <div class="p-4 bg-light rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Cloudflare Turnstile Bot Protection</h5>
                            <p class="text-muted small mb-0">Protect the admin login form against automated brute-force attacks.</p>
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

                <!-- Rate Limiting Configuration -->
                <div class="p-4 bg-light rounded-3 border">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="bi bi-speedometer2 text-danger me-2"></i>Anti-Brute Force Rate Limiter</h5>
                            <p class="text-muted small mb-0">Automatically throttle repeated failed login attempts from suspicious IP addresses.</p>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="rate_limit_enabled" value="1" id="rateLimitSwitch" <?= setting('rate_limit_enabled', '1') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Max Login Attempts Allowed</label>
                            <input type="number" name="rate_limit_max_attempts" value="<?= htmlspecialchars(setting('rate_limit_max_attempts', '5')) ?>" class="form-control" min="1" max="50">
                            <small class="text-muted">Threshold before temporary IP lockout is triggered.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Lockout Duration (Minutes)</label>
                            <input type="number" name="rate_limit_lockout_minutes" value="<?= htmlspecialchars(setting('rate_limit_lockout_minutes', '15')) ?>" class="form-control" min="1" max="1440">
                            <small class="text-muted">Minutes the attacker IP must wait before retrying.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. SMTP (EMAIL) TAB -->
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
                            <option value="ssl" <?= setting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL / TLS (Port 465)</option>
                            <option value="none" <?= setting('smtp_encryption') === 'none' ? 'selected' : '' ?>>None (Plain Port 25)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">From Email Address</label>
                        <input type="email" name="smtp_from" value="<?= htmlspecialchars(setting('smtp_from', '')) ?>" class="form-control" placeholder="noreply@example.com">
                    </div>
                </div>
            </div>

            <!-- 4. OAUTH SSO TAB -->
            <div class="tab-pane fade" id="oauthTab" role="tabpanel">
                <!-- MYETV SSO -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <h5 class="fw-bold text-primary mb-2"><i class="bi bi-tv me-2"></i>MYETV SSO Provider</h5>
                    <p class="small text-muted mb-3">API integration from <code>https://developers.myetv.tv</code></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">MYETV Client ID</label>
                            <input type="text" name="oauth_myetv_client_id" value="<?= htmlspecialchars(setting('oauth_myetv_client_id', '')) ?>" class="form-control" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">MYETV Client Secret</label>
                            <input type="password" name="oauth_myetv_client_secret" value="<?= htmlspecialchars(setting('oauth_myetv_client_secret', '')) ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Google SSO -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <h5 class="fw-bold text-danger mb-2"><i class="bi bi-google me-2"></i>Google OAuth 2.0</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Google Client ID</label>
                            <input type="text" name="oauth_google_client_id" value="<?= htmlspecialchars(setting('oauth_google_client_id', '')) ?>" class="form-control" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Client Secret</label>
                            <input type="password" name="oauth_google_client_secret" value="<?= htmlspecialchars(setting('oauth_google_client_secret', '')) ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Microsoft Azure AD SSO -->
                <div class="p-3 bg-light rounded-3 mb-4 border">
                    <h5 class="fw-bold text-info mb-2"><i class="bi bi-microsoft me-2"></i>Microsoft Azure AD</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Microsoft Application (Client) ID</label>
                            <input type="text" name="oauth_microsoft_client_id" value="<?= htmlspecialchars(setting('oauth_microsoft_client_id', '')) ?>" class="form-control" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Microsoft Client Secret</label>
                            <input type="password" name="oauth_microsoft_client_secret" value="<?= htmlspecialchars(setting('oauth_microsoft_client_secret', '')) ?>" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Facebook SSO -->
                <div class="p-3 bg-light rounded-3 border">
                    <h5 class="fw-bold text-primary mb-2"><i class="bi bi-facebook me-2"></i>Facebook Login</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Facebook App ID</label>
                            <input type="text" name="oauth_facebook_client_id" value="<?= htmlspecialchars(setting('oauth_facebook_client_id', '')) ?>" class="form-control" placeholder="Leave empty to disable">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Facebook App Secret</label>
                            <input type="password" name="oauth_facebook_client_secret" value="<?= htmlspecialchars(setting('oauth_facebook_client_secret', '')) ?>" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. AI ENGINE TAB -->
            <div class="tab-pane fade" id="aiTab" role="tabpanel">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">AI Provider Engine</label>
                        <select name="ai_provider" class="form-select">
                            <option value="gemini" <?= setting('ai_provider', 'gemini') === 'gemini' ? 'selected' : '' ?>>Google Gemini API</option>
                            <option value="ollama" <?= setting('ai_provider', 'gemini') === 'ollama' ? 'selected' : '' ?>>Ollama (Local / Self-Hosted)</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Model Name</label>
                        <input type="text" name="ai_model" value="<?= htmlspecialchars(setting('ai_model', '')) ?>" class="form-control" placeholder="e.g. gemini-1.5-flash or llama3">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Gemini API Key</label>
                        <input type="password" name="ai_api_key" value="<?= htmlspecialchars(setting('ai_api_key', '')) ?>" class="form-control" placeholder="AIzaSy... (leave empty if not using Gemini)">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Ollama Endpoint URL</label>
                        <input type="url" name="ai_endpoint" value="<?= htmlspecialchars(setting('ai_endpoint', '')) ?>" class="form-control" placeholder="e.g. http://127.0.0.1:11434 (leave empty if not using Ollama)">
                    </div>
                </div>
            </div>

            <!-- 6. INTEGRATIONS TAB -->
            <div class="tab-pane fade" id="integrationsTab" role="tabpanel">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><i class="bi bi-discord text-primary me-1"></i> Discord Channel Webhook URL</label>
                    <input type="url" name="discord_webhook_url" value="<?= htmlspecialchars(setting('discord_webhook_url', '')) ?>" class="form-control" placeholder="https://discord.com/api/webhooks/... (leave empty to disable)">
                    <small class="text-muted">Probes will post down/up alert embeds directly to this Discord channel.</small>
                </div>

                <!-- Cloudflare Zero Trust Tunnel Section -->
                <div class="p-4 bg-light rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-dark rounded-3 fs-4">
                                <i class="bi bi-shield-shaded"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">Cloudflare Zero Trust Tunnel Monitor (Optional)</h5>
                                <p class="text-muted small mb-0">Monitor the live health status of your private Cloudflare Tunnel (cloudflared).</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" name="cf_tunnel_is_primary" value="1" id="tunnelPrimarySwitch" <?= setting('cf_tunnel_is_primary', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="tunnelPrimarySwitch">Place in Core Systems</label>
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
                            <small class="text-muted">Requires <code>Account &gt; Cloudflare Tunnel &gt; Read</code> permissions.</small>
                        </div>
                    </div>
                </div>

                <!-- Cloudflare Edge Worker Section -->
                <div class="p-4 bg-light rounded-3 border mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                <i class="bi bi-globe-americas"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0">Cloudflare Edge Worker Probes & Sentinel (Optional)</h5>
                                <p class="text-muted small mb-0">Run multi-location latency probes from Cloudflare edge and monitor server downtime autonomously.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-5">
                            <input class="form-check-input" type="checkbox" name="edge_worker_enabled" value="1" id="edgeWorkerSwitch" <?= setting('edge_worker_enabled') === '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <!-- Step-by-Step Setup Guide Accordion -->
                    <div class="alert alert-info border-info-subtle mb-3 p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark small"><i class="bi bi-info-circle me-1 text-primary"></i> How to setup your Cloudflare Worker:</strong>
                            <button class="btn btn-sm btn-link p-0 text-decoration-none small fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#workerGuideCollapse">
                                Show Setup Guide <i class="bi bi-chevron-down ms-1"></i>
                            </button>
                        </div>
                        <div class="collapse mt-3 pt-2 border-top border-info-subtle small text-dark" id="workerGuideCollapse">
                            <ol class="ps-3 mb-2">
                                <li class="mb-1">
                                    <strong>Get the template code:</strong> Open the local file <code>edge/cloudflare-worker.js</code> in your repository.
                                </li>
                                <li class="mb-1">
                                    <strong>Create the Worker:</strong> In <a href="https://dash.cloudflare.com" target="_blank" class="text-primary fw-semibold">Cloudflare Dashboard</a> &rarr; <em>Workers & Pages</em> &rarr; <em>Create Application</em> &rarr; <em>Create Worker</em>, and paste the code from <code>edge/cloudflare-worker.js</code>.
                                </li>
                                <li class="mb-1">
                                    <strong>Configure Variables (Worker Settings &rarr; Variables):</strong>
                                    <ul class="mt-1 ps-3 text-muted">
                                        <li><code>SHARED_SECRET_TOKEN</code>: A custom password/token (must match the token entered below).</li>
                                        <li><code>ORIGIN_STATUS_URL</code>: Your status page URL (<code><?= htmlspecialchars(app_url()) ?></code>).</li>
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
                            <small class="text-muted">The public <code>.workers.dev</code> endpoint of your deployed worker.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Shared Secret Token</label>
                            <input type="password" name="edge_worker_token" value="<?= htmlspecialchars(setting('edge_worker_token', '')) ?>" class="form-control" placeholder="Matches SHARED_SECRET_TOKEN in Worker">
                            <small class="text-muted">Used to authenticate requests between your server and the Cloudflare Worker.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer bg-light p-3 text-end">
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

                <div class="p-3 bg-light rounded-3 border small">
                    <strong class="text-dark d-block mb-1">Currently Enabled Languages:</strong>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($enabledLocales as $code): ?>
                            <span class="badge bg-<?= $code === 'en' ? 'secondary' : 'primary' ?>">
                                <?= strtoupper($code) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <small class="text-muted d-block mt-2">Selecting "All Enabled Languages" will generate/sync all badges above except English.</small>
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

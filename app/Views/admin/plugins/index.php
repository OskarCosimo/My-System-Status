<!-- path: app/Views/admin/plugins/index.php -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('plugins.page_title') ?></h2>
            <p class="text-body-secondary mb-0"><?= __('plugins.page_subtitle') ?></p>
        </div>
        <form action="/admin/plugins/sync-feeds" method="POST">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-arrow-repeat me-1"></i> <?= __('plugins.btn_sync_feeds_now') ?>
            </button>
        </form>
    </div>

    <!-- Feeds Synced Alert -->
    <?php if (isset($_GET['synced'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('plugins.alert_feeds_synced') ?>
            <div class="small mt-1"><strong><?= __('plugins.alert_status_label') ?></strong> <?= htmlspecialchars($_GET['summary'] ?? '') ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Config Saved Alert -->
    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('plugins.alert_config_saved') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Translation Success Alert -->
    <?php if (isset($_GET['translated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg'] ?? __('plugins.alert_translations_updated')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Error Alert -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= __('plugins.alert_error', ['error' => htmlspecialchars($_GET['error'])]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- 1. External Status Feeds Importer with Sub-services Selection -->
        <div class="col-md-6 col-xl-6">
            <form action="/admin/plugins/save-feeds-config" method="POST" class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                <i class="bi bi-cloud-arrow-down"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('plugins.importer_title') ?></h5>
                                <span class="badge bg-success"><?= __('plugins.badge_multi_provider') ?></span>
                            </div>
                        </div>
                    </div>
                    <p class="text-body-secondary small mb-3">
                        <?= __('plugins.importer_desc') ?>
                    </p>

                    <!-- Provider Selection Checkboxes -->
                    <div class="bg-body-tertiary p-3 rounded-3 border mb-3">
                        <!-- Cloudflare Parent -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feed_cloudflare_enabled" value="1" id="feedCf" <?= setting('feed_cloudflare_enabled', '1') === '1' ? 'checked' : '' ?> onchange="toggleCfSubservices(this.checked)">
                            <label class="form-check-label fw-semibold" for="feedCf">
                                <?= __('plugins.feed_cloudflare') ?>
                            </label>
                        </div>

                        <!-- Cloudflare Sub-services Granular Selection -->
                        <div class="ms-4 mb-3 p-3 bg-body rounded-3 border <?= setting('feed_cloudflare_enabled', '1') === '1' ? '' : 'd-none' ?>" id="cfSubservicesContainer">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-body-secondary"><?= __('plugins.cf_subservices_label') ?></span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none me-2" onclick="setAllCfSubservices(true)"><?= __('plugins.select_all') ?></button>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger" onclick="setAllCfSubservices(false)"><?= __('plugins.deselect_all') ?></button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <?php foreach ($cfAvailableSubservices as $slug => $label): ?>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-check small">
                                            <input class="form-check-input cf-sub-checkbox" 
                                                   type="checkbox" 
                                                   name="cf_subservices[]" 
                                                   value="<?= htmlspecialchars($slug) ?>" 
                                                   id="cf_sub_<?= htmlspecialchars($slug) ?>"
                                                   <?= in_array($slug, $cfEnabledSubservices ?? [], true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="cf_sub_<?= htmlspecialchars($slug) ?>">
                                                <?= htmlspecialchars($label) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-body-secondary d-block mt-2" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i> <?= __('plugins.cf_deselect_help') ?>
                            </small>
                        </div>

                        <!-- Amazon AWS -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feed_aws_enabled" value="1" id="feedAws" <?= setting('feed_aws_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="feedAws">
                                <?= __('plugins.feed_aws') ?>
                            </label>
                        </div>

                        <!-- Microsoft Azure -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feed_azure_enabled" value="1" id="feedAzure" <?= setting('feed_azure_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="feedAzure">
                                <?= __('plugins.feed_azure') ?>
                            </label>
                        </div>

                        <!-- Stripe -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feed_stripe_enabled" value="1" id="feedStripe" <?= setting('feed_stripe_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="feedStripe">
                                <?= __('plugins.feed_stripe') ?>
                            </label>
                        </div>

                        <!-- PayPal -->
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feed_paypal_enabled" value="1" id="feedPaypal" <?= setting('feed_paypal_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="feedPaypal">
                                <?= __('plugins.feed_paypal') ?>
                            </label>
                        </div>

                        <!-- GitHub -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="feed_github_enabled" value="1" id="feedGh" <?= setting('feed_github_enabled', '1') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="feedGh">
                                <?= __('plugins.feed_github') ?>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-save me-1"></i> <?= __('plugins.btn_save_feeds') ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- 2. Discord Webhook Plugin -->
        <div class="col-md-6 col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                                <i class="bi bi-discord"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('plugins.discord_title') ?></h5>
                                <span class="badge bg-<?= $discordConfigured ? 'success' : 'secondary' ?>">
                                    <?= $discordConfigured ? __('plugins.badge_configured') : __('plugins.badge_not_configured') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-body-secondary small">
                        <?= __('plugins.discord_desc') ?>
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                    <a href="/admin/settings#integrationsTab" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i> <?= __('plugins.btn_configure_webhook') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. AI Incident Assistant (Ollama & Gemini) -->
        <div class="col-md-6 col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-danger bg-opacity-10 text-danger rounded-3 fs-4">
                                <i class="bi bi-robot"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('plugins.ai_title') ?></h5>
                                <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($aiProvider) ?></span>
                            </div>
                        </div>
                    </div>
                    <p class="text-body-secondary small">
                        <?= __('plugins.ai_desc', ['model' => '<strong>' . htmlspecialchars($aiModel) . '</strong>']) ?>
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                    <a href="/admin/settings#aiTab" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i> <?= __('plugins.btn_configure_ai') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- 4. LibreTranslate Auto-Translator -->
        <div class="col-md-6 col-xl-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 fs-4">
                                <i class="bi bi-translate"></i>
                            </span>
                            <div>
                                <h5 class="fw-bold mb-0"><?= __('plugins.translate_title') ?></h5>
                                <span class="badge bg-<?= !empty(setting('libretranslate_endpoint')) ? 'success' : 'secondary' ?>">
                                    <?= !empty(setting('libretranslate_endpoint')) ? __('plugins.badge_ready') : __('plugins.badge_not_configured') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-body-secondary small">
                        <?= __('plugins.translate_desc', ['endpoint' => '<code>' . htmlspecialchars($translateEndpoint ?: __('plugins.text_not_configured')) . '</code>']) ?>
                    </p>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0 pb-3 d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#translateModal">
                        <i class="bi bi-translate me-1"></i> <?= __('plugins.btn_autotranslate_jsons') ?>
                    </button>
                    <a href="/admin/settings#generalTab" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-gear me-1"></i> <?= __('plugins.btn_settings') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Auto-Translate with LibreTranslate -->
<div class="modal fade" id="translateModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/translations/sync" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-translate me-2 text-primary"></i><?= __('plugins.modal_translate_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= __('plugins.modal_translate_info') ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('plugins.field_target_lang') ?></label>
                    <select name="target_lang" class="form-select" required>
                        <option value="it"><?= __('plugins.lang_it') ?></option>
                        <option value="es"><?= __('plugins.lang_es') ?></option>
                        <option value="fr"><?= __('plugins.lang_fr') ?></option>
                        <option value="de"><?= __('plugins.lang_de') ?></option>
                        <option value="pt"><?= __('plugins.lang_pt') ?></option>
                    </select>
                    <small class="text-body-secondary"><?= __('plugins.help_lang_file') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-semibold">
                    <i class="bi bi-magic me-1"></i> <?= __('plugins.btn_start_translation') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCfSubservices(enabled) {
    const container = document.getElementById('cfSubservicesContainer');
    if (container) {
        if (enabled) {
            container.classList.remove('d-none');
        } else {
            container.classList.add('d-none');
        }
    }
}

function setAllCfSubservices(checked) {
    document.querySelectorAll('.cf-sub-checkbox').forEach(cb => {
        cb.checked = checked;
    });
}
</script>

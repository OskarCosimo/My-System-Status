<!-- path: app/Views/admin/updater/index.php -->
<?php
$permCheck = $permCheck ?? ['is_ready' => true, 'failed_paths' => []];
$canUpdate = $permCheck['is_ready'] ?? true;
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('updater.page_title') ?></h2>
            <p class="text-muted"><?= __('updater.page_subtitle') ?></p>
        </div>
        <a href="/admin/updater" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i> <?= __('updater.btn_check_again') ?>
        </a>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <strong><?= __('updater.alert_congratulations') ?></strong> <?= __('updater.alert_updated_success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= __('updater.alert_update_failed', ['error' => htmlspecialchars($_GET['error'])]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Version Status Card -->
        <div class="col-md-6 col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                            <i class="bi bi-tag-fill"></i>
                        </div>
                        <div>
                            <div class="text-muted small"><?= __('updater.installed_version') ?></div>
                            <h3 class="fw-bold mb-0">v<?= htmlspecialchars($currentVersion) ?></h3>
                        </div>
                    </div>

                    <?php if ($updateInfo && ($updateInfo['has_update'] ?? false)): ?>
                        <div class="alert alert-warning mb-0 py-2 small">
                            <i class="bi bi-arrow-up-circle-fill me-1"></i> <?= __('updater.update_available', ['version' => htmlspecialchars($updateInfo['version'])]) ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0 py-2 small">
                            <i class="bi bi-shield-check me-1"></i> <?= __('updater.running_latest') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Release Details Card -->
        <div class="col-md-6 col-xl-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-github me-2"></i><?= __('updater.release_channel_title') ?></h5>
                </div>
                <div class="card-body">
                    <?php if ($updateInfo === null): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-cloud-slash fs-1 d-block mb-2 text-secondary"></i>
                            <h6><?= __('updater.no_remote_release') ?></h6>
                            <p class="small mb-0"><?= __('updater.no_remote_release_desc') ?></p>
                        </div>
                    <?php elseif ($updateInfo['has_update']): ?>
                        <h5 class="text-primary fw-bold"><?= __('updater.new_release_title', ['version' => htmlspecialchars($updateInfo['version'])]) ?></h5>
                        <p class="text-muted small mb-2"><?= __('updater.published_at', ['date' => format_date($updateInfo['published_at'], 'M d, Y H:i')]) ?></p>
                        
                        <div class="p-3 bg-light rounded-3 border mb-3 small" style="max-height: 200px; overflow-y: auto;">
                            <?= nl2br(htmlspecialchars($updateInfo['release_notes'])) ?>
                        </div>

                        <!-- Pre-flight Write Permissions Warning -->
                        <?php if (!$canUpdate): ?>
                            <div class="alert alert-danger border-danger shadow-sm mb-3">
                                <h6 class="fw-bold mb-1"><i class="bi bi-shield-x me-1"></i> <?= __('updater.permissions_warning_title') ?></h6>
                                <p class="small mb-2">
                                    <?= __('updater.permissions_warning_desc') ?>
                                    <strong><?= htmlspecialchars(implode(', ', $permCheck['failed_paths'])) ?></strong>
                                </p>
                                <p class="small mb-1 text-muted"><?= __('updater.permissions_warning_help') ?></p>
                                <div class="p-2 bg-dark text-white rounded small font-monospace user-select-all">
                                    sudo chown -R www-data:www-data /var/www/mysystemstatus.myetv.tv
                                </div>
                            </div>
                        <?php endif; ?>

                        <form action="/admin/updater/apply" method="POST" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('updater.confirm_update'))) ?>');">
                            <input type="hidden" name="zip_url" value="<?= htmlspecialchars($updateInfo['zip_url']) ?>">
                            
                            <?php if ($canUpdate): ?>
                                <button type="submit" class="btn btn-success fw-bold px-4">
                                    <i class="bi bi-cloud-arrow-down-fill me-1"></i> <?= __('updater.btn_download_install', ['version' => htmlspecialchars($updateInfo['version'])]) ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary fw-bold px-4" disabled>
                                    <i class="bi bi-lock-fill me-1"></i> <?= __('updater.btn_permissions_disabled') ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <h5 class="fw-bold"><?= __('updater.up_to_date_title') ?></h5>
                            <p class="text-muted small mb-0"><?= __('updater.up_to_date_desc') ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

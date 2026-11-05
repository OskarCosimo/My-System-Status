<!-- path: app/Views/admin/profile/index.php -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('profile.page_title') ?></h2>
            <p class="text-muted"><?= __('profile.page_subtitle') ?></p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('profile.alert_account_saved') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['saved_2fa'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-shield-check me-2"></i> <?= __('profile.alert_2fa_saved', ['status' => '<strong>' . htmlspecialchars($_GET['saved_2fa']) . '</strong>']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= __('profile.alert_action_failed', ['error' => htmlspecialchars($_GET['error'])]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- 1. Account Details Form -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-person me-2"></i><?= __('profile.card_account_info') ?></h5>
                </div>
                <form action="/admin/profile/update-info" method="POST" class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('profile.field_name') ?></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('profile.field_email') ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= __('profile.btn_save_account') ?></button>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-key me-2"></i><?= __('profile.card_change_password') ?></h5>
                </div>
                <form action="/admin/profile/update-password" method="POST" class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('profile.field_current_password') ?></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('profile.field_new_password') ?></label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold"><?= __('profile.field_confirm_password') ?></label>
                        <input type="password" name="confirm_password" class="form-control" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-outline-primary"><?= __('profile.btn_update_password') ?></button>
                </form>
            </div>
        </div>

        <!-- 2. Two-Factor Authentication (2FA) Section -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-shield-lock me-2"></i><?= __('profile.card_2fa_title') ?></h5>
                    <span class="badge bg-<?= (int)$user['two_factor_enabled'] === 1 ? 'success' : 'secondary' ?>">
                        <?= (int)$user['two_factor_enabled'] === 1 ? __('profile.badge_enabled') : __('profile.badge_disabled') ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if ((int)$user['two_factor_enabled'] === 1): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-shield-fill-check fs-3"></i>
                            <div>
                                <strong><?= __('profile.active_2fa_title') ?></strong>
                                <div class="small"><?= __('profile.active_2fa_desc') ?></div>
                            </div>
                        </div>

                        <form action="/admin/profile/disable-2fa" method="POST" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('profile.confirm_disable_2fa'))) ?>');">
                            <h6 class="fw-bold"><?= __('profile.disable_2fa_heading') ?></h6>
                            <p class="text-muted small"><?= __('profile.disable_2fa_desc') ?></p>
                            <div class="mb-3">
                                <input type="password" name="password" class="form-control" placeholder="<?= htmlspecialchars(__('profile.placeholder_account_password')) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-outline-danger btn-sm"><?= __('profile.btn_disable_2fa') ?></button>
                        </form>

                    <?php else: ?>
                        <p class="text-muted small">
                            <?= __('profile.setup_2fa_desc') ?>
                        </p>

                        <div class="text-center p-3 bg-light rounded-3 border mb-3">
                            <div id="qrcode" class="d-inline-block p-2 bg-white rounded shadow-sm mb-2"></div>
                            <div class="small text-muted mt-1"><?= __('profile.scan_qr_desc') ?></div>
                            <code class="fw-bold text-dark fs-6 d-block my-1"><?= htmlspecialchars($tempSecret) ?></code>
                        </div>

                        <form action="/admin/profile/enable-2fa" method="POST">
                            <label class="form-label fw-semibold"><?= __('profile.field_authenticator_code') ?></label>
                            <div class="input-group mb-3">
                                <input type="text" name="code" class="form-control" placeholder="123456" maxlength="6" pattern="[0-9]{6}" required autofocus>
                                <button type="submit" class="btn btn-success fw-bold"><?= __('profile.btn_activate_2fa') ?></button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pure JS QRCode Generator Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const qrContainer = document.getElementById("qrcode");
    if (qrContainer) {
        new QRCode(qrContainer, {
            text: "<?= addslashes($qrCodeUrl) ?>",
            width: 160,
            height: 160,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    }
});
</script>

<!-- path: app/Views/admin/subscribers/index.php -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('subscribers.page_title') ?></h2>
            <p class="text-muted"><?= __('subscribers.page_subtitle') ?></p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#broadcastModal">
                <i class="bi bi-send me-1"></i> <?= __('subscribers.btn_send_broadcast') ?>
            </button>
        </div>
    </div>

    <?php if (isset($_GET['broadcast']) && $_GET['broadcast'] === 'sent'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('subscribers.alert_broadcast_sent') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><?= __('subscribers.total_subscribed') ?></div>
                    <h3 class="fw-bold mb-0 text-dark"><?= $totalCount ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small"><?= __('subscribers.verified_active') ?></div>
                    <h3 class="fw-bold mb-0 text-success"><?= $verifiedCount ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscribers Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= __('subscribers.table_email') ?></th>
                        <th><?= __('common.status') ?></th>
                        <th><?= __('subscribers.table_subscribed_date') ?></th>
                        <th class="text-end"><?= __('subscribers.table_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted"><?= __('subscribers.no_subscribers') ?></td></tr>
                    <?php endif; ?>

                    <?php foreach ($subscribers as $s): ?>
                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($s['email']) ?></td>
                            <td>
                                <?php if ((int)$s['is_verified'] === 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> <?= __('subscribers.status_verified') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> <?= __('subscribers.status_pending') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= format_date($s['created_at'], 'M d, Y H:i') ?></small></td>
                            <td class="text-end">
                                <form action="/admin/subscribers/delete" method="POST" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('subscribers.confirm_delete'))) ?>');" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('subscribers.tooltip_remove')) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Broadcast Email -->
<div class="modal fade" id="broadcastModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/subscribers/broadcast" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-broadcast me-2"></i><?= __('subscribers.modal_broadcast_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small">
                    <?= __('subscribers.broadcast_info', ['count' => '<strong>' . (int)$verifiedCount . '</strong>']) ?>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('subscribers.field_subject') ?></label>
                    <input type="text" name="subject" class="form-control" placeholder="<?= htmlspecialchars(__('subscribers.placeholder_subject')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('subscribers.field_message') ?></label>
                    <textarea name="message" class="form-control" rows="5" placeholder="<?= htmlspecialchars(__('subscribers.placeholder_message')) ?>" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> <?= __('subscribers.btn_send_to_subscribers', ['count' => (int)$verifiedCount]) ?></button>
            </div>
        </form>
    </div>
</div>

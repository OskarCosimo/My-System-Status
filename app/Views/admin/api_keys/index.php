<!-- path: app/Views/admin/api_keys/index.php -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('api_keys.page_title') ?></h2>
            <p class="text-muted"><?= __('api_keys.page_subtitle') ?></p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newKeyModal">
            <i class="bi bi-plus-lg me-1"></i> <?= __('api_keys.btn_generate_new') ?>
        </button>
    </div>

    <!-- One-Time Display of Newly Generated Plain Key -->
    <?php if (!empty($newKey)): ?>
        <div class="alert alert-success border-success shadow-sm mb-4">
            <h5 class="fw-bold"><i class="bi bi-check-circle-fill me-2"></i> <?= __('api_keys.alert_new_key_title') ?></h5>
            <p class="small mb-2">
                <?= __('api_keys.alert_new_key_desc') ?>
            </p>
            <div class="input-group" style="max-width: 650px;">
                <input type="text" id="plainKeyInput" value="<?= htmlspecialchars($newKey) ?>" class="form-control font-monospace fw-bold bg-white" readonly>
                <button class="btn btn-dark" type="button" onclick="copyApiKey()">
                    <i class="bi bi-clipboard me-1"></i> <?= __('api_keys.btn_copy_key') ?>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['revoked'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> <?= __('api_keys.alert_revoked') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- API Keys Table -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= __('api_keys.table_description') ?></th>
                        <th><?= __('api_keys.table_prefix') ?></th>
                        <th><?= __('api_keys.table_created_at') ?></th>
                        <th><?= __('api_keys.table_last_used') ?></th>
                        <th class="text-end"><?= __('api_keys.table_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($keys)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted"><?= __('api_keys.no_keys') ?></td></tr>
                    <?php endif; ?>

                    <?php foreach ($keys as $k): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($k['name']) ?></td>
                            <td><code class="text-muted"><?= htmlspecialchars($k['key_prefix']) ?></code></td>
                            <td><small class="text-muted"><?= format_date($k['created_at'], 'M d, Y') ?></small></td>
                            <td>
                                <small class="text-muted">
                                    <?= $k['last_used_at'] ? format_date($k['last_used_at'], 'M d, H:i') : __('api_keys.never') ?>
                                </small>
                            </td>
                            <td class="text-end">
                                <form action="/admin/api-keys/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('api_keys.confirm_revoke'))) ?>');">
                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('api_keys.tooltip_revoke')) ?>">
                                        <i class="bi bi-x-circle me-1"></i> <?= __('api_keys.btn_revoke') ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Documentation Card -->
    <div class="card shadow-sm border-0 p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-code-slash text-primary me-2"></i><?= __('api_keys.docs_title') ?></h5>
        <p class="text-muted small"><?= __('api_keys.docs_desc') ?> <code>Authorization: Bearer &lt;KEY&gt;</code></p>
        
        <div class="p-3 bg-dark text-white rounded font-monospace small mb-3">
            <span class="text-muted"><?= __('api_keys.docs_step1') ?></span><br>
            curl -X POST <?= htmlspecialchars(app_url()) ?>/api/v1/maintenance/enable \<br>
            &nbsp;&nbsp;-H "Authorization: Bearer YOUR_API_KEY" \<br>
            &nbsp;&nbsp;-H "Content-Type: application/json" \<br>
            &nbsp;&nbsp;-d '{"title": "Server Upgrades", "description": "Maintenance mode enabled via CI/CD"}'<br><br>
            <span class="text-muted"><?= __('api_keys.docs_step2') ?></span><br>
            curl -X POST <?= htmlspecialchars(app_url()) ?>/api/v1/maintenance/disable \<br>
            &nbsp;&nbsp;-H "Authorization: Bearer YOUR_API_KEY"
        </div>
    </div>
</div>

<!-- Modal: Generate New API Key -->
<div class="modal fade" id="newKeyModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/api-keys/store" method="POST" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= __('api_keys.modal_generate_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('api_keys.field_label') ?></label>
                    <input type="text" name="name" class="form-control" placeholder="<?= htmlspecialchars(__('api_keys.placeholder_label')) ?>" required autofocus>
                    <small class="text-muted"><?= __('api_keys.help_label') ?></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= __('api_keys.btn_submit_generate') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function copyApiKey() {
    const input = document.getElementById('plainKeyInput');
    input.select();
    navigator.clipboard.writeText(input.value);
    alert(<?= json_encode(__('api_keys.js_copied')) ?>);
}
</script>

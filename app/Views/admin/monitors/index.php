<!-- path: app/Views/admin/monitors/index.php -->
<?php
// Build a flat list containing both root monitors and sub-services for modals and delete forms
$allMonitors = [];
foreach ($monitors as $m) {
    $allMonitors[] = $m;
    if (!empty($m['children'])) {
        foreach ($m['children'] as $child) {
            $allMonitors[] = $child;
        }
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* Custom DataTables Styling Enhancements with Dark Mode support */
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
    }
    .dataTables_wrapper .dataTables_length select {
        padding-right: 2rem;
    }
    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_length select:focus {
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        border-color: var(--bs-primary);
        outline: 0;
    }
    .dataTables_info, .dataTables_paginate {
        padding-top: 1rem !important;
        padding-bottom: 0.5rem !important;
        color: var(--bs-body-color) !important;
    }
    tr.dt-child-row td {
        background-color: var(--bs-tertiary-bg) !important;
        padding: 0 !important;
    }
    /* Dynamic table header style for dark mode compatibility */
    [data-bs-theme="dark"] thead.table-light,
    [data-bs-theme="dark"] .table-light {
        --bs-table-bg: var(--bs-tertiary-bg);
        --bs-table-color: var(--bs-body-color);
        --bs-table-border-color: var(--bs-border-color);
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('monitors.page_title') ?></h2>
            <p class="text-body-secondary mb-0"><?= __('monitors.page_subtitle') ?></p>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" form="orderForm" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-down-up me-1"></i> <?= __('monitors.save_custom_order') ?>
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMonitorModal">
                <i class="bi bi-plus-lg me-1"></i> <?= __('monitors.add_new_route') ?>
            </button>
        </div>
    </div>

    <?php if (isset($_GET['reordered'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('monitors.alert_reordered') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('monitors.alert_created') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('monitors.alert_updated') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= __('monitors.alert_deleted') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form id="orderForm" action="/admin/monitors/save-order" method="POST" class="card shadow-sm border-0 p-3">
        <div class="table-responsive">
            <table id="monitorsTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;" class="text-center"><?= __('monitors.table_order') ?></th>
                        <th><?= __('common.status') ?></th>
                        <th><?= __('monitors.table_type') ?></th>
                        <th><?= __('monitors.table_service_name') ?></th>
                        <th><?= __('monitors.table_target') ?></th>
                        <th><?= __('monitors.table_uptime') ?></th>
                        <th><?= __('monitors.table_tier') ?></th>
                        <th class="text-end"><?= __('monitors.table_actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monitors as $m): ?>
                        <?php 
                            $hasChildren = !empty($m['children']); 
                            $isExternal  = !empty($m['is_external']);
                        ?>
                        <tr data-monitor-id="<?= $m['id'] ?>">
                            <!-- Order Input (Applies only to root monitor) -->
                            <td class="text-center">
                                <input type="number" 
                                       name="order[<?= $m['id'] ?>]" 
                                       value="<?= (int)($m['sort_order'] ?? 0) ?>" 
                                       class="form-control form-control-sm text-center fw-bold mx-auto" 
                                       style="width: 60px;">
                            </td>
                            <td>
                                <?php if ($m['current_status'] === 'operational'): ?>
                                    <span class="badge bg-success"><?= __('monitors.badge_up') ?></span>
                                <?php elseif ($m['current_status'] === 'down'): ?>
                                    <span class="badge bg-danger"><?= __('monitors.badge_down') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><?= strtoupper($m['current_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary text-uppercase"><?= $m['type'] ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body"><?= htmlspecialchars($m['name']) ?></strong>
                                    <?php if ($isExternal): ?>
                                        <span class="badge bg-body-secondary text-body border small" title="<?= htmlspecialchars(__('monitors.feed_tunnel_tooltip')) ?>">
                                            <i class="bi bi-plugin me-1 text-primary"></i> <?= __('monitors.feed_tunnel') ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($hasChildren): ?>
                                        <button class="btn btn-xs btn-outline-primary py-0 px-2 rounded-pill btn-toggle-subservices" 
                                                style="font-size: 11px;" 
                                                type="button" 
                                                data-monitor-id="<?= $m['id'] ?>">
                                            <span><?= __('monitors.subservices_count', ['count' => count($m['children'])]) ?></span> <i class="bi bi-chevron-down ms-1"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="max-width: 220px;" class="text-truncate">
                                <code class="small text-body-secondary"><?= htmlspecialchars($m['target']) ?></code>
                            </td>
                            <td><strong><?= number_format((float)($m['uptime_percentage'] ?? 100), 2) ?>%</strong></td>
                            <td>
                                <?php if ((int)($m['is_primary'] ?? 0) === 1): ?>
                                    <span class="badge bg-primary"><i class="bi bi-star-fill me-1"></i> <?= __('monitors.core_service') ?></span>
                                <?php else: ?>
                                    <span class="badge bg-body-secondary text-body-secondary border"><?= __('monitors.secondary_service') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <?php if ($isExternal): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="<?= htmlspecialchars(__('monitors.cannot_edit_external')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMonitorModal<?= $m['id'] ?>" title="<?= htmlspecialchars(__('monitors.edit_monitor')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" form="deleteForm<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('monitors.delete_monitor_tooltip')) ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<!-- Child Row Templates for DataTables Drawer -->
<?php foreach ($monitors as $m): ?>
    <?php if (!empty($m['children'])): ?>
        <template id="child-template-<?= $m['id'] ?>">
            <div class="p-3 bg-body-tertiary border-top border-bottom">
                <div class="ps-4 border-start border-3 border-primary">
                    <h6 class="fw-bold text-body-secondary small mb-2"><?= __('monitors.subservices_under', ['name' => htmlspecialchars($m['name'])]) ?></h6>
                    <div class="row g-2">
                        <?php foreach ($m['children'] as $child): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="d-flex justify-content-between align-items-center p-2 bg-body rounded border small">
                                    <div class="d-flex align-items-center text-truncate me-2">
                                        <span class="text-truncate fw-semibold text-body"><?= htmlspecialchars($child['name']) ?></span>
                                        <span class="badge bg-secondary text-uppercase ms-2" style="font-size: 10px;"><?= $child['type'] ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-<?= $child['current_status'] === 'operational' ? 'success' : 'warning text-dark' ?>">
                                            <?= strtoupper($child['current_status']) ?>
                                        </span>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($child['is_external']): ?>
                                                <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" disabled title="<?= htmlspecialchars(__('monitors.cannot_edit_external')) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#editMonitorModal<?= $child['id'] ?>" title="<?= htmlspecialchars(__('monitors.edit_subservice')) ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="submit" form="deleteForm<?= $child['id'] ?>" class="btn btn-xs btn-outline-danger py-0 px-2" title="<?= htmlspecialchars(__('monitors.delete_subservice_tooltip')) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </template>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Standalone Delete Forms for Root and Child Monitors -->
<?php foreach ($allMonitors as $m): ?>
    <form id="deleteForm<?= $m['id'] ?>" action="/admin/monitors/delete" method="POST" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('monitors.confirm_delete', ['name' => $m['name']]))) ?>');">
        <input type="hidden" name="id" value="<?= $m['id'] ?>">
    </form>
<?php endforeach; ?>

<!-- Edit Modals for Root and Child Monitors -->
<?php foreach ($allMonitors as $m): ?>
    <?php if (empty($m['is_external'])): ?>
        <?php
            $currentType = $m['type'] ?? 'http';
            $fieldConfig = [
                'http' => ['label' => __('monitors.target_label_url'), 'placeholder' => 'https://api.example.com'],
                'ping' => ['label' => __('monitors.target_label_host'), 'placeholder' => '192.168.1.1 or example.com'],
                'port' => ['label' => __('monitors.target_label_host'), 'placeholder' => '127.0.0.1 or db.example.com'],
                'ssl'  => ['label' => __('monitors.target_label_domain'), 'placeholder' => 'example.com'],
                'path' => ['label' => __('monitors.target_label_path'), 'placeholder' => '/mnt/storage or /media/backup_disk'],
            ];
            $currentLabel = $fieldConfig[$currentType]['label'] ?? __('monitors.table_target');
            $currentPlaceholder = $fieldConfig[$currentType]['placeholder'] ?? '';
        ?>
        <div class="modal fade" id="editMonitorModal<?= $m['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <form action="/admin/monitors/update" method="POST" class="modal-content">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><?= __('monitors.edit_monitor_title', ['name' => htmlspecialchars($m['name'])]) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('monitors.form_service_name') ?></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($m['name']) ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?= __('monitors.form_probe_type') ?></label>
                                <select name="type" id="probeTypeEdit<?= $m['id'] ?>" class="form-select" onchange="updateProbeFieldsEdit(<?= $m['id'] ?>)">
                                    <option value="http" <?= $currentType === 'http' ? 'selected' : '' ?>><?= __('monitors.type_http') ?></option>
                                    <option value="ping" <?= $currentType === 'ping' ? 'selected' : '' ?>><?= __('monitors.type_ping') ?></option>
                                    <option value="port" <?= $currentType === 'port' ? 'selected' : '' ?>><?= __('monitors.type_port') ?></option>
                                    <option value="ssl" <?= $currentType === 'ssl' ? 'selected' : '' ?>><?= __('monitors.type_ssl') ?></option>
                                    <option value="path" <?= $currentType === 'path' ? 'selected' : '' ?>><?= __('monitors.type_path') ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold"><?= __('monitors.form_check_interval') ?></label>
                                <select name="interval_seconds" class="form-select">
                                    <option value="30" <?= (int)$m['interval_seconds'] === 30 ? 'selected' : '' ?>><?= __('monitors.interval_30s') ?></option>
                                    <option value="60" <?= (int)$m['interval_seconds'] === 60 ? 'selected' : '' ?>><?= __('monitors.interval_1m') ?></option>
                                    <option value="300" <?= (int)$m['interval_seconds'] === 300 ? 'selected' : '' ?>><?= __('monitors.interval_5m') ?></option>
                                    <option value="600" <?= (int)$m['interval_seconds'] === 600 ? 'selected' : '' ?>><?= __('monitors.interval_10m') ?></option>
                                    <option value="900" <?= (int)$m['interval_seconds'] === 900 ? 'selected' : '' ?>><?= __('monitors.interval_15m') ?></option>
                                    <option value="1800" <?= (int)$m['interval_seconds'] === 1800 ? 'selected' : '' ?>><?= __('monitors.interval_30m') ?></option>
                                    <option value="3600" <?= (int)$m['interval_seconds'] === 3600 ? 'selected' : '' ?>><?= __('monitors.interval_1h') ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" id="targetLabelEdit<?= $m['id'] ?>"><?= $currentLabel ?></label>
                            <input type="text" name="target" id="targetInputEdit<?= $m['id'] ?>" class="form-control" value="<?= htmlspecialchars($m['target']) ?>" placeholder="<?= $currentPlaceholder ?>" required>
                        </div>

                        <div class="mb-3 <?= $currentType === 'port' ? '' : 'd-none' ?>" id="portFieldWrapperEdit<?= $m['id'] ?>">
                            <label class="form-label fw-semibold"><?= __('monitors.form_port_number') ?></label>
                            <input type="number" name="port" class="form-control" value="<?= htmlspecialchars($m['port'] ?? '') ?>" placeholder="e.g. 3306, 22, 443">
                        </div>

                        <!-- Parent Group Assignment -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('monitors.form_parent_group') ?></label>
                            <select name="parent_id" class="form-select">
                                <option value=""><?= __('monitors.form_no_parent') ?></option>
                                <?php foreach ($parentsList as $parent): ?>
                                    <?php if ($parent['id'] != $m['id']): ?>
                                        <option value="<?= $parent['id'] ?>" <?= (isset($m['parent_id']) && $m['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($parent['name']) ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Primary Core Service Checkbox -->
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="primarySwitchEdit<?= $m['id'] ?>" <?= (int)($m['is_primary'] ?? 0) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="primarySwitchEdit<?= $m['id'] ?>">
                                <?= __('monitors.form_mark_core') ?>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                        <button type="submit" class="btn btn-primary fw-semibold"><?= __('monitors.save_changes') ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<!-- Create New Monitor Modal -->
<div class="modal fade" id="newMonitorModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/monitors/store" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= __('monitors.add_new_monitor') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('monitors.form_service_name') ?></label>
                    <input type="text" name="name" class="form-control" placeholder="<?= htmlspecialchars(__('monitors.form_service_name_placeholder')) ?>" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('monitors.form_probe_type') ?></label>
                        <select name="type" id="probeType" class="form-select" onchange="updateProbeFields()">
                            <option value="http" selected><?= __('monitors.type_http') ?></option>
                            <option value="ping"><?= __('monitors.type_ping') ?></option>
                            <option value="port"><?= __('monitors.type_port') ?></option>
                            <option value="ssl"><?= __('monitors.type_ssl') ?></option>
                            <option value="path"><?= __('monitors.type_path') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('monitors.form_check_interval') ?></label>
                        <select name="interval_seconds" class="form-select">
                            <option value="30"><?= __('monitors.interval_30s') ?></option>
                            <option value="60" selected><?= __('monitors.interval_1m') ?></option>
                            <option value="300"><?= __('monitors.interval_5m') ?></option>
                            <option value="600"><?= __('monitors.interval_10m') ?></option>
                            <option value="900"><?= __('monitors.interval_15m') ?></option>
                            <option value="1800"><?= __('monitors.interval_30m') ?></option>
                            <option value="3600"><?= __('monitors.interval_1h') ?></option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" id="targetLabel"><?= __('monitors.target_label_url') ?></label>
                    <input type="text" name="target" id="targetInput" class="form-control" placeholder="https://api.example.com" required>
                </div>

                <div class="mb-3 d-none" id="portFieldWrapper">
                    <label class="form-label fw-semibold"><?= __('monitors.form_port_number') ?></label>
                    <input type="number" name="port" class="form-control" placeholder="e.g. 3306, 22, 443">
                </div>

                <!-- Parent Group Assignment (Optional) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('monitors.form_parent_group') ?></label>
                    <select name="parent_id" class="form-select">
                        <option value=""><?= __('monitors.form_no_parent') ?></option>
                        <?php foreach ($parentsList as $parent): ?>
                            <option value="<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-body-secondary"><?= __('monitors.form_parent_help') ?></small>
                </div>

                <!-- Primary Core Service Checkbox -->
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="primarySwitch" checked>
                    <label class="form-check-label fw-semibold" for="primarySwitch">
                        <?= __('monitors.form_mark_core') ?>
                    </label>
                    <div class="text-body-secondary small"><?= __('monitors.form_core_help') ?></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-semibold"><?= __('monitors.create_monitor') ?></button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
// Probe field definitions for localized labels and placeholders
const probeFieldConfig = {
    http: {
        label: <?= json_encode(__('monitors.target_label_url')) ?>,
        placeholder: 'https://api.example.com'
    },
    ping: {
        label: <?= json_encode(__('monitors.target_label_host')) ?>,
        placeholder: '192.168.1.1 or example.com'
    },
    port: {
        label: <?= json_encode(__('monitors.target_label_host')) ?>,
        placeholder: '127.0.0.1 or db.example.com'
    },
    ssl: {
        label: <?= json_encode(__('monitors.target_label_domain')) ?>,
        placeholder: 'example.com'
    },
    path: {
        label: <?= json_encode(__('monitors.target_label_path')) ?>,
        placeholder: '/mnt/storage or /media/backup_disk'
    }
};

$(document).ready(function() {
    // Initialize DataTables cleanly without DOM column count mismatches
    if ($('#monitorsTable tbody tr').length > 0) {
        var table = $('#monitorsTable').DataTable({
            ordering: false, // Maintain custom display order
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                search: "_INPUT_",
                searchPlaceholder: <?= json_encode(__('monitors.dt_search')) ?>,
                lengthMenu: <?= json_encode(__('monitors.dt_length')) ?>,
                info: <?= json_encode(__('monitors.dt_info')) ?>,
                infoEmpty: <?= json_encode(__('monitors.dt_info_empty')) ?>,
                zeroRecords: <?= json_encode(__('monitors.dt_zero_records')) ?>
            },
            columnDefs: [
                { orderable: false, targets: '_all' }
            ]
        });

        // Toggle Sub-services Drawer via DataTables Native Child Rows API
        $('#monitorsTable tbody').on('click', '.btn-toggle-subservices', function () {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            var mId = $(this).data('monitor-id');
            var template = document.getElementById('child-template-' + mId);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('dt-has-child-expanded');
                $(this).find('i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
            } else if (template) {
                row.child(template.innerHTML, 'dt-child-row').show();
                tr.addClass('dt-has-child-expanded');
                $(this).find('i').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            }
        });
    }
});

// Dynamic field updater for creation modal
function updateProbeFields() {
    const type = document.getElementById('probeType').value;
    const portField = document.getElementById('portFieldWrapper');
    const targetLabel = document.getElementById('targetLabel');
    const targetInput = document.getElementById('targetInput');

    if (type === 'port') {
        portField.classList.remove('d-none');
    } else {
        portField.classList.add('d-none');
    }

    const config = probeFieldConfig[type] || probeFieldConfig.http;
    targetLabel.textContent = config.label;
    targetInput.placeholder = config.placeholder;
}

// Dynamic field updater for edit modals
function updateProbeFieldsEdit(id) {
    const type = document.getElementById('probeTypeEdit' + id).value;
    const portField = document.getElementById('portFieldWrapperEdit' + id);
    const targetLabel = document.getElementById('targetLabelEdit' + id);
    const targetInput = document.getElementById('targetInputEdit' + id);

    if (type === 'port') {
        portField.classList.remove('d-none');
    } else {
        portField.classList.add('d-none');
    }

    const config = probeFieldConfig[type] || probeFieldConfig.http;
    targetLabel.textContent = config.label;
    targetInput.placeholder = config.placeholder;
}
</script>

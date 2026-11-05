<!-- path: app/Views/admin/incidents/index.php -->
<?php
// Fallbacks to avoid unhandled variable notices
$activeIncidents   = $activeIncidents ?? [];
$resolvedIncidents = $resolvedIncidents ?? [];
$activeTimezone    = $activeTimezone ?? \App\Services\DateService::getActiveTimezone();
$timezonesList     = $timezonesList ?? \App\Services\DateService::getTimezonesList();
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    /* DataTables Enhancements for Bootstrap 5 Dark Mode */
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
        padding: 0.75rem 1rem !important;
        color: var(--bs-body-color) !important;
    }
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
            <h2 class="fw-bold mb-0"><?= __('incidents.page_title') ?></h2>
            <p class="text-body-secondary mb-0"><?= __('incidents.page_subtitle') ?></p>
        </div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#newIncidentModal">
            <i class="bi bi-exclamation-octagon me-1"></i> <?= __('incidents.declare_incident') ?>
        </button>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('incidents.alert_created') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('incidents.alert_updated') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['edited'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('incidents.alert_edited') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> <?= __('incidents.alert_deleted') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 1. ACTIVE & ONGOING INCIDENTS SECTION -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= __('incidents.active_incidents_title') ?>
            </h5>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                <?= __('incidents.count_active', ['count' => count($activeIncidents)]) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($activeIncidents)): ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="bi bi-shield-fill-check text-success fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold text-body"><?= __('incidents.all_systems_operational') ?></h5>
                    <p class="small mb-0"><?= __('incidents.no_active_incidents') ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="activeIncidentsTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('incidents.table_impact') ?></th>
                                <th><?= __('incidents.table_title') ?></th>
                                <th><?= __('incidents.table_current_status') ?></th>
                                <th><?= __('incidents.table_start_window') ?></th>
                                <th class="text-end"><?= __('incidents.table_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeIncidents as $inc): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= $inc['impact'] === 'critical' ? 'danger' : ($inc['impact'] === 'major' ? 'warning text-dark' : 'secondary') ?>">
                                            <?= strtoupper($inc['impact']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-body d-block"><?= htmlspecialchars($inc['title']) ?></strong>
                                        <?php if (!empty($inc['ai_summary'])): ?>
                                            <small class="text-body-secondary"><i class="bi bi-robot text-primary me-1"></i><?= htmlspecialchars(mb_strimwidth($inc['ai_summary'], 0, 75, '...')) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-body-secondary text-body border text-uppercase px-2 py-1">
                                            <?= $inc['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-body-secondary">
                                            <?= format_date($inc['start_time'] ?? $inc['created_at'], 'M d, H:i') ?>
                                            <span class="badge bg-body-secondary text-body-secondary border ms-1"><?= htmlspecialchars($inc['timezone'] ?? 'UTC') ?></span>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <!-- Copy Direct Share Link Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                onclick="copyShareLink('<?= app_url() ?>/?incident=<?= $inc['id'] ?>', this)" 
                                                title="<?= htmlspecialchars(__('incidents.tooltip_copy_link')) ?>">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <button class="btn btn-sm btn-primary me-1" 
                                                onclick="openUpdateModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['status'] ?>')"
                                                title="<?= htmlspecialchars(__('incidents.tooltip_update_resolve')) ?>">
                                            <i class="bi bi-check2-circle me-1"></i> <?= __('incidents.btn_update_resolve') ?>
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['impact'] ?>', '<?= htmlspecialchars(addslashes($inc['ai_summary'] ?? '')) ?>', '<?= $inc['monitor_id'] ?? '' ?>', '<?= $inc['start_local'] ?? '' ?>', '<?= $inc['end_local'] ?? '' ?>', '<?= htmlspecialchars(addslashes($inc['timezone'] ?? $activeTimezone)) ?>')"
                                                title="<?= htmlspecialchars(__('incidents.tooltip_edit_incident')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/admin/incidents/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('incidents.confirm_delete'))) ?>');">
                                            <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('incidents.tooltip_delete')) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. PAST & RESOLVED INCIDENTS SECTION -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-secondary">
                <i class="bi bi-archive me-2"></i><?= __('incidents.past_incidents_title') ?>
            </h5>
            <span class="badge bg-body-secondary text-body-secondary border">
                <?= __('incidents.count_archived', ['count' => count($resolvedIncidents)]) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resolvedIncidents)): ?>
                <div class="text-center py-4 text-body-secondary small"><?= __('incidents.no_resolved_incidents') ?></div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="resolvedIncidentsTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('incidents.table_title') ?></th>
                                <th><?= __('incidents.table_impact') ?></th>
                                <th><?= __('incidents.table_timeline_period') ?></th>
                                <th class="text-end"><?= __('incidents.table_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resolvedIncidents as $inc): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                            <span class="fw-semibold text-body"><?= htmlspecialchars($inc['title']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-body-secondary text-body-secondary border text-uppercase">
                                            <?= $inc['impact'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-body-secondary">
                                            <?= format_date($inc['start_time'] ?? $inc['created_at'], 'M d, H:i') ?>
                                            &mdash;
                                            <?= format_date($inc['end_time'] ?? $inc['updated_at'], 'M d, Y H:i') ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <!-- Copy Direct Share Link Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                onclick="copyShareLink('<?= app_url() ?>/?incident=<?= $inc['id'] ?>', this)" 
                                                title="<?= htmlspecialchars(__('incidents.tooltip_copy_link')) ?>">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                onclick="openUpdateModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', 'monitoring')"
                                                title="<?= htmlspecialchars(__('incidents.tooltip_reopen')) ?>">
                                            <i class="bi bi-arrow-counterclockwise"></i> <?= __('incidents.btn_reopen') ?>
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['impact'] ?>', '<?= htmlspecialchars(addslashes($inc['ai_summary'] ?? '')) ?>', '<?= $inc['monitor_id'] ?? '' ?>', '<?= $inc['start_local'] ?? '' ?>', '<?= $inc['end_local'] ?? '' ?>', '<?= htmlspecialchars(addslashes($inc['timezone'] ?? $activeTimezone)) ?>')"
                                                title="<?= htmlspecialchars(__('incidents.tooltip_edit_incident')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/admin/incidents/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('incidents.confirm_delete_archived'))) ?>');">
                                            <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('incidents.tooltip_delete')) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal 1: Post Update / Mark as Resolved -->
<div class="modal fade" id="postUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/incidents/update-status" method="POST" class="modal-content shadow">
            <input type="hidden" name="incident_id" id="updateModalIncidentId" value="">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="updateModalTitle"><?= __('incidents.modal_update_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_new_status') ?></label>
                    <select name="status" id="updateModalStatusSelect" class="form-select" required>
                        <option value="investigating"><?= __('incidents.status_investigating_desc') ?></option>
                        <option value="identified"><?= __('incidents.status_identified_desc') ?></option>
                        <option value="monitoring"><?= __('incidents.status_monitoring_desc') ?></option>
                        <option value="resolved" class="fw-bold text-success"><?= __('incidents.status_resolved_desc') ?></option>
                    </select>
                    <small class="text-body-secondary"><?= __('incidents.help_status_resolved') ?></small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_timeline_note') ?></label>
                    <textarea name="message" class="form-control" rows="3" placeholder="<?= htmlspecialchars(__('incidents.placeholder_timeline_note')) ?>" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= __('incidents.btn_publish_update') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Incident Details -->
<div class="modal fade" id="editIncidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="/admin/incidents/update" method="POST" class="modal-content shadow">
            <input type="hidden" name="incident_id" id="editModalIncidentId" value="">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= __('incidents.modal_edit_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_incident_title') ?></label>
                    <input type="text" name="title" id="editModalTitleInput" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('incidents.field_impact_severity') ?></label>
                        <select name="impact" id="editModalImpactSelect" class="form-select">
                            <option value="minor"><?= __('incidents.impact_minor') ?></option>
                            <option value="major"><?= __('incidents.impact_major') ?></option>
                            <option value="critical"><?= __('incidents.impact_critical') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('incidents.field_affected_service') ?></label>
                        <select name="monitor_id" id="editModalMonitorSelect" class="form-select">
                            <option value=""><?= __('incidents.affected_all_services') ?></option>
                            <?php foreach ($monitorsList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><?= __('incidents.field_start_time') ?></label>
                        <input type="datetime-local" name="start_time" id="editModalStartTimeInput" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><?= __('incidents.field_end_time_optional') ?></label>
                        <input type="datetime-local" name="end_time" id="editModalEndTimeInput" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> <?= __('incidents.field_timezone') ?></label>
                        <select name="timezone" id="editModalTimezoneSelect" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>"><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_ai_summary') ?></label>
                    <textarea name="ai_summary" id="editModalAiSummaryInput" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= __('incidents.btn_save_changes') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Declare New Incident (with AI Draft) -->
<div class="modal fade" id="newIncidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="/admin/incidents/store" method="POST" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title text-danger fw-bold"><i class="bi bi-exclamation-triangle me-2"></i><?= __('incidents.modal_declare_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_incident_title') ?></label>
                    <input type="text" name="title" id="incidentTitle" class="form-control" placeholder="<?= htmlspecialchars(__('incidents.placeholder_incident_title')) ?>" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('incidents.field_impact_level') ?></label>
                        <select name="impact" class="form-select">
                            <option value="minor"><?= __('incidents.impact_minor') ?></option>
                            <option value="major"><?= __('incidents.impact_major') ?></option>
                            <option value="critical"><?= __('incidents.impact_critical_outage') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('incidents.field_initial_status') ?></label>
                        <select name="status" class="form-select">
                            <option value="investigating"><?= __('incidents.status_investigating') ?></option>
                            <option value="identified"><?= __('incidents.status_identified') ?></option>
                            <option value="monitoring"><?= __('incidents.status_monitoring') ?></option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_affected_service_optional') ?></label>
                    <select name="monitor_id" class="form-select">
                        <option value=""><?= __('incidents.affected_all_services') ?></option>
                        <?php foreach ($monitorsList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-body-secondary"><?= __('incidents.help_affected_service') ?></small>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><?= __('incidents.field_start_time') ?></label>
                        <input type="datetime-local" name="start_time" class="form-control" value="<?= \App\Services\DateService::toLocal(gmdate('Y-m-d H:i:s'), $activeTimezone) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><?= __('incidents.field_end_time_optional') ?></label>
                        <input type="datetime-local" name="end_time" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> <?= __('incidents.field_timezone') ?></label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>" <?= $tz === $activeTimezone ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_raw_logs') ?></label>
                    <textarea id="rawErrorDetails" class="form-control" rows="2" placeholder="<?= htmlspecialchars(__('incidents.placeholder_raw_logs')) ?>"></textarea>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0"><?= __('incidents.field_public_ai_summary') ?></label>
                        <button type="button" class="btn-sm btn-outline-primary" id="btnGenerateAi" onclick="fetchAiSummary()">
                            <i class="bi bi-robot me-1"></i> <?= __('incidents.btn_draft_ai') ?>
                        </button>
                    </div>
                    <textarea name="ai_summary" id="aiSummaryField" class="form-control" rows="2" placeholder="<?= htmlspecialchars(__('incidents.placeholder_ai_summary')) ?>"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('incidents.field_initial_timeline_note') ?></label>
                    <textarea name="message" class="form-control" rows="2" placeholder="<?= htmlspecialchars(__('incidents.placeholder_initial_timeline_note')) ?>" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-danger fw-bold"><?= __('incidents.btn_publish_incident') ?></button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTables for Active Incidents
    if ($('#activeIncidentsTable').length > 0) {
        $('#activeIncidentsTable').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            columnDefs: [{ orderable: false, targets: 4 }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: <?= json_encode(__('incidents.dt_search_active')) ?>,
                lengthMenu: <?= json_encode(__('incidents.dt_length')) ?>,
                info: <?= json_encode(__('incidents.dt_info_active')) ?>,
                infoEmpty: <?= json_encode(__('incidents.dt_info_empty_active')) ?>,
                zeroRecords: <?= json_encode(__('incidents.dt_zero_records_active')) ?>
            }
        });
    }

    // Initialize DataTables for Resolved Incidents
    if ($('#resolvedIncidentsTable').length > 0) {
        $('#resolvedIncidentsTable').DataTable({
            pageLength: 10,
            order: [[2, 'desc']],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            columnDefs: [{ orderable: false, targets: 3 }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: <?= json_encode(__('incidents.dt_search_resolved')) ?>,
                lengthMenu: <?= json_encode(__('incidents.dt_length')) ?>,
                info: <?= json_encode(__('incidents.dt_info_resolved')) ?>,
                infoEmpty: <?= json_encode(__('incidents.dt_info_empty_resolved')) ?>,
                zeroRecords: <?= json_encode(__('incidents.dt_zero_records_resolved')) ?>
            }
        });
    }
});

function openUpdateModal(id, title, currentStatus) {
    document.getElementById('updateModalIncidentId').value = id;
    document.getElementById('updateModalTitle').textContent = <?= json_encode(__('incidents.modal_update_title_prefix')) ?> + ': ' + title;
    document.getElementById('updateModalStatusSelect').value = currentStatus;
    new bootstrap.Modal(document.getElementById('postUpdateModal')).show();
}

function openEditModal(id, title, impact, aiSummary, monitorId, startLocal, endLocal, timezone) {
    document.getElementById('editModalIncidentId').value = id;
    document.getElementById('editModalTitleInput').value = title;
    document.getElementById('editModalImpactSelect').value = impact;
    document.getElementById('editModalAiSummaryInput').value = aiSummary;
    document.getElementById('editModalMonitorSelect').value = monitorId || '';
    document.getElementById('editModalStartTimeInput').value = startLocal || '';
    document.getElementById('editModalEndTimeInput').value = endLocal || '';
    if (timezone) {
        document.getElementById('editModalTimezoneSelect').value = timezone;
    }
    new bootstrap.Modal(document.getElementById('editIncidentModal')).show();
}

async function fetchAiSummary() {
    const btn = document.getElementById('btnGenerateAi');
    const title = document.getElementById('incidentTitle').value || 'Service Outage';
    const errorDetails = document.getElementById('rawErrorDetails').value || 'Service timeout';
    const target = document.getElementById('aiSummaryField');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + <?= json_encode(__('incidents.btn_generating_ai')) ?>;

    try {
        const res = await fetch('/admin/incidents/ai-generate', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({service_name: title, error_details: errorDetails})
        });
        const data = await res.json();
        if (data.status === 'success') {
            target.value = data.summary;
        } else {
            alert(<?= json_encode(__('incidents.js_ai_fail')) ?>);
        }
    } catch (e) {
        console.error(e);
        alert(<?= json_encode(__('incidents.js_ai_error')) ?>);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i> ' + <?= json_encode(__('incidents.btn_draft_ai')) ?>;
    }
}

// Copy direct share link to clipboard with visual feedback
function copyShareLink(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
        btn.classList.remove('btn-outline-info');
        btn.classList.add('btn-outline-success');
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-outline-info');
        }, 1500);
    }).catch(err => {
        prompt(<?= json_encode(__('incidents.js_direct_link')) ?>, url);
    });
}
</script>

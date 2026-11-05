<!-- path: app/Views/admin/maintenance/index.php -->
<?php
$activeTimezone = $activeTimezone ?? \App\Services\DateService::getActiveTimezone();
$timezonesList  = $timezonesList ?? \App\Services\DateService::getTimezonesList();
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

    /* FullCalendar Dark Theme Adjustments */
    [data-bs-theme="dark"] .fc {
        --fc-border-color: var(--bs-border-color);
        --fc-page-bg-color: var(--bs-body-bg);
        --fc-neutral-bg-color: var(--bs-tertiary-bg);
        --fc-list-event-hover-bg-color: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .fc .fc-col-header-cell-cushion,
    [data-bs-theme="dark"] .fc .fc-daygrid-day-number {
        color: var(--bs-body-color);
    }
    [data-bs-theme="dark"] .fc-theme-standard th,
    [data-bs-theme="dark"] .fc-theme-standard td {
        border-color: var(--bs-border-color);
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><?= __('maintenance.title') ?></h2>
            <p class="text-body-secondary mb-0"><?= __('maintenance.page_subtitle') ?></p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMaintenanceModal">
            <i class="bi bi-plus-lg me-1"></i> <?= __('maintenance.schedule_new') ?>
        </button>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('maintenance.alert_created') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= __('maintenance.alert_updated') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> <?= __('maintenance.alert_deleted') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FullCalendar Interactive View -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0"><i class="bi bi-calendar-week text-primary me-2"></i><?= __('maintenance.interactive_calendar') ?></h5>
            <small class="text-body-secondary"><?= __('maintenance.interactive_calendar_help') ?></small>
        </div>
        <div class="card-body p-4">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- 1. UPCOMING & IN-PROGRESS MAINTENANCES -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-primary">
                <i class="bi bi-clock-history me-2"></i><?= __('maintenance.upcoming_title') ?>
            </h5>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                <?= __('maintenance.count_active', ['count' => count($upcoming)]) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($upcoming)): ?>
                <div class="text-center py-4 text-body-secondary small"><?= __('maintenance.no_upcoming') ?></div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="upcomingMaintenanceTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('common.status') ?></th>
                                <th><?= __('common.title') ?></th>
                                <th><?= __('maintenance.table_start_window') ?></th>
                                <th><?= __('maintenance.table_end_window') ?></th>
                                <th class="text-end"><?= __('maintenance.table_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming as $m): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?= $m['status'] === 'in_progress' ? 'warning text-dark' : 'primary' ?> text-uppercase">
                                            <?= $m['status'] === 'in_progress' ? __('maintenance.in_progress') : __('maintenance.scheduled') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-body d-block"><?= htmlspecialchars($m['title']) ?></strong>
                                        <small class="text-body-secondary"><?= htmlspecialchars(mb_strimwidth($m['description'] ?? '', 0, 80, '...')) ?></small>
                                    </td>
                                    <td data-order="<?= strtotime($m['start_time']) ?>">
                                        <small class="text-body-secondary"><?= format_date($m['start_time'], 'M d, Y H:i') ?></small>
                                    </td>
                                    <td data-order="<?= strtotime($m['end_time']) ?>">
                                        <small class="text-body-secondary"><?= format_date($m['end_time'], 'M d, Y H:i') ?></small>
                                    </td>
                                    <td class="text-end">
                                        <!-- Copy Direct Share Link Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                onclick="copyShareLink('<?= app_url() ?>/?maintenance=<?= $m['id'] ?>', this)" 
                                                title="<?= htmlspecialchars(__('maintenance.tooltip_copy_link')) ?>">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <!-- Quick Mark as Completed Button (Preserves monitor_id) -->
                                        <form action="/admin/maintenance/update" method="POST" class="d-inline">
                                            <input type="hidden" name="maintenance_id" value="<?= $m['id'] ?>">
                                            <input type="hidden" name="monitor_id" value="<?= $m['monitor_id'] ?? '' ?>">
                                            <input type="hidden" name="title" value="<?= htmlspecialchars($m['title']) ?>">
                                            <input type="hidden" name="description" value="<?= htmlspecialchars($m['description'] ?? '') ?>">
                                            <input type="hidden" name="start_time" value="<?= $m['start_local'] ?>">
                                            <input type="hidden" name="end_time" value="<?= $m['end_local'] ?>">
                                            <input type="hidden" name="timezone" value="<?= htmlspecialchars($m['timezone'] ?? $activeTimezone) ?>">
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="btn btn-sm btn-success me-1" title="<?= htmlspecialchars(__('maintenance.tooltip_mark_completed')) ?>">
                                                <i class="bi bi-check-lg me-1"></i> <?= __('maintenance.btn_complete') ?>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Trigger -->
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditMaintenanceModal(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['title'])) ?>', '<?= htmlspecialchars(addslashes($m['description'] ?? '')) ?>', '<?= $m['start_local'] ?>', '<?= $m['end_local'] ?>', '<?= $m['status'] ?>', '<?= $m['monitor_id'] ?? '' ?>', '<?= htmlspecialchars(addslashes($m['timezone'] ?? $activeTimezone)) ?>')"
                                                title="<?= htmlspecialchars(__('maintenance.tooltip_edit')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Delete Trigger -->
                                        <form action="/admin/maintenance/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('maintenance.confirm_delete'))) ?>');">
                                            <input type="hidden" name="maintenance_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('maintenance.tooltip_delete')) ?>">
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

    <!-- 2. PAST & COMPLETED MAINTENANCES (ARCHIVE) -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-secondary">
                <i class="bi bi-archive me-2"></i><?= __('maintenance.past_title') ?>
            </h5>
            <span class="badge bg-body-secondary text-body-secondary border">
                <?= __('maintenance.count_archived', ['count' => count($past)]) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($past)): ?>
                <div class="text-center py-4 text-body-secondary small"><?= __('maintenance.no_past') ?></div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="pastMaintenanceTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('common.title') ?></th>
                                <th><?= __('maintenance.table_execution_window') ?></th>
                                <th><?= __('common.status') ?></th>
                                <th class="text-end"><?= __('maintenance.table_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past as $p): ?>
                                <tr>
                                    <td>
                                        <span class="fw-semibold text-body"><?= htmlspecialchars($p['title']) ?></span>
                                    </td>
                                    <td data-order="<?= strtotime($p['start_time']) ?>">
                                        <small class="text-body-secondary">
                                            <?= format_date($p['start_time'], 'M d, H:i') ?> &mdash; <?= format_date($p['end_time'], 'M d, H:i') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <?= __('maintenance.completed') ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <!-- Copy Direct Share Link Button -->
                                        <button type="button" class="btn btn-sm btn-outline-info me-1" 
                                                onclick="copyShareLink('<?= app_url() ?>/?maintenance=<?= $p['id'] ?>', this)" 
                                                title="<?= htmlspecialchars(__('maintenance.tooltip_copy_link')) ?>">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditMaintenanceModal(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['title'])) ?>', '<?= htmlspecialchars(addslashes($p['description'] ?? '')) ?>', '<?= $p['start_local'] ?>', '<?= $p['end_local'] ?>', '<?= $p['status'] ?>', '<?= $p['monitor_id'] ?? '' ?>', '<?= htmlspecialchars(addslashes($p['timezone'] ?? $activeTimezone)) ?>')"
                                                title="<?= htmlspecialchars(__('maintenance.tooltip_edit')) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/admin/maintenance/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(addslashes(__('maintenance.confirm_delete_archived'))) ?>');">
                                            <input type="hidden" name="maintenance_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= htmlspecialchars(__('maintenance.tooltip_delete')) ?>">
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

<!-- Modal 1: Schedule New Maintenance -->
<div class="modal fade" id="newMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/maintenance/store" method="POST" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= __('maintenance.modal_new_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('common.title') ?></label>
                    <input type="text" name="title" class="form-control" placeholder="<?= htmlspecialchars(__('maintenance.placeholder_title')) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('maintenance.field_target_service') ?></label>
                    <select name="monitor_id" class="form-select">
                        <option value=""><?= __('maintenance.target_all_services') ?></option>
                        <?php foreach ($monitorsList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-body-secondary"><?= __('maintenance.target_service_help') ?></small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('maintenance.field_start_window') ?></label>
                        <input type="datetime-local" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('maintenance.field_end_window') ?></label>
                        <input type="datetime-local" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('maintenance.field_initial_status') ?></label>
                        <select name="status" class="form-select">
                            <option value="scheduled" selected><?= __('maintenance.scheduled') ?></option>
                            <option value="in_progress"><?= __('maintenance.in_progress') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> <?= __('maintenance.field_timezone') ?></label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>" <?= $tz === $activeTimezone ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= __('maintenance.btn_schedule_event') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Edit Maintenance -->
<div class="modal fade" id="editMaintenanceModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="/admin/maintenance/update" method="POST" class="modal-content shadow">
            <input type="hidden" name="maintenance_id" id="editMaintId" value="">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><?= __('maintenance.modal_edit_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('common.title') ?></label>
                    <input type="text" name="title" id="editMaintTitle" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('common.description') ?></label>
                    <textarea name="description" id="editMaintDesc" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold"><?= __('maintenance.field_target_service') ?></label>
                    <select name="monitor_id" id="editMaintMonitorId" class="form-select">
                        <option value=""><?= __('maintenance.target_all_services') ?></option>
                        <?php foreach ($monitorsList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-body-secondary"><?= __('maintenance.target_service_help') ?></small>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('maintenance.field_start_window') ?></label>
                        <input type="datetime-local" name="start_time" id="editMaintStart" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('maintenance.field_end_window') ?></label>
                        <input type="datetime-local" name="end_time" id="editMaintEnd" class="form-control" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= __('common.status') ?></label>
                        <select name="status" id="editMaintStatus" class="form-select">
                            <option value="scheduled"><?= __('maintenance.scheduled') ?></option>
                            <option value="in_progress"><?= __('maintenance.in_progress') ?></option>
                            <option value="completed" class="text-success fw-bold"><?= __('maintenance.status_completed_archive') ?></option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> <?= __('maintenance.field_timezone') ?></label>
                        <select name="timezone" id="editMaintTimezone" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>"><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.cancel') ?></button>
                <button type="submit" class="btn btn-primary fw-bold"><?= __('maintenance.btn_save_changes') ?></button>
            </div>
        </form>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. FullCalendar Initialization
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: '/admin/maintenance/events',
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                const props = event.extendedProps;
                openEditMaintenanceModal(
                    event.id,
                    event.title,
                    props.description || '',
                    props.start_raw,
                    props.end_raw,
                    props.status,
                    props.monitor_id,
                    props.timezone
                );
            }
        });
        calendar.render();
    }

    // 2. DataTables Initialization for Upcoming Maintenances
    if ($('#upcomingMaintenanceTable').length > 0) {
        $('#upcomingMaintenanceTable').DataTable({
            pageLength: 10,
            order: [[2, 'asc']],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            columnDefs: [{ orderable: false, targets: 4 }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: <?= json_encode(__('maintenance.dt_search_upcoming')) ?>,
                lengthMenu: <?= json_encode(__('maintenance.dt_length')) ?>,
                info: <?= json_encode(__('maintenance.dt_info_upcoming')) ?>,
                infoEmpty: <?= json_encode(__('maintenance.dt_info_empty_upcoming')) ?>,
                zeroRecords: <?= json_encode(__('maintenance.dt_zero_records_upcoming')) ?>
            }
        });
    }

    // 3. DataTables Initialization for Past Maintenances
    if ($('#pastMaintenanceTable').length > 0) {
        $('#pastMaintenanceTable').DataTable({
            pageLength: 10,
            order: [[1, 'desc']],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            columnDefs: [{ orderable: false, targets: 3 }],
            language: {
                search: "_INPUT_",
                searchPlaceholder: <?= json_encode(__('maintenance.dt_search_past')) ?>,
                lengthMenu: <?= json_encode(__('maintenance.dt_length')) ?>,
                info: <?= json_encode(__('maintenance.dt_info_past')) ?>,
                infoEmpty: <?= json_encode(__('maintenance.dt_info_empty_past')) ?>,
                zeroRecords: <?= json_encode(__('maintenance.dt_zero_records_past')) ?>
            }
        });
    }
});

function openEditMaintenanceModal(id, title, desc, start, end, status, monitorId, timezone) {
    document.getElementById('editMaintId').value = id;
    document.getElementById('editMaintTitle').value = title;
    document.getElementById('editMaintDesc').value = desc;
    document.getElementById('editMaintStart').value = start;
    document.getElementById('editMaintEnd').value = end;
    document.getElementById('editMaintStatus').value = status;
    if (monitorId !== undefined) {
        document.getElementById('editMaintMonitorId').value = monitorId || '';
    }
    if (timezone) {
        document.getElementById('editMaintTimezone').value = timezone;
    }
    new bootstrap.Modal(document.getElementById('editMaintenanceModal')).show();
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
        prompt(<?= json_encode(__('maintenance.js_direct_link')) ?>, url);
    });
}
</script>

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
            <h2 class="fw-bold mb-0">Incidents & Outages</h2>
            <p class="text-body-secondary mb-0">Communicate downtimes, manage progress timelines, and resolve issues.</p>
        </div>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#newIncidentModal">
            <i class="bi bi-exclamation-octagon me-1"></i> Declare Incident
        </button>
    </div>

    <!-- Feedback Alerts -->
    <?php if (isset($_GET['created'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Incident declared and published!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Incident status update posted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['edited'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Incident details modified successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-trash-fill me-2"></i> Incident removed from database.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- 1. ACTIVE & ONGOING INCIDENTS SECTION -->
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-header bg-body py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="fw-bold mb-0 text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Active & Ongoing Incidents
            </h5>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                <?= count($activeIncidents) ?> Active
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($activeIncidents)): ?>
                <div class="text-center py-5 text-body-secondary">
                    <i class="bi bi-shield-fill-check text-success fs-1 d-block mb-2"></i>
                    <h5 class="fw-bold text-body">All Systems Operational</h5>
                    <p class="small mb-0">There are currently no active or unresolved incidents reported.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="activeIncidentsTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Impact</th>
                                <th>Incident Title</th>
                                <th>Current Status</th>
                                <th>Start Window</th>
                                <th class="text-end">Actions</th>
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
                                                title="Copy Direct Shareable Link">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <button class="btn btn-sm btn-primary me-1" 
                                                onclick="openUpdateModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['status'] ?>')"
                                                title="Post Progress Note or Mark as Resolved">
                                            <i class="bi bi-check2-circle me-1"></i> Update / Resolve
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['impact'] ?>', '<?= htmlspecialchars(addslashes($inc['ai_summary'] ?? '')) ?>', '<?= $inc['monitor_id'] ?? '' ?>', '<?= $inc['start_local'] ?? '' ?>', '<?= $inc['end_local'] ?? '' ?>', '<?= htmlspecialchars(addslashes($inc['timezone'] ?? $activeTimezone)) ?>')"
                                                title="Edit Incident Details">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/admin/incidents/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete this incident?');">
                                            <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
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
                <i class="bi bi-archive me-2"></i>Past & Resolved Incidents
            </h5>
            <span class="badge bg-body-secondary text-body-secondary border">
                <?= count($resolvedIncidents) ?> Archived
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($resolvedIncidents)): ?>
                <div class="text-center py-4 text-body-secondary small">No resolved incidents in history.</div>
            <?php else: ?>
                <div class="table-responsive p-3">
                    <table id="resolvedIncidentsTable" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Incident Title</th>
                                <th>Impact</th>
                                <th>Timeline Period</th>
                                <th class="text-end">Actions</th>
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
                                                title="Copy Direct Shareable Link">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-warning me-1" 
                                                onclick="openUpdateModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', 'monitoring')"
                                                title="Reopen Incident">
                                            <i class="bi bi-arrow-counterclockwise"></i> Reopen
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="openEditModal(<?= $inc['id'] ?>, '<?= htmlspecialchars(addslashes($inc['title'])) ?>', '<?= $inc['impact'] ?>', '<?= htmlspecialchars(addslashes($inc['ai_summary'] ?? '')) ?>', '<?= $inc['monitor_id'] ?? '' ?>', '<?= $inc['start_local'] ?? '' ?>', '<?= $inc['end_local'] ?? '' ?>', '<?= htmlspecialchars(addslashes($inc['timezone'] ?? $activeTimezone)) ?>')"
                                                title="Edit Incident Details">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="/admin/incidents/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this archived incident?');">
                                            <input type="hidden" name="incident_id" value="<?= $inc['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
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
                <h5 class="modal-title fw-bold" id="updateModalTitle">Post Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Status</label>
                    <select name="status" id="updateModalStatusSelect" class="form-select" required>
                        <option value="investigating">Investigating (Initial detection)</option>
                        <option value="identified">Identified (Cause identified)</option>
                        <option value="monitoring">Monitoring (Fix implemented, monitoring metrics)</option>
                        <option value="resolved" class="fw-bold text-success">Resolved (Closed & archived to past incidents)</option>
                    </select>
                    <small class="text-body-secondary">Setting status to <strong>Resolved</strong> will archive it under past incidents.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Timeline Note / Message</label>
                    <textarea name="message" class="form-control" rows="3" placeholder="e.g. The root cause was fixed and all services have recovered..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Publish Timeline Update</button>
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
                <h5 class="modal-title fw-bold">Edit Incident Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Incident Title</label>
                    <input type="text" name="title" id="editModalTitleInput" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Impact Severity</label>
                        <select name="impact" id="editModalImpactSelect" class="form-select">
                            <option value="minor">Minor Performance Degradation</option>
                            <option value="major">Major Service Disruption</option>
                            <option value="critical">Critical Outage</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Affected Service</label>
                        <select name="monitor_id" id="editModalMonitorSelect" class="form-select">
                            <option value="">All Services (Global Incident)</option>
                            <?php foreach ($monitorsList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="datetime-local" name="start_time" id="editModalStartTimeInput" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Time (Optional)</label>
                        <input type="datetime-local" name="end_time" id="editModalEndTimeInput" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> Timezone</label>
                        <select name="timezone" id="editModalTimezoneSelect" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>"><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">AI Public Summary</label>
                    <textarea name="ai_summary" id="editModalAiSummaryInput" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Declare New Incident (with AI Draft) -->
<div class="modal fade" id="newIncidentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="/admin/incidents/store" method="POST" class="modal-content shadow">
            <div class="modal-header">
                <h5 class="modal-title text-danger fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Declare New Incident</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Incident Title</label>
                    <input type="text" name="title" id="incidentTitle" class="form-control" placeholder="e.g. Core API Cluster Latency Spikes" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Impact Level</label>
                        <select name="impact" class="form-select">
                            <option value="minor">Minor Performance Degradation</option>
                            <option value="major">Major Service Disruption</option>
                            <option value="critical">Critical Infrastructure Outage</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Initial Status</label>
                        <select name="status" class="form-select">
                            <option value="investigating">Investigating</option>
                            <option value="identified">Identified</option>
                            <option value="monitoring">Monitoring</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Affected Service (Optional)</label>
                    <select name="monitor_id" class="form-select">
                        <option value="">All Services (Global Incident)</option>
                        <?php foreach ($monitorsList as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-body-secondary">If selected, this incident will color the 90-day bar of that specific service.</small>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="datetime-local" name="start_time" class="form-control" value="<?= \App\Services\DateService::toLocal(gmdate('Y-m-d H:i:s'), $activeTimezone) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">End Time (Optional)</label>
                        <input type="datetime-local" name="end_time" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold"><i class="bi bi-clock me-1"></i> Timezone</label>
                        <select name="timezone" class="form-select">
                            <?php foreach ($timezonesList as $tz): ?>
                                <option value="<?= $tz ?>" <?= $tz === $activeTimezone ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Technical Error / Raw Logs (for AI analysis)</label>
                    <textarea id="rawErrorDetails" class="form-control" rows="2" placeholder="Paste probe failure log or stack trace here..."></textarea>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label fw-semibold mb-0">Public AI-Assisted Summary</label>
                        <button type="button" class="btn-sm btn-outline-primary" id="btnGenerateAi" onclick="fetchAiSummary()">
                            <i class="bi bi-robot me-1"></i> Draft with AI
                        </button>
                    </div>
                    <textarea name="ai_summary" id="aiSummaryField" class="form-control" rows="2" placeholder="AI-generated public summary will appear here."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Initial Timeline Note</label>
                    <textarea name="message" class="form-control" rows="2" placeholder="Our engineering team is actively investigating the issue..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger fw-bold">Publish Incident</button>
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
                searchPlaceholder: "Search active incidents...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ incidents",
                infoEmpty: "No incidents available",
                zeroRecords: "No matching incidents found"
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
                searchPlaceholder: "Search resolved incidents...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ archived incidents",
                infoEmpty: "No archived incidents available",
                zeroRecords: "No matching archived incidents found"
            }
        });
    }
});

function openUpdateModal(id, title, currentStatus) {
    document.getElementById('updateModalIncidentId').value = id;
    document.getElementById('updateModalTitle').textContent = 'Update: ' + title;
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
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Generating...';

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
            alert('Failed to generate AI report.');
        }
    } catch (e) {
        console.error(e);
        alert('AI communication error.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-robot me-1"></i> Draft with AI';
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
        prompt('Direct link:', url);
    });
}
</script>

<!-- path: app/Views/public/index.php -->
<?php
$uptimeHistory     = $uptimeHistory ?? [];
$allIncidents      = $allIncidents ?? [];
$allMaintenances   = $allMaintenances ?? [];
$singleIncident    = $singleIncident ?? null;
$singleMaintenance = $singleMaintenance ?? null;
?>
<!-- Defensive Bootstrap 5.3 & Icons include to guarantee styling across environments -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<!-- DataTables CSS for Modal -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

<style>
    /* 90-Day Uptime Graph Container */
    .uptime-graph {
        display: flex;
        gap: 2px;
        align-items: stretch;
        height: 48px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        overflow: visible;
        touch-action: pan-y;
    }

    /* Mobile: 3 rows of 30 days with enlarged touch targets */
    @media (max-width: 576px) {
        .uptime-graph {
            flex-wrap: wrap;
            height: auto;
            gap: 3px 2px;
            padding: 4px 0;
        }

        .uptime-day-col {
            flex: 0 0 calc((100% - 58px) / 30) !important;
            height: 32px !important;
            padding: 2px 0 !important;
        }

        .uptime-arrow-top {
            top: -4px !important;
            width: 7px !important;
            height: 5px !important;
        }

        .uptime-arrow-bottom {
            bottom: -4px !important;
            width: 7px !important;
            height: 5px !important;
        }
    }

    .uptime-day-col {
        flex: 1 1 0;
        min-width: 0;
        height: 100%;
        position: relative;
        padding: 9px 0;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        overflow: visible;
    }

    .uptime-bar {
        width: 100%;
        height: 100%;
        border-radius: 2px;
        transition: transform 0.12s ease, filter 0.12s ease;
    }

    .uptime-day-col:hover .uptime-bar,
    .uptime-day-col:active .uptime-bar {
        filter: brightness(1.2);
        transform: scaleY(1.15);
        z-index: 5;
    }

    .uptime-arrow-top {
        position: absolute;
        top: 0px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 7px;
        pointer-events: none;
        z-index: 3;
    }

    .uptime-arrow-bottom {
        position: absolute;
        bottom: 0px;
        left: 50%;
        transform: translateX(-50%);
        width: 8px;
        height: 7px;
        pointer-events: none;
        z-index: 3;
    }

    /* Subservice problem warning indicator */
    .uptime-subservice-dot {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background-color: #f59e0b;
        border: 1px solid rgba(0, 0, 0, 0.5);
        box-shadow: 0 0 3px rgba(245, 158, 11, 0.9);
        pointer-events: none;
        z-index: 4;
    }

    /* Interactive modal stat cards */
    .modal-stat-card {
        transition: all 0.15s ease-in-out;
        cursor: pointer;
        user-select: none;
    }
    .modal-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
        border-color: #0d6efd !important;
    }
    .modal-stat-card:active {
        transform: translateY(0);
    }

    /* Monitor Individual Card Polish */
    .monitor-card {
        border-radius: 10px;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .monitor-card:hover {
        border-color: var(--bs-border-color-translucent);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06) !important;
    }

    /* Dark mode table compatibility */
    #dayModalLogsTable th {
        background-color: var(--bs-tertiary-bg);
        color: var(--bs-body-color);
        border-bottom: 2px solid var(--bs-border-color);
    }
    #dayModalLogsTable td {
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        border-color: var(--bs-border-color-translucent);
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        color: var(--bs-body-color) !important;
    }
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        color: var(--bs-secondary-color) !important;
    }
    .dataTables_wrapper .dataTables_filter input,
    .dataTables_wrapper .dataTables_length select {
        background-color: var(--bs-body-bg);
        color: var(--bs-body-color);
        border: 1px solid var(--bs-border-color);
        border-radius: 4px;
        padding: 4px 8px;
    }
</style>

<div class="container my-5 px-3 px-sm-4" style="max-width: 900px;">
    <!-- Feedback Alerts -->
    <?php if (isset($_GET['sub_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['sub_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sub_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_GET['sub_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['unsub_sent'])): ?>
        <div class="alert alert-info alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-envelope-check-fill me-2"></i> We have sent a secure confirmation link to your email. Click it within 60 minutes to finalize unsubscription.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['unsub_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> You have been successfully unsubscribed from all alert notifications.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Direct Share Link Card: Single Incident -->
    <?php if (!empty($singleIncident)): ?>
        <div class="card border-danger shadow-sm mb-5">
            <div class="card-header bg-danger text-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                    <strong class="fs-6"><?= htmlspecialchars($singleIncident['title']) ?></strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-danger text-uppercase"><?= htmlspecialchars($singleIncident['status']) ?></span>
                    <a href="/" class="btn btn-sm btn-outline-light py-0 px-2" title="Close single view">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-3 mb-3 text-body-secondary small">
                    <div>
                        <i class="bi bi-hdd-network text-primary me-1"></i>
                        <strong>Service:</strong> <?= htmlspecialchars($singleIncident['monitor_name'] ?? 'All Services (Global)') ?>
                    </div>
                    <div>
                        <i class="bi bi-shield-exclamation text-danger me-1"></i>
                        <strong>Impact:</strong> <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($singleIncident['impact']) ?></span>
                    </div>
                    <div>
                        <i class="bi bi-clock me-1"></i>
                        <strong>Timeline:</strong> <?= format_date($singleIncident['start_time'] ?? $singleIncident['created_at'], 'M d, Y H:i') ?>
                        <?php if (!empty($singleIncident['end_time'])): ?>
                            &mdash; <?= format_date($singleIncident['end_time'], 'M d, Y H:i T') ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($singleIncident['ai_summary'])): ?>
                    <div class="alert alert-body border mb-4">
                        <small class="text-body-secondary d-block fw-bold mb-1"><i class="bi bi-robot me-1 text-primary"></i> AI Public Summary</small>
                        <?= nl2br(htmlspecialchars($singleIncident['ai_summary'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($singleIncident['updates'])): ?>
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1 text-secondary"></i> Chronological Updates</h6>
                    <div class="timeline ps-3 border-start">
                        <?php foreach ($singleIncident['updates'] as $update): ?>
                            <div class="mb-3 position-relative">
                                <span class="badge bg-secondary"><?= format_date($update['created_at'], 'M d, H:i') ?></span>
                                <strong class="ms-2 text-capitalize text-body"><?= htmlspecialchars($update['status']) ?>:</strong>
                                <p class="mb-0 text-body-secondary mt-1"><?= nl2br(htmlspecialchars($update['message'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="mt-3 pt-3 border-top text-end">
                    <a href="/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> View Full Status Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Direct Share Link Card: Single Maintenance -->
    <?php if (!empty($singleMaintenance)): ?>
        <div class="card border-info shadow-sm mb-5">
            <div class="card-header bg-info text-dark py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tools fs-5"></i>
                    <strong class="fs-6"><?= htmlspecialchars($singleMaintenance['title']) ?></strong>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark text-white text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $singleMaintenance['status'])) ?></span>
                    <a href="/" class="btn btn-sm btn-outline-dark py-0 px-2" title="Close single view">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-3 mb-3 text-body-secondary small">
                    <div>
                        <i class="bi bi-hdd-network text-primary me-1"></i>
                        <strong>Service:</strong> <?= htmlspecialchars($singleMaintenance['monitor_name'] ?? 'All Services (Global)') ?>
                    </div>
                    <div>
                        <i class="bi bi-calendar-event text-info me-1"></i>
                        <strong>Window:</strong> <?= format_date($singleMaintenance['start_time'], 'M d, Y H:i') ?> &mdash; <?= format_date($singleMaintenance['end_time'], 'M d, Y H:i T') ?>
                    </div>
                </div>

                <?php if (!empty($singleMaintenance['description'])): ?>
                    <div class="bg-body-secondary p-3 rounded border mb-3 text-body">
                        <?= nl2br(htmlspecialchars($singleMaintenance['description'])) ?>
                    </div>
                <?php endif; ?>

                <div class="mt-3 pt-3 border-top text-end">
                    <a href="/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> View Full Status Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- 1. GLOBAL CORE STATUS BANNER -->
    <?php 
        $badgeClass = match ($primaryStatus) {
            'operational'  => 'bg-success',
            'degraded'     => 'bg-warning text-dark',
            'major_outage' => 'bg-danger',
            default        => 'bg-secondary'
        };
        $statusText = match ($primaryStatus) {
            'operational'  => __('status.all_core_operational'),
            'degraded'     => __('status.core_degraded'),
            'major_outage' => __('status.core_outage'),
            default        => __('status.operational')
        };
    ?>
    <div class="p-4 rounded-3 text-white <?= $badgeClass ?> shadow-sm mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0 fw-bold"><i class="bi bi-shield-fill-check me-2"></i> <?= $statusText ?></h4>
        <button class="btn btn-light btn-sm fw-bold shadow-sm d-flex align-items-center gap-1" 
                type="button" 
                onclick="openSubscriptionModal(null, '<?= htmlspecialchars(addslashes(__('public.all_core_services'))) ?>')">
            <i class="bi bi-bell-fill text-primary"></i> 
            <span><?= __('public.subscribe_unsubscribe') ?></span>
        </button>
    </div>

    <!-- Active Maintenances -->
    <?php if (!empty($maintenances)): ?>
        <div class="card border-info mb-4 shadow-sm">
            <div class="card-header bg-info text-dark fw-bold d-flex justify-content-between align-items-center flex-wrap gap-1">
                <span><i class="bi bi-tools me-2"></i> <?= __('maintenance.title') ?></span>
                <small class="badge bg-dark bg-opacity-25 text-white"><i class="bi bi-clock me-1"></i> <?= \App\Services\DateService::getActiveTimezone() ?></small>
            </div>
            <div class="card-body">
                <?php foreach ($maintenances as $maint): ?>
                    <h5 class="card-title fw-bold"><?= htmlspecialchars($maint['title']) ?></h5>
                    <p class="card-text text-muted"><?= nl2br(htmlspecialchars($maint['description'])) ?></p>
                    <small class="badge bg-secondary">
                        <time datetime="<?= htmlspecialchars($maint['start_time']) ?>">
                            <?= format_date($maint['start_time'], 'M d, H:i') ?>
                        </time>
                        &mdash;
                        <time datetime="<?= htmlspecialchars($maint['end_time']) ?>">
                            <?= format_date($maint['end_time'], 'M d, H:i T') ?>
                        </time>
                    </small>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Active Incidents -->
    <?php if (!empty($incidents)): ?>
        <h5 class="fw-bold text-danger mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i> Active Incidents</h5>
        <?php foreach ($incidents as $incident): ?>
            <div class="card border-danger mb-3 shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= htmlspecialchars($incident['title']) ?></strong>
                        <small class="badge bg-light bg-opacity-25 text-white ms-2">
                            <?= format_date($incident['start_time'] ?? $incident['created_at'], 'M d, H:i') ?>
                        </small>
                    </div>
                    <span class="badge bg-light text-danger text-uppercase"><?= $incident['status'] ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($incident['ai_summary'])): ?>
                        <div class="alert alert-body border mb-3">
                            <small class="text-muted d-block fw-bold mb-1"><i class="bi bi-robot me-1 text-primary"></i> AI Incident Summary</small>
                            <?= nl2br(htmlspecialchars($incident['ai_summary'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="timeline ps-3 border-start">
                        <?php foreach ($incident['updates'] as $update): ?>
                            <div class="mb-3 position-relative">
                                <span class="badge bg-secondary">
                                    <time datetime="<?= htmlspecialchars($update['created_at']) ?>">
                                        <?= format_date($update['created_at'], 'M d, H:i') ?>
                                    </time>
                                </span>
                                <strong class="ms-2 text-capitalize"><?= $update['status'] ?>:</strong>
                                <p class="mb-0 text-muted mt-1"><?= nl2br(htmlspecialchars($update['message'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Reusable Monitor Row Function -->
    <?php
    $renderMonitorRow = function(array $monitor) use ($uptimeHistory, $allIncidents, $allMaintenances) {
        $hasChildren = !empty($monitor['children']);
        $uptimePct   = (float)($monitor['uptime_percentage'] ?? 100.00);
        $isDown      = ($monitor['current_status'] === 'down');
        $isDegraded  = ($monitor['current_status'] === 'degraded');
        $mId         = (int)$monitor['id'];

        // Strict isolation: Parent only uses its OWN probe telemetry
        $monitorHistory = $uptimeHistory[$mId] ?? [];

        // Precalculate child probe issues per day for contextual alerts
        $childIssuesByDate = [];
        if ($hasChildren) {
            foreach ($monitor['children'] as $child) {
                $cId = (int)$child['id'];
                if (!empty($uptimeHistory[$cId])) {
                    foreach ($uptimeHistory[$cId] as $dateKey => $cStats) {
                        $cDown  = (int)($cStats['down'] ?? 0);
                        $cBlack = (int)($cStats['blackout'] ?? 0);
                        if ($cDown > 0 || $cBlack > 0) {
                            $childIssuesByDate[$dateKey][] = [
                                'name'     => $child['name'],
                                'down'     => $cDown,
                                'blackout' => $cBlack
                            ];
                        }
                    }
                }
            }
        }

        ob_start();
        ?>
        <div class="card shadow-sm border mb-3 monitor-card">
            <!-- Card Header: Title, Subscription, Sub-services Trigger, and Current Status Badge -->
            <div class="card-header bg-body py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold fs-6 text-body"><?= htmlspecialchars($monitor['name']) ?></span>

                    <!-- Single Probe Subscribe Button -->
                    <button class="btn btn-sm btn-outline-secondary py-0 px-2 rounded-pill shadow-none d-flex align-items-center gap-1" 
                            style="font-size: 11px;" 
                            type="button" 
                            onclick="openSubscriptionModal(<?= $monitor['id'] ?>, '<?= htmlspecialchars(addslashes($monitor['name'])) ?>')">
                        <i class="bi bi-bell"></i>
                        <span><?= __('public.subscribe_unsubscribe') ?></span>
                    </button>
                    
                    <!-- Sub-services Accordion Toggle Badge -->
                    <?php if ($hasChildren): ?>
                        <button class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill shadow-none" 
                                style="font-size: 11px;" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#subservices-<?= $monitor['id'] ?>">
                            <i class="bi bi-diagram-3 me-1"></i> <?= count($monitor['children']) ?> <?= __('public.sub_services') ?> <i class="bi bi-chevron-down ms-1"></i>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($monitor['current_status'] === 'operational'): ?>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                            <i class="bi bi-check-circle-fill me-1"></i> <?= __('status.operational') ?>
                        </span>
                    <?php elseif ($monitor['current_status'] === 'degraded'): ?>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= __('status.degraded') ?>
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                            <i class="bi bi-x-circle-fill me-1"></i> <?= __('status.major_outage') ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card Body: 90-Day Interactive Bars & Details -->
            <div class="card-body p-3 p-sm-4">
                <!-- 90-Day Interactive Uptime Graph -->
                <div class="uptime-graph" role="group" aria-label="90 Days Uptime History">
                    <?php
                        for ($day = 89; $day >= 0; $day--):
                            $dayTime       = strtotime("-{$day} days");
                            $dayDate       = date('Y-m-d', $dayTime);
                            $formattedDate = date('M d, Y', $dayTime);

                            $dayData = $monitorHistory[$dayDate] ?? null;

                            $totalChecks = (int)($dayData['total'] ?? 0);
                            $downChecks  = (int)($dayData['down'] ?? 0);
                            $blackChecks = (int)($dayData['blackout'] ?? 0);
                            $upChecks    = (int)($dayData['up'] ?? max(0, $totalChecks - $downChecks - $blackChecks));

                            $blackPct = ($totalChecks > 0) ? round(($blackChecks / $totalChecks) * 100, 2) : 0;
                            $downPct  = ($totalChecks > 0) ? round(($downChecks / $totalChecks) * 100, 2) : 0;
                            $dailyUptimePct = ($totalChecks > 0) ? round(($upChecks / $totalChecks) * 100, 2) : 100.00;

                            // Parent Incidents: Global OR strictly assigned to this parent monitor
                            $dayIncidents = [];
                            foreach ($allIncidents as $inc) {
                                $incMonId = !empty($inc['monitor_id']) ? (int)$inc['monitor_id'] : null;
                                if ($incMonId === null || $incMonId === $mId) {
                                    $rawStart = $inc['start_time'] ?? $inc['created_at'];
                                    $rawEnd   = $inc['end_time'] ?? ($inc['status'] === 'resolved' ? $inc['updated_at'] : gmdate('Y-m-d H:i:s'));
                                    $incStart = format_date($rawStart, 'Y-m-d');
                                    $incEnd   = format_date($rawEnd, 'Y-m-d');

                                    if ($dayDate >= $incStart && $dayDate <= $incEnd) {
                                        $dayIncidents[] = $inc;
                                    }
                                }
                            }

                            // Parent Maintenances: Global OR strictly assigned to this parent monitor
                            $dayMaintenances = [];
                            foreach ($allMaintenances as $maint) {
                                $maintMonId = !empty($maint['monitor_id']) ? (int)$maint['monitor_id'] : null;
                                if ($maintMonId === null || $maintMonId === $mId) {
                                    $mStart = format_date($maint['start_time'], 'Y-m-d');
                                    $mEnd   = format_date($maint['end_time'], 'Y-m-d');
                                    if ($dayDate >= $mStart && $dayDate <= $mEnd) {
                                        $dayMaintenances[] = $maint;
                                    }
                                }
                            }

                            $hasMaintenance = !empty($dayMaintenances);
                            $hasIncident    = !empty($dayIncidents);
                            $hasChildIssues = !empty($childIssuesByDate[$dayDate]);

                            // Check if any child probe has active issues today (day === 0)
                            if ($day === 0 && $hasChildren) {
                                foreach ($monitor['children'] as $child) {
                                    if ($child['current_status'] !== 'operational') {
                                        $hasChildIssues = true;
                                        break;
                                    }
                                }
                            }

                            $barClass = 'uptime-bar';
                            $barStyle = '';

                            // Strict telemetry display: parent bar reflects only its own checks
                            if ($blackChecks > 0 && $downChecks > 0) {
                                $vBlack = max(18, min(45, (int)$blackPct));
                                $vRed   = max(18, min(45, (int)$downPct));
                                $gStart = $vBlack;
                                $gEnd   = 100 - $vRed;
                                $barStyle = "style=\"background: linear-gradient(to bottom, #0f172a 0%, #0f172a {$gStart}%, #10b981 {$gStart}%, #10b981 {$gEnd}%, #ef4444 {$gEnd}%, #ef4444 100%);\"";
                                $statusDesc = "<span style='color: #0f172a;'>⬛</span> Blackout: {$blackPct}% &bull; <span style='color: #ef4444;'>●</span> Downtime: {$downPct}% ({$dailyUptimePct}% " . __('public.uptime') . ")";
                            } elseif ($blackChecks > 0) {
                                if ($blackPct >= 95.0) {
                                    $barStyle = 'style="background-color: #0f172a;"';
                                } else {
                                    $vBlack = max(18, min(85, (int)$blackPct));
                                    $barStyle = "style=\"background: linear-gradient(to bottom, #0f172a 0%, #0f172a {$vBlack}%, #10b981 {$vBlack}%, #10b981 100%);\"";
                                }
                                $statusDesc = "<span style='color: #0f172a;'>⬛</span> " . __('public.system_blackout') . ": {$blackPct}% ({$dailyUptimePct}% " . __('public.uptime') . ")";
                            } elseif ($downChecks > 0) {
                                if ($downPct >= 95.0) {
                                    $barStyle = 'style="background-color: #ef4444;"';
                                } else {
                                    $vRed = max(18, min(85, (int)$downPct));
                                    $barStyle = "style=\"background: linear-gradient(to top, #ef4444 0%, #ef4444 {$vRed}%, #10b981 {$vRed}%, #10b981 100%);\"";
                                }
                                $statusDesc = "<span style='color: #ef4444;'>●</span> Downtime: {$downPct}% ({$dailyUptimePct}% " . __('public.uptime') . ")";
                            } elseif ($day === 0 && $isDown) {
                                $barStyle = 'style="background-color: #ef4444;"';
                                $statusDesc = "<span style='color: #ef4444;'>●</span> " . __('status.major_outage');
                            } elseif ($day === 0 && $isDegraded && !$hasChildren) {
                                // Only standalone monitors without sub-services color the whole bar yellow
                                $barStyle = 'style="background-color: #f59e0b;"';
                                $statusDesc = "<span style='color: #f59e0b;'>●</span> " . __('status.degraded');
                            } elseif ($totalChecks > 0 || $day === 0) {
                                $barStyle = 'style="background-color: #10b981;"';
                                $statusDesc = "<span style='color: #10b981;'>●</span> 100% " . __('status.operational');
                            } else {
                                $barStyle = 'style="background-color: var(--bs-secondary-bg, #e9ecef);"';
                                $statusDesc = "<span style='color: #94a3b8;'>●</span> " . __('public.no_data_recorded');
                            }

                            $label = "<strong>{$formattedDate}</strong><br>{$statusDesc}";
                            if ($hasMaintenance) {
                                $label .= "<br><span style='color: #0ea5e9;'>▼</span> " . count($dayMaintenances) . " " . __('maintenance.title');
                            }
                            if ($hasIncident) {
                                $label .= "<br><span style='color: #ea580c;'>▲</span> " . count($dayIncidents) . " " . __('public.reported_incident');
                            }
                            if ($hasChildIssues) {
                                $childNames = implode(', ', array_map(fn($c) => htmlspecialchars($c['name']), $childIssuesByDate[$dayDate]));
                                $label .= "<br><span style='color: #f59e0b;'>⚠️</span> Secondary telemetry had issues: {$childNames}";
                            }

                            $modalPayload = [
                                'date'          => $formattedDate,
                                'date_raw'      => $dayDate,
                                'monitor_id'    => $monitor['id'],
                                'monitor'       => $monitor['name'],
                                'checks'        => $totalChecks,
                                'up_checks'     => $upChecks,
                                'uptime_pct'    => ($totalChecks > 0) ? $dailyUptimePct : null,
                                'blackouts'     => $blackChecks,
                                'blackout_pct'  => $blackPct,
                                'outages'       => $downChecks,
                                'outage_pct'    => $downPct,
                                'child_issues'  => $childIssuesByDate[$dayDate] ?? [],
                                'incidents'     => array_map(fn($inc) => [
                                    'title'       => $inc['title'],
                                    'impact'      => strtoupper($inc['impact']),
                                    'status'      => strtoupper($inc['status']),
                                    'created_at'  => format_date($inc['start_time'] ?? $inc['created_at'], 'M d, Y H:i'),
                                    'updated_at'  => format_date($inc['end_time'] ?? $inc['updated_at'], 'M d, Y H:i'),
                                    'updates'     => array_map(fn($u) => [
                                        'status'  => strtoupper($u['status']),
                                        'message' => $u['message'],
                                        'time'    => format_date($u['created_at'], 'M d, H:i')
                                    ], $inc['updates'] ?? [])
                                ], $dayIncidents),
                                'maintenances'  => array_map(fn($m) => [
                                    'title'       => $m['title'],
                                    'description' => $m['description'] ?? '',
                                    'status'      => strtoupper(str_replace('_', ' ', $m['status'])),
                                    'start_time'  => format_date($m['start_time'], 'M d, Y H:i'),
                                    'end_time'    => format_date($m['end_time'], 'M d, H:i T')
                                ], $dayMaintenances)
                            ];

                            $colClasses = 'uptime-day-col';
                            if ($hasMaintenance) $colClasses .= ' has-maint';
                            if ($hasIncident)    $colClasses .= ' has-inc';
                    ?>
                        <div class="<?= $colClasses ?>" 
                             data-bs-toggle="tooltip" 
                             data-bs-placement="top" 
                             data-bs-html="true" 
                             title="<?= htmlspecialchars($label, ENT_QUOTES) ?>"
                             data-day-payload='<?= htmlspecialchars(json_encode($modalPayload, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'
                             onclick="openDayDetailModalFromElement(this)">

                            <?php if ($hasMaintenance): ?>
                                <svg class="uptime-arrow-top" viewBox="0 0 10 7">
                                    <polygon points="0,0 10,0 5,7" fill="#0ea5e9" />
                                </svg>
                            <?php endif; ?>

                            <div class="<?= $barClass ?>" <?= $barStyle ?>></div>

                            <?php if ($hasChildIssues): ?>
                                <span class="uptime-subservice-dot" title="Secondary telemetry had issues"></span>
                            <?php endif; ?>

                            <?php if ($hasIncident): ?>
                                <svg class="uptime-arrow-bottom" viewBox="0 0 10 7">
                                    <polygon points="5,0 10,7 0,7" fill="#ea580c" />
                                </svg>
                            <?php endif; ?>

                        </div>
                    <?php endfor; ?>
                </div>

                <div class="d-flex justify-content-between text-muted small mt-2">
                    <span><?= __('public.days_ago', ['count' => 90]) ?></span>
                    <span class="fw-semibold text-body"><?= number_format($uptimePct, 2) ?>% <?= __('public.uptime') ?></span>
                    <span><?= __('public.today') ?></span>
                </div>

                <!-- Sub-services Drawer -->
                <?php if ($hasChildren): ?>
                    <div class="collapse mt-3 pt-3 border-top" id="subservices-<?= $monitor['id'] ?>">
                        <div class="ps-2 ps-sm-3 border-start border-3 border-primary-subtle d-flex flex-column gap-3">
                            <?php foreach ($monitor['children'] as $child): ?>
                                <?php
                                    $childDown     = ($child['current_status'] === 'down');
                                    $childDegraded = ($child['current_status'] === 'degraded');
                                    $childUptime   = (float)($child['uptime_percentage'] ?? 100.00);
                                    $cId           = (int)$child['id'];
                                    $childHistory  = $uptimeHistory[$cId] ?? [];
                                ?>
                                <div class="bg-body-secondary p-3 rounded-3 border">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                        <span class="fw-semibold text-body small">
                                            <i class="bi bi-arrow-return-right me-1 text-muted"></i>
                                            <?= htmlspecialchars($child['name']) ?>
                                        </span>
                                        <span class="badge bg-<?= $child['current_status'] === 'operational' ? 'success' : ($child['current_status'] === 'degraded' ? 'warning text-dark' : 'danger') ?> py-1 px-2" style="font-size: 10px;">
                                            <?= strtoupper($child['current_status']) ?>
                                        </span>
                                    </div>

                                    <!-- Sub-service 90-Day Mini Bar (Strictly its own metrics and child-specific arrows) -->
                                    <div class="uptime-graph" style="height: 28px; overflow: visible; margin: 4px 0;" role="group">
                                        <?php
                                            for ($cDay = 89; $cDay >= 0; $cDay--):
                                                $cDayTime  = strtotime("-{$cDay} days");
                                                $cDayDate  = date('Y-m-d', $cDayTime);
                                                $cDate     = date('M d, Y', $cDayTime);

                                                $cData = $childHistory[$cDayDate] ?? null;

                                                $cTotal = (int)($cData['total'] ?? 0);
                                                $cDown  = (int)($cData['down'] ?? 0);
                                                $cBlack = (int)($cData['blackout'] ?? 0);
                                                $cUp    = max(0, $cTotal - $cDown - $cBlack);

                                                $cBlackPct = ($cTotal > 0) ? round(($cBlack / $cTotal) * 100, 2) : 0;
                                                $cDownPct  = ($cTotal > 0) ? round(($cDown / $cTotal) * 100, 2) : 0;
                                                $cUpPct    = ($cTotal > 0) ? round(($cUp / $cTotal) * 100, 2) : 100.00;

                                                // Sub-service specific Incidents: STRICTLY assigned to this child probe
                                                $cDayIncidents = [];
                                                foreach ($allIncidents as $inc) {
                                                    $incMonId = !empty($inc['monitor_id']) ? (int)$inc['monitor_id'] : null;
                                                    if ($incMonId === $cId) {
                                                        $rawStart = $inc['start_time'] ?? $inc['created_at'];
                                                        $rawEnd   = $inc['end_time'] ?? ($inc['status'] === 'resolved' ? $inc['updated_at'] : gmdate('Y-m-d H:i:s'));
                                                        $incStart = format_date($rawStart, 'Y-m-d');
                                                        $incEnd   = format_date($rawEnd, 'Y-m-d');

                                                        if ($cDayDate >= $incStart && $cDayDate <= $incEnd) {
                                                            $cDayIncidents[] = $inc;
                                                        }
                                                    }
                                                }

                                                // Sub-service specific Maintenances: STRICTLY assigned to this child probe
                                                $cDayMaintenances = [];
                                                foreach ($allMaintenances as $maint) {
                                                    $maintMonId = !empty($maint['monitor_id']) ? (int)$maint['monitor_id'] : null;
                                                    if ($maintMonId === $cId) {
                                                        $mStart = format_date($maint['start_time'], 'Y-m-d');
                                                        $mEnd   = format_date($maint['end_time'], 'Y-m-d');
                                                        if ($cDayDate >= $mStart && $cDayDate <= $mEnd) {
                                                            $cDayMaintenances[] = $maint;
                                                        }
                                                    }
                                                }

                                                $cHasMaint = !empty($cDayMaintenances);
                                                $cHasInc   = !empty($cDayIncidents);

                                                $cStyle = '';
                                                $cLabel = "<strong>{$cDate}</strong><br>";

                                                if ($cBlack > 0 && $cDown > 0) {
                                                    $vBlack = max(18, min(45, (int)$cBlackPct));
                                                    $vRed   = max(18, min(45, (int)$downPct));
                                                    $gStart = $vBlack;
                                                    $gEnd   = 100 - $vRed;
                                                    $cStyle = "style=\"background: linear-gradient(to bottom, #0f172a 0%, #0f172a {$gStart}%, #10b981 {$gStart}%, #10b981 {$gEnd}%, #ef4444 {$gEnd}%, #ef4444 100%);\"";
                                                    $cLabel .= "<span style='color: #0f172a;'>⬛</span> Blackout: {$cBlackPct}% &bull; <span style='color: #ef4444;'>●</span> Downtime: {$cDownPct}% ({$cUpPct}% " . __('public.uptime') . ")";
                                                } elseif ($cBlack > 0) {
                                                    if ($cBlackPct >= 95.0) {
                                                        $cStyle = 'style="background-color: #0f172a;"';
                                                    } else {
                                                        $vBlack = max(18, min(85, (int)$cBlackPct));
                                                        $cStyle = "style=\"background: linear-gradient(to bottom, #0f172a 0%, #0f172a {$vBlack}%, #10b981 {$vBlack}%, #10b981 100%);\"";
                                                    }
                                                    $cLabel .= "<span style='color: #0f172a;'>⬛</span> " . __('public.system_blackout') . ": {$cBlackPct}% ({$cUpPct}% " . __('public.uptime') . ")";
                                                } elseif ($cDown > 0) {
                                                    if ($cDownPct >= 95.0) {
                                                        $cStyle = 'style="background-color: #ef4444;"';
                                                    } else {
                                                        $vRed = max(18, min(85, (int)$cDownPct));
                                                        $cStyle = "style=\"background: linear-gradient(to top, #ef4444 0%, #ef4444 {$vRed}%, #10b981 {$vRed}%, #10b981 100%);\"";
                                                    }
                                                    $cLabel .= "<span style='color: #ef4444;'>●</span> Downtime: {$cDownPct}% ({$cUpPct}% " . __('public.uptime') . ")";
                                                } elseif ($cDay === 0 && ($childDown || $childDegraded)) {
                                                    if ($childDown) {
                                                        $cStyle = 'style="background-color: #ef4444;"';
                                                        $cLabel .= "<span style='color: #ef4444;'>●</span> " . __('status.major_outage');
                                                    } else {
                                                        $cStyle = 'style="background-color: #f59e0b;"';
                                                        $cLabel .= "<span style='color: #f59e0b;'>●</span> " . __('status.degraded');
                                                    }
                                                } elseif ($cTotal > 0) {
                                                    $cStyle = 'style="background-color: #10b981;"';
                                                    $cLabel .= "<span style='color: #10b981;'>●</span> 100% " . __('status.operational');
                                                } else {
                                                    $cStyle = 'style="background-color: var(--bs-secondary-bg, #e9ecef);"';
                                                    $cLabel .= "<span style='color: #94a3b8;'>●</span> " . __('public.no_data_recorded');
                                                }

                                                if ($cHasMaint) {
                                                    $cLabel .= "<br><span style='color: #0ea5e9;'>▼</span> " . count($cDayMaintenances) . " " . __('maintenance.title');
                                                }
                                                if ($cHasInc) {
                                                    $cLabel .= "<br><span style='color: #ea580c;'>▲</span> " . count($cDayIncidents) . " " . __('public.reported_incident');
                                                }

                                                $childPayload = [
                                                    'date'          => $cDate,
                                                    'date_raw'      => $cDayDate,
                                                    'monitor_id'    => $child['id'],
                                                    'monitor'       => $child['name'],
                                                    'checks'        => $cTotal,
                                                    'up_checks'     => $cUp,
                                                    'uptime_pct'    => ($cTotal > 0) ? $cUpPct : null,
                                                    'blackouts'     => $cBlack,
                                                    'blackout_pct'  => $cBlackPct,
                                                    'outages'       => $cDown,
                                                    'outage_pct'    => $cDownPct,
                                                    'child_issues'  => [],
                                                    'incidents'     => array_map(fn($inc) => [
                                                        'title'       => $inc['title'],
                                                        'impact'      => strtoupper($inc['impact']),
                                                        'status'      => strtoupper($inc['status']),
                                                        'created_at'  => format_date($inc['start_time'] ?? $inc['created_at'], 'M d, Y H:i'),
                                                        'updated_at'  => format_date($inc['end_time'] ?? $inc['updated_at'], 'M d, Y H:i'),
                                                        'updates'     => array_map(fn($u) => [
                                                            'status'  => strtoupper($u['status']),
                                                            'message' => $u['message'],
                                                            'time'    => format_date($u['created_at'], 'M d, H:i')
                                                        ], $inc['updates'] ?? [])
                                                    ], $cDayIncidents),
                                                    'maintenances'  => array_map(fn($m) => [
                                                        'title'       => $m['title'],
                                                        'description' => $m['description'] ?? '',
                                                        'status'      => strtoupper(str_replace('_', ' ', $m['status'])),
                                                        'start_time'  => format_date($m['start_time'], 'M d, Y H:i'),
                                                        'end_time'    => format_date($m['end_time'], 'M d, H:i T')
                                                    ], $cDayMaintenances)
                                                ];

                                                $cColClasses = 'uptime-day-col';
                                                if ($cHasMaint) $cColClasses .= ' has-maint';
                                                if ($cHasInc)   $cColClasses .= ' has-inc';
                                        ?>
                                            <div class="<?= $cColClasses ?>"
                                                 style="padding: 4px 0;"
                                                 data-bs-toggle="tooltip" 
                                                 data-bs-placement="top" 
                                                 data-bs-html="true" 
                                                 title="<?= htmlspecialchars($cLabel, ENT_QUOTES) ?>"
                                                 data-day-payload='<?= htmlspecialchars(json_encode($childPayload, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>'
                                                 onclick="openDayDetailModalFromElement(this)">

                                                <?php if ($cHasMaint): ?>
                                                    <svg class="uptime-arrow-top" viewBox="0 0 10 7" style="top: -2px; width: 8px; height: 6px;">
                                                        <polygon points="0,0 10,0 5,7" fill="#0ea5e9" />
                                                    </svg>
                                                <?php endif; ?>

                                                <div class="uptime-bar" <?= $cStyle ?>></div>

                                                <?php if ($cHasInc): ?>
                                                    <svg class="uptime-arrow-bottom" viewBox="0 0 10 7" style="bottom: -2px; width: 8px; height: 6px;">
                                                        <polygon points="5,0 10,7 0,7" fill="#ea580c" />
                                                    </svg>
                                                <?php endif; ?>

                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    };

    $primaryMonitors   = array_values(array_filter($monitors, fn($m) => ((int)($m['is_primary'] ?? 0) === 1)));
    $secondaryMonitors = array_values(array_filter($monitors, fn($m) => ((int)($m['is_primary'] ?? 0) === 0)));
    ?>

    <!-- 2. PRIMARY CORE INFRASTRUCTURE SECTION -->
    <?php if (!empty($primaryMonitors)): ?>
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-hdd-rack text-primary me-2"></i><?= __('public.core_infrastructure') ?></h5>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                    <?= __('public.primary_systems') ?>
                </span>
            </div>
            <div>
                <?php foreach ($primaryMonitors as $monitor): ?>
                    <?= $renderMonitorRow($monitor) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. SECONDARY & THIRD-PARTY CLOUD DEPENDENCIES SECTION -->
    <?php if (!empty($secondaryMonitors)): ?>
        <?php
            $secBannerColor = match ($secondaryStatus) {
                'operational'  => 'alert-success',
                'degraded'     => 'alert-warning text-dark border-warning',
                'major_outage' => 'alert-danger',
                default        => 'alert-secondary'
            };
            $secBannerIcon = match ($secondaryStatus) {
                'operational'  => 'bi-check-circle-fill text-success',
                'degraded'     => 'bi-exclamation-triangle-fill text-warning',
                'major_outage' => 'bi-x-circle-fill text-danger',
                default        => 'bi-info-circle-fill'
            };
            $secStatusMessage = match ($secondaryStatus) {
                'operational'  => __('status.secondary_normal'),
                'degraded'     => __('status.secondary_degraded'),
                'major_outage' => __('status.secondary_outage'),
                default        => __('public.external_dependencies')
            };
        ?>
        <div class="alert <?= $secBannerColor ?> shadow-sm mb-3 d-flex align-items-center gap-2 py-3 px-4 rounded-3">
            <i class="bi <?= $secBannerIcon ?> fs-4"></i>
            <div>
                <strong><?= __('public.third_party_status') ?>:</strong> <?= $secStatusMessage ?>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-check text-secondary me-2"></i><?= __('public.external_cloud') ?></h5>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                    <?= __('public.external_dependencies') ?>
                </span>
            </div>
            <div>
                <?php foreach ($secondaryMonitors as $monitor): ?>
                    <?= $renderMonitorRow($monitor) ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Fallback if no monitors exist -->
    <?php if (empty($primaryMonitors) && empty($secondaryMonitors)): ?>
        <div class="card shadow-sm border-0 p-5 text-center text-muted mb-4">
            <i class="bi bi-hdd-network fs-1 mb-2 text-secondary"></i>
            <h5><?= __('public.no_services') ?></h5>
            <p class="small mb-0"><?= __('public.no_services_desc') ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal 1: Daily History Inspector with Interactive Stats Breakdown -->
<div class="modal fade" id="dayDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow border">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title fw-bold text-body" id="dayModalDateTitle"><?= __('public.daily_report') ?></h5>
                    <small class="text-muted" id="dayModalMonitorName">Service Name</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Secondary Telemetry Problem Alert in Modal -->
                <div id="dayModalChildAlert" class="alert alert-warning border border-warning d-none py-2 px-3 mb-3 small d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning"></i>
                    <div>
                        <strong>Notice:</strong> One or more secondary probes under this service reported issues on this date.
                    </div>
                </div>

                <!-- 4 Interactive Stat Cards -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-3">
                        <div class="p-2 bg-body-secondary rounded border modal-stat-card" onclick="toggleUptimeDonutChart()" title="Click to view visual health distribution">
                            <div class="small text-muted d-flex align-items-center justify-content-center gap-1">
                                <span><?= __('public.daily_uptime') ?></span>
                                <i class="bi bi-pie-chart text-success small"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-success" id="dayModalUptimePct">100%</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-body-secondary rounded border modal-stat-card" onclick="toggleChecksTable('all')" title="Click to view all telemetry checks">
                            <div class="small text-muted d-flex align-items-center justify-content-center gap-1">
                                <span><?= __('public.checks_executed') ?></span>
                                <i class="bi bi-list-ul text-primary small"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-body" id="dayModalChecksCount">0</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-body-secondary rounded border modal-stat-card" onclick="toggleChecksTable('down')" title="Click to filter outages">
                            <div class="small text-muted d-flex align-items-center justify-content-center gap-1">
                                <span><?= __('public.downtime_hits') ?></span>
                                <i class="bi bi-exclamation-octagon text-danger small"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-danger" id="dayModalOutagesCount">0</h5>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="p-2 bg-body-secondary rounded border modal-stat-card" onclick="toggleChecksTable('blackout')" title="Click to view blackouts">
                            <div class="small text-muted d-flex align-items-center justify-content-center gap-1">
                                <span><?= __('public.system_blackouts') ?></span>
                                <i class="bi bi-power text-body small"></i>
                            </div>
                            <h5 class="fw-bold mb-0 text-body" id="dayModalBlackoutsCount">0</h5>
                        </div>
                    </div>
                </div>

                <!-- A. Collapsible SVG Donut Chart Section -->
                <div class="collapse mb-4" id="dayModalDonutContainer">
                    <div class="card border bg-body-secondary p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 small text-body"><i class="bi bi-pie-chart-fill text-success me-1"></i> Daily Health Distribution</h6>
                            <button type="button" class="btn-close btn-sm" onclick="bootstrap.Collapse.getInstance(document.getElementById('dayModalDonutContainer')).hide()"></button>
                        </div>
                        <div class="row align-items-center g-3">
                            <div class="col-sm-5 text-center">
                                <div class="position-relative d-inline-block" style="width: 140px; height: 140px;">
                                    <svg id="dayModalDonutSvg" viewBox="0 0 36 36" style="width: 100%; height: 100%; transform: rotate(-90deg); border-radius: 50%;">
                                    </svg>
                                    <div class="position-absolute top-50 start-50 translate-middle text-center" style="pointer-events: none;">
                                        <h4 class="fw-bold mb-0 text-body" id="donutCenterUptime">100%</h4>
                                        <small class="text-muted" style="font-size: 10px;">UPTIME</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-7">
                                <ul class="list-group list-group-flush bg-transparent small">
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#10b981; border-radius:50%; margin-right:6px;"></span>Operational Checks:</span>
                                        <strong class="text-success" id="donutLegUp">100% (0)</strong>
                                    </li>
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#ef4444; border-radius:50%; margin-right:6px;"></span>Outage / Downtime:</span>
                                        <strong class="text-danger" id="donutLegDown">0% (0)</strong>
                                    </li>
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-1 border-0">
                                        <span><span style="display:inline-block; width:10px; height:10px; background:#0f172a; border-radius:50%; margin-right:6px;"></span>System Blackouts:</span>
                                        <strong class="text-body" id="donutLegBlack">0% (0)</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- B. Collapsible DataTables Table Section -->
                <div class="collapse mb-4" id="dayModalLogsContainer">
                    <div class="card border bg-body-secondary p-3 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 small text-body"><i class="bi bi-activity text-primary me-1"></i> Raw Heartbeat Telemetry</h6>
                            <button type="button" class="btn-close btn-sm" onclick="bootstrap.Collapse.getInstance(document.getElementById('dayModalLogsContainer')).hide()"></button>
                        </div>
                        <div class="table-responsive">
                            <table id="dayModalLogsTable" class="table table-sm table-hover align-middle mb-0 w-100 bg-body rounded border">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Time</th>
                                        <th style="width: 90px;">Status</th>
                                        <th style="width: 90px;">Latency</th>
                                        <th style="width: 80px;">HTTP</th>
                                        <th>Diagnostics</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Multiple Maintenances Container -->
                <div id="dayModalMaintenancesList" class="d-none mb-3"></div>

                <!-- Multiple Incidents Container -->
                <div id="dayModalIncidentsList" class="d-none mb-3"></div>

                <!-- Clean Status Box -->
                <div id="dayModalCleanMsg" class="alert alert-success d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                    <div>
                        <strong id="dayModalCleanTitle">100% <?= __('status.operational') ?></strong>
                        <div class="small" id="dayModalCleanDesc"><?= __('public.operational_clean') ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('common.close') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Unified 2-in-1 Subscribe / Unsubscribe -->
<div class="modal fade" id="subscriptionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content shadow border">
            <div class="modal-header border-bottom-0 pb-0">
                <ul class="nav nav-pills card-header-pills w-100" role="tablist">
                    <li class="nav-item flex-fill text-center">
                        <button class="nav-link active w-100 fw-bold" data-bs-toggle="pill" data-bs-target="#tabSubscribe" type="button">
                            <i class="bi bi-bell me-1"></i> <?= __('public.subscribe') ?>
                        </button>
                    </li>
                    <li class="nav-item flex-fill text-center">
                        <button class="nav-link w-100 fw-bold text-danger" data-bs-toggle="pill" data-bs-target="#tabUnsubscribe" type="button">
                            <i class="bi bi-bell-slash me-1"></i> <?= __('public.unsubscribe') ?>
                        </button>
                    </li>
                </ul>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4 tab-content">
                <!-- TAB 1: SUBSCRIBE FORM -->
                <div class="tab-pane fade show active" id="tabSubscribe">
                    <form action="/subscribe" method="POST">
                        <input type="hidden" name="monitor_id" id="modalMonitorId" value="">
                        
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1"><?= __('public.target_service') ?>:</label>
                            <div class="p-2 bg-body-secondary rounded-3 border fw-bold text-body small d-flex align-items-center gap-2" id="modalTargetServiceName">
                                <i class="bi bi-hdd-network text-primary"></i> <?= __('public.all_core_services') ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('public.email_address') ?></label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                            <div class="text-muted small mt-1"><?= __('public.subscribe_verification_note') ?></div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> <?= __('public.confirm_subscribe') ?>
                        </button>
                    </form>
                </div>

                <!-- TAB 2: UNSUBSCRIBE FORM -->
                <div class="tab-pane fade" id="tabUnsubscribe">
                    <form action="/subscribe/request-unsubscribe" method="POST">
                        <div class="alert alert-body border small text-muted mb-3">
                            <i class="bi bi-shield-lock text-danger me-1"></i>
                            <?= __('public.unsubscribe_info') ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= __('public.email_address') ?></label>
                            <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                            <i class="bi bi-envelope-x me-1"></i> <?= __('public.send_unsubscribe_link') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables Scripts for Interactive Modal Telemetry -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
let currentDayModalData = null;
let dayLogsDataTable = null;
let isLogsTableLoaded = false;

function openDayDetailModalFromElement(el) {
    const rawData = el.getAttribute('data-day-payload');
    if (!rawData) return;

    try {
        const data = JSON.parse(rawData);
        currentDayModalData = data;
        isLogsTableLoaded = false;

        bootstrap.Collapse.getOrCreateInstance(document.getElementById('dayModalDonutContainer'), { toggle: false }).hide();
        bootstrap.Collapse.getOrCreateInstance(document.getElementById('dayModalLogsContainer'), { toggle: false }).hide();

        if (dayLogsDataTable) {
            dayLogsDataTable.destroy();
            dayLogsDataTable = null;
            document.querySelector('#dayModalLogsTable tbody').innerHTML = '';
        }

        document.getElementById('dayModalDateTitle').textContent = '<?= addslashes(__('public.daily_report')) ?>: ' + data.date;
        document.getElementById('dayModalMonitorName').textContent = data.monitor;
        document.getElementById('dayModalChecksCount').textContent = data.checks;
        document.getElementById('dayModalOutagesCount').textContent = data.outages;
        document.getElementById('dayModalBlackoutsCount').textContent = data.blackouts;

        const childAlert = document.getElementById('dayModalChildAlert');
        if (data.child_issues && data.child_issues.length > 0) {
            childAlert.classList.remove('d-none');
        } else {
            childAlert.classList.add('d-none');
        }

        const uptimeElem = document.getElementById('dayModalUptimePct');
        const cleanMsg   = document.getElementById('dayModalCleanMsg');
        const cleanTitle = document.getElementById('dayModalCleanTitle');
        const cleanDesc  = document.getElementById('dayModalCleanDesc');

        if (data.checks > 0) {
            uptimeElem.textContent = data.uptime_pct + '%';
            if (data.uptime_pct >= 99.0) {
                uptimeElem.className = 'fw-bold mb-0 text-success';
            } else if (data.uptime_pct >= 90.0) {
                uptimeElem.className = 'fw-bold mb-0 text-warning';
            } else {
                uptimeElem.className = 'fw-bold mb-0 text-danger';
            }
            cleanTitle.textContent = '100% <?= addslashes(__('status.operational')) ?>';
            cleanDesc.textContent = '<?= addslashes(__('public.operational_clean')) ?>';
            cleanMsg.className = 'alert alert-success d-flex align-items-center gap-2 mb-0';
        } else {
            uptimeElem.textContent = 'N/A';
            uptimeElem.className = 'fw-bold mb-0 text-secondary';
            cleanTitle.textContent = '<?= addslashes(__('public.no_data_recorded')) ?>';
            cleanDesc.textContent = 'No monitoring checks were executed for this service on this date.';
            cleanMsg.className = 'alert alert-light border d-flex align-items-center gap-2 mb-0 text-muted';
        }

        const maintContainer = document.getElementById('dayModalMaintenancesList');
        const incContainer   = document.getElementById('dayModalIncidentsList');

        let hasAnyEvent = false;

        // 1. Render Maintenances on this Day
        if (data.maintenances && data.maintenances.length > 0) {
            hasAnyEvent = true;
            let maintHtml = '';
            data.maintenances.forEach(m => {
                maintHtml += `
                    <div class="card border-info mb-3 shadow-sm">
                        <div class="card-header bg-info bg-opacity-25 py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-body"><i class="bi bi-tools text-info me-1"></i> <?= addslashes(__('maintenance.title')) ?></span>
                            <span class="badge bg-info text-dark">${m.status}</span>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-2 text-body">${m.title}</h6>
                            <p class="small text-muted mb-2">${m.description || '<?= addslashes(__('common.description')) ?>'}</p>
                            <div class="p-2 bg-body-secondary rounded border small text-body">
                                <i class="bi bi-clock me-1 text-primary"></i> <strong><?= addslashes(__('public.window')) ?>:</strong> ${m.start_time} — ${m.end_time}
                            </div>
                        </div>
                    </div>
                `;
            });
            maintContainer.innerHTML = maintHtml;
            maintContainer.classList.remove('d-none');
        } else {
            maintContainer.innerHTML = '';
            maintContainer.classList.add('d-none');
        }

        // 2. Render Incidents on this Day
        if (data.incidents && data.incidents.length > 0) {
            hasAnyEvent = true;
            let incHtml = '';
            data.incidents.forEach(inc => {
                let timelineHtml = '';
                if (inc.updates && inc.updates.length > 0) {
                    inc.updates.forEach(u => {
                        timelineHtml += `
                            <div class="mb-2 position-relative">
                                <span class="badge bg-secondary me-1">${u.time}</span>
                                <strong class="small text-body text-capitalize">${u.status}:</strong>
                                <p class="mb-0 text-muted small ps-2">${u.message}</p>
                            </div>
                        `;
                    });
                }
                incHtml += `
                    <div class="card border-warning mb-3 shadow-sm">
                        <div class="card-header bg-warning bg-opacity-25 py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-body"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <?= addslashes(__('public.reported_incident')) ?></span>
                            <div class="d-flex gap-1">
                                <span class="badge bg-danger">${inc.impact}</span>
                                <span class="badge bg-dark">${inc.status}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold mb-2 text-body">${inc.title}</h6>
                            <div class="small text-muted mb-3">
                                <i class="bi bi-calendar-event me-1"></i> <strong><?= addslashes(__('public.opened')) ?>:</strong> ${inc.created_at} &bull; 
                                <i class="bi bi-clock-history me-1"></i> <strong><?= addslashes(__('public.updated')) ?>:</strong> ${inc.updated_at}
                            </div>
                            <h6 class="fw-bold small text-secondary mb-2"><?= addslashes(__('public.chronological_updates')) ?>:</h6>
                            <div class="timeline ps-3 border-start">${timelineHtml}</div>
                        </div>
                    </div>
                `;
            });
            incContainer.innerHTML = incHtml;
            incContainer.classList.remove('d-none');
        } else {
            incContainer.innerHTML = '';
            incContainer.classList.add('d-none');
        }

        if (hasAnyEvent || data.outages > 0 || data.blackouts > 0) {
            cleanMsg.classList.add('d-none');
        } else {
            cleanMsg.classList.remove('d-none');
        }

        new bootstrap.Modal(document.getElementById('dayDetailModal')).show();
    } catch (e) {
        console.error('Error opening day detail modal:', e);
    }
}

// 1. Interactive Donut Ring Chart Toggle
function toggleUptimeDonutChart() {
    if (!currentDayModalData || currentDayModalData.checks === 0) return;

    const container = document.getElementById('dayModalDonutContainer');
    const collapseInstance = bootstrap.Collapse.getOrCreateInstance(container);
    
    bootstrap.Collapse.getOrCreateInstance(document.getElementById('dayModalLogsContainer'), { toggle: false }).hide();

    collapseInstance.toggle();

    const total = currentDayModalData.checks;
    const up    = currentDayModalData.up_checks || 0;
    const down  = currentDayModalData.outages || 0;
    const black = currentDayModalData.blackouts || 0;

    const upPct    = (total > 0) ? ((up / total) * 100).toFixed(1) : '100.0';
    const downPct  = (total > 0) ? ((down / total) * 100).toFixed(1) : '0.0';
    const blackPct = (total > 0) ? ((black / total) * 100).toFixed(1) : '0.0';

    document.getElementById('donutCenterUptime').textContent = currentDayModalData.uptime_pct + '%';
    document.getElementById('donutLegUp').textContent = `${upPct}% (${up})`;
    document.getElementById('donutLegDown').textContent = `${downPct}% (${down})`;
    document.getElementById('donutLegBlack').textContent = `${blackPct}% (${black})`;

    let offset = 0;
    let svgHtml = '<circle cx="18" cy="18" r="15.915" fill="none" stroke="var(--bs-border-color)" stroke-width="3"></circle>';

    if (up > 0) {
        const strokeVal = ((up / total) * 100);
        svgHtml += `<circle cx="18" cy="18" r="15.915" fill="none" stroke="#10b981" stroke-width="3" stroke-dasharray="${strokeVal} ${100 - strokeVal}" stroke-dashoffset="${-offset}"></circle>`;
        offset += strokeVal;
    }
    if (down > 0) {
        const strokeVal = ((down / total) * 100);
        svgHtml += `<circle cx="18" cy="18" r="15.915" fill="none" stroke="#ef4444" stroke-width="3" stroke-dasharray="${strokeVal} ${100 - strokeVal}" stroke-dashoffset="${-offset}"></circle>`;
        offset += strokeVal;
    }
    if (black > 0) {
        const strokeVal = ((black / total) * 100);
        svgHtml += `<circle cx="18" cy="18" r="15.915" fill="none" stroke="#0f172a" stroke-width="3" stroke-dasharray="${strokeVal} ${100 - strokeVal}" stroke-dashoffset="${-offset}"></circle>`;
    }

    document.getElementById('dayModalDonutSvg').innerHTML = svgHtml;
}

// 2. Interactive DataTables Checks Table Toggle
async function toggleChecksTable(filterStatus = 'all') {
    if (!currentDayModalData || currentDayModalData.checks === 0) return;

    const container = document.getElementById('dayModalLogsContainer');
    const collapseInstance = bootstrap.Collapse.getOrCreateInstance(container);

    bootstrap.Collapse.getOrCreateInstance(document.getElementById('dayModalDonutContainer'), { toggle: false }).hide();

    collapseInstance.show();

    if (!isLogsTableLoaded) {
        try {
            const url = `/api/v1/monitor/day-logs?monitor_id=${currentDayModalData.monitor_id}&date=${currentDayModalData.date_raw}`;
            const res = await fetch(url);
            const json = await res.json();

            if (json.success && json.data) {
                if (dayLogsDataTable) {
                    dayLogsDataTable.destroy();
                }

                dayLogsDataTable = $('#dayModalLogsTable').DataTable({
                    data: json.data,
                    deferRender: true,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    order: [[0, 'desc']],
                    columns: [
                        { data: 'time' },
                        { data: 'status' },
                        { data: 'latency' },
                        { data: 'http_code' },
                        { data: 'details' }
                    ],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search logs...",
                        info: "Showing _START_ to _END_ of _TOTAL_ checks",
                        lengthMenu: "Show _MENU_"
                    }
                });

                isLogsTableLoaded = true;
            }
        } catch (err) {
            console.error('Error loading day logs:', err);
        }
    }

    if (dayLogsDataTable) {
        if (filterStatus === 'all') {
            dayLogsDataTable.search('').columns().search('').draw();
        } else {
            dayLogsDataTable.search(filterStatus.toUpperCase()).draw();
        }
    }
}

function openSubscriptionModal(monitorId, monitorName) {
    document.getElementById('modalMonitorId').value = monitorId ? monitorId : '';
    document.getElementById('modalTargetServiceName').innerHTML = '<i class="bi bi-hdd-network text-primary"></i> ' + monitorName;

    const subscribeTabTrigger = document.querySelector('#subscriptionModal .nav-link[data-bs-target="#tabSubscribe"]');
    if (subscribeTabTrigger) {
        bootstrap.Tab.getOrCreateInstance(subscribeTabTrigger).show();
    }

    new bootstrap.Modal(document.getElementById('subscriptionModal')).show();
}
</script>

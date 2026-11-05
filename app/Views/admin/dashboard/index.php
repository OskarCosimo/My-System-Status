<!-- path: app/Views/admin/dashboard/index.php -->
<div class="container-fluid py-4">
    <!-- Header Controls -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-speedometer2 text-primary"></i> Infrastructure Overview
            </h2>
            <p class="text-secondary mb-0">Real-time metrics, telemetry, and distributed probe analytics.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-inline-flex align-items-center px-3 py-2 fs-6 rounded-pill">
                <i class="bi bi-shield-check me-1"></i> System Uptime: <?= number_format($avgUptime, 2) ?>%
            </span>
            <a href="/admin/monitors" class="btn btn-outline-primary"><i class="bi bi-plus-lg me-1"></i> Add Route</a>
            <a href="/admin/incidents" class="btn btn-danger"><i class="bi bi-exclamation-triangle me-1"></i> Declare Outage</a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3">
                        <i class="bi bi-hdd-network"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Total Active Monitors</div>
                        <h3 class="fw-bold mb-0"><?= $monitorsTotal ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Operational Probes</div>
                        <h3 class="fw-bold mb-0"><?= $monitorsUp ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-3">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Active Incidents</div>
                        <h3 class="fw-bold mb-0"><?= $activeIncidents ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="text-secondary small fw-medium">Verified Subscribers</div>
                        <h3 class="fw-bold mb-0"><?= $subscribersCount ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafana Analytics: Row 1 (Telemetry Area Chart & Status Donut) -->
    <div class="row g-3 mb-4">
        <!-- 24h Latency Telemetry -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-graph-up text-primary me-2"></i>Global Latency Telemetry (24h)</h5>
                        <small class="text-secondary">Average response time (ms) and check failures</small>
                    </div>
                    <span class="badge bg-body-secondary text-body border px-2 py-1"><i class="bi bi-clock-history me-1"></i>1h Slots</span>
                </div>
                <div class="card-body pt-1">
                    <div style="height: 310px; position: relative;">
                        <canvas id="latencyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Health Distribution Donut -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-pie-chart text-success me-2"></i>Health Distribution</h5>
                    <small class="text-secondary">Breakdown of operational states</small>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div style="height: 250px; position: relative;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-around text-center mt-3 pt-2 border-top">
                        <div>
                            <span class="text-success fw-bold d-block"><?= $statusCounts['operational'] ?></span>
                            <small class="text-secondary">Operational</small>
                        </div>
                        <div>
                            <span class="text-warning fw-bold d-block"><?= $statusCounts['degraded'] ?></span>
                            <small class="text-secondary">Degraded</small>
                        </div>
                        <div>
                            <span class="text-danger fw-bold d-block"><?= $statusCounts['down'] ?></span>
                            <small class="text-secondary">Down</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafana Analytics: Row 2 (HTTP Distribution, Types & Incidents) -->
    <div class="row g-3 mb-4">
        <!-- HTTP Status Codes & Failures -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps text-info me-2"></i>Check Results (7 Days)</h6>
                    <small class="text-secondary">HTTP codes, timeouts and blackout occurrences</small>
                </div>
                <div class="card-body">
                    <div style="height: 240px; position: relative;">
                        <canvas id="httpCodesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Monitor Types -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 text-warning me-2"></i>Active Monitor Types</h6>
                    <small class="text-secondary">Protocol distribution of active probes</small>
                </div>
                <div class="card-body">
                    <div style="height: 240px; position: relative;">
                        <canvas id="monitorTypesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incidents & Maintenance -->
        <div class="col-xl-4 col-md-12">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-tools text-danger me-2"></i>Incidents & Maintenances</h6>
                    <small class="text-secondary">Severity levels and maintenance progress</small>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <div class="fw-semibold small text-secondary mb-2">INCIDENTS BY SEVERITY</div>
                        <div class="d-flex flex-column gap-2 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-record-circle me-1"></i>Critical</span>
                                <span class="fw-bold"><?= $incidentsByImpact['critical'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-record-circle me-1"></i>Major</span>
                                <span class="fw-bold"><?= $incidentsByImpact['major'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-record-circle me-1"></i>Minor</span>
                                <span class="fw-bold"><?= $incidentsByImpact['minor'] ?? 0 ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-3">
                        <div class="fw-semibold small text-secondary mb-2">SCHEDULED MAINTENANCE</div>
                        <div class="d-flex justify-content-between text-center">
                            <div class="bg-body-secondary p-2 rounded-2 flex-fill me-1">
                                <small class="text-secondary d-block">Scheduled</small>
                                <strong class="fs-6"><?= $maintenancesByStatus['scheduled'] ?? 0 ?></strong>
                            </div>
                            <div class="bg-primary-subtle p-2 rounded-2 flex-fill me-1">
                                <small class="text-primary d-block">In Progress</small>
                                <strong class="fs-6 text-primary"><?= $maintenancesByStatus['in_progress'] ?? 0 ?></strong>
                            </div>
                            <div class="bg-success-subtle p-2 rounded-2 flex-fill">
                                <small class="text-success d-block">Completed</small>
                                <strong class="fs-6 text-success"><?= $maintenancesByStatus['completed'] ?? 0 ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conditional Cloudflare Edge Worker Section (Rendered only if Edge Worker is Enabled) -->
    <?php if ($edgeEnabled): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-globe-americas text-primary"></i> Cloudflare Distributed Edge Probes
                    </h5>
                    <small class="text-secondary">Multi-region telemetry segmented by Cloudflare Edge PoP location (IATA codes)</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                        <i class="bi bi-broadcast me-1"></i> Worker Active
                    </span>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($edgePoPStats)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-cloud-arrow-down fs-2 d-block mb-2"></i>
                        No multi-region checks recorded yet. Run the cron runner to aggregate Edge PoP data.
                    </div>
                <?php else: ?>
                    <div class="row g-3 align-items-center">
                        <div class="col-xl-7">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>PoP / Node</th>
                                            <th>Location</th>
                                            <th class="text-center">Total Checks</th>
                                            <th class="text-center">Avg Latency</th>
                                            <th class="text-end">Uptime</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($edgePoPStats as $pop): ?>
                                            <tr>
                                                <td class="fw-bold font-monospace text-primary">
                                                    <span class="me-1"><?= $pop['flag'] ?></span> <?= htmlspecialchars($pop['code']) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($pop['city']) ?></div>
                                                    <small class="text-secondary"><?= htmlspecialchars($pop['country']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-body-secondary text-body border"><?= number_format($pop['total_checks']) ?></span>
                                                </td>
                                                <td class="text-center fw-semibold">
                                                    <?= $pop['avg_latency'] ?> ms
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-<?= $pop['uptime_rate'] >= 99 ? 'success' : ($pop['uptime_rate'] >= 95 ? 'warning' : 'danger') ?>-subtle text-<?= $pop['uptime_rate'] >= 99 ? 'success' : ($pop['uptime_rate'] >= 95 ? 'warning' : 'danger') ?> border">
                                                        <?= number_format($pop['uptime_rate'], 1) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-xl-5">
                            <div style="height: 240px; position: relative;">
                                <canvas id="edgePopsChart"></canvas>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Grafana Analytics: Row 3 (Slowest Endpoints & All Active Plugins) -->
    <div class="row g-3 mb-4">
        <!-- Top Slowest Endpoints -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-stopwatch text-danger me-2"></i>Slowest Endpoints (Avg 24h)</h6>
                    <small class="text-secondary">Highest response times requiring attention</small>
                </div>
                <div class="card-body">
                    <?php if (empty($slowestMonitors)): ?>
                        <div class="text-center py-5 text-secondary">No latency data accumulated yet.</div>
                    <?php else: ?>
                        <div style="height: 240px; position: relative;">
                            <canvas id="slowestChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- All Active External Plugins & Feeds (Target URLs removed) -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0"><i class="bi bi-plugin text-primary me-2"></i>Active External Plugins & Feeds</h6>
                        <small class="text-secondary">Integrated external cloud providers & zero trust networks</small>
                    </div>
                    <span class="badge bg-body-secondary text-body border"><?= count($allPluginServices) ?> Enabled</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Service Name</th>
                                    <th>Provider</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allPluginServices)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-secondary">
                                            No external plugin services active.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allPluginServices as $srv): ?>
                                        <tr>
                                            <td class="fw-semibold">
                                                <i class="bi bi-check2-circle text-primary me-2 opacity-75"></i>
                                                <?= htmlspecialchars($srv['name']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary border">
                                                    <?= htmlspecialchars($srv['provider']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($srv['current_status'] === 'operational'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">OPERATIONAL</span>
                                                <?php elseif ($srv['current_status'] === 'degraded'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">DEGRADED</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">DOWN</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Probes Activity (Bottom Table) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-activity text-primary me-2"></i>Latest Heartbeat Checks</h5>
            <a href="/admin/logs" class="btn btn-sm btn-outline-secondary">View All Logs</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Time</th>
                        <th>Monitor</th>
                        <th>Status</th>
                        <th>Latency</th>
                        <th>HTTP Code</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentLogs)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-secondary">No heartbeat checks recorded yet. Run the cron runner to begin.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td><small class="text-secondary"><?= format_date($log['created_at'], 'H:i:s') ?></small></td>
                            <td class="fw-semibold"><?= htmlspecialchars($log['monitor_name']) ?></td>
                            <td>
                                <span class="badge bg-<?= $log['status'] === 'up' ? 'success' : 'danger' ?>">
                                    <?= strtoupper($log['status']) ?>
                                </span>
                            </td>
                            <td><?= $log['response_time_ms'] ?> ms</td>
                            <td><code><?= $log['http_code'] ?? '-' ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js for Visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = 'inherit';

    // 1. Latency & Response Time Telemetry (Line Area Chart)
    const latencyLabels = <?= json_encode($latencyLabels) ?>;
    const latencyData   = <?= json_encode($latencyData) ?>;
    const errorPoints   = <?= json_encode($errorPoints) ?>;

    const ctxLatency = document.getElementById('latencyChart');
    if (ctxLatency) {
        const gradient = ctxLatency.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(56, 189, 248, 0.45)');
        gradient.addColorStop(1, 'rgba(56, 189, 248, 0.00)');

        new Chart(ctxLatency, {
            type: 'line',
            data: {
                labels: latencyLabels.length > 0 ? latencyLabels : ['00:00', '06:00', '12:00', '18:00', '24:00'],
                datasets: [
                    {
                        label: 'Average Latency (ms)',
                        data: latencyData.length > 0 ? latencyData : [0, 0, 0, 0, 0],
                        borderColor: '#0284c7',
                        backgroundColor: gradient,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Failed Checks',
                        data: errorPoints.length > 0 ? errorPoints : [0, 0, 0, 0, 0],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        borderWidth: 1.5,
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor } },
                    y: { position: 'left', grid: { color: gridColor }, ticks: { color: textColor, callback: (v) => v + ' ms' }, beginAtZero: true },
                    y1: { position: 'right', grid: { drawOnChartArea: false }, ticks: { color: '#ef4444', precision: 0 }, beginAtZero: true }
                },
                plugins: { legend: { position: 'top', labels: { boxWidth: 12, color: textColor } } }
            }
        });
    }

    // 2. Health Distribution (Donut Chart)
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus) {
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Operational', 'Degraded', 'Down', 'Maintenance'],
                datasets: [{
                    data: [
                        <?= (int)$statusCounts['operational'] ?>,
                        <?= (int)$statusCounts['degraded'] ?>,
                        <?= (int)$statusCounts['down'] ?>,
                        <?= (int)$statusCounts['maintenance'] ?>
                    ],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#64748b'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, color: textColor } } }
            }
        });
    }

    // 3. HTTP Codes & Failures (Bar Chart)
    const ctxHttp = document.getElementById('httpCodesChart');
    if (ctxHttp) {
        const httpMap = <?= json_encode($httpDistribution) ?>;
        const labels = Object.keys(httpMap).length ? Object.keys(httpMap) : ['2xx Success', 'Timeout', '5xx Server Err'];
        const values = Object.keys(httpMap).length ? Object.values(httpMap) : [0, 0, 0];

        new Chart(ctxHttp, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Occurrences',
                    data: values,
                    backgroundColor: ['#10b981', '#38bdf8', '#fbbf24', '#f87171', '#a855f7'],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // 4. Monitor Types Breakdown (Doughnut Chart)
    const ctxTypes = document.getElementById('monitorTypesChart');
    if (ctxTypes) {
        const typeMap = <?= json_encode($typeDistribution) ?>;
        const typeLabels = Object.keys(typeMap).map(s => s.toUpperCase());
        const typeValues = Object.values(typeMap);

        new Chart(ctxTypes, {
            type: 'doughnut',
            data: {
                labels: typeLabels.length ? typeLabels : ['HTTP', 'PING', 'PORT'],
                datasets: [{
                    data: typeValues.length ? typeValues : [1, 0, 0],
                    backgroundColor: ['#0284c7', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316'],
                    borderWidth: 2,
                    borderColor: isDark ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, color: textColor } } }
            }
        });
    }

    // 5. Cloudflare Edge PoP Checks Chart (Bar Chart)
    const ctxEdgePops = document.getElementById('edgePopsChart');
    if (ctxEdgePops) {
        const edgePops = <?= json_encode($edgePoPStats) ?>;
        new Chart(ctxEdgePops, {
            type: 'bar',
            data: {
                labels: edgePops.map(p => p.code),
                datasets: [{
                    label: 'Checks by PoP',
                    data: edgePops.map(p => p.total_checks),
                    backgroundColor: '#0284c7',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { color: textColor } },
                    y: { grid: { color: gridColor }, ticks: { color: textColor, precision: 0 }, beginAtZero: true }
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Check Volume per Edge Datacenter', color: textColor, font: { weight: 'bold' } }
                }
            }
        });
    }

    // 6. Slowest Monitors (Horizontal Bar Chart)
    const ctxSlowest = document.getElementById('slowestChart');
    if (ctxSlowest) {
        const slowestData = <?= json_encode($slowestMonitors) ?>;
        new Chart(ctxSlowest, {
            type: 'bar',
            data: {
                labels: slowestData.map(m => m.name),
                datasets: [{
                    label: 'Avg Latency (ms)',
                    data: slowestData.map(m => m.avg_latency),
                    backgroundColor: '#f43f5e',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { color: gridColor }, ticks: { color: textColor, callback: (v) => v + ' ms' } },
                    y: { grid: { display: false }, ticks: { color: textColor } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

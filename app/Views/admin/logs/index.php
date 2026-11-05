<!-- path: app/Views/admin/logs/index.php -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .dataTables_wrapper .dataTables_filter input {
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        border: 1px solid #dee2e6;
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 0.375rem;
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        border: 1px solid #dee2e6;
    }
    .dataTables_info, .dataTables_paginate {
        padding-top: 1rem !important;
        padding-bottom: 0.5rem !important;
    }
    /* Smooth loading state */
    #logsContainer {
        opacity: 0;
        transition: opacity 0.15s ease-in;
    }
    #logsContainer.ready {
        opacity: 1;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header with 24-Hour Date Picker -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-0"><?= __('logs.page_title') ?></h2>
            <p class="text-muted mb-0"><?= __('logs.page_subtitle') ?></p>
        </div>
        <div>
            <form action="/admin/logs" method="GET" class="d-flex align-items-center gap-2">
                <label for="log_date" class="form-label small fw-semibold mb-0 text-nowrap">
                    <i class="bi bi-calendar3 me-1"></i> <?= __('logs.label_window_24h') ?>
                </label>
                <input type="date" 
                       id="log_date" 
                       name="date" 
                       value="<?= htmlspecialchars($selectedDate ?? date('Y-m-d')) ?>" 
                       max="<?= date('Y-m-d') ?>"
                       class="form-control form-control-sm shadow-sm" 
                       onchange="this.form.submit()">
                <?php if (!empty($selectedDate) && $selectedDate !== date('Y-m-d')): ?>
                    <a href="/admin/logs" class="btn btn-sm btn-primary text-nowrap"><?= __('logs.btn_today') ?></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- 24-Hour Metric KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-4">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?= __('logs.kpi_checks_24h') ?></div>
                        <h4 class="fw-bold mb-0"><?= number_format($totalChecks ?? 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3 fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?= __('logs.kpi_successful') ?></div>
                        <h4 class="fw-bold mb-0 text-success"><?= number_format($upChecks ?? 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-4">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?= __('logs.kpi_outages') ?></div>
                        <h4 class="fw-bold mb-0 text-danger"><?= number_format($downChecks ?? 0) ?></h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-dark bg-opacity-10 text-dark rounded-3 fs-4">
                        <i class="bi bi-speedometer"></i>
                    </div>
                    <div>
                        <div class="text-muted small"><?= __('logs.kpi_avg_latency') ?></div>
                        <h4 class="fw-bold mb-0"><?= (int)($avgLatency ?? 0) ?> ms</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTables Card (Zero Layout Shift Container) -->
    <div class="card shadow-sm border-0 p-3">
        <div class="table-responsive" id="logsContainer">
            <table id="logsTable" class="table table-hover align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th style="width: 170px;"><?= __('logs.table_datetime', ['timezone' => htmlspecialchars(\App\Services\DateService::getActiveTimezone())]) ?></th>
                        <th><?= __('logs.table_monitor_name') ?></th>
                        <th><?= __('common.status') ?></th>
                        <th><?= __('logs.table_latency') ?></th>
                        <th><?= __('logs.table_http_code') ?></th>
                        <th><?= __('logs.table_diagnostic_details') ?></th>
                    </tr>
                </thead>
                <!-- Completely empty tbody: prevents the browser from rendering 6,500 raw HTML rows! -->
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
// Efficient JSON payload generated by PHP
const logsDataset = <?= json_encode($formattedLogs ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

$(document).ready(function() {
    $('#logsTable').DataTable({
        data: logsDataset,
        deferRender: true, // Crucial: Renders DOM elements ONLY for the 50 visible rows!
        order: [[0, 'desc']],
        pageLength: 50,
        lengthMenu: [[25, 50, 100, 250, -1], [25, 50, 100, 250, <?= json_encode(__('logs.dt_all')) ?>]],
        columns: [
            { data: 'time' },
            { data: 'monitor' },
            { data: 'status' },
            { data: 'latency' },
            { data: 'http_code' },
            { data: 'details' }
        ],
        language: {
            search: "_INPUT_",
            searchPlaceholder: <?= json_encode(__('logs.dt_search')) ?>,
            lengthMenu: <?= json_encode(__('logs.dt_length')) ?>,
            info: <?= json_encode(__('logs.dt_info')) ?>,
            infoEmpty: <?= json_encode(__('logs.dt_info_empty')) ?>,
            zeroRecords: <?= json_encode(__('logs.dt_zero_records')) ?>
        },
        initComplete: function() {
            // Instantly reveal the table with zero layout shift
            $('#logsContainer').addClass('ready');
        }
    });
});
</script>

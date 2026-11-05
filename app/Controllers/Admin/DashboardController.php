<?php
// path: app/Controllers/Admin/DashboardController.php

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\View;
use PDO;

class DashboardController
{
    private PDO $db;

    public function __construct()
    {
        // Authentication guard
        if (empty($_SESSION['user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // 1. Monitor KPI counts
        $monitorsTotal = (int)$this->db->query("SELECT COUNT(*) FROM monitors WHERE is_active = 1")->fetchColumn();
        $monitorsUp    = (int)$this->db->query("SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND current_status = 'operational'")->fetchColumn();
        $monitorsDeg   = (int)$this->db->query("SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND current_status = 'degraded'")->fetchColumn();
        $monitorsDown  = (int)$this->db->query("SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND current_status = 'down'")->fetchColumn();

        // 2. Active Incidents & Maintenance
        $activeIncidents = (int)$this->db->query("SELECT COUNT(*) FROM incidents WHERE status != 'resolved'")->fetchColumn();
        $upcomingMaint   = (int)$this->db->query("SELECT COUNT(*) FROM maintenances WHERE status IN ('scheduled', 'in_progress') AND end_time >= NOW()")->fetchColumn();

        // 3. Verified Subscribers
        $subscribersCount = (int)$this->db->query("SELECT COUNT(*) FROM subscribers WHERE is_verified = 1")->fetchColumn();

        // 4. Average System Uptime Percentage
        $avgUptime = (float)$this->db->query("SELECT COALESCE(AVG(uptime_percentage), 100.0) FROM monitors WHERE is_active = 1")->fetchColumn();

        // 5. Response Time & Latency Timeline (Last 24 Hours, hourly aggregate)
        $latencyStmt = $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%H:00') AS time_label,
                ROUND(AVG(CASE WHEN status = 'up' AND response_time_ms > 0 THEN response_time_ms ELSE NULL END)) AS avg_latency,
                SUM(CASE WHEN status != 'up' THEN 1 ELSE 0 END) AS error_count,
                COUNT(*) AS total_checks
            FROM monitor_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00')
            ORDER BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00') ASC
        ");
        $latencyRows = $latencyStmt->fetchAll(PDO::FETCH_ASSOC);

        $latencyLabels = [];
        $latencyData   = [];
        $errorPoints   = [];
        foreach ($latencyRows as $row) {
            $latencyLabels[] = $row['time_label'];
            $latencyData[]   = (int)($row['avg_latency'] ?? 0);
            $errorPoints[]   = (int)($row['error_count'] ?? 0);
        }

        // 6. Monitor Status Breakdown (Donut Chart)
        $statusCounts = [
            'operational' => $monitorsUp,
            'degraded'    => $monitorsDeg,
            'down'        => $monitorsDown,
            'maintenance' => (int)$this->db->query("SELECT COUNT(*) FROM monitors WHERE is_active = 1 AND current_status = 'maintenance'")->fetchColumn()
        ];

        // 7. HTTP Status & Result Distribution (Bar Chart - Last 7 Days)
        $httpDistStmt = $this->db->query("
            SELECT 
                CASE 
                    WHEN status = 'blackout' THEN 'Blackout'
                    WHEN status = 'timeout' THEN 'Timeout'
                    WHEN http_code >= 200 AND http_code < 300 THEN '2xx Success'
                    WHEN http_code >= 300 AND http_code < 400 THEN '3xx Redirect'
                    WHEN http_code >= 400 AND http_code < 500 THEN '4xx Client Err'
                    WHEN http_code >= 500 THEN '5xx Server Err'
                    ELSE 'Other Down'
                END AS code_bucket,
                COUNT(*) AS total
            FROM monitor_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY code_bucket
            ORDER BY total DESC
        ");
        $httpDistribution = $httpDistStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 8. Monitors by Type Breakdown (Doughnut Chart)
        $typeStmt = $this->db->query("
            SELECT type, COUNT(*) AS total 
            FROM monitors 
            WHERE is_active = 1 
            GROUP BY type
        ");
        $typeDistribution = $typeStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 9. Incidents by Severity & Maintenances Breakdown
        $incidentsStmt = $this->db->query("
            SELECT impact, COUNT(*) as total 
            FROM incidents 
            GROUP BY impact
        ");
        $incidentsByImpact = $incidentsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $maintStmt = $this->db->query("
            SELECT status, COUNT(*) as total 
            FROM maintenances 
            GROUP BY status
        ");
        $maintenancesByStatus = $maintStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // 10. Top 5 Slowest Routes (Average Latency in the last 24h)
        $slowestStmt = $this->db->query("
            SELECT m.name, ROUND(AVG(l.response_time_ms)) AS avg_latency
            FROM monitor_logs l
            JOIN monitors m ON m.id = l.monitor_id
            WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND l.status = 'up'
              AND l.response_time_ms > 0
            GROUP BY m.id, m.name
            ORDER BY avg_latency DESC
            LIMIT 5
        ");
        $slowestMonitors = $slowestStmt->fetchAll(PDO::FETCH_ASSOC);

        // 11. Cloudflare Status & Edge Probe Information
        $cfMonitorsStmt = $this->db->query("
            SELECT name, target, current_status 
            FROM monitors 
            WHERE is_active = 1 
              AND (target LIKE 'https://www.cloudflarestatus.com%' 
                   OR target LIKE '%dash.cloudflare.com%' 
                   OR name LIKE 'Tunnel:%')
            ORDER BY sort_order ASC, name ASC
        ");
        $cloudflareServices = $cfMonitorsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Edge Worker configuration status
        $edgeEnabled = (setting('edge_worker_enabled', '0') === '1');
        $edgeWorkerUrl = setting('edge_worker_url', '');

        // 12. Recent Heartbeat Logs (Table)
        $stmt = $this->db->query("
            SELECT l.*, m.name as monitor_name 
            FROM monitor_logs l 
            JOIN monitors m ON m.id = l.monitor_id 
            ORDER BY l.id DESC LIMIT 8
        ");
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('admin/dashboard/index', [
            'monitorsTotal'        => $monitorsTotal,
            'monitorsUp'           => $monitorsUp,
            'monitorsDeg'          => $monitorsDeg,
            'monitorsDown'         => $monitorsDown,
            'activeIncidents'      => $activeIncidents,
            'upcomingMaint'        => $upcomingMaint,
            'subscribersCount'     => $subscribersCount,
            'avgUptime'            => $avgUptime,
            'latencyLabels'        => $latencyLabels,
            'latencyData'          => $latencyData,
            'errorPoints'          => $errorPoints,
            'statusCounts'         => $statusCounts,
            'httpDistribution'     => $httpDistribution,
            'typeDistribution'     => $typeDistribution,
            'incidentsByImpact'    => $incidentsByImpact,
            'maintenancesByStatus' => $maintenancesByStatus,
            'slowestMonitors'      => $slowestMonitors,
            'cloudflareServices'   => $cloudflareServices,
            'edgeEnabled'          => $edgeEnabled,
            'edgeWorkerUrl'        => $edgeWorkerUrl,
            'recentLogs'           => $recentLogs
        ]);
    }
}

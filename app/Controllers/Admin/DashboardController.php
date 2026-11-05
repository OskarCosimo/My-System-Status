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

        // 5. Response Time & Latency Timeline (Last 24 Hours, strictly compatible with ONLY_FULL_GROUP_BY)
        $latencyStmt = $this->db->query("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:00') AS time_slot,
                DATE_FORMAT(MIN(created_at), '%H:00') AS time_label,
                ROUND(AVG(CASE WHEN status = 'up' AND response_time_ms > 0 THEN response_time_ms ELSE NULL END)) AS avg_latency,
                SUM(CASE WHEN status != 'up' THEN 1 ELSE 0 END) AS error_count,
                COUNT(*) AS total_checks
            FROM monitor_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d %H:00')
            ORDER BY time_slot ASC
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

        // 11. All Active External Plugins & Feeds (Cloudflare, AWS, Azure, Stripe, PayPal, GitHub, Tunnel)
        $externalStmt = $this->db->query("
            SELECT name, target, current_status 
            FROM monitors 
            WHERE is_active = 1 
              AND (
                  target LIKE 'https://www.cloudflarestatus.com%' 
                  OR target LIKE 'https://status.aws.amazon.com%' 
                  OR target LIKE 'https://health.aws.amazon.com%' 
                  OR target LIKE 'https://azure.status.microsoft%' 
                  OR target LIKE 'https://status.stripe.com%' 
                  OR target LIKE 'https://www.paypal-status.com%' 
                  OR target LIKE 'https://www.githubstatus.com%' 
                  OR target LIKE '%dash.cloudflare.com%' 
                  OR name LIKE 'Tunnel:%'
              )
            ORDER BY name ASC
        ");
        $rawExternalMonitors = $externalStmt->fetchAll(PDO::FETCH_ASSOC);

        $allPluginServices = [];
        foreach ($rawExternalMonitors as $ext) {
            $target = $ext['target'];
            $provider = 'External Feed';

            if (str_contains($target, 'cloudflarestatus.com') || str_contains($target, 'dash.cloudflare.com') || str_starts_with($ext['name'], 'Tunnel:')) {
                $provider = 'Cloudflare';
            } elseif (str_contains($target, 'aws.amazon.com')) {
                $provider = 'Amazon AWS';
            } elseif (str_contains($target, 'azure.status.microsoft')) {
                $provider = 'Microsoft Azure';
            } elseif (str_contains($target, 'stripe.com')) {
                $provider = 'Stripe';
            } elseif (str_contains($target, 'paypal-status.com')) {
                $provider = 'PayPal';
            } elseif (str_contains($target, 'githubstatus.com')) {
                $provider = 'GitHub';
            }

            $allPluginServices[] = [
                'name'           => $ext['name'],
                'provider'       => $provider,
                'current_status' => $ext['current_status']
            ];
        }

        // 12. Cloudflare Edge Worker Probe Statistics by PoP Location / Country
        $edgeEnabled   = (setting('edge_worker_enabled', '0') === '1');
        $edgeWorkerUrl = setting('edge_worker_url', '');
        $edgePoPStats  = [];

        if ($edgeEnabled) {
            // Airport PoP location dictionary with flags and city names
            $popDictionary = [
                'MXP' => ['city' => 'Milan', 'country' => 'Italy', 'flag' => '🇮🇹'],
                'FCO' => ['city' => 'Rome', 'country' => 'Italy', 'flag' => '🇮🇹'],
                'FRA' => ['city' => 'Frankfurt', 'country' => 'Germany', 'flag' => '🇩🇪'],
                'LHR' => ['city' => 'London', 'country' => 'United Kingdom', 'flag' => '🇬🇧'],
                'CDG' => ['city' => 'Paris', 'country' => 'France', 'flag' => '🇫🇷'],
                'AMS' => ['city' => 'Amsterdam', 'country' => 'Netherlands', 'flag' => '🇳🇱'],
                'MAD' => ['city' => 'Madrid', 'country' => 'Spain', 'flag' => '🇪🇸'],
                'VIE' => ['city' => 'Vienna', 'country' => 'Austria', 'flag' => '🇦🇹'],
                'ZRH' => ['city' => 'Zurich', 'country' => 'Switzerland', 'flag' => '🇨🇭'],
                'IAD' => ['city' => 'Ashburn / DC', 'country' => 'United States', 'flag' => '🇺🇸'],
                'ORD' => ['city' => 'Chicago', 'country' => 'United States', 'flag' => '🇺🇸'],
                'DFW' => ['city' => 'Dallas', 'country' => 'United States', 'flag' => '🇺🇸'],
                'SJC' => ['city' => 'San Jose', 'country' => 'United States', 'flag' => '🇺🇸'],
                'LAX' => ['city' => 'Los Angeles', 'country' => 'United States', 'flag' => '🇺🇸'],
                'NRT' => ['city' => 'Tokyo', 'country' => 'Japan', 'flag' => '🇯🇵'],
                'SIN' => ['city' => 'Singapore', 'country' => 'Singapore', 'flag' => '🇸🇬'],
                'HKG' => ['city' => 'Hong Kong', 'country' => 'Hong Kong', 'flag' => '🇭🇰'],
                'SYD' => ['city' => 'Sydney', 'country' => 'Australia', 'flag' => '🇦🇺']
            ];

            // Extract all logs that contain "via {POP}" in error_message over the last 7 days
            $popLogsStmt = $this->db->query("
                SELECT error_message, status, response_time_ms 
                FROM monitor_logs 
                WHERE error_message LIKE '%via %' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $popLogs = $popLogsStmt->fetchAll(PDO::FETCH_ASSOC);

            $groupedPops = [];
            foreach ($popLogs as $pl) {
                if (preg_match('/via\s+([A-Z0-9]{3,4})/i', $pl['error_message'] ?? '', $matches)) {
                    $code = strtoupper($matches[1]);
                    if (!isset($groupedPops[$code])) {
                        $groupedPops[$code] = [
                            'code'         => $code,
                            'city'         => $popDictionary[$code]['city'] ?? 'Edge Node',
                            'country'      => $popDictionary[$code]['country'] ?? 'Global',
                            'flag'         => $popDictionary[$code]['flag'] ?? '🌐',
                            'total_checks' => 0,
                            'up_checks'    => 0,
                            'latency_sum'  => 0,
                            'latency_qty'  => 0
                        ];
                    }

                    $groupedPops[$code]['total_checks']++;
                    if ($pl['status'] === 'up') {
                        $groupedPops[$code]['up_checks']++;
                        if ((int)$pl['response_time_ms'] > 0) {
                            $groupedPops[$code]['latency_sum'] += (int)$pl['response_time_ms'];
                            $groupedPops[$code]['latency_qty']++;
                        }
                    }
                }
            }

            foreach ($groupedPops as $code => $stat) {
                $avgLat = $stat['latency_qty'] > 0 ? (int)round($stat['latency_sum'] / $stat['latency_qty']) : 0;
                $uptimeRate = $stat['total_checks'] > 0 ? round(($stat['up_checks'] / $stat['total_checks']) * 100, 1) : 100.0;

                $edgePoPStats[] = [
                    'code'         => $code,
                    'city'         => $stat['city'],
                    'country'      => $stat['country'],
                    'flag'         => $stat['flag'],
                    'total_checks' => $stat['total_checks'],
                    'avg_latency'  => $avgLat,
                    'uptime_rate'  => $uptimeRate
                ];
            }

            // Sort by total checks descending
            usort($edgePoPStats, fn($a, $b) => $b['total_checks'] <=> $a['total_checks']);
        }

        // 13. Recent Heartbeat Logs (Table)
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
            'allPluginServices'    => $allPluginServices,
            'edgeEnabled'          => $edgeEnabled,
            'edgeWorkerUrl'        => $edgeWorkerUrl,
            'edgePoPStats'         => $edgePoPStats,
            'recentLogs'           => $recentLogs
        ]);
    }
}

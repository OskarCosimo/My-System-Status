<?php
// path: app/Controllers/StatusPageController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\MailerService;
use App\Services\SubscriberService;
use App\Services\SettingService;
use App\Services\DateService;
use App\Services\ContentTranslationService;
use PDO;

class StatusPageController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        // 1. Fetch root monitors ordered by custom sort order
        $stmt = $this->db->query("
            SELECT * FROM monitors 
            WHERE is_active = 1 AND parent_id IS NULL 
            ORDER BY sort_order ASC, name ASC
        ");
        $monitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Attach sub-services to each parent
        foreach ($monitors as &$m) {
            $childStmt = $this->db->prepare("
                SELECT * FROM monitors 
                WHERE is_active = 1 AND parent_id = ? 
                ORDER BY sort_order ASC, name ASC
            ");
            $childStmt->execute([$m['id']]);
            $m['children'] = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($m);

        // 3. Active Maintenances
        $maintenances = $this->db->query("
            SELECT * FROM maintenances 
            WHERE status IN ('scheduled', 'in_progress') 
            AND end_time >= UTC_TIMESTAMP() 
            ORDER BY start_time ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Active Incidents with updates
        $incidents = $this->db->query("
            SELECT * FROM incidents 
            WHERE status != 'resolved' 
            ORDER BY COALESCE(start_time, created_at) DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($incidents as &$incident) {
            $upStmt = $this->db->prepare("
                SELECT id, status, message, created_at 
                FROM incident_updates 
                WHERE incident_id = ? 
                ORDER BY created_at DESC
            ");
            $upStmt->execute([$incident['id']]);
            $incident['updates'] = $upStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($incident);

        // 5. Separate Primary vs Secondary Monitors
        $primaryMonitors   = array_values(array_filter($monitors, fn($m) => ((int)($m['is_primary'] ?? 0) === 1)));
        $secondaryMonitors = array_values(array_filter($monitors, fn($m) => ((int)($m['is_primary'] ?? 0) === 0)));

        $primaryStatus = 'operational';
        foreach ($primaryMonitors as $m) {
            if ($m['current_status'] === 'down') {
                $primaryStatus = 'major_outage';
                break;
            } elseif ($m['current_status'] === 'degraded' && $primaryStatus !== 'major_outage') {
                $primaryStatus = 'degraded';
            }
        }

        $secondaryStatus = 'operational';
        foreach ($secondaryMonitors as $m) {
            if ($m['current_status'] === 'down') {
                $secondaryStatus = 'major_outage';
            } elseif ($m['current_status'] === 'degraded' && $secondaryStatus !== 'major_outage') {
                $secondaryStatus = 'degraded';
            }
            foreach ($m['children'] as $child) {
                if ($child['current_status'] === 'down') {
                    $secondaryStatus = 'major_outage';
                } elseif ($child['current_status'] === 'degraded' && $secondaryStatus !== 'major_outage') {
                    $secondaryStatus = 'degraded';
                }
            }
        }

        // 6. Real 90-Day Probe Logs Aggregation
        $histStmt = $this->db->query("
            SELECT monitor_id, DATE(created_at) as check_date,
                   SUM(LOWER(status) = 'blackout') as blackout_count,
                   SUM(LOWER(status) = 'down') as down_count,
                   SUM(LOWER(status) = 'up') as up_count,
                   COUNT(*) as total_checks
            FROM monitor_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY monitor_id, DATE(created_at)
        ");
        $historyRows = $histStmt->fetchAll(PDO::FETCH_ASSOC);

        $uptimeHistory = [];
        foreach ($historyRows as $row) {
            $mId  = (int)$row['monitor_id'];
            $date = $row['check_date'];
            $uptimeHistory[$mId][$date] = [
                'blackout' => (int)$row['blackout_count'],
                'down'     => (int)$row['down_count'],
                'up'       => (int)$row['up_count'],
                'total'    => (int)$row['total_checks']
            ];
        }

        // 7. Map 90-Day Incidents (WITH timeline notes) and Maintenances per Monitor and Date
        $incStmt = $this->db->query("
            SELECT id, monitor_id, title, impact, status, ai_summary, start_time, end_time, timezone, created_at, updated_at 
            FROM incidents 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY) 
               OR start_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)
               OR status != 'resolved'
        ");
        $allIncidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allIncidents as &$inc) {
            $upStmt = $this->db->prepare("
                SELECT id, status, message, created_at 
                FROM incident_updates 
                WHERE incident_id = ? 
                ORDER BY created_at ASC
            ");
            $upStmt->execute([$inc['id']]);
            $inc['updates'] = $upStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($inc);

        $maintStmt = $this->db->query("
            SELECT id, monitor_id, title, description, status, start_time, end_time, timezone 
            FROM maintenances 
            WHERE start_time >= DATE_SUB(NOW(), INTERVAL 90 DAY) 
               OR end_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)
               OR status IN ('scheduled', 'in_progress')
        ");
        $allMaintenances = $maintStmt->fetchAll(PDO::FETCH_ASSOC);

        // 8. Direct Link Lookup for single Incident or Maintenance (active or archived)
        $singleIncident    = null;
        $singleMaintenance = null;

        $directIncidentId = isset($_GET['incident']) ? (int)$_GET['incident'] : 0;
        if ($directIncidentId > 0) {
            $stmt = $this->db->prepare("
                SELECT i.*, m.name as monitor_name 
                FROM incidents i 
                LEFT JOIN monitors m ON m.id = i.monitor_id 
                WHERE i.id = ? LIMIT 1
            ");
            $stmt->execute([$directIncidentId]);
            $singleIncident = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($singleIncident) {
                $upStmt = $this->db->prepare("
                    SELECT id, status, message, created_at 
                    FROM incident_updates 
                    WHERE incident_id = ? 
                    ORDER BY created_at DESC
                ");
                $upStmt->execute([$singleIncident['id']]);
                $singleIncident['updates'] = $upStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $directMaintenanceId = isset($_GET['maintenance']) ? (int)$_GET['maintenance'] : 0;
        if ($directMaintenanceId > 0) {
            $stmt = $this->db->prepare("
                SELECT ma.*, m.name as monitor_name 
                FROM maintenances ma 
                LEFT JOIN monitors m ON m.id = ma.monitor_id 
                WHERE ma.id = ? LIMIT 1
            ");
            $stmt->execute([$directMaintenanceId]);
            $singleMaintenance = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        // 9. Dynamic Content Translation & Cache (for non-English visitors)
        $currentLocale = \App\Core\I18n::getLocale();
        if ($currentLocale !== 'en') {
            $transService = new ContentTranslationService();

            if ($transService->isConfigured()) {
                // Translate Active Maintenances
                foreach ($maintenances as &$maint) {
                    $maint['title'] = $transService->getOrTranslate('maintenance', (int)$maint['id'], 'title', $maint['title'], $currentLocale);
                    if (!empty($maint['description'])) {
                        $maint['description'] = $transService->getOrTranslate('maintenance', (int)$maint['id'], 'description', $maint['description'], $currentLocale);
                    }
                }
                unset($maint);

                // Translate All Historical Maintenances
                foreach ($allMaintenances as &$maint) {
                    $maint['title'] = $transService->getOrTranslate('maintenance', (int)$maint['id'], 'title', $maint['title'], $currentLocale);
                    if (!empty($maint['description'])) {
                        $maint['description'] = $transService->getOrTranslate('maintenance', (int)$maint['id'], 'description', $maint['description'], $currentLocale);
                    }
                }
                unset($maint);

                // Translate Active Incidents
                foreach ($incidents as &$inc) {
                    $inc['title'] = $transService->getOrTranslate('incident', (int)$inc['id'], 'title', $inc['title'], $currentLocale);
                    if (!empty($inc['ai_summary'])) {
                        $inc['ai_summary'] = $transService->getOrTranslate('incident', (int)$inc['id'], 'ai_summary', $inc['ai_summary'], $currentLocale);
                    }
                    foreach ($inc['updates'] as &$up) {
                        $up['message'] = $transService->getOrTranslate('incident_update', (int)($up['id'] ?? $inc['id']), 'message', $up['message'], $currentLocale);
                    }
                    unset($up);
                }
                unset($inc);

                // Translate All Historical Incidents
                foreach ($allIncidents as &$inc) {
                    $inc['title'] = $transService->getOrTranslate('incident', (int)$inc['id'], 'title', $inc['title'], $currentLocale);
                    if (!empty($inc['ai_summary'])) {
                        $inc['ai_summary'] = $transService->getOrTranslate('incident', (int)$inc['id'], 'ai_summary', $inc['ai_summary'], $currentLocale);
                    }
                    foreach ($inc['updates'] as &$up) {
                        $up['message'] = $transService->getOrTranslate('incident_update', (int)($up['id'] ?? $inc['id']), 'message', $up['message'], $currentLocale);
                    }
                    unset($up);
                }
                unset($inc);

                // Translate Direct Link Incident
                if ($singleIncident) {
                    $singleIncident['title'] = $transService->getOrTranslate('incident', (int)$singleIncident['id'], 'title', $singleIncident['title'], $currentLocale);
                    if (!empty($singleIncident['ai_summary'])) {
                        $singleIncident['ai_summary'] = $transService->getOrTranslate('incident', (int)$singleIncident['id'], 'ai_summary', $singleIncident['ai_summary'], $currentLocale);
                    }
                    foreach ($singleIncident['updates'] as &$up) {
                        $up['message'] = $transService->getOrTranslate('incident_update', (int)($up['id'] ?? $singleIncident['id']), 'message', $up['message'], $currentLocale);
                    }
                    unset($up);
                }

                // Translate Direct Link Maintenance
                if ($singleMaintenance) {
                    $singleMaintenance['title'] = $transService->getOrTranslate('maintenance', (int)$singleMaintenance['id'], 'title', $singleMaintenance['title'], $currentLocale);
                    if (!empty($singleMaintenance['description'])) {
                        $singleMaintenance['description'] = $transService->getOrTranslate('maintenance', (int)$singleMaintenance['id'], 'description', $singleMaintenance['description'], $currentLocale);
                    }
                }
            }
        }

        View::render('public/index', [
            'monitors'          => $monitors,
            'primaryMonitors'   => $primaryMonitors,
            'secondaryMonitors' => $secondaryMonitors,
            'incidents'         => $incidents,
            'maintenances'      => $maintenances,
            'primaryStatus'     => $primaryStatus,
            'secondaryStatus'   => $secondaryStatus,
            'uptimeHistory'     => $uptimeHistory,
            'allIncidents'      => $allIncidents,
            'allMaintenances'   => $allMaintenances,
            'overallStatus'     => $primaryStatus,
            'singleIncident'    => $singleIncident,
            'singleMaintenance' => $singleMaintenance
        ], 'layouts/public');
    }

    public function verify(): void
    {
        $token = trim($_GET['token'] ?? '');
        if ($token) {
            $stmt = $this->db->prepare("UPDATE subscribers SET is_verified = 1 WHERE token = ?");
            $stmt->execute([$token]);
        }

        header('Location: /?verified=1');
        exit;
    }

    public function unsubscribe(): void
    {
        $token = trim($_GET['token'] ?? '');
        if ($token) {
            $stmt = $this->db->prepare("DELETE FROM subscribers WHERE token = ?");
            $stmt->execute([$token]);
        }

        header('Location: /?unsubscribed=1');
        exit;
    }

    public function subscribe(): void
    {
        $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $monitorId = !empty($_POST['monitor_id']) ? (int)$_POST['monitor_id'] : null;
        $smtpHost  = setting('smtp_host', '');

        if (!$email || empty($smtpHost)) {
            header('Location: /?error=' . urlencode('Email address invalid or SMTP not configured.'));
            exit;
        }

        $smtpConfig = [
            'host'       => $smtpHost,
            'port'       => setting('smtp_port', '587'),
            'username'   => setting('smtp_user', ''),
            'password'   => setting('smtp_pass', ''),
            'encryption' => setting('smtp_encryption', 'starttls'),
            'from_email' => setting('smtp_from', ''),
            'from_name'  => setting('app_name', 'My System Status')
        ];

        $mailer = new MailerService($smtpConfig);
        $subscriberService = new SubscriberService($mailer);
        $res = $subscriberService->subscribe($email, $monitorId);

        if ($res['status'] === 'error') {
            header('Location: /?sub_error=' . urlencode($res['message']));
        } else {
            header('Location: /?sub_success=' . urlencode($res['message']));
        }
        exit;
    }

    public function requestUnsubscribe(): void
    {
        $email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $smtpHost = setting('smtp_host', '');

        if ($email && !empty($smtpHost)) {
            $smtpConfig = [
                'host'       => $smtpHost,
                'port'       => setting('smtp_port', '587'),
                'username'   => setting('smtp_user', ''),
                'password'   => setting('smtp_pass', ''),
                'encryption' => setting('smtp_encryption', 'starttls'),
                'from_email' => setting('smtp_from', ''),
                'from_name'  => setting('app_name', 'My System Status')
            ];

            $mailer = new MailerService($smtpConfig);
            $subscriberService = new SubscriberService($mailer);
            $res = $subscriberService->requestUnsubscribeLink($email);

            if ($res['status'] === 'error') {
                header('Location: /?unsub_error=' . urlencode($res['message']));
            } else {
                header('Location: /?unsub_sent=1');
            }
            exit;
        }

        header('Location: /?unsub_error=' . urlencode('Please provide a valid email address.'));
        exit;
    }

    public function confirmUnsubscribe(): void
    {
        $token    = trim($_GET['token'] ?? '');
        $smtpHost = setting('smtp_host', '');

        if ($token && !empty($smtpHost)) {
            $smtpConfig = [
                'host'       => $smtpHost,
                'port'       => setting('smtp_port', '587'),
                'username'   => setting('smtp_user', ''),
                'password'   => setting('smtp_pass', ''),
                'encryption' => setting('smtp_encryption', 'starttls'),
                'from_email' => setting('smtp_from', ''),
                'from_name'  => setting('app_name', 'My System Status')
            ];

            $mailer = new MailerService($smtpConfig);
            $subscriberService = new SubscriberService($mailer);
            
            if ($subscriberService->confirmUnsubscribe($token)) {
                header('Location: /?unsub_success=1');
                exit;
            }
        }

        header('Location: /?unsub_error=' . urlencode('This unsubscribe link is invalid or has expired (links expire in 60 minutes).'));
        exit;
    }

    /**
     * AJAX Endpoint: Fetch detailed checks telemetry for a specific monitor and date.
     */
    public function getDayLogs(): void
    {
        header('Content-Type: application/json');

        $monitorId = (int)($_GET['monitor_id'] ?? 0);
        $date      = trim($_GET['date'] ?? '');

        if ($monitorId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
            exit;
        }

        $startWindow = "{$date} 00:00:00";
        $endWindow   = "{$date} 23:59:59";

        $stmt = $this->db->prepare("
            SELECT status, response_time_ms, http_code, error_message, created_at 
            FROM monitor_logs 
            WHERE monitor_id = ? AND created_at >= ? AND created_at <= ? 
            ORDER BY created_at DESC 
            LIMIT 2000
        ");
        $stmt->execute([$monitorId, $startWindow, $endWindow]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = [];
        foreach ($logs as $log) {
            $st = strtolower(trim($log['status'] ?? 'down'));

            if ($st === 'up') {
                $statusBadge = '<span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">UP</span>';
            } elseif ($st === 'blackout') {
                $statusBadge = '<span class="badge bg-dark text-white border border-light border-opacity-25 shadow-sm">BLACKOUT</span>';
            } elseif ($st === 'timeout') {
                $statusBadge = '<span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50">TIMEOUT</span>';
            } else {
                $statusBadge = '<span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">DOWN</span>';
            }

            $detailsHtml = !empty($log['error_message'])
                ? '<small class="text-danger fw-semibold">' . htmlspecialchars($log['error_message']) . '</small>'
                : '<small class="text-success"><i class="bi bi-check2"></i> Operational</small>';

            $timeFormatted = format_date($log['created_at'], 'H:i:s');

            $formatted[] = [
                'time'      => "<span class='font-monospace small text-body fw-medium'>{$timeFormatted}</span>",
                'status'    => $statusBadge,
                'latency'   => "<span class='font-monospace text-body'>" . (int)($log['response_time_ms'] ?? 0) . " ms</span>",
                'http_code' => "<code class='text-body-emphasis px-1 py-0 rounded border border-secondary border-opacity-25 bg-body-tertiary'>" . htmlspecialchars($log['http_code'] ?? '-') . "</code>",
                'details'   => $detailsHtml
            ];
        }

        echo json_encode(['success' => true, 'data' => $formatted]);
        exit;
    }
}

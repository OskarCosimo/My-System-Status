<?php
// path: app/Services/EdgeProbeService.php

namespace App\Services;

use App\Core\Database;
use PDO;

class EdgeProbeService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Dispatch custom pending monitors to Cloudflare Edge Worker
     */
    public function runEdgeChecks(): bool
    {
        $workerUrl   = setting('edge_worker_url');
        $workerToken = setting('edge_worker_token');

        if (empty($workerUrl) || empty($workerToken)) {
            return false;
        }

        // 1. Fetch only custom, active HTTP monitors that are not external plugin feeds
        $stmt = $this->db->query("
            SELECT id, name, target, timeout_seconds 
            FROM monitors 
            WHERE is_active = 1 
            AND type = 'http' 
            AND target NOT LIKE 'https://www.cloudflarestatus.com%'
            AND target NOT LIKE 'https://status.aws.amazon.com%'
            AND target NOT LIKE 'https://health.aws.amazon.com%'
            AND target NOT LIKE 'https://azure.status.microsoft%'
            AND target NOT LIKE 'https://status.stripe.com%'
            AND target NOT LIKE 'https://www.paypal-status.com%'
            AND target NOT LIKE 'https://www.githubstatus.com%'
            AND target NOT LIKE 'https://dash.cloudflare.com%'
            AND name NOT LIKE 'Tunnel:%'
        ");
        $monitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($monitors)) {
            return true;
        }

        // Explicitly set method to GET to mirror MonitorService behavior
        $targets = array_map(fn($m) => [
            'id'              => (int)$m['id'],
            'url'             => $m['target'],
            'method'          => 'GET',
            'timeout_seconds' => (int)$m['timeout_seconds']
        ], $monitors);

        // 2. Call Cloudflare Worker
        $ch = curl_init(rtrim($workerUrl, '/') . '/check');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['targets' => $targets]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $workerToken
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            throw new \Exception("Edge Worker check failed with HTTP {$httpCode}");
        }

        $data = json_decode((string)$response, true);
        if (empty($data['results'])) {
            return false;
        }

        // 3. Process Edge Results and record into database
        $edgeColo = $data['edge_colo'] ?? 'EDGE';
        foreach ($data['results'] as $res) {
            $monitorId  = (int)$res['id'];
            $status     = $res['status'];
            $duration   = (int)$res['response_time_ms'];
            $httpCode   = $res['http_code'];
            $error      = $res['error'] ? "{$res['error']} (via {$edgeColo})" : null;

            // Log entry
            $logStmt = $this->db->prepare("
                INSERT INTO monitor_logs (monitor_id, status, response_time_ms, http_code, error_message)
                VALUES (?, ?, ?, ?, ?)
            ");
            $logStmt->execute([$monitorId, $status, $duration, $httpCode, $error]);

            // Update monitor status
            $currentStatus = ($status === 'up') ? 'operational' : 'down';
            $upd = $this->db->prepare("UPDATE monitors SET current_status = ?, last_check = NOW() WHERE id = ?");
            $upd->execute([$currentStatus, $monitorId]);
        }

        return true;
    }
}

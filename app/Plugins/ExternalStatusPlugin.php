<?php
// path: app/Plugins/ExternalStatusPlugin.php

namespace App\Plugins;

use App\Core\Database;
use PDO;

class ExternalStatusPlugin
{
    private PDO $db;
    private bool $forceInsert = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Synchronize external status feeds.
     */
    public function syncAll(bool $forceInsert = false): array
    {
        $this->forceInsert = $forceInsert;

        // 0. Auto-cleanup: remove historical duplicates on same target
        $this->db->exec("
            DELETE m1 FROM monitors m1 
            INNER JOIN monitors m2 ON m1.target = m2.target 
            WHERE m1.id > m2.id
        ");

        $results = [];

        // 1. Cloudflare Public Services
        if (setting('feed_cloudflare_enabled', '1') === '1') {
            $results['cloudflare'] = $this->syncCloudflare();
        } else {
            $this->disableFeedMonitors('https://www.cloudflarestatus.com');
        }

        // 2. Custom Cloudflare Zero Trust Tunnel (Always check if credentials are set)
        $this->syncCustomCloudflareTunnel($forceInsert);

        // 3. Amazon AWS
        if (setting('feed_aws_enabled', '1') === '1') {
            $results['aws'] = $this->syncAws();
        } else {
            $this->disableFeedMonitors('https://health.aws.amazon.com');
        }

        // 4. Microsoft Azure
        if (setting('feed_azure_enabled', '1') === '1') {
            $results['azure'] = $this->syncAzure();
        } else {
            $this->disableFeedMonitors('https://azure.status.microsoft');
        }

        // 5. Stripe
        if (setting('feed_stripe_enabled', '1') === '1') {
            $results['stripe'] = $this->syncStripe();
        } else {
            $this->disableFeedMonitors('https://status.stripe.com');
        }

        // 6. PayPal
        if (setting('feed_paypal_enabled', '1') === '1') {
            $results['paypal'] = $this->syncPayPal();
        } else {
            $this->disableFeedMonitors('https://www.paypal-status.com');
        }

        // 7. GitHub
        if (setting('feed_github_enabled', '1') === '1') {
            $results['github'] = $this->syncGitHub();
        } else {
            $this->disableFeedMonitors('https://www.githubstatus.com');
        }

        return $results;
    }

    /**
     * Check Cloudflare Zero Trust Tunnel health with direct endpoints and fallback list
     */
    public function syncCustomCloudflareTunnel(bool $forceInsert = false): void
    {
        $accountId = trim(setting('cf_tunnel_account_id', ''));
        $tunnelId  = trim(setting('cf_tunnel_id', ''));
        $apiToken  = trim(setting('cf_tunnel_api_token', ''));

        if (empty($accountId) || empty($tunnelId) || empty($apiToken)) {
            return;
        }

        $customLabel = setting('cf_tunnel_name') ?: 'Zero Trust Tunnel';
        $isPrimary   = (setting('cf_tunnel_is_primary', '1') === '1') ? 1 : 0;
        $targetUrl   = "https://dash.cloudflare.com/{$accountId}/networks/tunnels/{$tunnelId}";
        $parentId    = $isPrimary ? null : $this->getCloudflareParentId();

        // 1. Try direct tunnel endpoints
        $endpoints = [
            "https://api.cloudflare.com/client/v4/accounts/{$accountId}/cfd_tunnel/{$tunnelId}",
            "https://api.cloudflare.com/client/v4/accounts/{$accountId}/tunnels/{$tunnelId}"
        ];

        $tunnelData = null;
        foreach ($endpoints as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$apiToken}",
                    "Content-Type: application/json"
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $json = json_decode($response, true);
                if (!empty($json['success']) && !empty($json['result'])) {
                    $tunnelData = $json['result'];
                    break;
                }
            }
        }

        // 2. Robust Fallback: search by ID or Name in account tunnel list (Crucial!)
        if (!$tunnelData) {
            $listUrl = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/cfd_tunnel?is_deleted=false";
            $ch = curl_init($listUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$apiToken}",
                    "Content-Type: application/json"
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $json = json_decode($response, true);
                if (!empty($json['success']) && !empty($json['result']) && is_array($json['result'])) {
                    foreach ($json['result'] as $t) {
                        if (
                            strcasecmp($t['id'] ?? '', $tunnelId) === 0 ||
                            strcasecmp($t['name'] ?? '', $tunnelId) === 0
                        ) {
                            $tunnelData = $t;
                            break;
                        }
                    }
                    if (!$tunnelData && count($json['result']) === 1) {
                        $tunnelData = $json['result'][0];
                    }
                }
            }
        }

        if ($tunnelData) {
            $rawStatus = strtolower($tunnelData['status'] ?? 'down');
            $status = match ($rawStatus) {
                'healthy', 'active', 'operational' => 'operational',
                'degraded'                         => 'degraded',
                default                            => 'down'
            };

            if (!empty($tunnelData['name']) && empty(setting('cf_tunnel_name'))) {
                $customLabel = $tunnelData['name'];
            }

            if (!empty($tunnelData['id'])) {
                $targetUrl = "https://dash.cloudflare.com/{$accountId}/networks/tunnels/{$tunnelData['id']}";
            }
        } else {
            // If Cloudflare API was completely unreachable, default to operational to avoid false down
            $status = 'operational';
        }

        // Always ensure the tunnel monitor exists if credentials are configured
        $this->upsertMonitor("Tunnel: {$customLabel}", $targetUrl, $status, $parentId, $isPrimary, true);
    }

    private function getCloudflareParentId(): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM monitors WHERE target = 'https://www.cloudflarestatus.com' LIMIT 1");
        $stmt->execute();
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function syncCloudflare(): string
    {
        $opts = ['http' => ['timeout' => 10, 'user_agent' => 'MySystemStatus/1.0']];
        $json = @file_get_contents('https://www.cloudflarestatus.com/api/v2/summary.json', false, stream_context_create($opts));
        if (!$json) return 'unknown';

        $data = json_decode($json, true);
        $overallIndicator = $data['status']['indicator'] ?? 'none';

        $parentStatus = match ($overallIndicator) {
            'none'  => 'operational',
            'minor' => 'degraded',
            default => 'down'
        };

        $parentId = $this->upsertMonitor('Cloudflare Global Network', 'https://www.cloudflarestatus.com', $parentStatus, null, 0);

        $coreKeywords = [
            'worker'        => ['name' => 'Workers & Pages Platform', 'slug' => 'workers'],
            'authoritative' => ['name' => 'Authoritative DNS Service', 'slug' => 'authoritative-dns'],
            'recursive'     => ['name' => 'Recursive DNS (1.1.1.1)', 'slug' => '1111-dns'],
            'cdn'           => ['name' => 'Edge CDN & Cache Network', 'slug' => 'cdn-cache'],
            'cache'         => ['name' => 'Edge CDN & Cache Network', 'slug' => 'cdn-cache'],
            'dashboard'     => ['name' => 'Dashboard & Control Panel API', 'slug' => 'dashboard-api'],
            'access'        => ['name' => 'Zero Trust, Access & Gateway', 'slug' => 'zero-trust'],
            'turnstile'     => ['name' => 'Turnstile Captcha Engine', 'slug' => 'turnstile'],
            'stream'        => ['name' => 'Cloudflare Stream Video', 'slug' => 'stream'],
            'warp'          => ['name' => 'WARP Client & Network', 'slug' => 'warp']
        ];

        $matchedSlugs = [];
        $components = $data['components'] ?? [];

        foreach ($components as $comp) {
            $name = $comp['name'] ?? '';
            if (str_contains($name, ' - ') || !empty($comp['group'])) {
                continue;
            }

            foreach ($coreKeywords as $keyword => $info) {
                if (stripos($name, $keyword) !== false && !in_array($info['slug'], $matchedSlugs, true)) {
                    $cStatus = match ($comp['status'] ?? '') {
                        'operational' => 'operational',
                        'degraded_performance', 'partial_outage' => 'degraded',
                        default => 'down'
                    };

                    $targetUrl = "https://www.cloudflarestatus.com#{$info['slug']}";
                    $this->upsertMonitor("Cloudflare - {$info['name']}", $targetUrl, $cStatus, $parentId, 0);
                    $matchedSlugs[] = $info['slug'];
                    break;
                }
            }
        }

        return $parentStatus;
    }

    public function syncAws(): string
    {
        $opts = ['http' => ['timeout' => 8, 'user_agent' => 'MySystemStatus/1.0']];
        $json = @file_get_contents('https://status.aws.amazon.com/data.json', false, stream_context_create($opts));

        $status = 'operational';
        if ($json) {
            $data = json_decode($json, true);
            if (!empty($data['current']) && is_array($data['current'])) {
                $status = 'degraded';
            }
        }

        $this->upsertMonitor('Amazon AWS Infrastructure', 'https://health.aws.amazon.com', $status, null, 0);
        return $status;
    }

    public function syncAzure(): string
    {
        $opts = ['http' => ['timeout' => 8, 'user_agent' => 'MySystemStatus/1.0']];
        $html = @file_get_contents('https://azure.status.microsoft/en-us/status', false, stream_context_create($opts));

        $status = 'operational';
        if ($html && (str_contains($html, 'warning-icon') || str_contains($html, 'Incident'))) {
            $status = 'degraded';
        }

        $this->upsertMonitor('Microsoft Azure Cloud', 'https://azure.status.microsoft', $status, null, 0);
        return $status;
    }

    public function syncPayPal(): string
    {
        $opts = ['http' => ['timeout' => 8, 'user_agent' => 'MySystemStatus/1.0']];
        $json = @file_get_contents('https://www.paypal-status.com/api/v1/components', false, stream_context_create($opts));

        $status = 'operational';
        if ($json) {
            $data = json_decode($json, true);
            if (is_array($data)) {
                foreach ($data as $item) {
                    $itemStatus = strtoupper($item['status'] ?? '');
                    if ($itemStatus !== 'OPERATIONAL' && $itemStatus !== '') {
                        $status = 'degraded';
                        break;
                    }
                }
            }
        }

        $this->upsertMonitor('PayPal Payments Engine', 'https://www.paypal-status.com', $status, null, 0);
        return $status;
    }

    public function syncStripe(): string
    {
        $json = @file_get_contents('https://status.stripe.com/current');
        $status = ($json && !str_contains($json, 'outage')) ? 'operational' : 'degraded';
        $this->upsertMonitor('Stripe Payments Engine', 'https://status.stripe.com', $status, null, 0);
        return $status;
    }

    public function syncGitHub(): string
    {
        $json = @file_get_contents('https://www.githubstatus.com/api/v2/status.json');
        $status = 'operational';

        if ($json) {
            $data = json_decode($json, true);
            $indicator = $data['status']['indicator'] ?? 'none';
            $status = ($indicator === 'none') ? 'operational' : 'degraded';
        }

        $this->upsertMonitor('GitHub Services', 'https://www.githubstatus.com', $status, null, 0);
        return $status;
    }

    /**
     * Update existing monitor or insert if missing.
     */
    private function upsertMonitor(string $name, string $target, string $status, ?int $parentId, int $isPrimary = 0, bool $alwaysCreate = false): int
    {
        $stmt = $this->db->prepare("
            SELECT id, is_active 
            FROM monitors 
            WHERE target = ? 
               OR (name = ? AND target LIKE '%dash.cloudflare.com%')
               OR target LIKE '%dash.cloudflare.com%' 
               OR name LIKE 'Tunnel:%' 
               OR name = ?
            LIMIT 1
        ");
        $stmt->execute([$target, $name, $name]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $monitorId = (int)$existing['id'];

            $update = $this->db->prepare("
                UPDATE monitors 
                SET name = ?, target = ?, current_status = ?, last_check = NOW(), parent_id = ?, is_primary = ?, is_active = 1, interval_seconds = COALESCE(interval_seconds, 60)
                WHERE id = ?
            ");
            $update->execute([$name, $target, $status, $parentId, $isPrimary, $monitorId]);

            $this->recordCheckLog($monitorId, $status);
            return $monitorId;
        }

        // Insert if forceInsert is active OR if it's the configured Tunnel
        if ($this->forceInsert || $alwaysCreate) {
            $insert = $this->db->prepare("
                INSERT INTO monitors (name, type, target, parent_id, sort_order, is_primary, current_status, interval_seconds, last_check, is_active) 
                VALUES (?, 'http', ?, ?, 99, ?, ?, 60, NOW(), 1)
            ");
            $insert->execute([$name, $target, $parentId, $isPrimary, $status]);
            $monitorId = (int)$this->db->lastInsertId();

            $this->recordCheckLog($monitorId, $status);
            return $monitorId;
        }

        return 0;
    }

    private function recordCheckLog(int $monitorId, string $status): void
    {
        // Map to valid MySQL ENUM values: 'up' for operational, 'down' for degraded/outage
        $shortStatus = ($status === 'operational') ? 'up' : 'down';
        $httpCode    = ($status === 'operational') ? 200 : (($status === 'degraded') ? 400 : 502);
        $errMessage  = ($status === 'operational') ? null : "Service reported {$status} performance";

        try {
            $stmt = $this->db->prepare("
                INSERT INTO monitor_logs (monitor_id, status, response_time_ms, http_code, error_message, created_at)
                VALUES (?, ?, 0, ?, ?, NOW())
            ");
            $stmt->execute([$monitorId, $shortStatus, $httpCode, $errMessage]);
        } catch (\Throwable $e) {
            // Silently ignore if check fails
        }
    }

    private function disableFeedMonitors(string $targetPrefix): void
    {
        $stmt = $this->db->prepare("UPDATE monitors SET is_active = 0 WHERE target LIKE ?");
        $stmt->execute(["{$targetPrefix}%"]);
    }
}

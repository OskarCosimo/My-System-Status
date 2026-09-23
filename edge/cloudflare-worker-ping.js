/**
 * path: edge/cloudflare-worker.js
 * Zero-Config Cloudflare Worker (No KV setup needed!)
 * Uses native built-in Cache API to track server outages & recovery.
 * Environment Variables:
 * - SHARED_SECRET_TOKEN: Shared secret string
 * - ORIGIN_STATUS_URL: e.g. "https://mysystemstatus.mywebsite.com"
 * - DISCORD_WEBHOOK_URL: Discord webhook URL
 */

export default {
    // 1. Probes Dispatcher: Called by your server cron
    async fetch(request, env) {
        if (request.method !== "POST") {
            return new Response("Method Not Allowed", { status: 405 });
        }

        const authHeader = request.headers.get("Authorization") || "";
        const expectedToken = "Bearer " + (env.SHARED_SECRET_TOKEN || "");
        if (authHeader !== expectedToken) {
            return new Response(JSON.stringify({ error: "Unauthorized" }), {
                status: 401,
                headers: { "Content-Type": "application/json" }
            });
        }

        try {
            const { targets } = await request.json();
            if (!Array.isArray(targets) || targets.length === 0) {
                return new Response(JSON.stringify({ error: "No targets provided" }), { status: 400 });
            }

            const edgeColo = request.cf?.colo || "EDGE";
            const results = await Promise.all(targets.map(t => checkTarget(t, edgeColo)));

            return new Response(JSON.stringify({
                edge_colo: edgeColo,
                timestamp: new Date().toISOString(),
                results: results
            }), { headers: { "Content-Type": "application/json" } });
        } catch (err) {
            return new Response(JSON.stringify({ error: err.message }), { status: 500 });
        }
    },

    // 2. Autonomous Sentinel: Runs every minute on Cloudflare Edge via Cron Trigger
    async scheduled(event, env) {
        const originUrl = env.ORIGIN_STATUS_URL;
        const discordWebhook = env.DISCORD_WEBHOOK_URL;
        const secretToken = env.SHARED_SECRET_TOKEN;

        if (!originUrl) return;

        let isServerUp = false;
        let failureReason = "";

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout

            // Use GET instead of HEAD to prevent WAF / Origin 403 or 405 blocks
            const res = await fetch(originUrl, {
                method: "GET",
                signal: controller.signal,
                headers: {
                    "User-Agent": "Mozilla/5.0 (compatible; MySystemStatus-Sentinel/1.0; +https://mysystemstatus.myetv.tv)",
                    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
                }
            });
            clearTimeout(timeoutId);

            if (res.status < 500) {
                isServerUp = true;
            } else {
                failureReason = `HTTP ${res.status}`;
            }
        } catch (err) {
            failureReason = err.name === "AbortError" ? "Connection Timeout" : err.message;
        }

        // Check state from zero-config Cache API
        const stateKey = "http://storage.internal/server_down_since";
        const cache = caches.default;
        const cachedRes = await cache.match(stateKey);
        const downSince = cachedRes ? await cachedRes.text() : null;

        if (!isServerUp) {
            // SERVER IS DOWN
            if (!downSince) {
                const nowIso = new Date().toISOString();
                // Save to cache (valid 7 days)
                await cache.put(stateKey, new Response(nowIso, { headers: { "Cache-Control": "max-age=604800" } }));

                if (discordWebhook) {
                    await notifyDiscord(discordWebhook, `🚨 **CRITICAL OUTAGE**: Origin server (${originUrl}) is DOWN!\nReason: ${failureReason}\nDetected at: ${nowIso}`);
                }
            }
        } else {
            // SERVER IS BACK ONLINE
            if (downSince) {
                const downDate = new Date(downSince);
                const recoveryDate = new Date();
                const downtimeMs = recoveryDate.getTime() - downDate.getTime();
                const downtimeMinutes = Math.max(1, Math.round(downtimeMs / 60000));

                // 1. Send recovery report to server database
                try {
                    await fetch(`${originUrl}/api/v1/edge/recovery`, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Authorization": "Bearer " + secretToken
                        },
                        body: JSON.stringify({
                            down_since: downSince,
                            recovered_at: recoveryDate.toISOString(),
                            downtime_minutes: downtimeMinutes,
                            reason: "Edge Outage Detected"
                        })
                    });
                } catch (e) {
                    // Retry next cycle if origin is still booting
                }

                // 2. Alert Discord
                if (discordWebhook) {
                    await notifyDiscord(discordWebhook, `✅ **RECOVERED**: Origin server (${originUrl}) is back ONLINE!\n⏱️ **Downtime**: ${downtimeMinutes} minute(s)\nPeriod: ${downDate.toUTCString()} &mdash; ${recoveryDate.toUTCString()}`);
                }

                // 3. Clear cache
                await cache.delete(stateKey);
            }
        }
    }
};

async function checkTarget(target, colo) {
    const start = Date.now();
    try {
        const controller = new AbortController();
        const timeoutSeconds = target.timeout_seconds || 10;
        const timeout = setTimeout(() => controller.abort(), timeoutSeconds * 1000);

        // Default to GET to avoid servers blocking HEAD requests
        const httpMethod = target.method || "GET";

        const res = await fetch(target.url, {
            method: httpMethod,
            signal: controller.signal,
            redirect: "follow",
            headers: {
                "User-Agent": "Mozilla/5.0 (compatible; MySystemStatus-EdgeProbe/1.0; +" + colo + ")",
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
            }
        });
        clearTimeout(timeout);

        const duration = Date.now() - start;
        const isUp = res.status >= 200 && res.status < 400;

        return {
            id: target.id,
            status: isUp ? "up" : "down",
            http_code: res.status,
            response_time_ms: duration,
            colo: colo,
            error: isUp ? null : "HTTP " + res.status
        };
    } catch (err) {
        return {
            id: target.id,
            status: "down",
            http_code: null,
            response_time_ms: Date.now() - start,
            colo: colo,
            error: err.name === "AbortError" ? "Timeout after " + target.timeout_seconds + "s" : err.message
        };
    }
}

async function notifyDiscord(webhookUrl, message) {
    await fetch(webhookUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            content: message,
            username: "My System Status Sentinel (Cloudflare Edge)"
        })
    });
}

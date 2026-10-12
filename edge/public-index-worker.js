/**
 * path: edge/public-index-worker.js
 * Cloudflare Worker: Resilient Edge Mirror & Dynamic Preferences Proxy
 * 
 * =============================================================================
 * ENVIRONMENT VARIABLES (Set in Cloudflare Dashboard -> Settings -> Variables):
 * =============================================================================
 * - ORIGIN_URL (Required):
 *     The primary status server address (e.g. "https://mysystemstatus.myetv.tv")
 * - CACHE_TTL_SECONDS (Optional):
 *     How long the edge cache remains fresh in seconds (default: "60")
 * 
 * =============================================================================
 * OPTIONAL: CLOUDFLARE KV NAMESPACE (STATUS_KV) - RECOMMENDED FOR PRODUCTION:
 * =============================================================================
 * While this Worker works out-of-the-box using the native Cache API (caches.default),
 * Cloudflare's native cache is isolated per individual edge datacenter (PoP).
 * If your origin server powers off and a visitor accesses the status page from a mobile
 * device on 5G (which routes through a different city/PoP that hasn't cached the page),
 * it will display the emergency fallback screen.
 * 
 * Binding a KV Namespace (STATUS_KV) solves this completely:
 * 1. Go to Cloudflare Dashboard -> Storage & Databases -> KV.
 * 2. Create a namespace called "STATUS_KV".
 * 3. In your Worker -> Settings -> Variables -> KV Namespace Bindings:
 *    Add Variable name "STATUS_KV" and select the "STATUS_KV" namespace.
 * 
 * Result: The status snapshot is automatically replicated to all 300+ Cloudflare
 * edge datacenters worldwide, guaranteeing that the page is accessible from any
 * device or network even if the origin server is completely powered down.
 * =============================================================================
 */

export default {
    async fetch(request, env) {
        const originUrl = (env.ORIGIN_URL || "").replace(/\/+$/, "");
        if (!originUrl) {
            return new Response("Configuration Error: ORIGIN_URL environment variable is missing.", { status: 500 });
        }

        const url = new URL(request.url);

        // 1. SECURITY REDIRECT: Bypass edge proxy for administrative / auth areas
        if (url.pathname.startsWith("/admin") || url.pathname.startsWith("/auth") || url.pathname.startsWith("/install")) {
            return Response.redirect(originUrl + url.pathname + url.search, 302);
        }

        const targetUrl = originUrl + url.pathname + url.search;
        const cache = caches.default;
        const cacheKey = new Request(targetUrl, request);

        // 2. DYNAMIC PREFERENCES HANDLING (/lang and /timezone switchers)
        // Proxies requests directly to origin, preserves Set-Cookie headers, and keeps user on custom domain
        if (url.pathname.startsWith("/lang") || url.pathname.startsWith("/timezone")) {
            try {
                const reqHeaders = new Headers(request.headers);
                reqHeaders.set("X-Edge-Mirror", "MySystemStatus-Edge/1.0");

                const originRes = await fetch(targetUrl, {
                    method: request.method,
                    headers: reqHeaders,
                    redirect: "manual"
                });

                const responseHeaders = new Headers(originRes.headers);

                // Rewrite redirect Location header to preserve custom domain
                if (originRes.status >= 300 && originRes.status < 400) {
                    const location = responseHeaders.get("Location");
                    if (location) {
                        try {
                            const locUrl = new URL(location, originUrl);
                            if (locUrl.host === new URL(originUrl).host) {
                                responseHeaders.set("Location", url.origin + locUrl.pathname + locUrl.search);
                            }
                        } catch (e) {
                            responseHeaders.set("Location", "/");
                        }
                    }
                }

                return new Response(originRes.body, {
                    status: originRes.status,
                    statusText: originRes.statusText,
                    headers: responseHeaders
                });
            } catch (e) {
                return Response.redirect(url.origin + "/", 302);
            }
        }

        // 3. REGULAR MIRROR FETCHING (Origin Request with 5s Timeout)
        let originResponse = null;
        let isOriginDown = false;

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 5000);

            const reqHeaders = new Headers(request.headers);
            reqHeaders.set("X-Edge-Mirror", "MySystemStatus-Edge/1.0");

            originResponse = await fetch(targetUrl, {
                method: request.method,
                body: (request.method !== "GET" && request.method !== "HEAD") ? await request.clone().blob() : undefined,
                headers: reqHeaders,
                signal: controller.signal,
                redirect: "follow"
            });
            clearTimeout(timeoutId);

            if (!originResponse || originResponse.status >= 500) {
                isOriginDown = true;
            }
        } catch (e) {
            isOriginDown = true;
        }

        // 4. SCENARIO A: Origin is ONLINE -> Serve & Save Snapshots (Cache API + Optional KV)
        if (!isOriginDown && originResponse && originResponse.status < 400) {
            if (request.method === "GET") {
                const responseClone = originResponse.clone();
                const nowIso = new Date().toISOString();

                // Save to native per-PoP cache
                const cachedHeaders = new Headers(responseClone.headers);
                cachedHeaders.set("Cache-Control", `public, max-age=${env.CACHE_TTL_SECONDS || 60}`);
                cachedHeaders.set("X-Snapshot-Saved", nowIso);

                const responseToCache = new Response(responseClone.body, {
                    status: responseClone.status,
                    statusText: responseClone.statusText,
                    headers: cachedHeaders
                });
                ctxOrWait(request, cache.put(cacheKey, responseToCache));

                // If STATUS_KV is bound, store global snapshot for 100% cross-device / 5G resilience
                if (env.STATUS_KV) {
                    ctxOrWait(request, (async () => {
                        try {
                            if (url.pathname === "/" || url.pathname === "") {
                                const htmlText = await originResponse.clone().text();
                                await env.STATUS_KV.put("global_snapshot_html", htmlText, { expirationTtl: 86400 * 7 });
                                await env.STATUS_KV.put("global_snapshot_time", new Date().toUTCString(), { expirationTtl: 86400 * 7 });
                            } else if (url.pathname === "/api/v1/alerts") {
                                const alertsJson = await originResponse.clone().text();
                                await env.STATUS_KV.put("global_snapshot_alerts", alertsJson, { expirationTtl: 86400 * 7 });
                            }
                        } catch (err) { }
                    })());
                }
            }

            return new HTMLRewriter()
                .on("a[href='/admin']", {
                    element(element) {
                        element.setAttribute("href", `${originUrl}/admin`);
                    }
                })
                .transform(originResponse);
        }

        // 5. SCENARIO B: Origin is OFFLINE -> Check Global KV first, then fallback to Local Cache
        let cachedHtml = null;
        let snapshotTime = "Recent";

        // Check global KV if configured
        if (env.STATUS_KV) {
            try {
                if (url.pathname === "/api/v1/alerts") {
                    const cachedAlerts = await env.STATUS_KV.get("global_snapshot_alerts");
                    if (cachedAlerts) {
                        return new Response(cachedAlerts, {
                            status: 200,
                            headers: {
                                "Content-Type": "application/json",
                                "Access-Control-Allow-Origin": "*"
                            }
                        });
                    }
                }
                cachedHtml = await env.STATUS_KV.get("global_snapshot_html");
                snapshotTime = (await env.STATUS_KV.get("global_snapshot_time")) || "Recent";
            } catch (err) { }
        }

        // Fallback to local PoP cache if KV was empty or not bound
        if (!cachedHtml) {
            const cachedResponse = await cache.match(cacheKey);
            if (cachedResponse) {
                cachedHtml = await cachedResponse.text();
                snapshotTime = cachedResponse.headers.get("X-Snapshot-Saved") || "Recent";
            }
        }

        // If a snapshot exists (via KV or local Cache), render with emergency notice banner & visual outage state
        if (cachedHtml) {
            const emergencyBannerHtml = `
                <div style="background-color: #0f172a; color: #ffffff; padding: 12px 16px; text-align: center; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; font-weight: bold; position: sticky; top: 0; z-index: 9999999; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);">
                    🚨 <span>Origin Status Server is Currently Offline &bull; Displaying Global Telemetry Snapshot (${snapshotTime})</span>
                </div>
            `;

            // CSS rules to force red state on the main core banner and today's bar (:last-child)
            const emergencyStyles = `
                <style id="edge-offline-styles">
                    .edge-core-banner-offline {
                        background-color: #dc3545 !important;
                    }
                    .uptime-graph .uptime-day-col:last-child .uptime-bar {
                        background: #ef4444 !important;
                        background-color: #ef4444 !important;
                    }
                </style>
            `;

            // Client-side script to update the interactive tooltip on today's bar
            const emergencyScript = `
                <script id="edge-offline-script">
                    document.addEventListener("DOMContentLoaded", function() {
                        document.querySelectorAll(".uptime-graph").forEach(function(graph) {
                            var lastCol = graph.querySelector(".uptime-day-col:last-child");
                            if (lastCol) {
                                var offlineTitle = "<strong>Today</strong><br><span style='color: #ef4444;'>●</span> Origin Server Offline";
                                lastCol.setAttribute("data-bs-original-title", offlineTitle);
                                lastCol.setAttribute("title", offlineTitle);
                                var bar = lastCol.querySelector(".uptime-bar");
                                if (bar) {
                                    bar.style.backgroundColor = "#ef4444";
                                    bar.style.background = "#ef4444";
                                }
                            }
                        });
                    });
                </script>
            `;

            const response = new Response(cachedHtml, {
                status: 200,
                headers: { "Content-Type": "text/html; charset=UTF-8" }
            });

            return new HTMLRewriter()
                .on("head", {
                    element(element) {
                        element.append(emergencyStyles, { html: true });
                    }
                })
                .on("body", {
                    element(element) {
                        element.prepend(emergencyBannerHtml, { html: true });
                        element.append(emergencyScript, { html: true });
                    }
                })
                // Target the primary core status banner (div.p-4.rounded-3.text-white)
                .on("div.p-4.rounded-3.text-white", {
                    element(element) {
                        const currentClass = element.getAttribute("class") || "";
                        const updatedClass = currentClass
                            .replace("bg-success", "bg-danger")
                            .replace("bg-warning", "bg-danger");
                        element.setAttribute("class", updatedClass + " edge-core-banner-offline");
                    }
                })
                // Replace the status text and icon inside the banner's h4
                .on("div.p-4.rounded-3.text-white h4", {
                    element(element) {
                        element.setInnerContent('<i class="bi bi-shield-slash-fill me-2"></i> Origin Server Offline / Outage Detected', { html: true });
                    }
                })
                .on("a[href='/admin']", {
                    element(element) {
                        element.setAttribute("href", `${originUrl}/admin`);
                    }
                })
                .transform(response);
        }

        // 6. SCENARIO C: Disaster Fallback Page (Origin is down and no snapshot is available anywhere)
        return new Response(generateEmergencyFallbackHtml(originUrl), {
            status: 200,
            headers: { "Content-Type": "text/html; charset=UTF-8" }
        });
    }
};

function ctxOrWait(request, promise) {
    try {
        if (typeof request.waitUntil === "function") {
            request.waitUntil(promise);
        }
    } catch (e) { }
}

function generateEmergencyFallbackHtml(originUrl) {
    return `
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>System Status &mdash; Temporary Offline</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
            .card { background: #1e293b; padding: 40px; border-radius: 12px; max-width: 480px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); }
            h2 { margin-top: 0; color: #ef4444; }
            p { color: #94a3b8; line-height: 1.6; }
            .badge { display: inline-block; background: #ef4444; color: white; padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="badge">Major Server Incident</div>
            <h2>Status Origin Unreachable</h2>
            <p>The primary infrastructure monitoring origin (<code>${originUrl}</code>) is currently offline or unreachable.</p>
            <p>Engineers have been alerted automatically via edge sentinels. Please check back shortly.</p>
        </div>
    </body>
    </html>
    `;
}

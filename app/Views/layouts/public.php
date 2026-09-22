<!-- path: app/Views/layouts/public.php -->
<?php
use App\Services\SettingService;
use App\Services\DateService;
use App\Core\I18n;

$appName = htmlspecialchars(setting('app_name', 'My System Status'));
$appUrl  = app_url();
$userTz  = DateService::getActiveTimezone();
$locale  = I18n::getLocale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <title><?= $appName ?> &mdash; Status</title>

    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            color: #1e293b;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand {
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .footer {
            margin-top: auto;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 24px 0;
        }

        /* Modern 90-Day Uptime Graph Styles */
        .uptime-graph {
            display: flex;
            gap: 2px;
            align-items: center;
            height: 32px;
            padding: 2px 0;
        }
        .uptime-bar {
            flex: 1 1 0;
            height: 100%;
            border-radius: 3px;
            transition: transform 0.12s ease, opacity 0.12s ease;
            cursor: pointer;
        }
        .uptime-bar:hover {
            transform: scaleY(1.35);
            opacity: 0.85;
            z-index: 10;
        }
        .uptime-operational { background-color: #10b981; } /* Green */
        .uptime-degraded    { background-color: #f59e0b; } /* Amber */
        .uptime-incident    { background-color: #f97316; } /* Orange */
        .uptime-maintenance { background-color: #0ea5e9; } /* Azure Blue */
        .uptime-outage      { background-color: #ef4444; } /* Red */
        .uptime-blackout    { background-color: #0f172a; } /* Black */
        .uptime-nodata      { background-color: #e2e8f0; } /* Gray */

        /* Custom Tooltip Styling */
        .tooltip-inner {
            background-color: #0f172a;
            color: #ffffff;
            padding: 6px 10px;
            font-size: 12px;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<!-- Public Top Navigation -->
<nav class="navbar navbar-expand-lg bg-white border-bottom py-3 shadow-sm">
    <div class="container" style="max-width: 900px;">
        <a class="navbar-brand d-flex align-items-center gap-2 text-dark text-decoration-none" href="/">
            <i class="bi bi-shield-check text-primary fs-3"></i>
            <span class="fs-4"><?= $appName ?></span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Timezone Switcher -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" title="Timezone">
                    <i class="bi bi-clock"></i>
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($userTz) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 280px; overflow-y: auto;">
                    <li><h6 class="dropdown-header">Select Timezone</h6></li>
                    <?php foreach (['UTC', 'Europe/Rome', 'Europe/London', 'Europe/Paris', 'America/New_York', 'America/Los_Angeles', 'Asia/Tokyo'] as $tz): ?>
                        <li>
                            <a class="dropdown-item <?= $userTz === $tz ? 'active fw-bold' : '' ?>" href="/timezone/set?timezone=<?= urlencode($tz) ?>">
                                <?= $tz ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Language Switcher -->
            <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle text-uppercase" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-translate me-1"></i><?= \App\Core\I18n::getLocale() ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <?php foreach (SettingService::getEnabledLocales() as $code => $info): ?>
            <li>
                <a class="dropdown-item <?= \App\Core\I18n::getLocale() === $code ? 'active fw-bold' : '' ?>" href="/lang/switch?lang=<?= $code ?>">
                    <?= htmlspecialchars($info['name']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

            <!-- Admin Login Link -->
            <a href="/admin" class="btn btn-sm btn-primary">
                <i class="bi bi-person-fill-lock me-1"></i> Admin
            </a>
        </div>
    </div>
</nav>

<!-- Page Content Injection -->
<main class="flex-grow-1">
    <?= $content ?>
</main>

<!-- Public Footer -->
<footer class="footer">
    <div class="container text-center text-muted small" style="max-width: 900px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <span>&copy; <?= date('Y') ?> <?= $appName ?>. All rights reserved.</span>
            <div class="d-flex gap-3 mt-2 mt-sm-0">
                <a href="/api/v1/alerts" target="_blank" class="text-decoration-none text-muted">API</a>
                <a href="https://github.com/myetv/my-system-status" target="_blank" class="text-decoration-none text-muted">Powered by My System Status</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Initialize Bootstrap Tooltips with HTML Support -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el, { html: true }));
});
</script>
<script>
    if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .catch((error) => {
        // Service worker registration failed
      });
  });
}
</script>
</body>
</html>

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
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="/manifest.json">
    <title><?= $appName ?> &mdash; Status</title>

    <!-- Early Theme Switcher Execution to Prevent FOUC -->
    <script>
        (() => {
            'use strict';
            const storedTheme = localStorage.getItem('theme') || 'auto';
            const systemScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', storedTheme === 'auto' ? systemScheme : storedTheme);
        })();
    </script>

    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: var(--bs-body-tertiary-bg);
            color: var(--bs-body-color);
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
            border-top: 1px solid var(--bs-border-color);
            background: var(--bs-body-bg);
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
        .uptime-nodata      { background-color: var(--bs-secondary-bg); } /* Gray */

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
<nav class="navbar navbar-expand-lg bg-body border-bottom py-3 shadow-sm">
    <div class="container" style="max-width: 900px;">
        <a class="navbar-brand d-flex align-items-center gap-2 text-body text-decoration-none" href="/">
            <i class="bi bi-shield-check text-primary fs-3"></i>
            <span class="fs-4"><?= $appName ?></span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <!-- Theme Switcher (Light / Dark / Auto) -->
            <div class="dropdown theme-switcher">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" id="bd-theme-public" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Toggle Theme">
                    <i class="bi bi-circle-half theme-icon-active"></i>
                    <span class="visually-hidden" id="bd-theme-text-public">Toggle Theme</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="bd-theme-text-public">
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-bs-theme-value="light">
                            <i class="bi bi-sun-fill opacity-50"></i>
                            Light
                            <i class="bi bi-check2 ms-auto d-none check-mark"></i>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-bs-theme-value="dark">
                            <i class="bi bi-moon-stars-fill opacity-50"></i>
                            Dark
                            <i class="bi bi-check2 ms-auto d-none check-mark"></i>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2" data-bs-theme-value="auto">
                            <i class="bi bi-circle-half opacity-50"></i>
                            Auto
                            <i class="bi bi-check2 ms-auto d-none check-mark"></i>
                        </button>
                    </li>
                </ul>
            </div>

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

<!-- Initialize Bootstrap Tooltips & Theme Switcher Handler -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el, { html: true }));

    // Theme Switcher Logic
    const getStoredTheme = () => localStorage.getItem('theme') || 'auto';
    const setStoredTheme = (theme) => localStorage.setItem('theme', theme);

    const themeIcons = {
        light: 'bi-sun-fill',
        dark: 'bi-moon-stars-fill',
        auto: 'bi-circle-half'
    };

    const applyTheme = (theme) => {
        if (theme === 'auto') {
            const systemScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-bs-theme', systemScheme);
        } else {
            document.documentElement.setAttribute('data-bs-theme', theme);
        }
    };

    const updateThemeUi = (theme) => {
        document.querySelectorAll('.theme-switcher').forEach((container) => {
            const activeIcon = container.querySelector('.theme-icon-active');
            if (activeIcon) {
                activeIcon.className = `bi ${themeIcons[theme] || 'bi-circle-half'} theme-icon-active`;
            }

            container.querySelectorAll('[data-bs-theme-value]').forEach((btn) => {
                const isSelected = btn.getAttribute('data-bs-theme-value') === theme;
                btn.classList.toggle('active', isSelected);
                btn.classList.toggle('fw-bold', isSelected);

                const checkMark = btn.querySelector('.check-mark');
                if (checkMark) {
                    checkMark.classList.toggle('d-none', !isSelected);
                }
            });
        });
    };

    const currentTheme = getStoredTheme();
    applyTheme(currentTheme);
    updateThemeUi(currentTheme);

    document.querySelectorAll('[data-bs-theme-value]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const selectedTheme = toggle.getAttribute('data-bs-theme-value');
            setStoredTheme(selectedTheme);
            applyTheme(selectedTheme);
            updateThemeUi(selectedTheme);
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getStoredTheme() === 'auto') {
            applyTheme('auto');
        }
    });
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

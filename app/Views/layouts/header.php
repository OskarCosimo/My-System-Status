<!-- path: app/Views/layouts/header.php -->
<?php
use App\Services\SettingService;

$appName = setting('app_name', 'My System Status');
$baseUrl = app_url();
$title   = $pageTitle ?? $appName;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="manifest" href="/manifest.json">
<title><?= htmlspecialchars($title) ?></title>

<!-- Favicon -->
<link rel="icon" type="image/png" href="<?= $baseUrl ?>/assets/img/favicon.png">

<!-- Early Theme Switcher Execution to Prevent FOUC -->
<script>
    (() => {
        'use strict';
        const getStoredTheme = () => localStorage.getItem('theme');
        const getPreferredTheme = () => {
            const storedTheme = getStoredTheme();
            if (storedTheme) {
                return storedTheme;
            }
            return 'auto';
        };

        const applyTheme = (theme) => {
            if (theme === 'auto') {
                const systemScheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', systemScheme);
            } else {
                document.documentElement.setAttribute('data-bs-theme', theme);
            }
        };

        applyTheme(getPreferredTheme());
    })();
</script>

<!-- Bootstrap 5 CSS & Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
    #wrapper {
        min-height: 100vh;
        display: flex;
        overflow-x: hidden;
    }

    /* Sidebar full width & smooth sliding animation */
    #sidebar-wrapper {
        min-height: 100vh;
        width: 250px;
        min-width: 250px;
        transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        overflow: hidden;
        z-index: 1000;
    }

    /* Collapsed state (icon-only mode) */
    #sidebar-wrapper.collapsed {
        width: 72px;
        min-width: 72px;
    }

    #sidebar-wrapper.collapsed .sidebar-text,
    #sidebar-wrapper.collapsed .sidebar-brand-text {
        display: none !important;
    }

    .sidebar-link {
        color: var(--bs-body-color);
        padding: 10px 16px;
        display: flex;
        align-items: center;
        text-decoration: none;
        border-radius: 6px;
        margin: 2px 8px;
        font-size: 0.95rem;
        transition: all 0.2s ease-in-out;
    }

    .sidebar-link:hover, .sidebar-link.active {
        background-color: var(--bs-tertiary-bg);
        color: var(--bs-primary);
        font-weight: 600;
    }

    .sidebar-link i {
        font-size: 1.25rem;
        margin-right: 14px;
        min-width: 24px;
        text-align: center;
        transition: margin 0.2s ease;
    }

    /* Center icons when sidebar is collapsed */
    #sidebar-wrapper.collapsed .sidebar-link {
        justify-content: center;
        padding: 12px 0;
        margin: 4px 10px;
    }

    #sidebar-wrapper.collapsed .sidebar-link i {
        margin-right: 0;
    }

    #sidebar-wrapper.collapsed .sidebar-heading {
        justify-content: center;
        padding: 15px 0 !important;
    }

    #page-content-wrapper {
        min-width: 0;
        width: 100%;
        overflow-x: hidden;
    }

    .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
</style>
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

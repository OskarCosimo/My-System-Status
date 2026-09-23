<!-- path: app/Views/layouts/admin.php -->
<?php
use App\Services\SettingService;
use App\Services\DateService;

$appName = htmlspecialchars(setting('app_name', 'My System Status'));
$appUrl  = app_url();
$userTz  = DateService::getActiveTimezone();
?>
<!DOCTYPE html>
<html lang="<?= \App\Core\I18n::getLocale() ?>">
<head>
    <?php require __DIR__ . '/header.php'; ?>
    <style>
        /* Mobile Navbar adjustments */
        @media (max-width: 576px) {
            .admin-navbar {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
            .admin-navbar .btn-sm {
                padding: 0.25rem 0.45rem;
                font-size: 0.8125rem;
            }
        }
    </style>
</head>
<body class="bg-body-tertiary">

<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper" class="w-100 min-vw-0">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand bg-body border-bottom px-2 px-md-4 py-2 shadow-sm admin-navbar">
            <button class="btn btn-sm btn-outline-secondary flex-shrink-0" id="sidebarToggle" title="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>

            <!-- Action buttons container (wraps cleanly without overflow) -->
            <div class="ms-auto d-flex align-items-center gap-1 gap-sm-2 gap-md-3 flex-nowrap">
                <!-- Public Page Link -->
                <a href="<?= $appUrl ?>/" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap" title="<?= __('nav.view_public_page') ?>">
                    <i class="bi bi-box-arrow-up-right"></i>
                    <span class="d-none d-md-inline ms-1"><?= __('nav.view_public_page') ?></span>
                </a>

                <!-- Theme Switcher (Light / Dark / Auto) -->
                <div class="dropdown theme-switcher">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Toggle Theme">
                        <i class="bi bi-circle-half theme-icon-active"></i>
                        <span class="visually-hidden" id="bd-theme-text">Toggle Theme</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="bd-theme-text">
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

                <!-- Timezone Selector -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle text-nowrap" type="button" data-bs-toggle="dropdown" title="Change Timezone">
                        <i class="bi bi-clock"></i>
                        <span class="d-none d-lg-inline ms-1"><?= htmlspecialchars($userTz) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 280px; overflow-y: auto;">
                        <li><h6 class="dropdown-header">Timezone</h6></li>
                        <?php foreach (['UTC', 'Europe/Rome', 'Europe/London', 'America/New_York', 'America/Los_Angeles', 'Asia/Tokyo'] as $tz): ?>
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
                    <button class="btn btn-sm btn-secondary dropdown-toggle text-nowrap" type="button" data-bs-toggle="dropdown" title="Language">
                        <i class="bi bi-translate"></i>
                        <span class="d-none d-sm-inline ms-1"><?= strtoupper(\App\Core\I18n::getLocale()) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <?php foreach (SettingService::getEnabledLocales() as $code => $info): ?>
                            <li>
                                <a class="dropdown-item <?= \App\Core\I18n::getLocale() === $code ? 'active fw-bold' : '' ?>" href="/lang/switch?lang=<?= $code ?>">
                                    <?= htmlspecialchars($info['name']) ?> (<?= $info['flag'] ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- User Profile Link -->
                <a href="/admin/profile" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1 text-decoration-none text-nowrap" title="Edit Profile, Password & 2FA">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span class="fw-semibold d-none d-sm-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                </a>

                <!-- Logout Button -->
                <a href="/auth/logout" class="btn btn-sm btn-danger flex-shrink-0" title="Sign Out">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </nav>

        <!-- Main View Dynamic Container -->
        <main class="container-fluid p-3 p-md-4">
            <?= $content ?? '' ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>

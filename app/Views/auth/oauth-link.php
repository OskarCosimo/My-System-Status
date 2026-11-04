<!-- path: app/Views/auth/oauth-link.php -->
<?php
$appName  = htmlspecialchars(setting('app_name', 'My System Status'));
$pending  = $pending ?? [];
$provider = htmlspecialchars(strtoupper($pending['provider'] ?? 'SSO'));
$ssoName  = htmlspecialchars($pending['name'] ?? '');
$ssoEmail = htmlspecialchars($pending['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link SSO Account &mdash; <?= $appName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center py-4" style="min-height: 100vh;">
<div class="container" style="max-width: 440px;">
    <div class="card border-0 shadow-sm rounded-4 p-3">
        <div class="card-body">
            <div class="text-center mb-3">
                <i class="bi bi-person-fill-lock text-primary" style="font-size: 3rem;"></i>
                <h4 class="fw-bold mt-2">Link <?= $provider ?> Account</h4>
                <p class="text-muted small mb-0">Verify your existing administrator credentials to link this identity.</p>
            </div>

            <!-- SSO Identity Badge -->
            <div class="alert alert-primary border-primary-subtle py-2 px-3 small mb-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-4 text-primary"></i>
                    <div>
                        <div class="fw-bold text-body">Authenticated via <?= $provider ?></div>
                        <div class="text-body-secondary"><?= $ssoName ? $ssoName . ' &bull; ' : '' ?><?= $ssoEmail ?: 'ID Verified' ?></div>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'rate_limited'): ?>
                <div class="alert alert-danger py-2 small text-center">
                    <i class="bi bi-shield-x me-1"></i>
                    Too many failed attempts. Locked for <strong><?= htmlspecialchars($_GET['wait'] ?? '15') ?> minutes</strong>.
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials'): ?>
                <div class="alert alert-danger py-2 small text-center">
                    <i class="bi bi-x-circle me-1"></i>
                    Invalid administrator email or password. Please verify and try again.
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'missing_fields'): ?>
                <div class="alert alert-danger py-2 small text-center">
                    Please provide both your administrator email and password.
                </div>
            <?php endif; ?>

            <form action="/auth/oauth-link-verify" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Administrator Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $ssoEmail ?>" required autofocus placeholder="admin@example.com">
                    <small class="text-muted" style="font-size: 11px;">You can change this email if your admin account uses a different address.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Administrator Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    <small class="text-muted" style="font-size: 11px;">Required once to authorize permanent SSO association.</small>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMeLink" value="1" checked>
                    <label class="form-check-label small text-muted user-select-none" for="rememberMeLink">
                        Stay signed in on this device
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-link-45deg me-1"></i> Authorize &amp; Link Account
                </button>
            </form>

            <div class="text-center mt-3 pt-2 border-top">
                <a href="/auth/login?cancel_oauth=1" class="text-decoration-none small text-muted">
                    <i class="bi bi-arrow-left me-1"></i> Cancel and return to Login
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
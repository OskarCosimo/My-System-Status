<!-- path: app/Views/auth/login.php -->
<?php
$appName          = htmlspecialchars(setting('app_name', 'My System Status'));
$myetvEnabled     = !empty(setting('oauth_myetv_client_id'));
$googleEnabled    = !empty(setting('oauth_google_client_id'));
$turnstileEnabled = (bool)setting('turnstile_enabled', '0');
$turnstileSiteKey = setting('turnstile_site_key', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; <?= $appName ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php if ($turnstileEnabled && !empty($turnstileSiteKey)): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
<div class="container" style="max-width: 420px;">
    <div class="card border-0 shadow-sm rounded-4 p-3">
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
                <h4 class="fw-bold mt-2"><?= $appName ?></h4>
                <p class="text-muted small">Sign in to access your administrative dashboard</p>
            </div>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'rate_limited'): ?>
                <div class="alert alert-danger py-2 small text-center">
                    <i class="bi bi-shield-x me-1"></i>
                    Too many failed attempts. Your IP is temporarily locked for <strong><?= htmlspecialchars($_GET['wait'] ?? '15') ?> minutes</strong>.
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'turnstile_failed'): ?>
                <div class="alert alert-danger py-2 small text-center">
                    Bot verification failed. Please check Turnstile and try again.
                </div>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="alert alert-danger py-2 small text-center">
                    Invalid email or password. Please try again.
                </div>
            <?php endif; ?>

            <form action="/auth/authenticate" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email address</label>
                    <input type="email" name="email" class="form-control" required autofocus placeholder="email@your-domain.com">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <!-- Remember Me Checkbox -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" value="1" checked>
                    <label class="form-check-label small text-muted user-select-none" for="rememberMe">
                        Stay signed in on this device (6 months)
                    </label>
                </div>

                <!-- Cloudflare Turnstile Captcha Widget -->
                <?php if ($turnstileEnabled && !empty($turnstileSiteKey)): ?>
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars($turnstileSiteKey) ?>" data-theme="light"></div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Sign In</button>
            </form>

            <?php if ($myetvEnabled || $googleEnabled): ?>
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1">
                    <span class="px-2 text-muted small">or continue with</span>
                    <hr class="flex-grow-1">
                </div>

                <div class="d-grid gap-2">
                    <?php if ($myetvEnabled): ?>
                        <a href="/auth/oauth?provider=myetv" class="btn btn-outline-primary btn-sm py-2">
                            <i class="bi bi-tv me-1"></i> Sign in with <strong>MYETV</strong>
                        </a>
                    <?php endif; ?>
                    <?php if ($googleEnabled): ?>
                        <a href="/auth/oauth?provider=google" class="btn btn-outline-danger btn-sm py-2">
                            <i class="bi bi-google me-1"></i> Sign in with <strong>Google</strong>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="/" class="text-decoration-none small text-muted">&larr; Back to Public Status Page</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

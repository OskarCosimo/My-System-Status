<?php
// path: app/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\OAuthService;
use App\Services\RateLimiterService;
use App\Services\RememberMeService;
use App\Services\TotpService;
use App\Services\TurnstileService;
use PDO;

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function login(): void
    {
        if (!empty($_SESSION['user_id'])) {
            header('Location: /admin');
            exit;
        }

        // Allow clearing pending OAuth state if user cancels
        if (isset($_GET['cancel_oauth'])) {
            unset($_SESSION['pending_oauth_link']);
        }

        View::render('auth/login', [], null);
    }

    public function authenticate(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimiter = new RateLimiterService();

        // 1. Anti-Brute Force Check
        if ($rateLimiter->isBlocked($ip, 'login')) {
            $remMinutes = $rateLimiter->getLockoutRemainingMinutes($ip, 'login');
            header("Location: /auth/login?error=rate_limited&wait={$remMinutes}");
            exit;
        }

        // 2. Cloudflare Turnstile Check
        $cfToken = $_POST['cf-turnstile-response'] ?? null;
        if (!TurnstileService::verify($cfToken)) {
            header('Location: /auth/login?error=turnstile_failed');
            exit;
        }

        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Successful login: reset failed attempts
            $rateLimiter->clearAttempts($ip, 'login');

            // Check if 2FA is required
            if ((int)$user['two_factor_enabled'] === 1 && !empty($user['two_factor_secret'])) {
                $_SESSION['2fa_pending_user_id'] = $user['id'];
                $_SESSION['2fa_remember']        = $rememberMe;
                header('Location: /auth/2fa');
                exit;
            }

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Handle persistent remember me cookie
            if ($rememberMe) {
                RememberMeService::createToken((int)$user['id']);
            }

            header('Location: /admin');
            exit;
        }

        // Failed login attempt: record in rate limiter
        $rateLimiter->recordFailedAttempt($ip, 'login');

        header('Location: /auth/login?error=invalid_credentials');
        exit;
    }

    public function twoFactorView(): void
    {
        if (empty($_SESSION['2fa_pending_user_id'])) {
            header('Location: /auth/login');
            exit;
        }

        View::render('auth/2fa', [], null);
    }

    public function twoFactorVerify(): void
    {
        $userId     = $_SESSION['2fa_pending_user_id'] ?? null;
        $rememberMe = !empty($_SESSION['2fa_remember']);
        $code       = trim($_POST['code'] ?? '');

        if (!$userId || empty($code)) {
            header('Location: /auth/login');
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $totp = new TotpService();
        if ($user && $totp->verifyCode($user['two_factor_secret'], $code)) {
            unset($_SESSION['2fa_pending_user_id'], $_SESSION['2fa_remember']);
            
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            // Handle persistent remember me cookie after 2FA verification
            if ($rememberMe) {
                RememberMeService::createToken((int)$user['id']);
            }

            header('Location: /admin');
            exit;
        }

        header('Location: /auth/2fa?error=invalid_code');
        exit;
    }

    public function logout(): void
    {
        // Remove persistent remember me token and cookie
        RememberMeService::removeToken();

        $_SESSION = [];
        if (session_id()) {
            session_destroy();
        }
        header('Location: /auth/login');
        exit;
    }

    public function oauthRedirect(): void
    {
        $provider = $_GET['provider'] ?? '';
        try {
            $oauth = new OAuthService($provider);
            header("Location: " . $oauth->getAuthorizationUrl());
            exit;
        } catch (\Throwable $e) {
            die("OAuth Error: " . $e->getMessage());
        }
    }

    public function oauthCallback(): void
    {
        $provider = $_GET['provider'] ?? '';
        $code     = $_GET['code'] ?? '';

        if (!$code) {
            header('Location: /auth/login?error=oauth_denied');
            exit;
        }

        try {
            $oauth = new OAuthService($provider);
            $profile = $oauth->handleCallback($code);

            // 1. Check if an existing admin is ALREADY linked to this specific SSO account
            $stmt = $this->db->prepare("SELECT * FROM users WHERE oauth_provider = ? AND oauth_id = ? LIMIT 1");
            $stmt->execute([$provider, $profile['provider_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Already linked: authenticate directly
                if ((int)$user['two_factor_enabled'] === 1 && !empty($user['two_factor_secret'])) {
                    $_SESSION['2fa_pending_user_id'] = $user['id'];
                    $_SESSION['2fa_remember']        = true;
                    header('Location: /auth/2fa');
                    exit;
                }

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                RememberMeService::createToken((int)$user['id']);
                header('Location: /admin');
                exit;
            }

            // 2. Security Safeguard: Do NOT auto-create an admin account!
            // Store verified SSO payload in session and redirect to credentials linking verification
            $_SESSION['pending_oauth_link'] = [
                'provider'    => $provider,
                'provider_id' => $profile['provider_id'],
                'email'       => $profile['email'] ?? '',
                'name'        => $profile['name'] ?? ''
            ];

            header('Location: /auth/oauth-link');
            exit;
        } catch (\Throwable $e) {
            die("Authentication error: " . $e->getMessage());
        }
    }

    /**
     * Render the SSO Account Linking verification view
     */
    public function oauthLinkView(): void
    {
        $pending = $_SESSION['pending_oauth_link'] ?? null;
        if (!$pending) {
            header('Location: /auth/login');
            exit;
        }

        View::render('auth/oauth-link', [
            'pending' => $pending
        ], null);
    }

    /**
     * Process administrator credentials verification to permanently link SSO account
     */
    public function oauthLinkVerify(): void
    {
        $pending = $_SESSION['pending_oauth_link'] ?? null;
        if (!$pending) {
            header('Location: /auth/login');
            exit;
        }

        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            header('Location: /auth/oauth-link?error=missing_fields');
            exit;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateLimiter = new RateLimiterService();

        // Anti-Brute Force Protection on linking form
        if ($rateLimiter->isBlocked($ip, 'login')) {
            $remMinutes = $rateLimiter->getLockoutRemainingMinutes($ip, 'login');
            header("Location: /auth/oauth-link?error=rate_limited&wait={$remMinutes}");
            exit;
        }

        // Verify that target account exists and password matches
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $rateLimiter->clearAttempts($ip, 'login');

            // Permanently link this OAuth provider and provider_id to the administrator
            $update = $this->db->prepare("
                UPDATE users 
                SET oauth_provider = ?, oauth_id = ? 
                WHERE id = ?
            ");
            $update->execute([
                $pending['provider'],
                $pending['provider_id'],
                $user['id']
            ]);

            // Clear temporary pending state
            unset($_SESSION['pending_oauth_link']);

            // Respect 2FA if enabled on target account
            if ((int)$user['two_factor_enabled'] === 1 && !empty($user['two_factor_secret'])) {
                $_SESSION['2fa_pending_user_id'] = $user['id'];
                $_SESSION['2fa_remember']        = $rememberMe;
                header('Location: /auth/2fa');
                exit;
            }

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            if ($rememberMe) {
                RememberMeService::createToken((int)$user['id']);
            }

            header('Location: /admin');
            exit;
        }

        // Authentication failed for linking
        $rateLimiter->recordFailedAttempt($ip, 'login');
        header('Location: /auth/oauth-link?error=invalid_credentials');
        exit;
    }
}

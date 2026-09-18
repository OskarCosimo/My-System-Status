<?php
// path: core/helpers.php

use App\Core\I18n;
use App\Services\DateService;
use App\Services\SettingService;

if (!function_exists('__')) {
    /**
     * Translate the given message key. Supports both array replacements and string fallback defaults.
     */
    function __(string $key, array|string $replace = []): string
    {
        if (is_string($replace)) {
            $translated = I18n::trans($key);
            return ($translated === $key) ? $replace : $translated;
        }
        return I18n::trans($key, $replace);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format UTC date string into user/system active timezone.
     */
    function format_date(?string $utcDate, string $format = 'M d, Y H:i'): string
    {
        return DateService::format($utcDate, $format);
    }
}

if (!function_exists('setting')) {
    /**
     * Get platform setting from database.
     */
    function setting(string $key, ?string $default = null): ?string
    {
        return SettingService::get($key, $default);
    }
}

if (!function_exists('app_url')) {
    /**
     * Get platform dynamic base URL.
     */
    function app_url(): string
    {
        return SettingService::getAppUrl();
    }
}

<?php
// path: config/app.php

return [
    /*
     * Application Name & Release Version
     * The version string is checked against GitHub API tags for auto-updates.
     */
    'name'        => 'My System Status',
    'version'     => '1.0.53',

    /*
     * Debug Mode
     * Set to false in production to prevent sensitive stack trace leaks.
     */
    'debug'       => false,

    /*
     * Default Application Timezone
     */
    'timezone'    => 'UTC',

    /*
     * Default & Supported Locales
     */
    'locale'      => 'en',
    'supported_locales' => ['en', 'it', 'es', 'fr', 'de'],

    /*
     * Probe Defaults
     */
    'default_probe_timeout' => 10, // seconds
];
